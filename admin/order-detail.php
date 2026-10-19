

<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if(!isAdmin()) {
    redirect('../login.php');
}

$order_id = isset($_GET['id']) ? $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if(!$order) {
    redirect('orders.php');
}

// Handle cancel from detail page
if(isset($_GET['action']) && $_GET['action'] == 'cancel') {
    if($order['status'] == 'pending' || $order['payment_status'] == 'unpaid') {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'unpaid' WHERE id = ?");
        $stmt->execute([$order_id]);
        $_SESSION['message'] = 'Pesanan berhasil dibatalkan!';
        $_SESSION['message_type'] = 'success';
        redirect('order-detail.php?id=' . $order_id);
    }
}

// Handle delete from detail page
if(isset($_GET['action']) && $_GET['action'] == 'delete') {
    if($order['status'] == 'cancelled' || $order['status'] == 'completed') {
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $_SESSION['message'] = 'Pesanan berhasil dihapus permanen!';
        $_SESSION['message_type'] = 'success';
        redirect('orders.php');
    }
}

// Handle update status & tracking number & blockchain receipt
if(isset($_POST['update_order'])) {
    $new_status = $_POST['new_status'];
    $tracking   = trim($_POST['tracking_number'] ?? '');
    $tx_hash    = trim($_POST['tx_hash'] ?? '');
    $block_num  = trim($_POST['block_number'] ?? '');
    $allowed    = ['pending','paid','production','shipped','completed','cancelled'];
    if(in_array($new_status, $allowed)) {
        if(!empty($tx_hash)) {
            $stmt = $pdo->prepare("UPDATE orders SET status=?, tracking_number=?, tx_hash=?, block_number=?, payment_status='paid', paid_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$new_status, $tracking ?: null, $tx_hash, $block_num ?: null, $order_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET status=?, tracking_number=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$new_status, $tracking ?: null, $order_id]);
        }

        // Refresh order
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        $_SESSION['message'] = 'Status dan data pesanan berhasil diperbarui!';
        $_SESSION['message_type'] = 'success';
        redirect('order-detail.php?id=' . $order_id);
    }
}

$stmt = $pdo->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$order['user_id']]);
$customer = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Admin H-Sport</title>
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
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .alert-custom {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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
                <a href="customers.php"><i class="fas fa-users me-2"></i> Pelanggan</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Alert Messages -->
                <?php if(isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php 
                            echo $_SESSION['message'];
                            unset($_SESSION['message']);
                            unset($_SESSION['message_type']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-eye me-2"></i> Detail Pesanan</h2>
                    <div>
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <?php if($order['status'] == 'cancelled'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-ban me-2"></i>
                        <strong>Pesanan ini telah dibatalkan!</strong> Pesanan tidak dapat diproses lebih lanjut.
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box">
                            <h5><i class="fas fa-user me-2"></i> Informasi Pelanggan</h5>
                            <hr>
                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($customer['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                            <p><strong>ID Pelanggan:</strong> #<?php echo $customer['id']; ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="info-box">
                            <h5><i class="fas fa-truck me-2"></i> Informasi Pengiriman</h5>
                            <hr>
                            <p><strong>Metode Kirim:</strong> <?php echo $order['shipping_method']; ?></p>
                            <p><strong>Alamat:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            <?php if($order['tracking_number']): ?>
                                <p><strong>No. Resi:</strong> <?php echo $order['tracking_number']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="info-box">
                    <h5><i class="fas fa-info-circle me-2"></i> Informasi Pesanan & Pembayaran</h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <strong>No. Pesanan:</strong><br>
                            <?php echo $order['order_number']; ?>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong>Tanggal:</strong><br>
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong>Status Pesanan:</strong><br>
                            <span class="badge bg-<?php 
                                echo $order['status'] == 'pending' ? 'warning' : 
                                    ($order['status'] == 'paid' ? 'info' : 
                                    ($order['status'] == 'production' ? 'primary' : 
                                    ($order['status'] == 'shipped' ? 'success' : 
                                    ($order['status'] == 'cancelled' ? 'danger' : 'secondary')))); 
                            ?>">
                                <?php 
                                $status_map = [
                                    'pending' => 'Menunggu Pembayaran',
                                    'paid' => 'Dibayar',
                                    'production' => 'Dalam Produksi',
                                    'shipped' => 'Dikirim',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                echo $status_map[$order['status']] ?? ucfirst($order['status']);
                                ?>
                            </span>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong>Status Pembayaran:</strong><br>
                            <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'danger'; ?>">
                                <?php echo $order['payment_status'] == 'paid' ? 'Lunas' : 'Belum Dibayar'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if(!empty($order['tx_hash']) || !empty($order['wallet_address'])): ?>
                    <hr class="my-3">
                    <h6 class="text-primary"><i class="fas fa-cubes me-1"></i> Data Bukti Transaksi Blockchain (Receipt)</h6>
                    <div class="row bg-light p-2 rounded">
                        <div class="col-md-6 mb-2">
                            <small class="text-muted d-block">Transaction Hash (Tx):</small>
                            <code class="text-break user-select-all"><?php echo htmlspecialchars($order['tx_hash'] ?? '-'); ?></code>
                        </div>
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Block Number:</small>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($order['block_number'] ?? '-'); ?></span>
                        </div>
                        <div class="col-md-3 mb-2">
                            <small class="text-muted d-block">Metode / Wallet:</small>
                            <small class="badge bg-dark"><?php echo strtoupper($order['payment_type'] ?? 'CRYPTO'); ?></small><br>
                            <code class="text-break" style="font-size: 11px;"><?php echo htmlspecialchars($order['wallet_address'] ?? ''); ?></code>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i> Produk yang Dipesan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th>Ukuran</th>
                                        <th>Custom Text</th>
                                        <th>Jumlah</th>
                                        <th>Harga</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($items as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo $item['size']; ?></span></td>
                                        <td><?php echo $item['custom_text'] ?: '<em class="text-muted">-</em>'; ?></td>
                                        <td><?php echo $item['quantity']; ?> </td>
                                        <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> </td>
                                        <td class="text-success fw-bold">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">Total Keseluruhan:</td>
                                        <td class="fw-bold">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <?php if($order['status'] != 'cancelled'): ?>
                <div class="action-buttons">
                    <?php if($order['status'] == 'pending' || $order['payment_status'] == 'unpaid'): ?>
                        <a href="?action=cancel&id=<?php echo $order['id']; ?>" 
                           class="btn btn-warning"
                           onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">
                            <i class="fas fa-times-circle"></i> Batalkan Pesanan
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Form Update Status & Resi -->
                <div class="card mt-3">
                    <div class="card-header bg-dark text-white"><i class="fas fa-edit me-2"></i>Update Status, Nomor Resi & Blockchain Receipt</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_order" value="1">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Status Pesanan</label>
                                    <select name="new_status" class="form-select">
                                        <?php foreach(['pending'=>'Pending','paid'=>'Dibayar','production'=>'Produksi','shipped'=>'Dikirim','completed'=>'Selesai'] as $val=>$label): ?>
                                        <option value="<?=$val?>" <?=$order['status']==$val?'selected':''?>><?=$label?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Nomor Resi Pengiriman</label>
                                    <input type="text" name="tracking_number" class="form-control"
                                        placeholder="Contoh: JNE1234567890"
                                        value="<?=htmlspecialchars($order['tracking_number'] ?? '')?>">
                                    <small class="text-muted">Isi nomor resi setelah barang dikirim</small>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">TX Hash (Jika Manual)</label>
                                    <input type="text" name="tx_hash" class="form-control"
                                        placeholder="0x..."
                                        value="<?=htmlspecialchars($order['tx_hash'] ?? '')?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Block Number</label>
                                    <input type="text" name="block_number" class="form-control"
                                        placeholder="Contoh: 12"
                                        value="<?=htmlspecialchars($order['block_number'] ?? '')?>">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="action-buttons">
                    <a href="?action=delete&id=<?php echo $order['id']; ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('Yakin ingin menghapus pesanan ini secara permanen? Tindakan ini tidak dapat dibatalkan!')">
                        <i class="fas fa-trash-alt"></i> Hapus Permanen
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto hide alert after 5 seconds
        setTimeout(function() {
            const alert = document.querySelector('.alert-custom');
            if(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>