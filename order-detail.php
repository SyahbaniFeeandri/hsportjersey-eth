<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$order_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    redirect('orders.php');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Color mapping for display
$colorMap = [
    'Merah' => '#FF0000',
    'Biru' => '#0000FF',
    'Hijau' => '#00FF00',
    'Kuning' => '#FFFF00',
    'Hitam' => '#000000',
    'Putih' => '#FFFFFF',
    'Abu-abu' => '#808080',
    'Orange' => '#FFA500',
    'Ungu' => '#800080',
    'Pink' => '#FF69B4'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - H-Sport Jersey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .color-preview {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .btn-payment {
            background-color: #004b9c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-payment:hover {
            background-color: #003366;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-receipt me-2" style="color: #004b9c;"></i>
                Detail Pesanan
            </h2>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="info-box">
                    <h5 class="mb-3">
                        <i class="fas fa-box me-2" style="color: #004b9c;"></i>
                        Produk yang Dipesan
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Detail</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-tag"></i> Ukuran: <strong><?php echo $item['size']; ?></strong>
                                        </small>
                                        <?php if($item['color']): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-palette"></i> Warna: 
                                            <span class="color-preview" style="background-color: <?php 
                                                $color = $item['color'];
                                                echo isset($colorMap[$color]) ? $colorMap[$color] : '#CCCCCC'; 
                                            ?>;"></span>
                                            <strong><?php echo htmlspecialchars($item['color']); ?></strong>
                                        </small>
                                        <?php endif; ?>
                                        <?php if($item['custom_text']): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-text-height"></i> Teks: <strong><?php echo htmlspecialchars($item['custom_text']); ?></strong>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['quantity']; ?> pcs</td>
                                    <td class="text-success fw-bold">
                                        Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Keseluruhan:</td>
                                    <td class="fw-bold">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="info-box">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle me-2" style="color: #004b9c;"></i>
                        Informasi Pesanan
                    </h5>
                    <hr>
                    <p><strong>No. Pesanan:</strong><br> <?php echo $order['order_number']; ?></p>
                    <p><strong>Tanggal Pemesanan:</strong><br> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status Pesanan:</strong><br>
                        <span class="status-badge bg-<?php 
                            echo $order['status'] == 'pending' ? 'warning' : 
                                ($order['status'] == 'paid' ? 'info' : 
                                ($order['status'] == 'production' ? 'primary' : 
                                ($order['status'] == 'shipped' ? 'success' : 'secondary'))); 
                        ?> text-white">
                            <?php 
                            $status_map = [
                                'pending' => 'Menunggu Pembayaran',
                                'paid' => 'Dibayar',
                                'production' => 'Dalam Produksi',
                                'shipped' => 'Dikirim',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan'
                            ];
                            echo $status_map[$order['status']];
                            ?>
                        </span>
                    </p>
                    <p><strong>Status Pembayaran:</strong><br>
                        <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'danger'; ?>">
                            <?php echo $order['payment_status'] == 'paid' ? '✅ Lunas' : '❌ Belum Dibayar'; ?>
                        </span>
                    </p>
                </div>
                
                <div class="info-box">
                    <h5 class="mb-3">
                        <i class="fas fa-truck me-2" style="color: #004b9c;"></i>
                        Informasi Pengiriman
                    </h5>
                    <hr>
                    <p><strong>Metode Kirim:</strong><br> <?php echo $order['shipping_method']; ?></p>
                    <p><strong>Alamat Pengiriman:</strong><br> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <?php if($order['notes']): ?>
                    <p><strong>Catatan:</strong><br> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if($order['payment_status'] == 'unpaid' && $order['status'] == 'pending'): ?>
                    <?php if(($order['payment_type'] ?? '') === 'crypto'): ?>
                    <div class="info-box" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: white; border: 1px solid rgba(129, 140, 248, 0.3);">
                        <h5 class="mb-3">
                            <i class="fab fa-ethereum me-2 text-warning"></i>
                            Pembayaran Crypto (ETH)
                        </h5>
                        <hr style="background-color: rgba(255,255,255,0.2);">
                        <p class="mb-1"><small class="text-light">Metode: <strong>Ethereum via MetaMask (Smart Contract)</strong></small></p>
                        <p><strong>Total Pembayaran:</strong><br> 
                            <span style="font-size: 1.3rem; font-weight: bold; color: #38bdf8;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                            <br><small class="text-warning">≈ <?php echo number_format($order['total_amount'] / 10000000, 4); ?> ETH</small>
                        </p>
                        <a href="crypto-payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-warning w-100 fw-bold py-2">
                            <i class="fab fa-ethereum me-2"></i> Lanjutkan Pembayaran ETH
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="info-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="mb-3">
                            <i class="fas fa-credit-card me-2"></i>
                            Informasi Pembayaran
                        </h5>
                        <hr style="background-color: rgba(255,255,255,0.3);">
                        <p><strong>Total Pembayaran:</strong><br> 
                            <span style="font-size: 1.3rem; font-weight: bold;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                        </p>
                        <a href="generate-qr.php?order_id=<?php echo $order['id']; ?>" class="btn btn-light w-100 fw-bold mb-2 text-primary">
                            <i class="fas fa-qrcode me-2"></i> Bayar dengan QRIS
                        </a>
                        <p class="small text-white-50 mb-0"><i class="fas fa-info-circle me-1"></i> Scan QRIS untuk verifikasi instan.</p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>