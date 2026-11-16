<?php
/**
 * CLI: Benchmark matrix dataset x jumlah worker.
 * Issue: W7-KEN-07, W7-KEN-08, W7-FDR-12
 * Pemilik: Kenjiro B.H.G. / Farelly De Rossi
 *
 * Menjalankan benchmark untuk beberapa dataset (default 100/500/1000/5000)
 * dan beberapa jumlah worker (default 1/2/4/8), lalu menghasilkan:
 * - JSON terstruktur (semua run + baseline)
 * - CSV untuk spreadsheet
 * - tabel Markdown untuk laporan
 * - grafik SVG speedup vs jumlah worker
 *
 * Contoh:
 *   php benchmark/run_matrix.php
 *   php benchmark/run_matrix.php --sizes=100,500 --workers=1,2,4 --latency-us=2000
 *   php benchmark/run_matrix.php --reuse
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/benchmark/lib/DatasetGenerator.php';

use HSport\Benchmark\DatasetGenerator;

function parseMatrixOptions(array $argv): array
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
 * @param array<int,string> $command
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function runMatrixProcess(array $command, string $cwd): array
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

function matrixDatasetPath(string $root, int $size): string
{
    return $root . '/benchmark/datasets/D' . $size . '.json';
}

function ensureMatrixDataset(string $root, int $size, string $seed): string
{
    $path = matrixDatasetPath($root, $size);
    if (is_file($path)) {
        $decoded = json_decode((string)file_get_contents($path), true);
        if (is_array($decoded) && isset($decoded['orders'], $decoded['meta'])) {
            return $path;
        }
    }

    $generator = new DatasetGenerator($seed);
    $dataset = $generator->generate($size);
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, json_encode($dataset, JSON_UNESCAPED_SLASHES));

    return $path;
}

function matrixSvg(array $rows, array $datasets): string
{
    $width = 960;
    $height = 540;
    $left = 70;
    $right = 30;
    $top = 50;
    $bottom = 70;
    $plotW = $width - $left - $right;
    $plotH = $height - $top - $bottom;

    $workerValues = [];
    $maxSpeedup = 1.0;
    foreach ($rows as $row) {
        $workerValues[(int)$row['workers']] = true;
        $maxSpeedup = max($maxSpeedup, (float)($row['speedup'] ?? 1));
    }
    $workers = array_keys($workerValues);
    sort($workers);
    if (empty($workers)) {
        $workers = [1, 2, 4, 8];
    }
    $maxY = max(1.0, ceil($maxSpeedup * 1.1));
    $colors = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#dc3545', '#20c997'];
    $workersCount = count($workers);

    $xFor = static function (int $worker, int $index) use ($left, $plotW, $workersCount): float {
        $count = max(1, $workersCount - 1);
        return $left + ($index / $count) * $plotW;
    };
    $yFor = static function (float $value) use ($top, $plotH, $maxY): float {
        return $top + $plotH - ($value / $maxY) * $plotH;
    };

    $svg = [];
    $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
    $svg[] = '<rect width="100%" height="100%" fill="#ffffff"/>';
    $svg[] = '<text x="' . ($width / 2) . '" y="28" text-anchor="middle" font-family="Segoe UI, Arial" font-size="18" font-weight="700" fill="#212529">Speedup vs Jumlah Worker</text>';

    // Grid + sumbu Y
    for ($i = 0; $i <= 5; $i++) {
        $value = $maxY * $i / 5;
        $y = $yFor($value);
        $svg[] = '<line x1="' . $left . '" y1="' . $y . '" x2="' . ($left + $plotW) . '" y2="' . $y . '" stroke="#e9ecef" stroke-width="1"/>';
        $svg[] = '<text x="' . ($left - 10) . '" y="' . ($y + 4) . '" text-anchor="end" font-family="Segoe UI, Arial" font-size="11" fill="#6c757d">' . number_format($value, 1, ',', '.') . 'x</text>';
    }

    // Sumbu X
    foreach ($workers as $index => $worker) {
        $x = $xFor($worker, $index);
        $svg[] = '<line x1="' . $x . '" y1="' . $top . '" x2="' . $x . '" y2="' . ($top + $plotH) . '" stroke="#f1f3f5" stroke-width="1"/>';
        $svg[] = '<text x="' . $x . '" y="' . ($top + $plotH + 24) . '" text-anchor="middle" font-family="Segoe UI, Arial" font-size="12" fill="#495057">P=' . $worker . '</text>';
    }
    $svg[] = '<line x1="' . $left . '" y1="' . ($top + $plotH) . '" x2="' . ($left + $plotW) . '" y2="' . ($top + $plotH) . '" stroke="#343a40" stroke-width="1.5"/>';
    $svg[] = '<line x1="' . $left . '" y1="' . $top . '" x2="' . $left . '" y2="' . ($top + $plotH) . '" stroke="#343a40" stroke-width="1.5"/>';
    $svg[] = '<text x="' . ($left + $plotW / 2) . '" y="' . ($height - 22) . '" text-anchor="middle" font-family="Segoe UI, Arial" font-size="13" fill="#343a40">Jumlah worker (P)</text>';
    $svg[] = '<text x="18" y="' . ($top + $plotH / 2) . '" text-anchor="middle" transform="rotate(-90 18 ' . ($top + $plotH / 2) . ')" font-family="Segoe UI, Arial" font-size="13" fill="#343a40">Speedup (baseline / wall-clock)</text>';

    // Garis ideal speedup = P (skala terhadap P=1) sebagai referensi
    $idealPoints = [];
    foreach ($workers as $index => $worker) {
        $idealPoints[] = $xFor($worker, $index) . ',' . $yFor((float)$worker);
    }
    $svg[] = '<polyline points="' . implode(' ', $idealPoints) . '" fill="none" stroke="#adb5bd" stroke-width="1.5" stroke-dasharray="6 5"/>';

    // Garis per dataset
    foreach ($datasets as $datasetIndex => $dataset) {
        $points = [];
        foreach ($rows as $row) {
            if ((string)$row['dataset'] !== (string)$dataset) {
                continue;
            }
            $index = array_search((int)$row['workers'], $workers, true);
            if ($index === false) {
                continue;
            }
            $points[] = $xFor((int)$row['workers'], (int)$index) . ',' . $yFor((float)$row['speedup']);
        }
        if (empty($points)) {
            continue;
        }
        $color = $colors[$datasetIndex % count($colors)];
        $svg[] = '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="' . $color . '" stroke-width="2.5"/>';
        foreach ($points as $point) {
            [$x, $y] = explode(',', $point);
            $svg[] = '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="' . $color . '"/>';
        }
    }

    // Legenda
    $legendX = $left + 16;
    $legendY = $top + 12;
    foreach ($datasets as $datasetIndex => $dataset) {
        $color = $colors[$datasetIndex % count($colors)];
        $y = $legendY + $datasetIndex * 20;
        $svg[] = '<rect x="' . $legendX . '" y="' . $y . '" width="12" height="12" fill="' . $color . '" rx="2"/>';
        $svg[] = '<text x="' . ($legendX + 18) . '" y="' . ($y + 10) . '" font-family="Segoe UI, Arial" font-size="12" fill="#343a40">' . htmlspecialchars((string)$dataset, ENT_QUOTES) . '</text>';
    }
    $svg[] = '<line x1="' . ($legendX + 190) . '" y1="' . ($legendY + 6) . '" x2="' . ($legendX + 220) . '" y2="' . ($legendY + 6) . '" stroke="#adb5bd" stroke-width="1.5" stroke-dasharray="6 5"/>';
    $svg[] = '<text x="' . ($legendX + 226) . '" y="' . ($legendY + 10) . '" font-family="Segoe UI, Arial" font-size="12" fill="#6c757d">ideal = P</text>';
    $svg[] = '</svg>';

    return implode("\n", $svg) . "\n";
}

function writeMatrixMarkdown(array $rows, array $datasets, array $baselines, int $latencyUs): string
{
    $lines = [];
    $lines[] = '# Benchmark Matrix — Sequential vs Parallel';
    $lines[] = '';
    $lines[] = '**Dibuat:** ' . date('Y-m-d H:i:s');
    $lines[] = '**Latensi simulasi:** ' . $latencyUs . ' us/order (Mode A)';
    $lines[] = '';
    $lines[] = '| Dataset | P | Baseline (ms) | Wall (ms) | Speedup | Efficiency | Throughput (order/s) | Checksum | Gate |';
    $lines[] = '|---|---:|---:|---:|---:|---:|---:|---|---|';
    foreach ($rows as $row) {
        $baselineMs = (int)($baselines[(string)$row['dataset']]['duration_ms'] ?? 0);
        $lines[] = '| ' . $row['dataset']
            . ' | ' . $row['workers']
            . ' | ' . $baselineMs
            . ' | ' . $row['duration_ms']
            . ' | ' . number_format((float)$row['speedup'], 3, ',', '.')
            . ' | ' . number_format((float)$row['efficiency'] * 100, 1, ',', '.') . '%'
            . ' | ' . number_format((float)$row['throughput_per_sec'], 2, ',', '.')
            . ' | `' . substr((string)$row['checksum'], 0, 12) . '…`'
            . ' | ' . ($row['checksum_match'] ? 'MATCH' : 'DIFFERENT')
            . ' |';
    }
    $lines[] = '';
    $lines[] = 'Checksum lengkap tersedia di `benchmark_matrix.json`. Seluruh baris di atas'
        . ' memakai dataset deterministik yang sama dengan baseline sequential.';

    return implode("\n", $lines) . "\n";
}

$options = parseMatrixOptions($argv);
$sizes = isset($options['sizes'])
    ? array_values(array_filter(array_map('intval', explode(',', (string)$options['sizes'])), static fn(int $n): bool => $n > 0))
    : [100, 500, 1000, 5000];
$workers = isset($options['workers'])
    ? array_values(array_filter(array_map('intval', explode(',', (string)$options['workers'])), static fn(int $n): bool => $n >= 1 && $n <= 64))
    : [1, 2, 4, 8];
$latencyUs = isset($options['latency-us']) ? max(0, (int)$options['latency-us']) : 5000;
$seed = (string)($options['seed'] ?? 'HSPORT-2026');
$outDir = (string)($options['out-dir'] ?? ($root . '/docs/week7/evidence'));
$reuse = isset($options['reuse']);

if (empty($sizes) || empty($workers)) {
    fwrite(STDERR, "Daftar dataset/worker tidak valid.\n");
    exit(2);
}
sort($sizes);
sort($workers);
@mkdir($outDir, 0777, true);

$allRows = [];
$baselines = [];
$datasetReports = [];
$failures = [];
$startedAt = microtime(true);

echo "===============================================================\n";
echo " H-SPORT BENCHMARK MATRIX (M7)\n";
echo " Dataset : " . implode(', ', array_map(static fn(int $n): string => 'D' . $n, $sizes)) . "\n";
echo " Worker  : " . implode(', ', $workers) . "\n";
echo " Latency : {$latencyUs} us/order\n";
echo " Waktu   : " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n\n";

foreach ($sizes as $size) {
    $datasetPath = ensureMatrixDataset($root, $size, $seed);
    $datasetName = 'D' . $size;
    $jsonPath = $outDir . '/parallel_' . $datasetName . '.json';

    echo ">>> {$datasetName} ({$size} order)\n";

    if ($reuse && is_file($jsonPath)) {
        $report = json_decode((string)file_get_contents($jsonPath), true);
        echo "    memakai ulang {$jsonPath}\n";
    } else {
        $command = [
            PHP_BINARY,
            $root . '/benchmark/parallel_runner.php',
            '--dataset=' . $datasetPath,
            '--workers=' . implode(',', $workers),
            '--latency-us=' . $latencyUs,
            '--json-out=' . $jsonPath,
        ];
        echo "    menjalankan " . count($workers) . " konfigurasi worker...\n";
        $run = runMatrixProcess($command, $root);
        file_put_contents($outDir . '/parallel_' . $datasetName . '.log', $run['stdout'] . $run['stderr']);
        if ($run['exit_code'] !== 0) {
            $failures[] = "{$datasetName}: runner exit={$run['exit_code']} " . trim($run['stderr']);
        }
        $report = is_file($jsonPath) ? json_decode((string)file_get_contents($jsonPath), true) : null;
    }

    if (!is_array($report) || !isset($report['runs'], $report['baseline'])) {
        $failures[] = "{$datasetName}: report JSON tidak valid";
        echo "    [FAIL] report tidak valid\n\n";
        continue;
    }

    $datasetReports[$datasetName] = $report;
    $baselines[$datasetName] = $report['baseline'];

    foreach ($report['runs'] as $run) {
        $row = [
            'dataset' => $datasetName,
            'dataset_size' => (int)$report['dataset']['size'],
            'dataset_hash' => (string)$report['dataset']['hash'],
            'workers' => (int)$run['workers'],
            'batch_id' => (int)$run['batch_id'],
            'batch_code' => (string)$run['batch_code'],
            'baseline_duration_ms' => (int)$report['baseline']['duration_ms'],
            'duration_ms' => (int)$run['wall_ms'],
            'speedup' => (float)$run['speedup'],
            'efficiency' => (float)$run['efficiency'],
            'throughput_per_sec' => (float)$run['throughput_per_sec'],
            'checksum' => (string)$run['checksum'],
            'baseline_checksum' => (string)$run['baseline_checksum'],
            'checksum_match' => $run['checksum_match'] === true,
            'gate' => $run['checksum_match'] === true ? 'MATCH' : 'DIFFERENT',
            'jobs_done' => (int)$run['jobs_done'],
            'jobs_total' => (int)$run['jobs_total'],
            'distinct_workers' => (int)$run['distinct_workers'],
            'max_attempts' => (int)$run['max_attempts'],
            'processed_orders' => (int)$run['processed_orders'],
            'verified_count' => (int)$run['verified_count'],
            'mismatch_count' => (int)$run['mismatch_count'],
            'error_count' => (int)$run['error_count'],
            'revenue_all' => (float)$run['revenue_all'],
            'metrics_match' => $run['metrics_match'] === true,
            'duplicate_count' => count($run['duplicate_ids'] ?? []),
        ];

        $rowPass = $row['checksum_match']
            && $row['metrics_match']
            && $row['processed_orders'] === $row['dataset_size']
            && $row['jobs_done'] === $row['jobs_total']
            && $row['distinct_workers'] === $row['jobs_total']
            && $row['max_attempts'] === 1
            && $row['duplicate_count'] === 0
            && $row['speedup'] > 0
            && $row['throughput_per_sec'] > 0;
        $row['pass'] = $rowPass;
        if (!$rowPass) {
            $failures[] = "{$datasetName} P={$row['workers']}: gate/metrik gagal";
        }
        $allRows[] = $row;

        printf(
            "    P=%-2d wall=%5d ms | speedup=%5.3f | eff=%5.1f%% | %7.2f order/s | %s\n",
            $row['workers'],
            $row['duration_ms'],
            $row['speedup'],
            $row['efficiency'] * 100,
            $row['throughput_per_sec'],
            $row['gate']
        );
    }

    if (($report['all_gates_pass'] ?? false) !== true) {
        $failures[] = "{$datasetName}: all_gates_pass=false";
    }
    echo "\n";
}

$datasets = array_keys($datasetReports);
$elapsed = round(microtime(true) - $startedAt, 2);

$report = [
    'executed_at' => date('c'),
    'mode' => 'ALGORITHM',
    'latency_us' => $latencyUs,
    'datasets' => $datasets,
    'workers' => $workers,
    'baselines' => $baselines,
    'rows' => $allRows,
    'summary' => [
        'datasets' => count($datasets),
        'configurations' => count($allRows),
        'passed' => count(array_filter($allRows, static fn(array $row): bool => $row['pass'] === true)),
        'failed' => count(array_filter($allRows, static fn(array $row): bool => $row['pass'] !== true)),
        'elapsed_seconds' => $elapsed,
        'all_pass' => empty($failures),
    ],
    'failures' => $failures,
];

$jsonPath = (string)($options['json-out'] ?? ($outDir . '/benchmark_matrix.json'));
$csvPath = (string)($options['csv-out'] ?? ($outDir . '/benchmark_matrix.csv'));
$mdPath = (string)($options['md-out'] ?? ($outDir . '/benchmark_matrix.md'));
$chartPath = (string)($options['chart-out'] ?? ($outDir . '/benchmark_chart.svg'));

file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$csvHandle = fopen($csvPath, 'wb');
if ($csvHandle !== false) {
    $columns = [
        'dataset', 'dataset_size', 'workers', 'baseline_duration_ms', 'duration_ms', 'speedup',
        'efficiency', 'throughput_per_sec', 'checksum', 'baseline_checksum', 'checksum_match',
        'gate', 'jobs_done', 'jobs_total', 'distinct_workers', 'max_attempts', 'processed_orders',
        'verified_count', 'mismatch_count', 'error_count', 'revenue_all', 'metrics_match',
        'duplicate_count', 'pass',
    ];
    fputcsv($csvHandle, $columns);
    foreach ($allRows as $row) {
        $line = [];
        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            if (is_bool($value)) {
                $value = $value ? 'yes' : 'no';
            }
            $line[] = $value;
        }
        fputcsv($csvHandle, $line);
    }
    fclose($csvHandle);
}

file_put_contents($mdPath, writeMatrixMarkdown($allRows, $datasets, $baselines, $latencyUs));
file_put_contents($chartPath, matrixSvg($allRows, $datasets));

echo "===============================================================\n";
echo " RINGKASAN MATRIX\n";
echo "===============================================================\n";
printf(" Dataset : %d | Konfigurasi : %d | PASS : %d | FAIL : %d\n",
    count($datasets),
    count($allRows),
    $report['summary']['passed'],
    $report['summary']['failed']
);
printf(" Durasi total: %.2f detik\n", $elapsed);
echo " Gate       : " . ($report['summary']['all_pass'] ? 'PASS' : 'FAIL') . "\n";
echo " JSON       : {$jsonPath}\n";
echo " CSV        : {$csvPath}\n";
echo " Markdown   : {$mdPath}\n";
echo " Grafik     : {$chartPath}\n";
if (!empty($failures)) {
    echo "---------------------------------------------------------------\n";
    foreach ($failures as $failure) {
        echo " - {$failure}\n";
    }
}
echo "===============================================================\n";

exit($report['summary']['all_pass'] ? 0 : 1);
