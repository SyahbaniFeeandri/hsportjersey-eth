<?php
/**
 * CLI: Membuat batch + partisi verifikasi untuk order nyata (Mode B).
 * Issue: W6-FDR-07, W6-LUI-12
 * Pemilik: Farelly De Rossi
 *
 * Batch dibuat dari rentang ID order di tabel `orders`, lalu dipecah menjadi
 * P partisi contiguous. Worker (`workers/verify_worker.php`) mengklaim partisi
 * dan memverifikasi DB vs smart contract.
 *
 * Contoh:
 *   php workers/enqueue_batch.php --workers=4
 *   php workers/enqueue_batch.php --workers=2 --from-id=1 --to-id=40
 *   php workers/enqueue_batch.php --workers=4 --code=INT-DEMO
 */

declare(strict_types=1);

function parseEnqueueOptions(array $argv): array
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

$options = parseEnqueueOptions($argv);
$config = require dirname(__DIR__) . '/config/parallel.php';

$workers = isset($options['workers']) ? max(1, (int)$options['workers']) : 4;
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

$fromId = isset($options['from-id']) ? max(1, (int)$options['from-id']) : 0;
$toId = isset($options['to-id']) ? max(1, (int)$options['to-id']) : 0;

if ($fromId <= 0 || $toId <= 0) {
    $row = $pdo->query('SELECT MIN(id) AS min_id, MAX(id) AS max_id, COUNT(*) AS total FROM orders')->fetch();
    $fromId = $fromId > 0 ? $fromId : (int)($row['min_id'] ?? 1);
    $toId = $toId > 0 ? $toId : (int)($row['max_id'] ?? 1);
}

if ($toId < $fromId) {
    fwrite(STDERR, "[ERROR] --to-id harus >= --from-id.\n");
    exit(2);
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE id BETWEEN ? AND ?');
$countStmt->execute([$fromId, $toId]);
$totalOrders = (int)$countStmt->fetchColumn();

if ($totalOrders <= 0) {
    fwrite(STDERR, "[ERROR] Tidak ada order pada rentang {$fromId}..{$toId}.\n");
    exit(1);
}

$span = $toId - $fromId + 1;
$base = intdiv($span, $workers);
$remainder = $span % $workers;
$partitions = [];
$cursor = $fromId;
for ($i = 1; $i <= $workers; $i++) {
    $size = $base + ($i <= $remainder ? 1 : 0);
    if ($size <= 0) {
        continue;
    }
    $partitions[] = [
        'partition_no' => count($partitions) + 1,
        'range_start' => $cursor,
        'range_end' => $cursor + $size - 1,
        'size' => $size,
    ];
    $cursor += $size;
}

$batchCode = (string)($options['code'] ?? ('INT-' . date('YmdHis')));
$datasetHash = hash('sha256', "orders-live|{$fromId}|{$toId}|{$totalOrders}");

$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO job_batches
            (batch_code, mode, dataset_name, dataset_size, dataset_hash, worker_count,
             is_baseline, status, total_orders, started_at, notes)
        VALUES (?, 'INTEGRATION', 'orders-live', ?, ?, ?, 0, 'PENDING', ?, NOW(), ?)")
        ->execute([
            $batchCode,
            $totalOrders,
            $datasetHash,
            $workers,
            $totalOrders,
            json_encode([
                'mode' => 'INTEGRATION',
                'from_id' => $fromId,
                'to_id' => $toId,
                'created_by' => 'workers/enqueue_batch.php',
            ], JSON_UNESCAPED_SLASHES),
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
            (int)$config['max_attempts'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "[ERROR] Gagal membuat batch: {$e->getMessage()}\n");
    exit(1);
}

echo "Batch integrasi dibuat\n";
echo "  batch_id    : {$batchId}\n";
echo "  batch_code  : {$batchCode}\n";
echo "  rentang ID  : {$fromId}..{$toId} ({$totalOrders} order)\n";
echo "  worker      : {$workers}\n";
echo "  partisi     : " . count($partitions) . "\n";
foreach ($partitions as $partition) {
    printf("    #%d  %d..%d  (%d slot ID)\n", $partition['partition_no'], $partition['range_start'], $partition['range_end'], $partition['size']);
}
echo "\nJalankan worker:\n";
for ($w = 1; $w <= count($partitions); $w++) {
    echo "  php workers/verify_worker.php --batch={$batchId} --worker=int-{$workers}-w{$w} --loop\n";
}
