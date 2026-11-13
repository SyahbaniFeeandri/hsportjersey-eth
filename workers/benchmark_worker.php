<?php
/**
 * Worker benchmark paralel (Mode A - algorithm benchmark).
 * Issue: W6-FDR-07, W6-FDR-08, W6-FDR-09, W6-FDR-10
 * Pemilik: Farelly De Rossi
 *
 * Satu proses = satu worker. Worker mengambil satu job PENDING dari
 * `verification_jobs` dengan `FOR UPDATE SKIP LOCKED`, memverifikasi rentang
 * order partisi memakai aturan yang sama dengan baseline sequential, lalu
 * menyimpan checkpoint (hasil per order + metrik + checksum partisi).
 *
 * Contoh:
 *   php workers/benchmark_worker.php --batch=12 --worker=par-4-w1 --latency-us=1000 --loop
 *   php workers/benchmark_worker.php --batch=12 --worker=w1 --dataset=benchmark/datasets/D500.json
 *   php workers/benchmark_worker.php --batch=12 --worker=w1 --lease-recovery --loop
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/benchmark/lib/Checksum.php';
require_once dirname(__DIR__) . '/benchmark/lib/DatasetGenerator.php';
require_once dirname(__DIR__) . '/benchmark/lib/MockChainAdapter.php';
require_once dirname(__DIR__) . '/benchmark/lib/MockChainException.php';
require_once dirname(__DIR__) . '/benchmark/lib/SequentialVerifier.php';

use HSport\Benchmark\Checksum;
use HSport\Benchmark\DatasetGenerator;
use HSport\Benchmark\MockChainAdapter;
use HSport\Benchmark\SequentialVerifier;

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

function connectBenchmarkDatabase(array $config): PDO
{
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/**
 * @return array<string,mixed>|null
 */
function claimJob(PDO $pdo, int $batchId, string $workerId): ?array
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM verification_jobs
            WHERE batch_id = ? AND status = 'PENDING'
            ORDER BY partition_no ASC
            LIMIT 1
            FOR UPDATE SKIP LOCKED");
        $stmt->execute([$batchId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $pdo->commit();
            return null;
        }

        $pdo->prepare("UPDATE verification_jobs
                SET status = 'RUNNING', worker_id = ?, attempts = attempts + 1,
                    leased_at = NOW(), started_at = COALESCE(started_at, NOW())
                WHERE id = ?")
            ->execute([$workerId, $job['id']]);

        $pdo->commit();

        return $job;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function recoverExpiredLeases(PDO $pdo, int $batchId, int $leaseSeconds, string $workerId): int
{
    $stmt = $pdo->prepare("UPDATE verification_jobs
        SET status = 'PENDING', worker_id = NULL, leased_at = NULL,
            error_message = CONCAT(COALESCE(error_message, ''), ' [lease recovered by ', ?, ' at ', NOW(), ']')
        WHERE batch_id = ? AND status = 'RUNNING'
          AND leased_at IS NOT NULL
          AND leased_at < (NOW() - INTERVAL ? SECOND)");
    $stmt->execute([$workerId, $batchId, $leaseSeconds]);

    return $stmt->rowCount();
}

$options = parseWorkerOptions($argv);
$config = require dirname(__DIR__) . '/config/parallel.php';

$workerId = (string)($options['worker'] ?? ('bench-w-' . getmypid()));
$batchId = isset($options['batch']) ? (int)$options['batch'] : 0;
$latencyUs = isset($options['latency-us']) ? max(0, (int)$options['latency-us']) : (int)($config['algorithm']['default_latency_us'] ?? 0);
$loop = isset($options['loop']);
$maxJobs = isset($options['max-jobs']) ? max(1, (int)$options['max-jobs']) : 0;

if ($batchId <= 0) {
    fwrite(STDERR, "Parameter --batch=<id> wajib diisi.\n");
    exit(2);
}

try {
    $pdo = connectBenchmarkDatabase($config);
    $stmt = $pdo->prepare('SELECT * FROM job_batches WHERE id = ?');
    $stmt->execute([$batchId]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$batch) {
        throw new RuntimeException("Batch #{$batchId} tidak ditemukan.");
    }
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] {$workerId}: {$e->getMessage()}\n");
    exit(1);
}

if (isset($options['lease-recovery']) || ($config['lease_recovery_on_start'] ?? false)) {
    try {
        $recovered = recoverExpiredLeases($pdo, $batchId, (int)$config['lease_seconds'], $workerId);
        if ($recovered > 0) {
            echo "[{$workerId}] Lease recovery: {$recovered} job dikembalikan ke PENDING\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "[WARN] {$workerId}: lease recovery gagal: {$e->getMessage()}\n");
    }
}

// ---- Muat dataset (sekali per proses) ----
$datasetPath = isset($options['dataset']) ? (string)$options['dataset'] : '';
if ($datasetPath === '') {
    $datasetPath = dirname(__DIR__) . '/benchmark/datasets/' . $batch['dataset_name'] . '.json';
}

try {
    if (is_file($datasetPath)) {
        $dataset = json_decode((string)file_get_contents($datasetPath), true);
        if (!is_array($dataset) || !isset($dataset['orders'])) {
            throw new RuntimeException("Format dataset tidak valid: {$datasetPath}");
        }
    } else {
        $dataset = (new DatasetGenerator('HSPORT-2026'))->generate(
            (int)$batch['dataset_size'],
            (string)$batch['dataset_name']
        );
        echo "[{$workerId}] Dataset file tidak ada, memakai generator deterministik.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] {$workerId}: gagal memuat dataset: {$e->getMessage()}\n");
    exit(1);
}

$ordersById = [];
foreach ($dataset['orders'] as $order) {
    $ordersById[(int)$order['id']] = $order;
}

$chain = new MockChainAdapter($dataset['orders'], $latencyUs);
$verifier = new SequentialVerifier($chain, (int)($dataset['meta']['eth_rate_idr'] ?? 10000000));

$processedJobs = 0;
while (true) {
    try {
        $job = claimJob($pdo, $batchId, $workerId);
    } catch (Throwable $e) {
        fwrite(STDERR, "[ERROR] {$workerId}: gagal claim job: {$e->getMessage()}\n");
        exit(1);
    }

    if ($job === null) {
        if ($processedJobs === 0) {
            echo "[{$workerId}] Tidak ada job PENDING. Worker idle.\n";
        }
        exit(0);
    }

    $jobId = (int)$job['id'];
    $start = (int)$job['range_start'];
    $end = (int)$job['range_end'];
    $startedAt = microtime(true);

    echo "[{$workerId}] Job #{$jobId} partisi {$job['partition_no']} ({$start}..{$end})\n";

    try {
        $results = [];
        for ($id = $start; $id <= $end; $id++) {
            if (!isset($ordersById[$id])) {
                continue;
            }
            $results[] = $verifier->verify($ordersById[$id]);
        }

        $verified = 0;
        $mismatch = 0;
        $errors = 0;
        $paid = 0;
        $unpaid = 0;
        $revenueAll = 0;
        $revenueVerified = 0;

        foreach ($results as $result) {
            $isPaid = ($result['paid'] ?? false) === true;
            if ($result['verification'] === 'VERIFIED') {
                if ($isPaid) {
                    $revenueVerified += (int)$result['amount_idr'];
                }
                $verified++;
            } elseif ($result['verification'] === 'MISMATCH') {
                $mismatch++;
            } else {
                $errors++;
            }
            $revenueAll += (int)$result['amount_idr'];
            $isPaid ? $paid++ : $unpaid++;
        }

        $processed = count($results);
        $partitionChecksum = Checksum::compute($results);
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);

        $payload = json_encode([
            'worker_id' => $workerId,
            'batch_id' => $batchId,
            'partition_no' => (int)$job['partition_no'],
            'range_start' => $start,
            'range_end' => $end,
            'latency_us' => $latencyUs,
            'dataset_hash' => $dataset['meta']['dataset_hash'] ?? $batch['dataset_hash'],
            'processed' => $processed,
            'verified' => $verified,
            'mismatch' => $mismatch,
            'errors' => $errors,
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
                $batchId,
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
                $batchId,
                $jobId,
                $workerId,
                "Job #{$jobId} selesai: {$processed} order dalam {$durationMs} ms",
                json_encode([
                    'partition_no' => (int)$job['partition_no'],
                    'range_start' => $start,
                    'range_end' => $end,
                    'verified' => $verified,
                    'mismatch' => $mismatch,
                    'errors' => $errors,
                    'checksum' => $partitionChecksum,
                    'duration_ms' => $durationMs,
                ], JSON_UNESCAPED_SLASHES),
            ]);

        echo "[{$workerId}] Selesai partisi {$job['partition_no']}: {$processed} order | "
            . "VERIFIED={$verified} MISMATCH={$mismatch} ERROR={$errors} | {$durationMs} ms\n";

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
                $batchId,
                $jobId,
                $workerId,
                "Job #{$jobId} gagal: {$e->getMessage()}",
                json_encode(['partition_no' => (int)$job['partition_no']], JSON_UNESCAPED_SLASHES),
            ]);

        fwrite(STDERR, "[{$workerId}] Job #{$jobId} gagal: {$e->getMessage()}\n");
        exit(1);
    }
}
