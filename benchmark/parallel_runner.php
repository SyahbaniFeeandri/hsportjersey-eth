<?php
/**
 * CLI: Orkestrator benchmark paralel (Mode A - algorithm benchmark).
 * Issue: W6-FDR-07, W6-FDR-08, W6-FDR-09, W6-FDR-10, W6-FDR-11
 * Pemilik: Farelly De Rossi
 *
 * Alur:
 *   1. Pastikan baseline sequential tersedia (jalankan bila belum ada).
 *   2. Buat batch + partisi contiguous untuk tiap jumlah worker (1/2/4/8).
 *   3. Spawn sejumlah proses worker secara bersamaan; setiap worker klaim job
 *      berbeda lewat `FOR UPDATE SKIP LOCKED`.
 *   4. Reducer menggabungkan checkpoint dan membandingkan checksum dengan
 *      baseline sequential.
 *
 * Contoh:
 *   php benchmark/parallel_runner.php --dataset=benchmark/datasets/D500.json --workers=1,2,4,8 --latency-us=1000
 *   php benchmark/parallel_runner.php --size=1000 --workers=2,4 --json-out=hasil.json
 *   php benchmark/parallel_runner.php --size=100 --workers=4 --no-baseline-run
 */

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/benchmark/lib/Checksum.php';
require_once $root . '/benchmark/lib/DatasetGenerator.php';
require_once $root . '/benchmark/lib/MockChainAdapter.php';
require_once $root . '/benchmark/lib/MockChainException.php';
require_once $root . '/benchmark/lib/SequentialVerifier.php';
require_once $root . '/benchmark/lib/PartitionPlanner.php';
require_once $root . '/benchmark/lib/Reducer.php';

use HSport\Benchmark\DatasetGenerator;
use HSport\Benchmark\PartitionPlanner;
use HSport\Benchmark\Reducer;

function parseRunnerOptions(array $argv): array
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

function connectRunnerDatabase(array $config): PDO
{
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function loadRunnerDataset(array $options): array
{
    global $root;

    if (isset($options['dataset'])) {
        $path = (string)$options['dataset'];
        if (!is_file($path)) {
            fwrite(STDERR, "Dataset tidak ditemukan: {$path}\n");
            exit(1);
        }
        $dataset = json_decode((string)file_get_contents($path), true);
        if (!is_array($dataset) || !isset($dataset['orders'])) {
            fwrite(STDERR, "Format dataset tidak valid: {$path}\n");
            exit(1);
        }

        return [$dataset, $path];
    }

    $size = isset($options['size']) ? (int)$options['size'] : 500;
    $seed = (string)($options['seed'] ?? 'HSPORT-2026');
    $dataset = (new DatasetGenerator($seed))->generate($size);
    $path = $root . '/benchmark/datasets/' . $dataset['meta']['dataset_name'] . '.json';
    @file_put_contents($path, json_encode($dataset, JSON_UNESCAPED_SLASHES));

    return [$dataset, $path];
}

/**
 * Menjalankan satu perintah PHP tanpa shell dan mengembalikan output + exit code.
 *
 * @param array<int,string> $command
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function runProcess(array $command, string $cwd): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, $cwd);
    if (!is_resource($process)) {
        return ['exit_code' => -1, 'stdout' => '', 'stderr' => 'Gagal membuat proses.'];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => $stdout === false ? '' : $stdout,
        'stderr' => $stderr === false ? '' : $stderr,
    ];
}

/**
 * Menjalankan baseline sequential lewat CLI yang sudah ada dan mengembalikan
 * ringkasan JSON-nya.
 *
 * @return array<string,mixed>
 */
function runSequentialBaselineCli(string $root, string $datasetPath, int $latencyUs, string $batchCode): array
{
    $jsonPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hsp_seq_' . bin2hex(random_bytes(6)) . '.json';
    $command = [
        PHP_BINARY,
        $root . '/benchmark/sequential_baseline.php',
        '--dataset=' . $datasetPath,
        '--latency-us=' . $latencyUs,
        '--db',
        '--batch=' . $batchCode,
        '--json-out=' . $jsonPath,
    ];
    $run = runProcess($command, $root);

    if ($run['exit_code'] !== 0 || !is_file($jsonPath)) {
        throw new RuntimeException(
            'Baseline sequential gagal: ' . trim($run['stderr']) . ' ' . trim($run['stdout'])
        );
    }

    $summary = json_decode((string)file_get_contents($jsonPath), true);
    @unlink($jsonPath);
    if (!is_array($summary)) {
        throw new RuntimeException('JSON baseline sequential tidak valid.');
    }

    return $summary;
}

/**
 * @return array<string,mixed>|null
 */
function latestBaseline(PDO $pdo, string $mode, string $datasetName, int $datasetSize): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM job_batches
        WHERE mode = ? AND dataset_name = ? AND dataset_size = ?
          AND is_baseline = 1 AND status = 'DONE'
        ORDER BY id DESC LIMIT 1");
    $stmt->execute([$mode, $datasetName, $datasetSize]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * @param array<int,array<string,mixed>> $partitions
 */
function createBatch(PDO $pdo, array $dataset, array $partitions, int $workers, int $latencyUs, string $datasetPath, ?int $baselineId, string $batchCode, int $maxAttempts): int
{
    $meta = $dataset['meta'];
    $notes = json_encode([
        'mode' => 'ALGORITHM',
        'latency_us' => $latencyUs,
        'dataset_path' => $datasetPath,
        'baseline_batch_id' => $baselineId,
        'partition_strategy' => 'contiguous-by-id',
        'created_by' => 'benchmark/parallel_runner.php',
    ], JSON_UNESCAPED_SLASHES);

    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO job_batches
                (batch_code, mode, dataset_name, dataset_size, dataset_hash, worker_count,
                 is_baseline, parent_batch_id, status, total_orders, started_at, notes)
            VALUES (?, 'ALGORITHM', ?, ?, ?, ?, 0, ?, 'RUNNING', ?, NOW(), ?)")
            ->execute([
                $batchCode,
                $meta['dataset_name'],
                (int)$meta['dataset_size'],
                $meta['dataset_hash'],
                $workers,
                $baselineId,
                (int)$meta['dataset_size'],
                $notes,
            ]);

        $batchId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO verification_jobs
                (batch_id, partition_no, range_start, range_end, status, max_attempts)
            VALUES (?, ?, ?, ?, 'PENDING', ?)");
        foreach ($partitions as $partition) {
            $stmt->execute([
                $batchId,
                $partition['partition_no'],
                $partition['range_start'],
                $partition['range_end'],
                $maxAttempts,
            ]);
        }

        $pdo->commit();

        return $batchId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

$options = parseRunnerOptions($argv);
$config = require $root . '/config/parallel.php';

if (isset($options['help'])) {
    echo "Usage: php benchmark/parallel_runner.php [--size=N | --dataset=path] [--workers=1,2,4,8]\n";
    echo "       [--latency-us=N] [--seed=S] [--json-out=path] [--no-baseline-run]\n";
    exit(0);
}

[$dataset, $datasetPath] = loadRunnerDataset($options);
$meta = $dataset['meta'];
$workerCounts = isset($options['workers'])
    ? array_values(array_unique(array_map('intval', explode(',', (string)$options['workers']))))
    : $config['worker_counts'];
$workerCounts = array_values(array_filter($workerCounts, static fn(int $n): bool => $n >= 1 && $n <= 64));
sort($workerCounts);
if (empty($workerCounts)) {
    fwrite(STDERR, "Daftar worker tidak valid.\n");
    exit(2);
}

$latencyUs = isset($options['latency-us'])
    ? max(0, (int)$options['latency-us'])
    : (int)($config['algorithm']['default_latency_us'] ?? 1000);

echo "===============================================================\n";
echo " H-SPORT FINANCIAL dAPP - PARALLEL RUNNER (M6)\n";
echo " Dataset     : {$meta['dataset_name']} ({$meta['dataset_size']} order)\n";
echo " Dataset hash: {$meta['dataset_hash']}\n";
echo " Worker      : " . implode(', ', $workerCounts) . "\n";
echo " Latency     : {$latencyUs} us/order (simulasi RPC Mode A)\n";
echo " Waktu       : " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n";

try {
    $pdo = connectRunnerDatabase($config);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] MySQL wajib aktif untuk benchmark paralel: {$e->getMessage()}\n");
    exit(1);
}

// ---- Baseline sequential ----
$baseline = null;
if (isset($options['baseline-batch'])) {
    $stmt = $pdo->prepare('SELECT * FROM job_batches WHERE id = ? AND is_baseline = 1');
    $stmt->execute([(int)$options['baseline-batch']]);
    $baseline = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$baseline) {
        fwrite(STDERR, "[ERROR] Baseline batch tidak ditemukan.\n");
        exit(1);
    }
} else {
    $baseline = latestBaseline($pdo, 'ALGORITHM', (string)$meta['dataset_name'], (int)$meta['dataset_size']);
}

if ($baseline === null || !isset($options['no-baseline-run'])) {
    echo "[baseline] Menjalankan sequential baseline (worker_count=1)...\n";
    try {
        $batchCode = 'SEQ-' . $meta['dataset_name'] . '-L' . $latencyUs . '-' . date('YmdHis') . '-' . substr(uniqid('', true), -4);
        $summary = runSequentialBaselineCli($root, $datasetPath, $latencyUs, $batchCode);
        $baseline = latestBaseline($pdo, 'ALGORITHM', (string)$meta['dataset_name'], (int)$meta['dataset_size']);
        printf(
            "[baseline] checksum=%s duration=%d ms throughput=%s order/detik\n",
            $summary['checksum'] ?? '-',
            (int)($summary['duration_ms'] ?? 0),
            isset($summary['throughput_per_sec']) ? number_format((float)$summary['throughput_per_sec'], 2, ',', '.') : '-'
        );
    } catch (Throwable $e) {
        fwrite(STDERR, "[ERROR] Baseline gagal: {$e->getMessage()}\n");
        exit(1);
    }
}

if ($baseline === null) {
    fwrite(STDERR, "[ERROR] Baseline sequential tidak tersedia.\n");
    exit(1);
}

$baselineSummary = [
    'batch_id' => (int)$baseline['id'],
    'batch_code' => $baseline['batch_code'],
    'checksum' => $baseline['checksum'],
    'duration_ms' => (int)$baseline['duration_ms'],
    'verified_count' => (int)$baseline['verified_count'],
    'mismatch_count' => (int)$baseline['mismatch_count'],
    'error_count' => (int)$baseline['error_count'],
    'revenue_all' => (float)$baseline['revenue_all'],
    'revenue_verified' => (float)$baseline['revenue_verified'],
    'aov' => (float)$baseline['aov'],
];

echo "[baseline] batch #{$baselineSummary['batch_id']} ({$baselineSummary['batch_code']}) "
    . "checksum={$baselineSummary['checksum']}\n";

$reducer = new Reducer($pdo);
$runs = [];
$allGood = true;

foreach ($workerCounts as $workers) {
    $total = (int)$meta['dataset_size'];
    $partitions = PartitionPlanner::plan($total, $workers);
    if (!PartitionPlanner::coversAll($total, $partitions)) {
        fwrite(STDERR, "[ERROR] Partisi tidak menutup seluruh order untuk P={$workers}.\n");
        exit(1);
    }

    $batchCode = 'PAR-' . $meta['dataset_name'] . '-W' . $workers . '-' . date('YmdHis') . '-' . substr(uniqid('', true), -4);
    try {
        $batchId = createBatch($pdo, $dataset, $partitions, $workers, $latencyUs, $datasetPath, (int)$baseline['id'], $batchCode, (int)$config['max_attempts']);
    } catch (Throwable $e) {
        fwrite(STDERR, "[ERROR] Gagal membuat batch P={$workers}: {$e->getMessage()}\n");
        $allGood = false;
        continue;
    }

    echo "\n--- P={$workers} | batch #{$batchId} ({$batchCode}) | " . count($partitions) . " partisi ---\n";

    $workerScript = $root . '/workers/benchmark_worker.php';
    $wallStarted = microtime(true);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $processes = [];
    for ($w = 1; $w <= count($partitions); $w++) {
        $workerId = 'par-' . $workers . '-w' . $w;
        $command = [
            PHP_BINARY,
            $workerScript,
            '--batch=' . $batchId,
            '--worker=' . $workerId,
            '--dataset=' . $datasetPath,
            '--latency-us=' . $latencyUs,
            '--max-jobs=1',
        ];
        $process = proc_open($command, $descriptors, $pipes, $root);
        if (!is_resource($process)) {
            fwrite(STDERR, "[ERROR] Gagal spawn {$workerId}.\n");
            $allGood = false;
            continue;
        }
        fclose($pipes[0]);
        $processes[] = ['process' => $process, 'pipes' => $pipes, 'worker' => $workerId];
    }

    $workerOutput = [];
    $workerErrors = [];
    $exitCodes = [];
    foreach ($processes as $entry) {
        $stdout = stream_get_contents($entry['pipes'][1]);
        $stderr = stream_get_contents($entry['pipes'][2]);
        fclose($entry['pipes'][1]);
        fclose($entry['pipes'][2]);
        $exitCode = proc_close($entry['process']);
        $workerOutput[$entry['worker']] = trim($stdout === false ? '' : $stdout);
        $workerErrors[$entry['worker']] = trim($stderr === false ? '' : $stderr);
        $exitCodes[$entry['worker']] = $exitCode;
        if ($stdout) {
            echo $stdout;
        }
        if ($stderr) {
            fwrite(STDERR, $stderr);
        }
    }
    $wallMs = (int)round((microtime(true) - $wallStarted) * 1000);

    try {
        $reduce = $reducer->reduce($batchId, $wallMs);
    } catch (Throwable $e) {
        fwrite(STDERR, "[ERROR] Reduce batch #{$batchId} gagal: {$e->getMessage()}\n");
        $pdo->prepare("UPDATE job_batches SET status = 'FAILED', duration_ms = ?, finished_at = NOW(),
                notes = CONCAT(COALESCE(notes, ''), ' | reduce_error: ', ?) WHERE id = ?")
            ->execute([$wallMs, $e->getMessage(), $batchId]);
        $allGood = false;
        $runs[] = [
            'workers' => $workers,
            'batch_id' => $batchId,
            'batch_code' => $batchCode,
            'status' => 'FAILED',
            'complete' => false,
            'wall_ms' => $wallMs,
            'checksum_match' => null,
            'error' => $e->getMessage(),
            'worker_output' => $workerOutput,
            'worker_errors' => $workerErrors,
            'exit_codes' => $exitCodes,
        ];
        continue;
    }
    $speedup = $baselineSummary['duration_ms'] > 0
        ? round($baselineSummary['duration_ms'] / max(1, $wallMs), 4)
        : null;
    $efficiency = ($speedup !== null && $workers > 0) ? round($speedup / $workers, 4) : null;
    $distinctWorkers = count(array_unique(array_filter(array_column($reduce['partitions'], 'worker_id'))));
    $jobsDone = count(array_filter($reduce['partitions'], static fn(array $p): bool => $p['status'] === 'DONE'));
    $maxAttempts = 0;
    foreach ($reduce['partitions'] as $partition) {
        $maxAttempts = max($maxAttempts, (int)$partition['attempts']);
    }

    $metricsMatch = $reduce['verified_count'] === $baselineSummary['verified_count']
        && $reduce['mismatch_count'] === $baselineSummary['mismatch_count']
        && $reduce['error_count'] === $baselineSummary['error_count']
        && (int)round($reduce['revenue_all']) === (int)round($baselineSummary['revenue_all'])
        && (int)round($reduce['revenue_verified']) === (int)round($baselineSummary['revenue_verified']);

    $good = $reduce['complete']
        && $reduce['checksum_match'] === true
        && $metricsMatch
        && $jobsDone === count($partitions)
        && $distinctWorkers === count($partitions);
    $allGood = $allGood && $good;

    printf(
        " P=%-2d wall=%4d ms | checksum=%s (%s) | jobs %d/%d | worker unik=%d | maxAttempts=%d | speedup=%s\n",
        $workers,
        $wallMs,
        $reduce['checksum'] ?? '-',
        $reduce['checksum_match'] === true ? 'MATCH' : ($reduce['checksum_match'] === false ? 'DIFFERENT' : 'NO-BASELINE'),
        $jobsDone,
        count($partitions),
        $distinctWorkers,
        $maxAttempts,
        $speedup === null ? '-' : number_format($speedup, 3, ',', '.')
    );
    if (!$good) {
        echo "      [!] Gate gagal: complete=" . ($reduce['complete'] ? '1' : '0')
            . " metricsMatch=" . ($metricsMatch ? '1' : '0')
            . " duplicates=" . count($reduce['duplicate_ids']) . "\n";
    }

    $runs[] = [
        'workers' => $workers,
        'batch_id' => $batchId,
        'batch_code' => $batchCode,
        'status' => $reduce['status'],
        'complete' => $reduce['complete'],
        'wall_ms' => $wallMs,
        'duration_ms' => $reduce['duration_ms'],
        'speedup' => $speedup,
        'efficiency' => $efficiency,
        'throughput_per_sec' => $reduce['throughput_per_sec'],
        'jobs_total' => count($reduce['partitions']),
        'jobs_done' => $jobsDone,
        'distinct_workers' => $distinctWorkers,
        'max_attempts' => $maxAttempts,
        'checksum' => $reduce['checksum'],
        'baseline_checksum' => $reduce['baseline_checksum'],
        'checksum_match' => $reduce['checksum_match'],
        'metrics_match' => $metricsMatch,
        'metrics_consistent' => $reduce['metrics_consistent'],
        'duplicate_ids' => $reduce['duplicate_ids'],
        'processed_orders' => $reduce['processed_orders'],
        'verified_count' => $reduce['verified_count'],
        'mismatch_count' => $reduce['mismatch_count'],
        'error_count' => $reduce['error_count'],
        'paid_count' => $reduce['paid_count'],
        'unpaid_count' => $reduce['unpaid_count'],
        'revenue_all' => $reduce['revenue_all'],
        'revenue_verified' => $reduce['revenue_verified'],
        'aov' => $reduce['aov'],
        'correctness_gate' => $reduce['checksum_match'] === true ? 'MATCH' : 'DIFFERENT',
        'partitions' => $reduce['partitions'],
        'worker_output' => $workerOutput,
        'worker_errors' => $workerErrors,
        'exit_codes' => $exitCodes,
    ];
}

$report = [
    'executed_at' => date('c'),
    'mode' => 'ALGORITHM',
    'dataset' => [
        'name' => $meta['dataset_name'],
        'size' => (int)$meta['dataset_size'],
        'hash' => $meta['dataset_hash'],
        'path' => $datasetPath,
    ],
    'latency_us' => $latencyUs,
    'baseline' => $baselineSummary,
    'runs' => $runs,
    'all_gates_pass' => $allGood,
];

echo "\n===============================================================\n";
echo " RINGKASAN PARALLEL\n";
echo "===============================================================\n";
printf(" %-4s %-10s %-12s %-10s %-8s %-8s\n", 'P', 'Wall(ms)', 'Checksum', 'Match', 'Speedup', 'Jobs');
foreach ($runs as $run) {
    printf(
        " %-4d %-10d %-12s %-10s %-8s %d/%d\n",
        $run['workers'],
        $run['wall_ms'],
        substr((string)$run['checksum'], 0, 12),
        $run['checksum_match'] === true ? 'MATCH' : 'DIFF',
        $run['speedup'] === null ? '-' : number_format($run['speedup'], 3, ',', '.'),
        $run['jobs_done'],
        $run['jobs_total']
    );
}
echo "---------------------------------------------------------------\n";
echo " Exit gate output sequential = parallel : " . ($allGood ? 'PASS' : 'FAIL') . "\n";
echo "===============================================================\n";

if (isset($options['json-out'])) {
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents((string)$options['json-out'], $json . "\n") === false) {
        fwrite(STDERR, "[WARN] Gagal menulis JSON report.\n");
    } else {
        echo " JSON report : " . (string)$options['json-out'] . "\n";
    }
}

exit($allGood ? 0 : 1);
