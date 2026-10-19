<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if(!isAdmin()) {
    redirect('../login.php');
}

// Get statistics
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - H-Sport</title>
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
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
                <a href="dashboard.php"><i class="fas fa-dashboard me-2"></i> Dashboard</a>
                <a href="products.php"><i class="fas fa-box me-2"></i> Kelola Produk</a>
                <a href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan</a>
                <a href="customers.php"><i class="fas fa-users me-2"></i> Pelanggan</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard Admin</h2>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-box fa-3x text-primary"></i>
                            <h3><?php echo $total_products; ?></h3>
                            <p>Total Produk</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-shopping-cart fa-3x text-success"></i>
                            <h3><?php echo $total_orders; ?></h3>
                            <p>Total Pesanan</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-users fa-3x text-info"></i>
                            <h3><?php echo $total_customers; ?></h3>
                            <p>Pelanggan</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <i class="fas fa-clock fa-3x text-warning"></i>
                            <h3><?php echo $pending_orders; ?></h3>
                            <p>Pesanan Pending</p>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Pesanan Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                                while($order = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><?php echo $order['order_number']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                    <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] == 'pending' ? 'warning' : 
                                                ($order['status'] == 'paid' ? 'info' : 
                                                ($order['status'] == 'production' ? 'primary' : 
                                                ($order['status'] == 'shipped' ? 'success' : 'secondary'))); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Detail</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>