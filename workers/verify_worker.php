<?php
/**
 * Worker skeleton verifikasi DB vs blockchain.
 * Issue: W5-FDR-06 (skeleton) → dilanjutkan pada M6 (multi-worker).
 * Pemilik: Farelly De Rossi
 *
 * Worker mengambil satu job PENDING dari `verification_jobs` dengan
 * `FOR UPDATE SKIP LOCKED`, memverifikasi rentang order, lalu menyimpan
 * checkpoint dan menandai job DONE/FAILED. Logika paralel penuh (multi-worker,
 * reducer, lease recovery) baru diaktifkan pada M6; file ini sudah memakai
 * kontrak data yang sama sehingga M6 tinggal menjalankan beberapa instance.
 *
 * Contoh:
 *   php workers/verify_worker.php --worker=w1
 *   php workers/verify_worker.php --worker=w2 --batch=12
 *   php workers/verify_worker.php --dry-run
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/api/services/BlockchainClient.php';
require_once dirname(__DIR__) . '/api/services/ContractService.php';
require_once dirname(__DIR__) . '/api/services/ReceiptService.php';
require_once dirname(__DIR__) . '/api/services/ProofRepository.php';
require_once dirname(__DIR__) . '/api/services/VerificationService.php';

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

$options = parseWorkerOptions($argv);
$config = require dirname(__DIR__) . '/config/parallel.php';

$workerId = (string)($options['worker'] ?? ('w-' . getmypid()));
$batchId = isset($options['batch']) ? (int)$options['batch'] : null;

if (isset($options['dry-run'])) {
    echo "Worker config (dry-run)\n";
    echo "  worker_id        : {$workerId}\n";
    echo "  partition_size   : {$config['default_partition_size']}\n";
    echo "  lease_seconds    : {$config['lease_seconds']}\n";
    echo "  max_attempts     : {$config['max_attempts']}\n";
    echo "  worker_counts    : " . implode(',', $config['worker_counts']) . "\n";
    echo "  modes            : " . implode(',', $config['modes']) . "\n";
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

// ---- Claim satu job secara atomik (siap untuk multi-worker M6). ----
$pdo->beginTransaction();
try {
    $sql = "SELECT * FROM verification_jobs
            WHERE status = 'PENDING'";
    $params = [];
    if ($batchId !== null) {
        $sql .= ' AND batch_id = ?';
        $params[] = $batchId;
    }
    $sql .= ' ORDER BY partition_no ASC LIMIT 1 FOR UPDATE SKIP LOCKED';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $job = $stmt->fetch();

    if (!$job) {
        $pdo->commit();
        echo "[{$workerId}] Tidak ada job PENDING. Worker idle.\n";
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
$batch = $job['batch_id'];
$start = (int)$job['range_start'];
$end = (int)$job['range_end'];

echo "[{$workerId}] Job #{$jobId} batch #{$batch} partisi {$job['partition_no']} ({$start}..{$end})\n";

$contractService = new ContractService(new BlockchainClient());
$proofRepo = new ProofRepository($pdo);
$verificationService = new VerificationService($contractService, $proofRepo);

$counts = ['VERIFIED' => 0, 'MISMATCH' => 0, 'PENDING' => 0];
$processed = 0;

try {
    $orderStmt = $pdo->prepare('SELECT id FROM orders WHERE id BETWEEN ? AND ? ORDER BY id');
    $orderStmt->execute([$start, $end]);

    foreach ($orderStmt->fetchAll(PDO::FETCH_COLUMN) as $orderId) {
        $result = $verificationService->verifyOrder((int)$orderId, true);
        $status = $result['verification_status'] ?? 'PENDING';
        if (!isset($counts[$status])) {
            $counts[$status] = 0;
        }
        $counts[$status]++;
        $processed++;
    }

    $payload = json_encode([
        'worker_id' => $workerId,
        'partition_no' => (int)$job['partition_no'],
        'counts' => $counts,
        'processed' => $processed,
    ], JSON_UNESCAPED_SLASHES);

    // Checkpoint idempoten per job.
    $pdo->prepare("INSERT INTO checkpoints
        (batch_id, job_id, partition_no, processed_count, verified_count, mismatch_count, error_count,
         revenue_all, revenue_verified, checksum, payload)
        VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, NULL, ?)
        ON DUPLICATE KEY UPDATE
            processed_count = VALUES(processed_count),
            verified_count = VALUES(verified_count),
            mismatch_count = VALUES(mismatch_count),
            payload = VALUES(payload)")
        ->execute([
            $batch,
            $jobId,
            (int)$job['partition_no'],
            $processed,
            $counts['VERIFIED'],
            $counts['MISMATCH'],
            $payload,
        ]);

    $pdo->prepare("UPDATE verification_jobs SET status = 'DONE', finished_at = NOW(), leased_at = NULL WHERE id = ?")
        ->execute([$jobId]);

    $pdo->prepare("INSERT INTO worker_logs (batch_id, job_id, worker_id, level, message, context)
                   VALUES (?, ?, ?, 'INFO', ?, ?)")
        ->execute([
            $batch,
            $jobId,
            $workerId,
            "Job #{$jobId} selesai: {$processed} order",
            $payload,
        ]);

    echo "[{$workerId}] Selesai: {$processed} order | VERIFIED={$counts['VERIFIED']} MISMATCH={$counts['MISMATCH']}\n";
    exit(0);
} catch (Throwable $e) {
    $pdo->prepare("UPDATE verification_jobs
                   SET status = CASE WHEN attempts >= max_attempts THEN 'DEAD' ELSE 'PENDING' END,
                       error_message = ?, leased_at = NULL
                   WHERE id = ?")
        ->execute([$e->getMessage(), $jobId]);

    $pdo->prepare("INSERT INTO worker_logs (batch_id, job_id, worker_id, level, message, context)
                   VALUES (?, ?, ?, 'ERROR', ?, ?)")
        ->execute([$batch, $jobId, $workerId, 'Job gagal: ' . $e->getMessage(), json_encode(['error' => true])]);

    fwrite(STDERR, "[{$workerId}] Job #{$jobId} gagal: {$e->getMessage()}\n");
    exit(1);
}
