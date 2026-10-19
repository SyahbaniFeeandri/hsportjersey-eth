<?php
require_once '../config/database.php';
require_once '../config/functions.php';
// Load contract configuration for Web3 interactions
$contract_address = file_get_contents('../blockchain/config/contract_address.txt');
$contract_abi = file_get_contents('../blockchain/config/contract_abi.json');

if(!isAdmin()) {
    redirect('../login.php');
}

// Update order status
if(isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    
    if($_POST['status'] == 'paid') {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$_POST['order_id']]);

        // Simpan tx_hash dan block_number jika diisi oleh admin/form
        if(!empty($_POST['tx_hash'])) {
            $stmt = $pdo->prepare("UPDATE orders SET tx_hash = ?, block_number = ?, paid_at = NOW() WHERE id = ?");
            $stmt->execute([
                trim($_POST['tx_hash']), 
                !empty($_POST['block_number']) ? trim($_POST['block_number']) : null, 
                $_POST['order_id']
            ]);
        }
    }
    
    $_SESSION['message'] = 'Status pesanan berhasil diupdate!';
    $_SESSION['message_type'] = 'success';
    redirect('orders.php');
}

// Cancel/Delete order
if(isset($_GET['cancel'])) {
    $order_id = $_GET['cancel'];
    
    $stmt = $pdo->prepare("SELECT status, payment_status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if($order && ($order['status'] == 'pending' || $order['payment_status'] == 'unpaid')) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'unpaid', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
        $_SESSION['message'] = 'Pesanan berhasil dibatalkan!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Pesanan tidak dapat dibatalkan!';
        $_SESSION['message_type'] = 'danger';
    }
    redirect('orders.php');
}

// Delete order permanently
if(isset($_GET['delete'])) {
    $order_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if($order && ($order['status'] == 'cancelled' || $order['status'] == 'completed')) {
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        
        $_SESSION['message'] = 'Pesanan berhasil dihapus secara permanen!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Pesanan tidak dapat dihapus!';
        $_SESSION['message_type'] = 'danger';
    }
    redirect('orders.php');
}

// Reset all completed orders
if(isset($_GET['reset_completed'])) {
    $stmt = $pdo->prepare("DELETE FROM orders WHERE status = 'completed'");
    $stmt->execute();
    
    $stmt = $pdo->prepare("
        DELETE oi FROM order_items oi 
        INNER JOIN orders o ON oi.order_id = o.id 
        WHERE o.status = 'completed'
    ");
    $stmt->execute();
    
    $_SESSION['message'] = 'Semua pesanan selesai telah direset!';
    $_SESSION['message_type'] = 'success';
    redirect('orders.php');
}

// Get orders with customer info
$orders = $pdo->query("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
")->fetchAll();

$completed_count = count(array_filter($orders, function($o) { return $o['status'] == 'completed'; }));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin H-Sport</title>
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
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
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
        .btn-action {
            margin: 2px;
            padding: 5px 10px;
            font-size: 12px;
        }
        .order-row-cancelled {
            background-color: #f8d7da !important;
            opacity: 0.8;
        }
        .order-row-completed {
            background-color: #d4edda !important;
        }
        .tx-hash-badge {
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
            font-size: 11px;
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
                <a href="orders.php" class="active"><i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan</a>
                <a href="customers.php"><i class="fas fa-users me-2"></i> Pelanggan</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan</h2>
                    <div>
                        <?php if($completed_count > 0): ?>
                            <a href="?reset_completed=1" class="btn btn-danger" onclick="return confirm('Reset semua pesanan selesai?')">
                                <i class="fas fa-trash-alt"></i> Reset Semua Selesai (<?php echo $completed_count; ?>)
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-info ms-2" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
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
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i> Daftar Semua Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No. Pesanan</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Pembayaran</th>
                                        <th>Blockchain Receipt</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders as $order): ?>
                                    <tr class="<?php echo $order['status'] == 'cancelled' ? 'order-row-cancelled' : ($order['status'] == 'completed' ? 'order-row-completed' : ''); ?>">
                                        <td><?php echo $order['order_number']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td class="fw-bold">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?> </td>
                                        <td>
                                            <?php if($order['status'] != 'cancelled' && $order['status'] != 'completed'): ?>
                                                <form method="POST" class="d-flex gap-2" id="statusForm_<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" id="statusSelect_<?php echo $order['id']; ?>" class="form-select form-select-sm" style="width: 120px;">
                                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="paid" <?php echo $order['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        <option value="production" <?php echo $order['status'] == 'production' ? 'selected' : ''; ?>>Production</option>
                                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    </select>
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="handleStatusUpdate(<?php echo $order['id']; ?>, '<?php echo $order['order_number']; ?>', '<?php echo $order['payment_type'] ?? ''; ?>')">Update</button>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'danger'; ?>">
                                                <?php echo $order['payment_status'] == 'paid' ? 'Lunas' : 'Belum'; ?>
                                            </span>
                                            <?php if(!empty($order['payment_type'])): ?>
                                                <br><small class="badge bg-dark mt-1"><?php echo strtoupper($order['payment_type']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($order['tx_hash'])): ?>
                                                <span class="badge bg-info text-dark tx-hash-badge" title="<?php echo htmlspecialchars($order['tx_hash']); ?>">
                                                    <i class="fas fa-link me-1"></i><?php echo htmlspecialchars($order['tx_hash']); ?>
                                                </span>
                                                <?php if(!empty($order['block_number'])): ?>
                                                    <br><small class="text-muted">Block: #<?php echo htmlspecialchars($order['block_number']); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info btn-action" title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if($order['status'] == 'pending' && $order['payment_status'] != 'paid'): ?>
                                                    <a href="?cancel=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-warning btn-action" 
                                                       onclick="return confirm('Batalkan pesanan?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if($order['status'] == 'cancelled' || $order['status'] == 'completed'): ?>
                                                    <a href="?delete=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-danger btn-action" 
                                                       onclick="return confirm('Hapus permanen?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if(empty($orders)): ?>
                            <div class="alert alert-info text-center">Belum ada pesanan</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="alert alert-secondary mt-3">
                    <strong><i class="fas fa-info-circle"></i> Catatan Integrasi Blockchain:</strong>
                    <ul class="mb-0 mt-1">
                        <li>Jika pesanan berjenis <strong>CRYPTO</strong> diubah menjadi <strong>Shipped</strong>, sistem akan secara otomatis meminta MetaMask Admin menandatangani transaksi <code>shipOrder()</code> pada Smart Contract agar pembeli bisa melakukan konfirmasi penerimaan barang.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js"></script>
    <script>
        const contractAddress = '<?php echo trim($contract_address); ?>';
        const contractABI = <?php echo empty($contract_abi) ? '[]' : $contract_abi; ?>;

        async function handleStatusUpdate(orderId, orderNumber, paymentType) {
            const form = document.getElementById('statusForm_' + orderId);
            const select = document.getElementById('statusSelect_' + orderId);
            const selectedStatus = select.value;

            // Jika status diubah menjadi 'shipped' dan order tersebut adalah pesanan crypto
            if (selectedStatus === 'shipped' && paymentType === 'crypto' && typeof window.ethereum !== 'undefined' && contractAddress) {
                const proceed = confirm("Pesanan ini menggunakan pembayaran Crypto. Apakah Anda ingin sekaligus menandai 'shipOrder' di Smart Contract Ganache via MetaMask?");
                if (proceed) {
                    try {
                        const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                        const web3 = new Web3(window.ethereum);
                        const contract = new web3.eth.Contract(contractABI, contractAddress);
                        
                        // Cari ID pesanan di blockchain
                        const orderCount = await contract.methods.orderCount().call();
                        let onChainId = 0;
                        for (let i = 1; i <= Number(orderCount); i++) {
                            const info = await contract.methods.getOrderInfo(i).call();
                            if (info.orderNumber === orderNumber) {
                                onChainId = i;
                                break;
                            }
                        }

                        if (onChainId > 0) {
                            const encoded = contract.methods.shipOrder(onChainId).encodeABI();
                            await web3.eth.sendTransaction({
                                from: accounts[0],
                                to: contractAddress,
                                data: encoded,
                                gas: 200000
                            });
                            alert("Berhasil menandai 'Shipped' di Smart Contract!");
                        } else {
                            console.warn("Order number tidak ditemukan di blockchain, hanya update DB.");
                        }
                    } catch (e) {
                        alert("Peringatan Blockchain: " + e.message + "\nStatus di database tetap akan diperbarui.");
                    }
                }
            }

            form.submit();
        }

        setTimeout(function() {
            const alert = document.querySelector('.alert-custom');
            if(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 500);
            }
        }, 5000);
    </script>
</body>
</html>