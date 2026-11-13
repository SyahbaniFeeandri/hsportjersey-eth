<?php
/**
 * CLI: Reducer batch verifikasi (ALGORITHM maupun INTEGRATION).
 * Issue: W6-FDR-09, W6-FDR-10, W6-LUI-12
 * Pemilik: Farelly De Rossi
 *
 * Membaca seluruh checkpoint batch, menggabungkan metrik, menghitung ulang
 * checksum, membandingkan dengan baseline sequential (bila ada), lalu
 * memperbarui `job_batches`.
 *
 * Contoh:
 *   php workers/reduce_batch.php --batch=12
 *   php workers/reduce_batch.php --batch=12 --json-out=hasil.json
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/benchmark/lib/Reducer.php';

use HSport\Benchmark\Reducer;

function parseReduceOptions(array $argv): array
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

$options = parseReduceOptions($argv);
$batchId = isset($options['batch']) ? (int)$options['batch'] : 0;
if ($batchId <= 0) {
    fwrite(STDERR, "Parameter --batch=<id> wajib diisi.\n");
    exit(2);
}

$config = require dirname(__DIR__) . '/config/parallel.php';
$db = $config['db'];
$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $summary = (new Reducer($pdo))->reduce($batchId);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] Reduce gagal: {$e->getMessage()}\n");
    exit(1);
}

echo "===============================================================\n";
echo " REDUCER - BATCH #{$summary['batch_id']} ({$summary['batch_code']})\n";
echo "===============================================================\n";
printf(" Mode             : %s / worker_count=%d\n", $summary['mode'], $summary['worker_count']);
printf(" Dataset          : %s (%d order)\n", $summary['dataset_name'], $summary['dataset_size']);
printf(" Status           : %s (complete=%s)\n", $summary['status'], $summary['complete'] ? 'yes' : 'no');
printf(" Processed        : %d / %d\n", $summary['processed_orders'], $summary['expected_orders']);
printf(" VERIFIED         : %d\n", $summary['verified_count']);
printf(" MISMATCH         : %d\n", $summary['mismatch_count']);
printf(" ERROR            : %d\n", $summary['error_count']);
printf(" SKIPPED          : %d\n", $summary['skipped_count']);
printf(" Paid / Unpaid    : %d / %d\n", $summary['paid_count'], $summary['unpaid_count']);
printf(" Revenue all      : Rp %s\n", number_format((float)$summary['revenue_all'], 0, ',', '.'));
printf(" Revenue verified : Rp %s\n", number_format((float)$summary['revenue_verified'], 0, ',', '.'));
printf(" AOV              : Rp %s\n", number_format((float)$summary['aov'], 2, ',', '.'));
printf(" Checksum         : %s\n", $summary['checksum'] ?? '-');
printf(" Baseline         : %s\n", $summary['baseline_checksum'] ?? '-');
printf(" Correctness gate : %s\n", $summary['checksum_match'] === null ? 'NO-BASELINE' : ($summary['checksum_match'] ? 'MATCH' : 'DIFFERENT'));
printf(" Metrics konsisten: %s\n", $summary['metrics_consistent'] ? 'yes' : 'no');
printf(" Duplikat order   : %d\n", count($summary['duplicate_ids']));
printf(" Duration         : %d ms\n", $summary['duration_ms']);
echo "---------------------------------------------------------------\n";
foreach ($summary['partitions'] as $partition) {
    printf(
        " Partisi %-2d %5d..%-5d %-6s worker=%-14s attempts=%d\n",
        $partition['partition_no'],
        $partition['range_start'],
        $partition['range_end'],
        $partition['status'],
        (string)($partition['worker_id'] ?? '-'),
        $partition['attempts']
    );
}

if (isset($options['json-out'])) {
    $json = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents((string)$options['json-out'], $json . "\n") === false) {
        fwrite(STDERR, "[WARN] Gagal menulis JSON report.\n");
    } else {
        echo " JSON report : " . (string)$options['json-out'] . "\n";
    }
}

exit($summary['complete'] ? 0 : 1);
