<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if(!isAdmin()) {
    redirect('../login.php');
}

// Filter bulan & tahun
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// Statistik ringkasan bulan ini
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount),0) as total_revenue,
        COUNT(DISTINCT user_id) as unique_customers
    FROM orders
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    AND status IN ('completed','shipped')
");
$stmt->execute([$month, $year]);
$summary = $stmt->fetch();

// Semua pesanan bulan ini (semua status)
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE MONTH(created_at)=? AND YEAR(created_at)=?");
$stmt2->execute([$month, $year]);
$all_orders_month = $stmt2->fetchColumn();

// Produk terlaris
$best_selling = $pdo->query("SELECT * FROM best_selling_products LIMIT 10")->fetchAll();

// Penjualan harian bulan ini
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as sale_date, COUNT(*) as total_orders, SUM(total_amount) as total_revenue
    FROM orders
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    AND status IN ('completed','shipped')
    GROUP BY DATE(created_at)
    ORDER BY sale_date ASC
");
$stmt->execute([$month, $year]);
$daily_sales = $stmt->fetchAll();

// Statistik pelanggan
$top_customers = $pdo->query("SELECT * FROM customer_statistics LIMIT 10")->fetchAll();

// Total revenue sepanjang masa
$total_all_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status IN ('completed','shipped')")->fetchColumn();
$total_all_orders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('completed','shipped')")->fetchColumn();

// Distribusi status pesanan
$order_status_dist = $pdo->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll();

// Data chart harian (JSON)
$chart_labels   = json_encode(array_column($daily_sales, 'sale_date'));
$chart_revenue  = json_encode(array_column($daily_sales, 'total_revenue'));
$chart_orders   = json_encode(array_column($daily_sales, 'total_orders'));

// Best selling for chart
$bs_labels   = json_encode(array_column($best_selling, 'name'));
$bs_sold     = json_encode(array_column($best_selling, 'total_sold'));

// Status labels
$status_labels = json_encode(array_column($order_status_dist, 'status'));
$status_counts = json_encode(array_column($order_status_dist, 'cnt'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Analitik - Admin H-Sport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { min-height:100vh; background:#2c3e50; }
        .sidebar a { color:white; text-decoration:none; padding:10px 20px; display:block; transition:.3s; }
        .sidebar a:hover, .sidebar a.active { background:#34495e; }
        .stat-card { background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.1); }
        .section-title { border-left:4px solid #e74c3c; padding-left:12px; margin:30px 0 16px; font-weight:700; }
        .chart-box { background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:24px; }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
<div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 p-0 sidebar">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main -->
    <div class="col-md-10 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line text-danger me-2"></i>Laporan &amp; Analitik</h2>
            <!-- Filter bulan -->
            <form method="GET" class="d-flex gap-2">
                <select name="month" class="form-select form-select-sm">
                    <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?=$m?>" <?=$m==$month?'selected':''?>><?=date('F', mktime(0,0,0,$m,1))?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-select form-select-sm">
                    <?php for($y=date('Y');$y>=2024;$y--): ?>
                    <option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-danger btn-sm"><i class="fas fa-filter me-1"></i>Filter</button>
            </form>
        </div>

        <!-- Kartu statistik ringkasan -->
        <div class="row g-3 mb-2">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                    <h4 class="text-success">Rp <?=number_format($total_all_revenue,0,',','.')?></h4>
                    <small class="text-muted">Total Revenue (Semua Waktu)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-shopping-bag fa-2x text-primary mb-2"></i>
                    <h4 class="text-primary"><?=$total_all_orders?></h4>
                    <small class="text-muted">Pesanan Selesai (Semua Waktu)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-calendar-check fa-2x text-warning mb-2"></i>
                    <h4 class="text-warning">Rp <?=number_format($summary['total_revenue'],0,',','.')?></h4>
                    <small class="text-muted">Revenue Bulan Ini</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <h4 class="text-info"><?=$summary['unique_customers']?></h4>
                    <small class="text-muted">Pelanggan Aktif Bulan Ini</small>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h4><?=$summary['total_orders']?></h4>
                    <small class="text-muted">Pesanan Selesai Bulan Ini</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-list fa-2x text-secondary mb-2"></i>
                    <h4><?=$all_orders_month?></h4>
                    <small class="text-muted">Semua Pesanan Bulan Ini</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-calculator fa-2x text-danger mb-2"></i>
                    <h4>Rp <?=$summary['total_orders']>0 ? number_format($summary['total_revenue']/$summary['total_orders'],0,',','.') : 0?></h4>
                    <small class="text-muted">Rata-rata Nilai Pesanan</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                    <h4><?=count($best_selling)?> Produk</h4>
                    <small class="text-muted">Produk Aktif Terjual</small>
                </div>
            </div>
        </div>

        <!-- Charts baris 1 -->
        <div class="row g-3">
            <div class="col-md-8">
                <div class="chart-box">
                    <h6 class="section-title">Grafik Penjualan Harian — <?=date('F Y', mktime(0,0,0,$month,1,$year))?></h6>
                    <canvas id="dailyChart" height="100"></canvas>
                    <?php if(empty($daily_sales)): ?>
                    <p class="text-center text-muted mt-3">Belum ada data penjualan bulan ini.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-box">
                    <h6 class="section-title">Distribusi Status Pesanan</h6>
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts baris 2 -->
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <div class="chart-box">
                    <h6 class="section-title">Produk Terlaris</h6>
                    <canvas id="bestChart" height="160"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Tabel produk terlaris -->
                <div class="chart-box">
                    <h6 class="section-title">Detail Produk Terlaris</h6>
                    <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-dark">
                            <tr><th>#</th><th>Produk</th><th>Kategori</th><th>Terjual</th><th>Revenue</th></tr>
                        </thead>
                        <tbody>
                        <?php if(empty($best_selling)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>
                        <?php else: ?>
                        <?php foreach($best_selling as $i => $p): ?>
                            <tr>
                                <td><?=$i+1?></td>
                                <td><?=htmlspecialchars($p['name'])?></td>
                                <td><span class="badge bg-secondary"><?=htmlspecialchars($p['category'])?></span></td>
                                <td><strong><?=$p['total_sold']?></strong> pcs</td>
                                <td>Rp <?=number_format($p['total_revenue'],0,',','.')?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <h5 class="section-title mt-4">Pelanggan Terbaik</h5>
        <div class="chart-box">
        <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr><th>#</th><th>Username</th><th>Email</th><th>Total Pesanan</th><th>Total Belanja</th><th>Pesanan Terakhir</th></tr>
            </thead>
            <tbody>
            <?php if(empty($top_customers)): ?>
                <tr><td colspan="6" class="text-center text-muted">Belum ada data</td></tr>
            <?php else: ?>
            <?php foreach($top_customers as $i => $c): ?>
                <tr>
                    <td><?=$i+1?></td>
                    <td><i class="fas fa-user text-primary me-1"></i><?=htmlspecialchars($c['username'])?></td>
                    <td><?=htmlspecialchars($c['email'])?></td>
                    <td><span class="badge bg-primary"><?=$c['total_orders']?> pesanan</span></td>
                    <td><strong>Rp <?=number_format($c['total_spent'],0,',','.')?></strong></td>
                    <td><?=$c['last_order_date'] ? date('d M Y', strtotime($c['last_order_date'])) : '-'?></td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</div>
</div>

<script>
// Daily Sales Chart
const ctxDaily = document.getElementById('dailyChart').getContext('2d');
new Chart(ctxDaily, {
    type: 'line',
    data: {
        labels: <?=$chart_labels?>,
        datasets: [{
            label: 'Revenue (Rp)',
            data: <?=$chart_revenue?>,
            borderColor: '#e74c3c',
            backgroundColor: 'rgba(231,76,60,.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y'
        },{
            label: 'Jumlah Pesanan',
            data: <?=$chart_orders?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52,152,219,.1)',
            tension: 0.4,
            fill: false,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y: { type:'linear', display:true, position:'left', beginAtZero:true,
                 ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } },
            y1: { type:'linear', display:true, position:'right', beginAtZero:true,
                  grid: { drawOnChartArea:false },
                  ticks: { stepSize: 1 } }
        }
    }
});

// Status Donut Chart
const ctxStatus = document.getElementById('statusChart').getContext('2d');
new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
        labels: <?=$status_labels?>,
        datasets: [{ data: <?=$status_counts?>,
            backgroundColor: ['#f39c12','#27ae60','#3498db','#2ecc71','#e74c3c','#95a5a6'] }]
    },
    options: { responsive: true, plugins: { legend: { position:'bottom' } } }
});

// Best Selling Bar Chart
const ctxBest = document.getElementById('bestChart').getContext('2d');
new Chart(ctxBest, {
    type: 'bar',
    data: {
        labels: <?=$bs_labels?>,
        datasets: [{ label: 'Unit Terjual', data: <?=$bs_sold?>,
            backgroundColor: 'rgba(231,76,60,.7)', borderColor:'#e74c3c', borderWidth:1 }]
    },
    options: { responsive: true, plugins: { legend:{display:false} },
               scales: { y: { beginAtZero:true, ticks:{stepSize:1} } } }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
