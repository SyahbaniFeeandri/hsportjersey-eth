<?php
/**
 * Harness verifikasi PAR-01 & PAR-02 + cek race condition/duplikasi.
 * Issue: W6-FDR-11, W6-KEN-06
 * Pemilik: Farelly De Rossi
 * Validator: Kenjiro B.H.G.
 *
 * Yang diuji:
 * - PAR-01: baseline sequential (waktu, komposisi, agregasi finansial, checksum).
 * - PAR-02: worker 1/2/4/8 memproses partisi berbeda, hasil sama dengan sequential.
 * - Race condition: setiap job diklaim tepat satu worker, attempts=1,
 *   satu checkpoint per job, tidak ada order duplikat/gap.
 * - Lease recovery: job RUNNING yang lease-nya kadaluarsa dipulihkan worker.
 * - Idempotensi reducer: reduce dua kali menghasilkan checksum sama.
 *
 * Jalankan:
 *   php tests/parallel/run_par.php
 *   php tests/parallel/run_par.php --latency-us=5000 --workers=1,2,4,8
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/benchmark/lib/Reducer.php';

use HSport\Benchmark\Reducer;

function parseHarnessOptions(array $argv): array
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
function runPhpScript(string $root, array $args): array
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

function readJsonFile(string $path): ?array
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

function check(string $code, string $name, bool $condition, string $detail): void
{
    global $results, $passed, $failed;
    $status = $condition ? 'PASS' : 'FAIL';
    $condition ? $passed++ : $failed++;
    $results[] = ['code' => $code, 'name' => $name, 'status' => $status, 'detail' => $detail];
    echo ' [' . ($condition ? '✓ PASS' : '✗ FAIL') . "] {$code}: {$name}\n";
    echo "         -> {$detail}\n\n";
}

$options = parseHarnessOptions($argv);
$config = require $root . '/config/parallel.php';
$latencyUs = isset($options['latency-us']) ? max(0, (int)$options['latency-us']) : 5000;
$workerCounts = isset($options['workers']) ? (string)$options['workers'] : '1,2,4,8';
$datasetPath = $root . '/benchmark/datasets/D500.json';
$evidenceDir = $root . '/docs/week6/evidence';
if (!is_dir($evidenceDir)) {
    mkdir($evidenceDir, 0777, true);
}

$dataset = readJsonFile($datasetPath);
if ($dataset === null || !isset($dataset['orders'], $dataset['meta'])) {
    fwrite(STDERR, "[ERROR] Dataset D500 tidak valid: {$datasetPath}\n");
    exit(1);
}
$meta = $dataset['meta'];
$totalOrders = (int)$meta['dataset_size'];

$sumRevenue = 0;
$composition = ['VERIFIED' => 0, 'MISMATCH' => 0, 'ERROR' => 0];
$paid = 0;
$unpaid = 0;
foreach ($dataset['orders'] as $order) {
    $sumRevenue += (int)$order['total_amount'];
    $composition[$order['mock_category']] = ($composition[$order['mock_category']] ?? 0) + 1;
    ($order['payment_status'] ?? '') === 'paid' ? $paid++ : $unpaid++;
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
    fwrite(STDERR, "[ERROR] MySQL wajib aktif: {$e->getMessage()}\n");
    exit(1);
}

echo "===============================================================\n";
echo "   H-SPORT FINANCIAL dAPP — PARALLEL TEST SUITE (PAR-01/02)\n";
echo "   Dataset: {$meta['dataset_name']} ({$totalOrders} order) | latency={$latencyUs} us\n";
echo "   Waktu  : " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n\n";

// ---------------------------------------------------------------------
// PAR-01: baseline sequential
// ---------------------------------------------------------------------
$seqJson = $evidenceDir . '/par01_sequential.json';
$seqBatchCode = 'SEQ-PAR01-' . date('YmdHis') . '-' . substr(uniqid('', true), -4);
$seqRun = runPhpScript($root, [
    'benchmark/sequential_baseline.php',
    '--dataset=' . $datasetPath,
    '--latency-us=' . $latencyUs,
    '--db',
    '--batch=' . $seqBatchCode,
    '--json-out=' . $seqJson,
]);
$seq = readJsonFile($seqJson);

if ($seq === null) {
    check('PAR-01', 'Baseline sequential berjalan', false, 'JSON baseline tidak dihasilkan. stderr: ' . trim($seqRun['stderr']));
} else {
    $checksumOk = isset($seq['checksum']) && strlen((string)$seq['checksum']) === 64;
    $compositionOk = (int)$seq['verified_count'] === $composition['VERIFIED']
        && (int)$seq['mismatch_count'] === $composition['MISMATCH']
        && (int)$seq['error_count'] === $composition['ERROR'];
    $financeOk = (int)round((float)$seq['revenue_all']) === $sumRevenue
        && (int)$seq['paid_count'] === $paid
        && (int)$seq['unpaid_count'] === $unpaid
        && abs((float)$seq['aov'] - round($sumRevenue / $totalOrders, 4)) < 0.01;
    $countsOk = (int)$seq['total_orders'] === $totalOrders
        && (int)$seq['worker_count'] === 1
        && (int)$seq['duration_ms'] > 0;

    check('PAR-01.a', 'Baseline sequential selesai & tersimpan', $seqRun['exit_code'] === 0 && $countsOk,
        "exit={$seqRun['exit_code']} total={$seq['total_orders']} durasi={$seq['duration_ms']} ms worker={$seq['worker_count']}");
    check('PAR-01.b', 'Komposisi VERIFIED/MISMATCH/ERROR sesuai dataset', $compositionOk,
        "seq={$seq['verified_count']}/{$seq['mismatch_count']}/{$seq['error_count']} expected={$composition['VERIFIED']}/{$composition['MISMATCH']}/{$composition['ERROR']}");
    check('PAR-01.c', 'Agregasi finansial (revenue, paid/unpaid, AOV) konsisten', $financeOk,
        'revenue=Rp ' . number_format((float)$seq['revenue_all'], 0, ',', '.') . " expected=Rp " . number_format($sumRevenue, 0, ',', '.')
        . " paid/unpaid={$seq['paid_count']}/{$seq['unpaid_count']} aov={$seq['aov']}");
    check('PAR-01.d', 'Checksum sequential valid (sha256)', $checksumOk,
        'checksum=' . ($seq['checksum'] ?? '-'));
}

$sequentialChecksum = is_array($seq) ? (string)($seq['checksum'] ?? '') : '';

// ---------------------------------------------------------------------
// PAR-02: parallel 1/2/4/8 worker
// ---------------------------------------------------------------------
$parJson = $evidenceDir . '/par02_parallel.json';
$parRun = runPhpScript($root, [
    'benchmark/parallel_runner.php',
    '--dataset=' . $datasetPath,
    '--workers=' . $workerCounts,
    '--latency-us=' . $latencyUs,
    '--json-out=' . $parJson,
]);
$par = readJsonFile($parJson);

if ($par === null || !isset($par['runs'])) {
    check('PAR-02', 'Parallel runner berjalan', false, 'JSON parallel tidak dihasilkan. stderr: ' . trim($parRun['stderr']));
    $par = ['runs' => [], 'all_gates_pass' => false];
} else {
    check('PAR-02.a', 'Parallel runner selesai untuk semua jumlah worker', $parRun['exit_code'] === 0 && ($par['all_gates_pass'] ?? false) === true,
        'exit=' . $parRun['exit_code'] . ' all_gates_pass=' . (($par['all_gates_pass'] ?? false) ? 'true' : 'false'));

    $allChecksumMatch = !empty($par['runs']);
    foreach ($par['runs'] as $candidate) {
        if (($candidate['checksum_match'] ?? null) !== true
            || !hash_equals($sequentialChecksum, (string)($candidate['checksum'] ?? ''))) {
            $allChecksumMatch = false;
            break;
        }
    }
    check('PAR-02.b', 'Checksum parallel = sequential untuk setiap P', $allChecksumMatch,
        'baseline=' . substr($sequentialChecksum, 0, 16) . '… run=' . count($par['runs']));

    foreach ($par['runs'] as $run) {
        $p = (int)$run['workers'];
        $ranges = [];
        foreach (($run['partitions'] ?? []) as $partition) {
            $ranges[] = [(int)$partition['range_start'], (int)$partition['range_end']];
        }
        usort($ranges, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $cursor = 1;
        $coverageOk = true;
        foreach ($ranges as $range) {
            if ($range[0] !== $cursor || $range[1] < $range[0]) {
                $coverageOk = false;
                break;
            }
            $cursor = $range[1] + 1;
        }
        $coverageOk = $coverageOk && $cursor === $totalOrders + 1;

        $sameChecksum = ($run['checksum_match'] ?? null) === true
            && hash_equals($sequentialChecksum, (string)($run['checksum'] ?? ''));
        $jobsOk = ($run['complete'] ?? false) === true
            && (int)$run['processed_orders'] === $totalOrders
            && (int)$run['jobs_done'] === (int)$run['jobs_total']
            && (int)$run['distinct_workers'] === (int)$run['jobs_total']
            && (int)$run['max_attempts'] === 1;
        $metricsOk = ($run['metrics_match'] ?? false) === true
            && (int)round((float)$run['revenue_all']) === $sumRevenue
            && empty($run['duplicate_ids']);

        check("PAR-02.P{$p}.a", "P={$p}: checksum & jumlah order sama dengan sequential", $sameChecksum && $jobsOk,
            "checksum={$run['checksum']} jobs={$run['jobs_done']}/{$run['jobs_total']} worker_unik={$run['distinct_workers']} attempts_max={$run['max_attempts']}");
        check("PAR-02.P{$p}.b", "P={$p}: partisi menutup 1..{$totalOrders} tanpa gap/overlap", $coverageOk,
            'ranges=' . implode(', ', array_map(static fn(array $r): string => "{$r[0]}..{$r[1]}", $ranges)));
        check("PAR-02.P{$p}.c", "P={$p}: metrik finansial & bebas duplikasi", $metricsOk,
            'revenue=Rp ' . number_format((float)$run['revenue_all'], 0, ',', '.') . " duplicates=" . count($run['duplicate_ids']));

        // Race condition: satu checkpoint per job, tidak ada job dikerjakan >1 kali.
        $batchId = (int)$run['batch_id'];
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM checkpoints WHERE batch_id = ?');
        $stmt->execute([$batchId]);
        $checkpointCount = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM verification_jobs WHERE batch_id = ? AND attempts > 1');
        $stmt->execute([$batchId]);
        $retriedJobs = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT worker_id) FROM verification_jobs WHERE batch_id = ? AND status = 'DONE'");
        $stmt->execute([$batchId]);
        $dbWorkers = (int)$stmt->fetchColumn();

        check("PAR-02.P{$p}.d", "P={$p}: tidak ada job diklaim dua kali / checkpoint ganda",
            $checkpointCount === (int)$run['jobs_total'] && $retriedJobs === 0 && $dbWorkers === (int)$run['jobs_total'],
            "checkpoints={$checkpointCount} jobs={$run['jobs_total']} retried={$retriedJobs} distinct_worker_db={$dbWorkers}");
    }
}

// ---------------------------------------------------------------------
// Lease recovery: job RUNNING dengan lease kadaluarsa harus dipulihkan
// ---------------------------------------------------------------------
$leaseBatchCode = 'LEASE-' . date('YmdHis') . '-' . substr(uniqid('', true), -4);
$recoveryDetail = '';
$leaseOk = false;
try {
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO job_batches
            (batch_code, mode, dataset_name, dataset_size, dataset_hash, worker_count, is_baseline,
             status, total_orders, started_at, notes)
        VALUES (?, 'ALGORITHM', 'D500', 500, ?, 1, 0, 'RUNNING', 10, NOW(), 'lease recovery test')")
        ->execute([$leaseBatchCode, $meta['dataset_hash']]);
    $leaseBatchId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO verification_jobs
            (batch_id, partition_no, range_start, range_end, status, worker_id, attempts, max_attempts, leased_at, started_at)
        VALUES (?, 1, 1, 10, 'RUNNING', 'crashed-worker', 1, 3, (NOW() - INTERVAL 600 SECOND), NOW())")
        ->execute([$leaseBatchId]);
    $pdo->commit();

    $recoveryRun = runPhpScript($root, [
        'workers/benchmark_worker.php',
        '--batch=' . $leaseBatchId,
        '--worker=recovery-test',
        '--dataset=' . $datasetPath,
        '--latency-us=0',
        '--lease-recovery',
        '--max-jobs=1',
    ]);

    $stmt = $pdo->prepare('SELECT status, worker_id, attempts FROM verification_jobs WHERE batch_id = ?');
    $stmt->execute([$leaseBatchId]);
    $leaseJob = $stmt->fetch();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM checkpoints WHERE batch_id = ?');
    $stmt->execute([$leaseBatchId]);
    $leaseCheckpoints = (int)$stmt->fetchColumn();

    $leaseOk = $recoveryRun['exit_code'] === 0
        && $leaseJob
        && $leaseJob['status'] === 'DONE'
        && $leaseJob['worker_id'] === 'recovery-test'
        && (int)$leaseJob['attempts'] === 2
        && $leaseCheckpoints === 1;
    $recoveryDetail = 'status=' . ($leaseJob['status'] ?? '-')
        . ' worker=' . ($leaseJob['worker_id'] ?? '-')
        . ' attempts=' . ($leaseJob['attempts'] ?? '-')
        . ' checkpoints=' . $leaseCheckpoints;

    $pdo->prepare('DELETE FROM job_batches WHERE id = ?')->execute([$leaseBatchId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $recoveryDetail = 'exception: ' . $e->getMessage();
}
check('RACE-01', 'Lease recovery memulihkan job RUNNING yang kadaluarsa', $leaseOk, $recoveryDetail);

// ---------------------------------------------------------------------
// Idempotensi reducer: reduce dua kali harus menghasilkan checksum sama
// ---------------------------------------------------------------------
$idempotentOk = false;
$idempotentDetail = 'tidak ada run paralel';
if (!empty($par['runs'])) {
    $lastRun = $par['runs'][count($par['runs']) - 1];
    $batchId = (int)$lastRun['batch_id'];
    $reducer = new Reducer($pdo);
    $stmtBefore = $pdo->prepare('SELECT COUNT(*) FROM checkpoints WHERE batch_id = ?');
    $stmtBefore->execute([$batchId]);
    $before = (int)$stmtBefore->fetchColumn();

    $first = $reducer->reduce($batchId);
    $second = $reducer->reduce($batchId);

    $stmtAfter = $pdo->prepare('SELECT COUNT(*) FROM checkpoints WHERE batch_id = ?');
    $stmtAfter->execute([$batchId]);
    $after = (int)$stmtAfter->fetchColumn();

    $idempotentOk = $first['checksum'] === $second['checksum']
        && $first['checksum'] === $sequentialChecksum
        && $before === $after;
    $idempotentDetail = 'checksum1=' . substr((string)$first['checksum'], 0, 16)
        . ' checksum2=' . substr((string)$second['checksum'], 0, 16)
        . " checkpoints={$before}->{$after}";
}
check('RACE-02', 'Reducer idempoten (reduce ulang tidak mengubah checksum/checkpoint)', $idempotentOk, $idempotentDetail);

// ---------------------------------------------------------------------
// Ringkasan + evidence
// ---------------------------------------------------------------------
$totalChecks = $passed + $failed;
echo "===============================================================\n";
echo "   HASIL AKHIR: {$passed} / {$totalChecks} CHECK PASS\n";
echo "   Status: " . ($failed === 0 ? 'ALL PASS (100%)' : 'PARTIAL / FAIL') . "\n";
echo "===============================================================\n";

$evidence = [
    'executed_at' => date('c'),
    'dataset' => [
        'name' => $meta['dataset_name'],
        'size' => $totalOrders,
        'hash' => $meta['dataset_hash'],
        'latency_us' => $latencyUs,
    ],
    'par01' => $seq,
    'par02' => $par,
    'summary' => [
        'total_checks' => $totalChecks,
        'passed' => $passed,
        'failed' => $failed,
        'all_pass' => $failed === 0,
    ],
    'checks' => $results,
];
file_put_contents($evidenceDir . '/par01_par02_test.json', json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

exit($failed === 0 ? 0 : 1);
