<?php
/**
 * CLI: generate dataset mock deterministik untuk baseline sequential.
 * Issue: W4-FDR-05
 *
 * Contoh:
 *   php benchmark/generate_dataset.php --sizes=100,500,1000,5000
 *   php benchmark/generate_dataset.php --size=1000 --seed=HSPORT-2026
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/DatasetGenerator.php';

use HSport\Benchmark\DatasetGenerator;

function parseOptions(array $argv): array
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

$options = parseOptions($argv);
$seed = (string)($options['seed'] ?? 'HSPORT-2026');
$outDir = (string)($options['out'] ?? __DIR__ . '/datasets');

$sizes = [];
if (isset($options['sizes'])) {
    $sizes = array_map('intval', array_filter(explode(',', (string)$options['sizes']), 'strlen'));
} elseif (isset($options['size'])) {
    $sizes = [(int)$options['size']];
} else {
    $sizes = [100];
}

if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Tidak dapat membuat folder dataset: {$outDir}\n");
    exit(1);
}

$generator = new DatasetGenerator($seed);
$written = [];

foreach ($sizes as $size) {
    if ($size <= 0) {
        fwrite(STDERR, "Ukuran dataset tidak valid: {$size}\n");
        exit(1);
    }

    $dataset = $generator->generate($size);
    $path = rtrim($outDir, '/\\') . DIRECTORY_SEPARATOR . $dataset['meta']['dataset_name'] . '.json';
    $json = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($path, $json . "\n") === false) {
        fwrite(STDERR, "Gagal menulis dataset: {$path}\n");
        exit(1);
    }

    $written[] = [
        'dataset' => $dataset['meta']['dataset_name'],
        'size' => $size,
        'hash' => $dataset['meta']['dataset_hash'],
        'verified' => $dataset['meta']['composition']['verified'],
        'mismatch' => $dataset['meta']['composition']['mismatch'],
        'error' => $dataset['meta']['composition']['error'],
        'path' => $path,
    ];

    printf("Generated %s: %d orders (V/M/E = %d/%d/%d), hash=%s\n",
        $dataset['meta']['dataset_name'],
        $size,
        $dataset['meta']['composition']['verified'],
        $dataset['meta']['composition']['mismatch'],
        $dataset['meta']['composition']['error'],
        $dataset['meta']['dataset_hash']
    );
}

$summaryPath = rtrim($outDir, '/\\') . DIRECTORY_SEPARATOR . 'datasets_index.json';
file_put_contents($summaryPath, json_encode($written, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
printf("Index dataset: %s\n", $summaryPath);
