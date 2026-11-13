<?php
/**
 * Worker verifikasi DB vs blockchain (Mode B - integration).
 * Issue: W5-FDR-06 (skeleton), W6-FDR-07/08/09/10, W6-LUI-12
 * Pemilik: Farelly De Rossi
 *
 * Worker mengambil satu job PENDING dari `verification_jobs` dengan
 * `FOR UPDATE SKIP LOCKED`, memverifikasi rentang order memakai
 * VerificationService (DB vs kontrak nyata), lalu menyimpan checkpoint
 * (hasil per order + metrik + checksum partisi) dan menandai job DONE.
 *
 * Perilaku M6:
 * - Loop opsional (`--loop`) sehingga satu proses dapat memproses beberapa job.
 * - Lease recovery (`--lease-recovery`) mengembalikan job RUNNING yang
 *   lease-nya kadaluarsa ke PENDING.
 * - Order tanpa relasi on-chain dihitung `SKIPPED`, terpisah dari MISMATCH.
 * - Checkpoint menyimpan hasil per order agar reducer dapat menghitung
 *   checksum yang sama dengan sequential.
 *
 * Contoh:
 *   php workers/verify_worker.php --worker=w1 --batch=12 --loop
 *   php workers/verify_worker.php --worker=w1 --lease-recovery --loop
 *   php workers/verify_worker.php --dry-run
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/benchmark/lib/Checksum.php';
require_once dirname(__DIR__) . '/api/services/BlockchainClient.php';
require_once dirname(__DIR__) . '/api/services/ContractService.php';
require_once dirname(__DIR__) . '/api/services/ReceiptService.php';
require_once dirname(__DIR__) . '/api/services/ProofRepository.php';
require_once dirname(__DIR__) . '/api/services/VerificationService.php';

use HSport\Benchmark\Checksum;
use HSport\Services\BlockchainClient;
use HSport\Services\ContractService;
use HSport\Services\ProofRepository;
use HSport\Services\VerificationService;

function parseWorkerOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--')) {
            $parts = explode('=', substr($arg, 2), 2);
            $options[$parts[0]] = $parts[1] ?? true;
        }
    }

    return $options;
}

function normalizeDifferences(array $differences): array
{
    $fields = [];
    foreach ($differences as $difference) {
        if (is_array($difference)) {
            $fields[] = (string)($difference['field'] ?? 'unknown');
        } else {
            $fields[] = (string)$difference;
        }
    }

    return $fields;
}

$options = parseWorkerOptions($argv);
$config = require dirname(__DIR__) . '/config/parallel.php';

$workerId = (string)($options['worker'] ?? ('w-' . getmypid()));
$batchId = isset($options['batch']) ? (int)$options['batch'] : null;
$loop = isset($options['loop']);
$maxJobs = isset($options['max-jobs']) ? max(1, (int)$options['max-jobs']) : 0;
$targetJobId = isset($options['job-id']) ? max(0, (int)$options['job-id']) : 0;

if (isset($options['dry-run'])) {
    echo "Worker config (dry-run)\n";
    echo "  worker_id        : {$workerId}\n";
    echo "  partition_size   : {$config['default_partition_size']}\n";
    echo "  lease_seconds    : {$config['lease_seconds']}\n";
    echo "  max_attempts     : {$config['max_attempts']}\n";
    echo "  worker_counts    : " . implode(',', $config['worker_counts']) . "\n";
    echo "  modes            : " . implode(',', $config['modes']) . "\n";
    echo "  lease_recovery   : " . (($config['lease_recovery_on_start'] ?? false) ? 'on' : 'off') . "\n";
    echo "  skip_without_id  : " . (($config['integration']['skip_without_chain_id'] ?? true) ? 'yes' : 'no') . "\n";
    exit(0);
}

$db = $config['db'];
$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "[ERROR] Koneksi DB gagal: {$e->getMessage()}\n");
    exit(1);
}

if (isset($options['lease-recovery']) || ($config['lease_recovery_on_start'] ?? false)) {
    $sql = "UPDATE verification_jobs
            SET status = 'PENDING', worker_id = NULL, leased_at = NULL,
                error_message = CONCAT(COALESCE(error_message, ''), ' [lease recovered by ', ?, ' at ', NOW(), ']')
            WHERE status = 'RUNNING'
              AND leased_at IS NOT NULL
              AND leased_at < (NOW() - INTERVAL ? SECOND)";
    $params = [$workerId, (int)$config['lease_seconds']];
    if ($batchId !== null) {
        $sql .= ' AND batch_id = ?';
        $params[] = $batchId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->rowCount() > 0) {
        echo "[{$workerId}] Lease recovery: {$stmt->rowCount()} job dikembalikan ke PENDING\n";
    }
}

$contractService = new ContractService(new BlockchainClient());
$proofRepo = new ProofRepository($pdo);
$verificationService = new VerificationService($contractService, $proofRepo);

$processedJobs = 0;

while (true) {
    // ---- Claim satu job secara atomik (SKIP LOCKED). ----
    $pdo->beginTransaction();
    try {
        $sql = "SELECT * FROM verification_jobs WHERE status = 'PENDING'";
        $params = [];
        if ($batchId !== null) {
            $sql .= ' AND batch_id = ?';
            $params[] = $batchId;
        }
        if ($targetJobId > 0) {
            $sql .= ' AND id = ?';
            $params[] = $targetJobId;
        }
        $sql .= ' ORDER BY partition_no ASC LIMIT 1 FOR UPDATE SKIP LOCKED';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $job = $stmt->fetch();

        if (!$job) {
            $pdo->commit();
            if ($processedJobs === 0) {
                echo "[{$workerId}] Tidak ada job PENDING. Worker idle.\n";
            }
            exit(0);
        }

        $pdo->prepare("UPDATE verification_jobs
                SET status = 'RUNNING', worker_id = ?, attempts = attempts + 1,
                    leased_at = NOW(), started_at = COALESCE(started_at, NOW())
                WHERE id = ?")
            ->execute([$workerId, $job['id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "[ERROR] Gagal claim job: {$e->getMessage()}\n");
        exit(1);
    }

    $jobId = (int)$job['id'];
    $batch = (int)$job['batch_id'];
    $start = (int)$job['range_start'];
    $end = (int)$job['range_end'];
    $startedAt = microtime(true);

    echo "[{$workerId}] Job #{$jobId} batch #{$batch} partisi {$job['partition_no']} ({$start}..{$end})\n";

    try {
        $orderStmt = $pdo->prepare('SELECT * FROM orders WHERE id BETWEEN ? AND ? ORDER BY id');
        $orderStmt->execute([$start, $end]);
        $orders = $orderStmt->fetchAll();

        $results = [];
        $verified = 0;
        $mismatch = 0;
        $errors = 0;
        $skipped = 0;
        $paid = 0;
        $unpaid = 0;
        $revenueAll = 0;
        $revenueVerified = 0;

        foreach ($orders as $order) {
            $orderId = (int)$order['id'];
            $amountIdr = (int)$order['total_amount'];
            $amountWei = function_exists('bcmul')
                ? (string)bcmul((string)$amountIdr, '100000000000', 0)
                : (string)$amountIdr;
            $isPaid = ($order['payment_status'] === 'paid');

            $hasChainRef = !empty($order['blockchain_order_id']) || !empty($order['external_order_hash']);
            if (!$hasChainRef && ($config['integration']['skip_without_chain_id'] ?? true)) {
                $result = [
                    'id' => $orderId,
                    'verification' => 'SKIPPED',
                    'amount_idr' => $amountIdr,
                    'amount_wei' => $amountWei,
                    'paid' => $isPaid,
                    'differences' => [],
                    'error' => 'NO_CHAIN_REFERENCE',
                ];
                $skipped++;
            } else {
                $verification = $verificationService->verifyOrder($orderId, true);
                $status = (string)($verification['verification_status'] ?? 'MISMATCH');
                $differences = normalizeDifferences($verification['differences'] ?? []);
                $result = [
                    'id' => $orderId,
                    'verification' => $status,
                    'amount_idr' => $amountIdr,
                    'amount_wei' => $amountWei,
                    'paid' => $isPaid,
                    'differences' => $differences,
                    'error' => $verification['error'] ?? null,
                ];

                if ($status === 'VERIFIED') {
                    $verified++;
                    if ($isPaid) {
                        $revenueVerified += $amountIdr;
                    }
                } elseif ($status === 'MISMATCH') {
                    $mismatch++;
                } else {
                    $errors++;
                }
            }

            $results[] = $result;
            $revenueAll += $amountIdr;
            $isPaid ? $paid++ : $unpaid++;
        }

        $processed = count($results);
        $partitionChecksum = Checksum::compute($results);
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);

        $payload = json_encode([
            'worker_id' => $workerId,
            'batch_id' => $batch,
            'partition_no' => (int)$job['partition_no'],
            'range_start' => $start,
            'range_end' => $end,
            'mode' => 'INTEGRATION',
            'processed' => $processed,
            'verified' => $verified,
            'mismatch' => $mismatch,
            'errors' => $errors,
            'skipped' => $skipped,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'revenue_all' => $revenueAll,
            'revenue_verified' => $revenueVerified,
            'checksum' => $partitionChecksum,
            'duration_ms' => $durationMs,
            'results' => $results,
        ], JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Gagal encode payload checkpoint.');
        }

        // Checkpoint idempoten per job.
        $pdo->prepare("INSERT INTO checkpoints
                (batch_id, job_id, partition_no, processed_count, verified_count, mismatch_count,
                 error_count, revenue_all, revenue_verified, checksum, payload)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                processed_count = VALUES(processed_count),
                verified_count = VALUES(verified_count),
                mismatch_count = VALUES(mismatch_count),
                error_count = VALUES(error_count),
                revenue_all = VALUES(revenue_all),
                revenue_verified = VALUES(revenue_verified),
                checksum = VALUES(checksum),
                payload = VALUES(payload)")
            ->execute([
                $batch,
                $jobId,
                (int)$job['partition_no'],
                $processed,
                $verified,
                $mismatch,
                $errors,
                $revenueAll,
                $revenueVerified,
                $partitionChecksum,
                $payload,
            ]);

        $pdo->prepare("UPDATE verification_jobs
                SET status = 'DONE', finished_at = NOW(), leased_at = NULL, error_message = NULL
                WHERE id = ?")
            ->execute([$jobId]);

        $pdo->prepare("INSERT INTO worker_logs (batch_id, job_id, worker_id, level, message, context)
                VALUES (?, ?, ?, 'INFO', ?, ?)")
            ->execute([
                $batch,
                $jobId,
                $workerId,
                "Job #{$jobId} selesai: {$processed} order ({$skipped} skipped) dalam {$durationMs} ms",
                $payload,
            ]);

        echo "[{$workerId}] Selesai: {$processed} order | VERIFIED={$verified} MISMATCH={$mismatch} "
            . "ERROR={$errors} SKIPPED={$skipped} | {$durationMs} ms\n";

        $processedJobs++;
        if (!$loop || ($maxJobs > 0 && $processedJobs >= $maxJobs)) {
            exit(0);
        }
    } catch (Throwable $e) {
        $pdo->prepare("UPDATE verification_jobs
                SET status = CASE WHEN attempts >= max_attempts THEN 'DEAD' ELSE 'PENDING' END,
                    error_message = ?, leased_at = NULL
                WHERE id = ?")
            ->execute([$e->getMessage(), $jobId]);

        $pdo->prepare("INSERT INTO worker_logs (batch_id, job_id, worker_id, level, message, context)
                VALUES (?, ?, ?, 'ERROR', ?, ?)")
            ->execute([
                $batch,
                $jobId,
                $workerId,
                "Job #{$jobId} gagal: {$e->getMessage()}",
                json_encode(['error' => true], JSON_UNESCAPED_SLASHES),
            ]);

        fwrite(STDERR, "[{$workerId}] Job #{$jobId} gagal: {$e->getMessage()}\n");
        exit(1);
    }
}
