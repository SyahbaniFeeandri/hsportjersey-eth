<?php
/**
 * Performance Dashboard (awal) - H-Sport Financial dApp
 * Issue: W6-IHS-07
 * Pemilik: Ihsanul
 *
 * Membaca view `v_benchmark_summary` (gabungan job_batches + baseline) untuk
 * menampilkan throughput, speedup, efficiency, dan correctness gate hasil
 * benchmark sequential vs parallel.
 */

require_once '../config/database.php';
require_once '../config/functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$rows = [];
$viewError = null;
$stats = [
    'batches' => 0,
    'best_speedup' => null,
    'avg_efficiency' => null,
    'correctness_pass' => 0,
    'correctness_total' => 0,
];

try {
    $rows = $pdo->query("SELECT * FROM v_benchmark_summary ORDER BY id DESC LIMIT 60")->fetchAll();

    $doneRows = array_values(array_filter($rows, static fn(array $row): bool => $row['status'] === 'DONE'));
    $stats['batches'] = count($doneRows);

    foreach ($doneRows as $row) {
        if ($row['speedup'] !== null && ($stats['best_speedup'] === null || (float)$row['speedup'] > $stats['best_speedup'])) {
            $stats['best_speedup'] = (float)$row['speedup'];
        }
        if ((int)$row['worker_count'] > 1 && $row['efficiency'] !== null) {
            $stats['avg_efficiency'] = $stats['avg_efficiency'] === null
                ? (float)$row['efficiency']
                : ($stats['avg_efficiency'] + (float)$row['efficiency']) / 2;
        }
        if ($row['correctness_gate'] !== null) {
            $stats['correctness_total']++;
            if ($row['correctness_gate'] === 'MATCH') {
                $stats['correctness_pass']++;
            }
        }
    }
} catch (Throwable $e) {
    $viewError = $e->getMessage();
}

$chartRows = array_values(array_filter($rows, static fn(array $row): bool => $row['status'] === 'DONE' && $row['speedup'] !== null));
$chartRows = array_slice($chartRows, 0, 12);
$maxChartSpeedup = 1.0;
foreach ($chartRows as $row) {
    $maxChartSpeedup = max($maxChartSpeedup, (float)$row['speedup']);
}

$formatNumber = static fn($value, int $decimals = 0): string => $value === null
    ? '-'
    : number_format((float)$value, $decimals, ',', '.');
$correctnessBadge = static function (?string $gate): string {
    if ($gate === 'MATCH') {
        return '<span class="badge bg-success">MATCH</span>';
    }
    if ($gate === 'DIFFERENT') {
        return '<span class="badge bg-danger">DIFFERENT</span>';
    }

    return '<span class="badge bg-secondary">-</span>';
};
$statusBadge = static function (string $status): string {
    $map = ['DONE' => 'success', 'RUNNING' => 'info', 'PENDING' => 'warning', 'FAILED' => 'danger', 'CANCELLED' => 'secondary'];

    return '<span class="badge bg-' . ($map[$status] ?? 'secondary') . '">' . htmlspecialchars($status) . '</span>';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Dashboard - H-Sport Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar a { color: #fff; text-decoration: none; padding: 10px 20px; display: block; transition: .3s; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; }
        .stat-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,.1); margin-bottom: 20px; }
        .bar-track { background: #e9ecef; border-radius: 6px; height: 18px; overflow: hidden; }
        .bar-fill { background: linear-gradient(90deg, #0d6efd, #20c997); height: 100%; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 p-0 sidebar">
            <div class="text-center py-4">
                <i class="fas fa-tshirt fa-3x text-white"></i>
                <h5 class="text-white mt-2">Admin Panel</h5>
            </div>
            <a href="dashboard.php"><i class="fas fa-dashboard me-2"></i> Dashboard</a>
            <a href="products.php"><i class="fas fa-box me-2"></i> Kelola Produk</a>
            <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan</a>
            <a href="customers.php"><i class="fas fa-users me-2"></i> Pelanggan</a>
            <a href="performance.php" class="active"><i class="fas fa-gauge-high me-2"></i> Performance</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Performance Dashboard</h2>
                    <p class="text-muted mb-0">Sequential vs parallel verification — sumber: <code>v_benchmark_summary</code></p>
                </div>
                <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                    <i class="fas fa-rotate me-1"></i>Refresh
                </button>
            </div>

            <?php if ($viewError): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    View benchmark belum tersedia. Jalankan <code>migration_parallel.sql</code>.
                    <div class="small mt-1"><?php echo htmlspecialchars($viewError); ?></div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-layer-group fa-2x text-primary"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['batches']; ?></h3>
                        <p class="text-muted mb-0">Batch DONE</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-rocket fa-2x text-success"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['best_speedup'] === null ? '-' : number_format($stats['best_speedup'], 2, ',', '.'); ?>x</h3>
                        <p class="text-muted mb-0">Speedup terbaik</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-gauge fa-2x text-info"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['avg_efficiency'] === null ? '-' : number_format($stats['avg_efficiency'] * 100, 1, ',', '.'); ?>%</h3>
                        <p class="text-muted mb-0">Rata-rata efficiency (P&gt;1)</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-shield-halved fa-2x text-warning"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['correctness_pass']; ?>/<?php echo $stats['correctness_total']; ?></h3>
                        <p class="text-muted mb-0">Correctness gate MATCH</p>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-simple me-2 text-primary"></i>Speedup per Batch</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($chartRows)): ?>
                        <p class="text-muted mb-0">Belum ada batch dengan metrik speedup. Jalankan benchmark paralel terlebih dahulu.</p>
                    <?php else: ?>
                        <?php foreach ($chartRows as $row): ?>
                            <?php $width = min(100, max(2, ((float)$row['speedup'] / $maxChartSpeedup) * 100)); ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>
                                        <strong><?php echo htmlspecialchars($row['dataset_name']); ?></strong>
                                        <span class="text-muted">(<?php echo (int)$row['dataset_size']; ?> order)</span>
                                        — P=<?php echo (int)$row['worker_count']; ?>
                                        <?php if ((int)$row['is_baseline'] === 1): ?><span class="badge bg-dark">baseline</span><?php endif; ?>
                                    </span>
                                    <span><?php echo number_format((float)$row['speedup'], 2, ',', '.'); ?>x</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?php echo $width; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2 text-primary"></i>Riwayat Benchmark</h5>
                    <span class="small text-muted">60 batch terbaru — speedup dihitung terhadap baseline terbaru per dataset</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch</th>
                                    <th>Mode</th>
                                    <th>Dataset</th>
                                    <th class="text-center">P</th>
                                    <th>Status</th>
                                    <th class="text-end">Order</th>
                                    <th class="text-end">V/M/E</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">AOV</th>
                                    <th class="text-end">Durasi</th>
                                    <th class="text-end">Throughput</th>
                                    <th class="text-end">Speedup</th>
                                    <th class="text-end">Efficiency</th>
                                    <th>Gate</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="14" class="text-center text-muted py-4">Belum ada data benchmark.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td class="mono"><?php echo htmlspecialchars($row['batch_code']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['mode']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['dataset_name']); ?> <span class="text-muted">(<?php echo (int)$row['dataset_size']; ?>)</span></td>
                                        <td class="text-center"><?php echo (int)$row['worker_count']; ?></td>
                                        <td><?php echo $statusBadge((string)$row['status']); ?></td>
                                        <td class="text-end"><?php echo (int)$row['total_orders']; ?></td>
                                        <td class="text-end"><?php echo (int)$row['verified_count']; ?>/<?php echo (int)$row['mismatch_count']; ?>/<?php echo (int)$row['error_count']; ?></td>
                                        <td class="text-end">Rp <?php echo $formatNumber($row['revenue_all']); ?></td>
                                        <td class="text-end">Rp <?php echo $formatNumber($row['aov'], 2); ?></td>
                                        <td class="text-end"><?php echo (int)$row['duration_ms']; ?> ms</td>
                                        <td class="text-end"><?php echo $row['throughput_per_sec'] === null ? '-' : number_format((float)$row['throughput_per_sec'], 1, ',', '.'); ?>/s</td>
                                        <td class="text-end"><?php echo $row['speedup'] === null ? '-' : number_format((float)$row['speedup'], 2, ',', '.'); ?>x</td>
                                        <td class="text-end"><?php echo $row['efficiency'] === null ? '-' : number_format((float)$row['efficiency'] * 100, 1, ',', '.'); ?>%</td>
                                        <td><?php echo $correctnessBadge($row['correctness_gate']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white small text-muted">
                    V/M/E = VERIFIED / MISMATCH / ERROR. Gate MATCH berarti checksum batch sama dengan baseline sequential dataset yang sama.
                    Data berasal dari <code>benchmark/parallel_runner.php</code> dan <code>workers/verify_worker.php</code>.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
