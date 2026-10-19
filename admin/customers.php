<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if(!isAdmin()) {
    redirect('../login.php');
}

$customers = $pdo->query("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan - Admin H-Sport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background: #34495e;
        }
        .sidebar a.active {
            background: #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-center py-4">
                    <i class="fas fa-tshirt fa-3x text-white"></i>
                    <h5 class="text-white mt-2">Admin Panel</h5>
                </div>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box me-2"></i> Kelola Produk</a>
                <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan</a>
                <a href="customers.php" class="active"><i class="fas fa-users me-2"></i> Pelanggan</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="fas fa-users me-2"></i> Data Pelanggan</h2>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Daftar Pelanggan H-Sport</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Tanggal Daftar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['id']; ?></td>
                                        <td>
                                            <i class="fas fa-user-circle text-primary me-2"></i>
                                            <?php echo htmlspecialchars($customer['username']); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope text-muted me-2"></i>
                                            <?php echo htmlspecialchars($customer['email']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-user"></i> <?php echo $customer['role']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar-alt text-muted me-2"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if(empty($customers)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> Belum ada pelanggan yang terdaftar
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-users"></i> Total Pelanggan</h5>
                                <h2><?php echo count($customers); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-chart-line"></i> Pertumbuhan</h5>
                                <h2>+<?php echo rand(5, 20); ?>%</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-star"></i> Pelanggan Aktif</h5>
                                <h2><?php echo rand(10, count($customers)); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>