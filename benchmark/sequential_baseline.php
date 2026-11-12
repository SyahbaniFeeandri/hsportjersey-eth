<?php
/**
 * CLI: sequential baseline verifikasi DB vs mock chain.
 * Issue: W4-FDR-05
 *
 * Menjalankan verifikasi satu worker (sequential), mencatat metrik agregat,
 * lalu (opsional) menyimpan batch ke tabel job_batches / verification_jobs /
 * checkpoints / worker_logs.
 *
 * Contoh:
 *   php benchmark/sequential_baseline.php --size=100 --no-db
 *   php benchmark/sequential_baseline.php --size=1000 --db
 *   php benchmark/sequential_baseline.php --dataset=benchmark/datasets/D500.json --json-out=hasil.json
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/DatasetGenerator.php';
require_once __DIR__ . '/lib/MockChainAdapter.php';
require_once __DIR__ . '/lib/MockChainException.php';
require_once __DIR__ . '/lib/SequentialVerifier.php';

use HSport\Benchmark\DatasetGenerator;
use HSport\Benchmark\MockChainAdapter;
use HSport\Benchmark\SequentialVerifier;

function parseCliOptions(array $argv): array
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

function loadDataset(array $options): array
{
    if (isset($options['dataset'])) {
        $path = (string)$options['dataset'];
        if (!is_file($path)) {
            fwrite(STDERR, "Dataset tidak ditemukan: {$path}\n");
            exit(1);
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        if (!is_array($decoded) || !isset($decoded['orders'])) {
            fwrite(STDERR, "Format dataset tidak valid: {$path}\n");
            exit(1);
        }
        return $decoded;
    }

    $size = isset($options['size']) ? (int)$options['size'] : 100;
    $seed = (string)($options['seed'] ?? 'HSPORT-2026');
    return (new DatasetGenerator($seed))->generate($size);
}

function connectDatabase(): ?PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'h_sport_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * @return array{batch_id:int,job_id:int}
 */
function persistBatch(PDO $pdo, array $summary, string $batchCode): array
{
    $stmt = $pdo->prepare("INSERT INTO job_batches
        (batch_code, mode, dataset_name, dataset_size, dataset_hash, worker_count, is_baseline,
         status, total_orders, verified_count, mismatch_count, error_count,
         revenue_all, revenue_verified, aov, checksum, duration_ms,
         started_at, finished_at, notes)
        VALUES
        (:batch_code, 'ALGORITHM', :dataset_name, :dataset_size, :dataset_hash, 1, 1,
         'DONE', :total_orders, :verified_count, :mismatch_count, :error_count,
         :revenue_all, :revenue_verified, :aov, :checksum, :duration_ms,
         :started_at, :finished_at, :notes)");

    $stmt->execute([
        ':batch_code' => $batchCode,
        ':dataset_name' => $summary['dataset_name'],
        ':dataset_size' => $summary['dataset_size'],
        ':dataset_hash' => $summary['dataset_hash'],
        ':total_orders' => $summary['total_orders'],
        ':verified_count' => $summary['verified_count'],
        ':mismatch_count' => $summary['mismatch_count'],
        ':error_count' => $summary['error_count'],
        ':revenue_all' => $summary['revenue_all'],
        ':revenue_verified' => $summary['revenue_verified'],
        ':aov' => $summary['aov'],
        ':checksum' => $summary['checksum'],
        ':duration_ms' => $summary['duration_ms'],
        ':started_at' => date('Y-m-d H:i:s'),
        ':finished_at' => date('Y-m-d H:i:s'),
        ':notes' => 'Sequential baseline (worker_count=1) dengan mock chain adapter, seed=' . $summary['seed'],
    ]);

    $batchId = (int)$pdo->lastInsertId();

    $job = $pdo->prepare("INSERT INTO verification_jobs
        (batch_id, partition_no, range_start, range_end, status, worker_id, attempts, max_attempts, started_at, finished_at)
        VALUES (?, 1, 1, ?, 'DONE', 'seq-baseline', 1, 3, NOW(), NOW())");
    $job->execute([$batchId, $summary['total_orders']]);
    $jobId = (int)$pdo->lastInsertId();

    $payload = json_encode([
        'mode' => $summary['mode'],
        'worker_count' => $summary['worker_count'],
        'dataset_hash' => $summary['dataset_hash'],
        'checksum' => $summary['checksum'],
        'metrics' => [
            'verified' => $summary['verified_count'],
            'mismatch' => $summary['mismatch_count'],
            'error' => $summary['error_count'],
            'revenue_all' => $summary['revenue_all'],
            'revenue_verified' => $summary['revenue_verified'],
            'aov' => $summary['aov'],
        ],
        'results' => $summary['results'],
    ], JSON_UNESCAPED_SLASHES);

    $checkpoint = $pdo->prepare("INSERT INTO checkpoints
        (batch_id, job_id, partition_no, processed_count, verified_count, mismatch_count, error_count,
         revenue_all, revenue_verified, checksum, payload)
        VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)");
    $checkpoint->execute([
        $batchId,
        $jobId,
        $summary['total_orders'],
        $summary['verified_count'],
        $summary['mismatch_count'],
        $summary['error_count'],
        $summary['revenue_all'],
        $summary['revenue_verified'],
        $summary['checksum'],
        $payload,
    ]);

    $log = $pdo->prepare("INSERT INTO worker_logs (batch_id, job_id, worker_id, level, message, context)
        VALUES (?, ?, 'seq-baseline', 'INFO', ?, ?)");
    $log->execute([
        $batchId,
        $jobId,
        'Sequential baseline selesai: ' . $summary['total_orders'] . ' order dalam ' . $summary['duration_ms'] . ' ms',
        json_encode([
            'verified' => $summary['verified_count'],
            'mismatch' => $summary['mismatch_count'],
            'error' => $summary['error_count'],
            'checksum' => $summary['checksum'],
        ], JSON_UNESCAPED_SLASHES),
    ]);

    return ['batch_id' => $batchId, 'job_id' => $jobId];
}

$options = parseCliOptions($argv);
$dataset = loadDataset($options);

$chain = new MockChainAdapter($dataset['orders']);
$verifier = new SequentialVerifier($chain, (int)($dataset['meta']['eth_rate_idr'] ?? 10000000));
$summary = $verifier->run($dataset);

echo "===============================================================\n";
echo " H-SPORT FINANCIAL dAPP — SEQUENTIAL BASELINE (W4-FDR-05)\n";
echo " Dataset : {$summary['dataset_name']} ({$summary['dataset_size']} order)\n";
echo " Seed    : {$summary['seed']}\n";
echo " Mode    : {$summary['mode']} / worker_count={$summary['worker_count']}\n";
echo " Waktu   : " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n";
printf(" Total orders      : %d\n", $summary['total_orders']);
printf(" VERIFIED          : %d\n", $summary['verified_count']);
printf(" MISMATCH          : %d\n", $summary['mismatch_count']);
printf(" ERROR             : %d\n", $summary['error_count']);
printf(" Paid / Unpaid     : %d / %d\n", $summary['paid_count'], $summary['unpaid_count']);
printf(" Revenue all       : Rp %s\n", number_format($summary['revenue_all'], 0, ',', '.'));
printf(" Revenue verified  : Rp %s\n", number_format($summary['revenue_verified'], 0, ',', '.'));
printf(" AOV               : Rp %s\n", number_format($summary['aov'], 2, ',', '.'));
printf(" Duration          : %d ms (%d us)\n", $summary['duration_ms'], $summary['duration_us']);
printf(" Throughput        : %s order/detik\n", $summary['throughput_per_sec'] === null ? '-' : number_format($summary['throughput_per_sec'], 2, ',', '.'));
printf(" Checksum          : %s\n", $summary['checksum']);
echo "---------------------------------------------------------------\n";

$useDb = isset($options['db']);
if (isset($options['no-db'])) {
    $useDb = false;
}

$persisted = null;
if ($useDb) {
    $pdo = connectDatabase();
    if (!$pdo) {
        fwrite(STDERR, "[WARN] MySQL tidak tersedia. Baseline tetap valid, tetapi tidak disimpan ke job_batches.\n");
    } else {
        $batchCode = (string)($options['batch'] ?? 'SEQ-' . $summary['dataset_name'] . '-' . date('YmdHis'));
        try {
            $persisted = persistBatch($pdo, $summary, $batchCode);
            printf(" Disimpan ke DB    : batch_id=%d job_id=%d batch_code=%s\n",
                $persisted['batch_id'], $persisted['job_id'], $batchCode);
        } catch (Throwable $e) {
            fwrite(STDERR, "[WARN] Gagal menyimpan batch ke DB: " . $e->getMessage() . "\n");
            $persisted = null;
        }
    }
} else {
    echo " DB                : dilewati (--no-db)\n";
}

if (isset($options['json-out'])) {
    $report = $summary;
    if (!isset($options['include-results'])) {
        unset($report['results']);
    }
    $report['persisted'] = $persisted;
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents((string)$options['json-out'], $json . "\n") === false) {
        fwrite(STDERR, "[WARN] Gagal menulis JSON report.\n");
    } else {
        echo " JSON report       : " . (string)$options['json-out'] . "\n";
    }
}

echo "===============================================================\n";
exit(($summary['error_count'] >= 0) ? 0 : 1);
