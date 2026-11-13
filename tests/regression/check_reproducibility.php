<?php
/**
 * Pemeriksa reproducibility benchmark matrix.
 * Issue: W7-FRL-11
 * Pemilik: Farelly De Rossi
 * Validator: Kenjiro B.H.G.
 *
 * Membandingkan dua hasil `benchmark/run_matrix.php` (run utama vs run ulang di
 * workspace/environment kedua) lalu memastikan:
 * - daftar dataset dan jumlah konfigurasi sama;
 * - dataset_hash identik (generator deterministik);
 * - checksum tiap konfigurasi identik (hasil pipeline sama);
 * - seluruh gate MATCH;
 * - speedup & wall-clock tidak menyimpang melebihi toleransi (default 20%).
 *
 * Jalankan:
 *   php tests/regression/check_reproducibility.php
 *   php tests/regression/check_reproducibility.php --a=path/a.json --b=path/b.json --tolerance=0.25
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$evidenceDir = $root . '/docs/week7/evidence';

function reproOptions(array $argv): array
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

function reproLoad(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("File tidak ditemukan: {$path}");
    }
    $decoded = json_decode((string)file_get_contents($path), true);
    if (!is_array($decoded) || !isset($decoded['rows'])) {
        throw new RuntimeException("JSON matrix tidak valid: {$path}");
    }

    return $decoded;
}

/**
 * @return array<string,array<string,mixed>>
 */
function reproIndexRows(array $matrix): array
{
    $indexed = [];
    foreach ($matrix['rows'] as $row) {
        $indexed[$row['dataset'] . '|P' . $row['workers']] = $row;
    }

    return $indexed;
}

$options = reproOptions($argv);
$pathA = (string)($options['a'] ?? ($evidenceDir . '/benchmark_matrix.json'));
$pathB = (string)($options['b'] ?? ($evidenceDir . '/repro_matrix.json'));
$tolerance = isset($options['tolerance']) ? max(0.0, (float)$options['tolerance']) : 0.20;
$jsonOut = (string)($options['json-out'] ?? ($evidenceDir . '/reproducibility_check.json'));
$mdOut = (string)($options['md-out'] ?? ($evidenceDir . '/reproducibility_check.md'));

echo "===============================================================\n";
echo "   H-SPORT — REPRODUCIBILITY CHECK (M7)\n";
echo "   Run A : {$pathA}\n";
echo "   Run B : {$pathB}\n";
echo "   Toleransi metrik: " . number_format($tolerance * 100, 0, ',', '.') . "%\n";
echo "===============================================================\n\n";

$errors = [];
$warnings = [];
$maxSpeedupDelta = 0.0;
$maxDurationDelta = 0.0;
$perConfiguration = [];

try {
    $matrixA = reproLoad($pathA);
    $matrixB = reproLoad($pathB);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] {$e->getMessage()}\n");
    exit(1);
}

$datasetsA = array_map('strval', $matrixA['datasets'] ?? []);
$datasetsB = array_map('strval', $matrixB['datasets'] ?? []);
if ($datasetsA !== $datasetsB) {
    $errors[] = 'Daftar dataset berbeda: ' . implode(',', $datasetsA) . ' vs ' . implode(',', $datasetsB);
}

$workersA = array_map('intval', $matrixA['workers'] ?? []);
$workersB = array_map('intval', $matrixB['workers'] ?? []);
if ($workersA !== $workersB) {
    $errors[] = 'Daftar worker berbeda: ' . implode(',', $workersA) . ' vs ' . implode(',', $workersB);
}

foreach ($datasetsA as $dataset) {
    $hashA = '';
    $hashB = '';
    foreach ($matrixA['rows'] as $row) {
        if ((string)$row['dataset'] === $dataset) {
            $hashA = (string)($row['dataset_hash'] ?? '');
            break;
        }
    }
    foreach ($matrixB['rows'] as $row) {
        if ((string)$row['dataset'] === $dataset) {
            $hashB = (string)($row['dataset_hash'] ?? '');
            break;
        }
    }
    if ($hashA === '' || $hashA !== $hashB) {
        $errors[] = "dataset_hash {$dataset} berbeda: " . substr($hashA, 0, 16) . ' vs ' . substr($hashB, 0, 16);
    }
    $checksumA = (string)($matrixA['baselines'][$dataset]['checksum'] ?? '');
    $checksumB = (string)($matrixB['baselines'][$dataset]['checksum'] ?? '');
    if ($checksumA === '' || $checksumA !== $checksumB) {
        $errors[] = "checksum baseline {$dataset} berbeda";
    }
}

$rowsA = reproIndexRows($matrixA);
$rowsB = reproIndexRows($matrixB);
if (array_keys($rowsA) !== array_keys($rowsB)) {
    $errors[] = 'Himpunan konfigurasi berbeda (' . count($rowsA) . ' vs ' . count($rowsB) . ')';
}

foreach ($rowsA as $key => $rowA) {
    if (!isset($rowsB[$key])) {
        continue;
    }
    $rowB = $rowsB[$key];

    $checksumMatch = (string)$rowA['checksum'] === (string)$rowB['checksum'];
    if (!$checksumMatch) {
        $errors[] = "{$key}: checksum berbeda";
    }
    if ((int)$rowA['processed_orders'] !== (int)$rowB['processed_orders']) {
        $errors[] = "{$key}: processed_orders berbeda";
    }
    if (($rowA['checksum_match'] ?? false) !== true || ($rowB['checksum_match'] ?? false) !== true) {
        $errors[] = "{$key}: salah satu run tidak MATCH";
    }

    $speedupDelta = abs((float)$rowA['speedup'] - (float)$rowB['speedup']) / max(0.0001, (float)$rowA['speedup']);
    $durationDelta = abs((int)$rowA['duration_ms'] - (int)$rowB['duration_ms']) / max(1, (int)$rowA['duration_ms']);
    $maxSpeedupDelta = max($maxSpeedupDelta, $speedupDelta);
    $maxDurationDelta = max($maxDurationDelta, $durationDelta);

    if ($speedupDelta > $tolerance) {
        $warnings[] = "{$key}: speedup menyimpang " . number_format($speedupDelta * 100, 1, ',', '.') . '%';
    }
    if ($durationDelta > $tolerance) {
        $warnings[] = "{$key}: wall-clock menyimpang " . number_format($durationDelta * 100, 1, ',', '.') . '%';
    }

    $perConfiguration[] = [
        'configuration' => $key,
        'checksum_match' => $checksumMatch,
        'processed_orders_a' => (int)$rowA['processed_orders'],
        'processed_orders_b' => (int)$rowB['processed_orders'],
        'speedup_a' => round((float)$rowA['speedup'], 4),
        'speedup_b' => round((float)$rowB['speedup'], 4),
        'speedup_delta_pct' => round($speedupDelta * 100, 2),
        'duration_ms_a' => (int)$rowA['duration_ms'],
        'duration_ms_b' => (int)$rowB['duration_ms'],
        'duration_delta_pct' => round($durationDelta * 100, 2),
    ];
}

$pass = empty($errors);
$evidence = [
    'executed_at' => date('c'),
    'run_a' => $pathA,
    'run_b' => $pathB,
    'tolerance_pct' => $tolerance * 100,
    'datasets' => $datasetsA,
    'workers' => $workersA,
    'configurations' => count($perConfiguration),
    'checksum_all_match' => empty(array_filter($perConfiguration, static fn(array $row): bool => $row['checksum_match'] !== true)),
    'max_speedup_delta_pct' => round($maxSpeedupDelta * 100, 2),
    'max_duration_delta_pct' => round($maxDurationDelta * 100, 2),
    'errors' => $errors,
    'warnings' => $warnings,
    'pass' => $pass,
    'rows' => $perConfiguration,
];
@mkdir(dirname($jsonOut), 0777, true);
file_put_contents($jsonOut, json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$md = [];
$md[] = '# Reproducibility Check — Benchmark Matrix';
$md[] = '';
$md[] = '**Dibuat:** ' . date('Y-m-d H:i:s');
$md[] = '**Run A:** `' . basename($pathA) . '` · **Run B:** `' . basename($pathB) . '`';
$md[] = '**Toleransi metrik:** ' . number_format($tolerance * 100, 0, ',', '.') . '%';
$md[] = '**Hasil:** ' . ($pass ? 'PASS' : 'FAIL');
$md[] = '';
$md[] = '| Konfigurasi | Checksum | Order A/B | Speedup A | Speedup B | Δ speedup | Δ wall |';
$md[] = '|---|---|---:|---:|---:|---:|---:|';
foreach ($perConfiguration as $row) {
    $md[] = '| ' . $row['configuration']
        . ' | ' . ($row['checksum_match'] ? 'MATCH' : 'DIFFERENT')
        . ' | ' . $row['processed_orders_a'] . '/' . $row['processed_orders_b']
        . ' | ' . number_format($row['speedup_a'], 3, ',', '.')
        . ' | ' . number_format($row['speedup_b'], 3, ',', '.')
        . ' | ' . number_format($row['speedup_delta_pct'], 2, ',', '.') . '%'
        . ' | ' . number_format($row['duration_delta_pct'], 2, ',', '.') . '%'
        . ' |';
}
$md[] = '';
$md[] = 'Delta maksimum: speedup ' . number_format($evidence['max_speedup_delta_pct'], 2, ',', '.')
    . '%, wall-clock ' . number_format($evidence['max_duration_delta_pct'], 2, ',', '.') . '%.';
if (!empty($errors)) {
    $md[] = '';
    $md[] = '**Error:**';
    foreach ($errors as $error) {
        $md[] = '- ' . $error;
    }
}
if (!empty($warnings)) {
    $md[] = '';
    $md[] = '**Peringatan toleransi:**';
    foreach ($warnings as $warning) {
        $md[] = '- ' . $warning;
    }
}
@mkdir(dirname($mdOut), 0777, true);
file_put_contents($mdOut, implode("\n", $md) . "\n");

echo ' Checksum identik   : ' . ($evidence['checksum_all_match'] ? 'YA' : 'TIDAK') . "\n";
echo ' Konfigurasi        : ' . $evidence['configurations'] . "\n";
echo ' Delta speedup maks : ' . number_format($evidence['max_speedup_delta_pct'], 2, ',', '.') . "%\n";
echo ' Delta wall maks    : ' . number_format($evidence['max_duration_delta_pct'], 2, ',', '.') . "%\n";
echo ' Warning toleransi  : ' . count($warnings) . "\n";
echo ' Error              : ' . count($errors) . "\n";
echo "===============================================================\n";
echo '   HASIL: ' . ($pass ? 'PASS — benchmark reproducible' : 'FAIL') . "\n";
echo "===============================================================\n";
foreach ($errors as $error) {
    echo " - {$error}\n";
}

exit($pass ? 0 : 1);
