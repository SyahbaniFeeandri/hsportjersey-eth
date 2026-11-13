<?php
/**
 * Harness PAR-03 & PAR-04 - metrik benchmark dan ketahanan konkurensi.
 * Issue: W7-KEN-08, W7-KEN-09, W7-KEN-10
 * Pemilik: Kenjiro B.H.G.
 * Validator: Feeandri
 *
 * PAR-03: validasi tabel metrik (wall-clock, speedup, efficiency,
 *         throughput) dari benchmark matrix 4 dataset x 4 jumlah worker.
 * PAR-04: ketahanan konkurensi & integritas agregasi:
 *         - dua worker memperebutkan satu job -> hanya satu yang memproses;
 *         - pengulangan P=8 menghasilkan checksum yang sama;
 *         - checkpoint unik, attempts=1, tidak ada order duplikat;
 *         - lease recovery memulihkan job yang ditinggal worker mati;
 *         - reducer idempoten.
 *
 * Jalankan (matrix harus sudah dibuat):
 *   php benchmark/run_matrix.php
 *   php tests/parallel/run_par03_par04.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/benchmark/lib/Reducer.php';

use HSport\Benchmark\Reducer;

function parParseOptions(array $argv): array
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

/**
 * @param array<int,string> $args
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function parRunPhp(string $root, array $args): array
{
    $command = array_merge([PHP_BINARY], $args);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, $root);
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
 * Menjalankan beberapa proses PHP secara bersamaan.
 *
 * @param array<int,array<int,string>> $commands
 * @return array<int,array{exit_code:int,stdout:string,stderr:string}>
 */
function parRunParallel(string $root, array $commands): array
{
    $processes = [];
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    foreach ($commands as $index => $args) {
        $process = proc_open(array_merge([PHP_BINARY], $args), $descriptors, $pipes, $root);
        if (!is_resource($process)) {
            $processes[$index] = null;
            continue;
        }
        fclose($pipes[0]);
        $processes[$index] = ['process' => $process, 'pipes' => $pipes];
    }

    $results = [];
    foreach ($processes as $index => $entry) {
        if ($entry === null) {
            $results[$index] = ['exit_code' => -1, 'stdout' => '', 'stderr' => 'spawn gagal'];
            continue;
        }
        $stdout = stream_get_contents($entry['pipes'][1]);
        $stderr = stream_get_contents($entry['pipes'][2]);
        fclose($entry['pipes'][1]);
        fclose($entry['pipes'][2]);
        $exitCode = proc_close($entry['process']);
        $results[$index] = [
            'exit_code' => $exitCode,
            'stdout' => $stdout === false ? '' : $stdout,
            'stderr' => $stderr === false ? '' : $stderr,
        ];
    }

    return $results;
}

function parReadJson(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $decoded = json_decode((string)file_get_contents($path), true);

    return is_array($decoded) ? $decoded : null;
}

$results = [];
$passed = 0;
$failed = 0;

function parCheck(string $code, string $name, bool $condition, string $detail): void
{
    global $results, $passed, $failed;
    $status = $condition ? 'PASS' : 'FAIL';
    $condition ? $passed++ : $failed++;
    $results[] = ['code' => $code, 'name' => $name, 'status' => $status, 'detail' => $detail];
    echo ' [' . ($condition ? '✓ PASS' : '✗ FAIL') . "] {$code}: {$name}\n";
    echo "         -> {$detail}\n\n";
}

$options = parParseOptions($argv);
$config = require $root . '/config/parallel.php';
$evidenceDir = $root . '/docs/week7/evidence';
$matrixPath = (string)($options['matrix'] ?? ($evidenceDir . '/benchmark_matrix.json'));
$latencyUs = isset($options['latency-us']) ? max(0, (int)$options['latency-us']) : 5000;
$par04Workers = isset($options['workers']) ? max(1, (int)$options['workers']) : 8;

$db = $config['db'];
$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "[ERROR] MySQL wajib aktif: {$e->getMessage()}\n");
    exit(1);
}

echo "===============================================================\n";
echo "   H-SPORT — PAR-03 & PAR-04 (METRIK + KONKURENSI)\n";
echo "   Matrix : {$matrixPath}\n";
echo "   Waktu  : " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n\n";

// ---------------------------------------------------------------------
// PAR-03 — validasi metrik benchmark matrix
// ---------------------------------------------------------------------
$matrix = parReadJson($matrixPath);
$par03Pass = false;
$par03Detail = 'matrix tidak ditemukan';

if ($matrix === null || !isset($matrix['rows'])) {
    parCheck('PAR-03', 'Tabel metrik 4 dataset x 4 worker tersedia', false, $par03Detail);
} else {
    $rows = $matrix['rows'];
    $expectedConfigs = 16;
    $configsOk = count($rows) === $expectedConfigs;
    $metricErrors = [];
    $efficiencyErrors = [];
    $gateErrors = [];

    foreach ($rows as $row) {
        $duration = (int)$row['duration_ms'];
        $processed = (int)$row['processed_orders'];
        $expectedThroughput = $duration > 0 ? $processed / ($duration / 1000) : 0;
        $expectedSpeedup = (int)$row['baseline_duration_ms'] > 0
            ? (int)$row['baseline_duration_ms'] / max(1, $duration)
            : 0;
        $expectedEfficiency = (int)$row['workers'] > 0 ? $expectedSpeedup / (int)$row['workers'] : 0;

        if ($duration <= 0
            || abs((float)$row['throughput_per_sec'] - $expectedThroughput) > max(0.05, $expectedThroughput * 0.01)
            || abs((float)$row['speedup'] - $expectedSpeedup) > 0.01) {
            $metricErrors[] = "{$row['dataset']} P={$row['workers']}";
        }
        if ((float)$row['efficiency'] <= 0
            || (float)$row['efficiency'] > 1.05
            || abs((float)$row['efficiency'] - $expectedEfficiency) > 0.01) {
            $efficiencyErrors[] = "{$row['dataset']} P={$row['workers']}";
        }
        if (($row['checksum_match'] ?? false) !== true || ($row['pass'] ?? false) !== true) {
            $gateErrors[] = "{$row['dataset']} P={$row['workers']}";
        }
    }

    // Speedup harus naik dari P=1 ke P=2 dan P=2 ke P=4 pada dataset >= 500.
    $monotonicErrors = [];
    $byDataset = [];
    foreach ($rows as $row) {
        $byDataset[(string)$row['dataset']][(int)$row['workers']] = (float)$row['speedup'];
    }
    foreach ($byDataset as $dataset => $series) {
        $size = (int)substr($dataset, 1);
        if ($size < 500) {
            continue;
        }
        if (!isset($series[1], $series[2], $series[4]) || !($series[2] > $series[1] && $series[4] > $series[2])) {
            $monotonicErrors[] = $dataset;
        }
    }

    $par03Pass = $configsOk && empty($metricErrors) && empty($efficiencyErrors) && empty($gateErrors) && empty($monotonicErrors);
    $par03Detail = 'rows=' . count($rows)
        . ' metric_error=' . count($metricErrors)
        . ' efficiency_error=' . count($efficiencyErrors)
        . ' gate_error=' . count($gateErrors)
        . ' monotonic_error=' . count($monotonicErrors);
    if (!$par03Pass) {
        $par03Detail .= ' | ' . implode(', ', array_merge($metricErrors, $efficiencyErrors, $gateErrors, $monotonicErrors));
    }

    parCheck('PAR-03.a', 'Matrix berisi 4 dataset x 4 jumlah worker', $configsOk, 'rows=' . count($rows) . ' expected=' . $expectedConfigs);
    parCheck('PAR-03.b', 'Throughput & speedup konsisten dengan wall-clock', empty($metricErrors), 'error=' . count($metricErrors));
    parCheck('PAR-03.c', 'Efficiency = speedup / worker dan bernilai wajar', empty($efficiencyErrors), 'error=' . count($efficiencyErrors));
    parCheck('PAR-03.d', 'Speedup meningkat pada dataset >= 500 order', empty($monotonicErrors), 'dataset gagal=' . count($monotonicErrors));
    parCheck('PAR-03.e', 'Seluruh konfigurasi checksum MATCH', empty($gateErrors), 'gate gagal=' . count($gateErrors));
}

// ---------------------------------------------------------------------
// PAR-04a — dua worker memperebutkan satu job yang sama
// ---------------------------------------------------------------------
$raceOk = false;
$raceDetail = '';
$raceBatchId = 0;
$raceCode = 'PAR04-RACE-' . date('YmdHis') . '-' . substr(uniqid('', true), -4);
$datasetPath = $root . '/benchmark/datasets/D1000.json';
$dataset = null;

try {
    $dataset = parReadJson($datasetPath);
    if ($dataset === null) {
        throw new RuntimeException('Dataset D1000 tidak ditemukan.');
    }

    $pdo->prepare("INSERT INTO job_batches
            (batch_code, mode, dataset_name, dataset_size, dataset_hash, worker_count, is_baseline,
             status, total_orders, started_at, notes)
        VALUES (?, 'ALGORITHM', 'D1000', 1000, ?, 2, 0, 'RUNNING', 50, NOW(), 'PAR-04 race test')")
        ->execute([$raceCode, $dataset['meta']['dataset_hash']]);
    $raceBatchId = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO verification_jobs
            (batch_id, partition_no, range_start, range_end, status, max_attempts)
        VALUES (?, 1, 1, 50, 'PENDING', 3)")
        ->execute([$raceBatchId]);
    $raceJobId = (int)$pdo->lastInsertId();

    $runs = parRunParallel($root, [
        [
            'workers/benchmark_worker.php',
            '--batch=' . $raceBatchId,
            '--job-id=' . $raceJobId,
            '--worker=par04-racer-a',
            '--dataset=' . $datasetPath,
            '--latency-us=0',
            '--max-jobs=1',
        ],
        [
            'workers/benchmark_worker.php',
            '--batch=' . $raceBatchId,
            '--job-id=' . $raceJobId,
            '--worker=par04-racer-b',
            '--dataset=' . $datasetPath,
            '--latency-us=0',
            '--max-jobs=1',
        ],
    ]);

    $stmt = $pdo->prepare("SELECT status, worker_id, attempts FROM verification_jobs WHERE id = ?");
    $stmt->execute([$raceJobId]);
    $job = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM checkpoints WHERE job_id = ?");
    $stmt->execute([$raceJobId]);
    $checkpoints = (int)$stmt->fetchColumn();

    $successRuns = count(array_filter($runs, static fn(array $run): bool => $run['exit_code'] === 0));
    $raceOk = $successRuns === 2
        && $job
        && $job['status'] === 'DONE'
        && (int)$job['attempts'] === 1
        && $checkpoints === 1
        && in_array($job['worker_id'], ['par04-racer-a', 'par04-racer-b'], true);
    $raceDetail = 'exit=' . implode('/', array_column($runs, 'exit_code'))
        . ' status=' . ($job['status'] ?? '-')
        . ' worker=' . ($job['worker_id'] ?? '-')
        . ' attempts=' . ($job['attempts'] ?? '-')
        . ' checkpoints=' . $checkpoints;
} catch (Throwable $e) {
    $raceDetail = 'exception: ' . $e->getMessage();
}
parCheck('PAR-04.a', 'Dua worker memperebutkan satu job: hanya satu yang memproses', $raceOk, $raceDetail);
if ($raceBatchId > 0) {
    $pdo->prepare('DELETE FROM job_batches WHERE id = ?')->execute([$raceBatchId]);
}

// ---------------------------------------------------------------------
// PAR-04b — pengulangan P=8 menghasilkan checksum identik
// ---------------------------------------------------------------------
$repeatOk = false;
$repeatDetail = 'belum dijalankan';
$batchIds = [];
try {
    $repeatFiles = [];
    for ($i = 1; $i <= 2; $i++) {
        $jsonPath = $evidenceDir . '/par04_repeat' . $i . '.json';
        $run = parRunPhp($root, [
            'benchmark/parallel_runner.php',
            '--dataset=' . $datasetPath,
            '--workers=' . $par04Workers,
            '--latency-us=' . $latencyUs,
            '--no-baseline-run',
            '--json-out=' . $jsonPath,
        ]);
        if ($run['exit_code'] !== 0) {
            throw new RuntimeException('runner repeat ' . $i . ' exit=' . $run['exit_code'] . ' ' . trim($run['stderr']));
        }
        $report = parReadJson($jsonPath);
        if ($report === null || empty($report['runs'][0])) {
            throw new RuntimeException('JSON repeat ' . $i . ' tidak valid.');
        }
        $repeatFiles[] = $report;
        $batchIds[] = (int)$report['runs'][0]['batch_id'];
    }

    $checksums = [];
    $baselineChecksum = null;
    $gateOk = true;
    foreach ($repeatFiles as $report) {
        $repeatRun = $report['runs'][0];
        $checksums[] = (string)$repeatRun['checksum'];
        $baselineChecksum = (string)$report['baseline']['checksum'];
        $gateOk = $gateOk
            && ($repeatRun['checksum_match'] ?? false) === true
            && ($repeatRun['metrics_match'] ?? false) === true
            && (int)$repeatRun['processed_orders'] === 1000
            && (int)$repeatRun['jobs_done'] === $par04Workers
            && (int)$repeatRun['distinct_workers'] === $par04Workers
            && (int)$repeatRun['max_attempts'] === 1
            && empty($repeatRun['duplicate_ids']);
    }
    $repeatOk = $gateOk
        && count(array_unique($checksums)) === 1
        && $checksums[0] === $baselineChecksum;
    $repeatDetail = 'checksum1=' . substr($checksums[0] ?? '-', 0, 16)
        . ' checksum2=' . substr($checksums[1] ?? '-', 0, 16)
        . ' baseline=' . substr((string)$baselineChecksum, 0, 16)
        . ' gate=' . ($gateOk ? 'ok' : 'gagal');
} catch (Throwable $e) {
    $repeatDetail = 'exception: ' . $e->getMessage();
}
parCheck('PAR-04.b', 'Pengulangan P=' . $par04Workers . ' menghasilkan checksum identik', $repeatOk, $repeatDetail);

// ---------------------------------------------------------------------
// PAR-04c — checkpoint unik, attempts=1, reducer idempoten
// ---------------------------------------------------------------------
$integrityOk = false;
$integrityDetail = 'tidak ada batch';
if (!empty($batchIds)) {
    $batchId = $batchIds[0];
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM checkpoints WHERE batch_id = ?');
    $stmt->execute([$batchId]);
    $checkpointCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM verification_jobs WHERE batch_id = ? AND attempts > 1');
    $stmt->execute([$batchId]);
    $retried = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT worker_id) FROM verification_jobs WHERE batch_id = ? AND status = 'DONE'");
    $stmt->execute([$batchId]);
    $distinctWorkers = (int)$stmt->fetchColumn();

    $reducer = new Reducer($pdo);
    $first = $reducer->reduce($batchId);
    $second = $reducer->reduce($batchId);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM checkpoints WHERE batch_id = ?');
    $stmt->execute([$batchId]);
    $checkpointAfter = (int)$stmt->fetchColumn();

    $integrityOk = $checkpointCount === $par04Workers
        && $retried === 0
        && $distinctWorkers === $par04Workers
        && $first['checksum'] === $second['checksum']
        && $first['checksum'] === $first['baseline_checksum']
        && $checkpointCount === $checkpointAfter;
    $integrityDetail = "checkpoints={$checkpointCount}->{$checkpointAfter} jobs={$par04Workers}"
        . " retried={$retried} distinct_worker={$distinctWorkers}"
        . ' reduce1=' . substr((string)$first['checksum'], 0, 16)
        . ' reduce2=' . substr((string)$second['checksum'], 0, 16);
}
parCheck('PAR-04.c', 'Checkpoint unik, attempts=1, dan reducer idempoten', $integrityOk, $integrityDetail);

// ---------------------------------------------------------------------
// PAR-04d — lease recovery
// ---------------------------------------------------------------------
$leaseOk = false;
$leaseDetail = '';
$leaseBatchId = 0;
try {
    if ($dataset === null) {
        throw new RuntimeException('Dataset D1000 tidak tersedia untuk uji lease.');
    }
    $leaseCode = 'PAR04-LEASE-' . date('YmdHis') . '-' . substr(uniqid('', true), -4);
    $pdo->prepare("INSERT INTO job_batches
            (batch_code, mode, dataset_name, dataset_size, dataset_hash, worker_count, is_baseline,
             status, total_orders, started_at, notes)
        VALUES (?, 'ALGORITHM', 'D1000', 1000, ?, 1, 0, 'RUNNING', 20, NOW(), 'PAR-04 lease test')")
        ->execute([$leaseCode, $dataset['meta']['dataset_hash']]);
    $leaseBatchId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO verification_jobs
            (batch_id, partition_no, range_start, range_end, status, worker_id, attempts, max_attempts, leased_at, started_at)
        VALUES (?, 1, 1, 20, 'RUNNING', 'crashed-worker', 1, 3, (NOW() - INTERVAL 600 SECOND), NOW())")
        ->execute([$leaseBatchId]);
    $leaseJobId = (int)$pdo->lastInsertId();

    $recovery = parRunPhp($root, [
        'workers/benchmark_worker.php',
        '--batch=' . $leaseBatchId,
        '--job-id=' . $leaseJobId,
        '--worker=par04-recovery',
        '--dataset=' . $datasetPath,
        '--latency-us=0',
        '--lease-recovery',
        '--max-jobs=1',
    ]);

    $stmt = $pdo->prepare('SELECT status, worker_id, attempts FROM verification_jobs WHERE id = ?');
    $stmt->execute([$leaseJobId]);
    $leaseJob = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM checkpoints WHERE job_id = ?');
    $stmt->execute([$leaseJobId]);
    $leaseCheckpoints = (int)$stmt->fetchColumn();

    $leaseOk = $recovery['exit_code'] === 0
        && $leaseJob
        && $leaseJob['status'] === 'DONE'
        && $leaseJob['worker_id'] === 'par04-recovery'
        && (int)$leaseJob['attempts'] === 2
        && $leaseCheckpoints === 1;
    $leaseDetail = 'status=' . ($leaseJob['status'] ?? '-')
        . ' worker=' . ($leaseJob['worker_id'] ?? '-')
        . ' attempts=' . ($leaseJob['attempts'] ?? '-')
        . ' checkpoints=' . $leaseCheckpoints;
} catch (Throwable $e) {
    $leaseDetail = 'exception: ' . $e->getMessage();
}
parCheck('PAR-04.d', 'Lease recovery memulihkan job yang ditinggal worker mati', $leaseOk, $leaseDetail);
if ($leaseBatchId > 0) {
    $pdo->prepare('DELETE FROM job_batches WHERE id = ?')->execute([$leaseBatchId]);
}

// ---------------------------------------------------------------------
// Ringkasan + evidence
// ---------------------------------------------------------------------
$total = $passed + $failed;
echo "===============================================================\n";
echo "   HASIL AKHIR: {$passed} / {$total} CHECK PASS\n";
echo "   Status: " . ($failed === 0 ? 'ALL PASS (100%)' : 'PARTIAL / FAIL') . "\n";
echo "===============================================================\n";

$evidence = [
    'executed_at' => date('c'),
    'matrix' => $matrixPath,
    'latency_us' => $latencyUs,
    'par03' => [
        'rows' => isset($matrix['rows']) ? count($matrix['rows']) : 0,
        'pass' => $par03Pass,
    ],
    'par04' => [
        'race' => $raceOk,
        'repeat' => $repeatOk,
        'integrity' => $integrityOk,
        'lease_recovery' => $leaseOk,
    ],
    'summary' => [
        'total_checks' => $total,
        'passed' => $passed,
        'failed' => $failed,
        'all_pass' => $failed === 0,
    ],
    'checks' => $results,
];
file_put_contents($evidenceDir . '/par03_par04_test.json', json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

exit($failed === 0 ? 0 : 1);
