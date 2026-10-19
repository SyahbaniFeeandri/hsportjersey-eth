<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil config blockchain untuk JS
$contract_address = file_get_contents('blockchain/config/contract_address.txt');
$contract_abi = file_get_contents('blockchain/config/contract_abi.json');

// Handle konfirmasi penerimaan barang
if(isset($_GET['confirm_received'])) {
    $order_id = (int)$_GET['confirm_received'];
    
    // Update status pesanan menjadi completed
    $stmt = $pdo->prepare("UPDATE orders SET status = 'completed', updated_at = NOW() WHERE id = ? AND user_id = ? AND status = 'shipped'");
    $stmt->execute([$order_id, $user_id]);
    
    if($stmt->rowCount() > 0) {
        $_SESSION['message'] = 'Terima kasih! Pesanan telah selesai dan diterima.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal mengkonfirmasi penerimaan.';
        $_SESSION['message_type'] = 'danger';
    }
    redirect('orders.php');
}

// Handle reset pesanan (clear all completed orders)
if(isset($_GET['reset_completed'])) {
    $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    
    // Also delete related order items
    $stmt = $pdo->prepare("
        DELETE oi FROM order_items oi 
        INNER JOIN orders o ON oi.order_id = o.id 
        WHERE o.user_id = ? AND o.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    
    $_SESSION['message'] = 'Pesanan yang sudah selesai telah direset!';
    $_SESSION['message_type'] = 'success';
    redirect('orders.php');
}

// Handle hapus pesanan selesai
if(isset($_GET['delete_completed'])) {
    $order_id = (int)$_GET['delete_completed'];
    
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ? AND status = 'completed'");
    $stmt->execute([$order_id, $user_id]);
    
    $_SESSION['message'] = 'Pesanan berhasil dihapus!';
    $_SESSION['message_type'] = 'success';
    redirect('orders.php');
}

// Update status pesanan menjadi shipped jika payment sudah dikonfirmasi
$stmt = $pdo->prepare("
    UPDATE orders 
    SET status = 'shipped', 
        updated_at = NOW() 
    WHERE user_id = ? 
    AND payment_status = 'paid' 
    AND status = 'pending'
");
$stmt->execute([$user_id]);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Hitung statistik
$total_orders = count($orders);
$completed_orders = count(array_filter($orders, function($o) { return $o['status'] == 'completed'; }));
$shipped_orders = count(array_filter($orders, function($o) { return $o['status'] == 'shipped'; }));
$pending_orders = count(array_filter($orders, function($o) { return $o['status'] == 'pending'; }));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - H-Sport Jersey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .order-card {
            border-left: 4px solid #004b9c;
            margin-bottom: 20px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .order-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .order-card.completed {
            border-left-color: #28a745;
            opacity: 0.9;
        }
        .tracking-status {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }
        .status-line {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
        }
        .status-line .status-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .status-line.completed .status-icon {
            background: #28a745;
            color: white;
        }
        .status-line.active .status-icon {
            background: #004b9c;
            color: white;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(92, 79, 74, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(92, 79, 74, 0); }
            100% { box-shadow: 0 0 0 0 rgba(92, 79, 74, 0); }
        }
        .payment-success {
            animation: slideDown 0.5s ease;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .btn-confirm {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            border: none;
            transition: all 0.3s;
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-reset {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            transition: all 0.3s;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .stats-number {
            font-size: 2rem;
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
            }
        }
        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9998;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
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
            <h2><i class="fas fa-clipboard-list me-2"></i> Pesanan Saya</h2>
            <?php if($completed_orders > 0): ?>
                <button class="btn btn-danger btn-sm" onclick="confirmReset()">
                    <i class="fas fa-trash-alt"></i> Reset Semua Pesanan Selesai
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-shopping-bag fa-2x text-primary"></i>
                    <div class="stats-number"><?php echo $total_orders; ?></div>
                    <div>Total Pesanan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-clock fa-2x text-warning"></i>
                    <div class="stats-number"><?php echo $pending_orders; ?></div>
                    <div>Menunggu</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-truck fa-2x text-info"></i>
                    <div class="stats-number"><?php echo $shipped_orders; ?></div>
                    <div>Dalam Pengiriman</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                    <div class="stats-number"><?php echo $completed_orders; ?></div>
                    <div>Selesai</div>
                </div>
            </div>
        </div>
        
        <?php if(isset($_GET['payment_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show payment-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Pembayaran Berhasil!</strong> Pesanan Anda sedang diproses dan akan segera dikirim.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-5x text-muted mb-3"></i>
                <h4>Belum ada pesanan</h4>
                <p class="text-muted">Mulai belanja sekarang!</p>
                <a href="products.php" class="btn btn-primary mt-3">Belanja Sekarang</a>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
            <div class="card order-card <?php echo $order['status'] == 'completed' ? 'completed' : ''; ?>">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <strong><i class="fas fa-receipt me-2"></i> No. Pesanan:</strong>
                            <?php echo $order['order_number']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong><i class="fas fa-calendar me-2"></i> Tanggal:</strong>
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </div>
                        <div class="col-md-5 text-end">
                            <span class="status-badge" style="background: <?php 
                                echo $order['status'] == 'completed' ? '#28a745' :
                                    ($order['payment_status'] == 'paid' && $order['status'] == 'shipped' ? '#17a2b8' : 
                                    ($order['status'] == 'pending' ? '#ffc107' : '#6c757d')); 
                            ?>; color: white;">
                                <?php if($order['status'] == 'completed'): ?>
                                    <i class="fas fa-check-circle"></i> Selesai
                                <?php elseif($order['payment_status'] == 'paid' && $order['status'] == 'shipped'): ?>
                                    <i class="fas fa-truck"></i> Sedang Dikirim
                                <?php elseif($order['payment_status'] == 'paid'): ?>
                                    <i class="fas fa-check-circle"></i> Dibayar
                                <?php elseif($order['status'] == 'pending'): ?>
                                    <i class="fas fa-clock"></i> Menunggu Pembayaran
                                <?php else: ?>
                                    <i class="fas fa-info-circle"></i> <?php echo ucfirst($order['status']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p><strong><i class="fas fa-truck me-2"></i> Metode Pengiriman:</strong> <?php echo $order['shipping_method']; ?></p>
                            <p><strong><i class="fas fa-map-marker-alt me-2"></i> Alamat:</strong> <?php echo nl2br($order['shipping_address']); ?></p>
                            <p><strong><i class="fas fa-qrcode me-2"></i> Status Pembayaran:</strong> 
                                <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'danger'; ?>">
                                    <?php echo $order['payment_status'] == 'paid' ? 'Lunas' : 'Belum Dibayar'; ?>
                                </span>
                                <?php if(!empty($order['payment_type'])): ?>
                                    <span class="badge bg-dark ms-1"><?php echo strtoupper($order['payment_type']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <h5 class="text-success">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></h5>
                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                            <?php if($order['payment_status'] != 'paid' && $order['status'] != 'cancelled'): ?>
                                <?php if(($order['payment_type'] ?? '') === 'crypto'): ?>
                                    <a href="crypto-payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm mt-2 fw-bold text-dark">
                                        <i class="fab fa-ethereum me-1"></i> Bayar Crypto
                                    </a>
                                <?php else: ?>
                                    <a href="generate-qr.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success btn-sm mt-2">
                                        <i class="fas fa-qrcode"></i> Bayar
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if($order['status'] == 'completed'): ?>
                                <button onclick="deleteCompletedOrder(<?php echo $order['id']; ?>)" class="btn btn-danger btn-sm mt-2">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tracking Status -->
                    <div class="tracking-status mt-3">
                        <h6><i class="fas fa-truck me-2"></i> Status Pengiriman:</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="status-line <?php echo $order['payment_status'] == 'paid' || $order['status'] == 'completed' ? 'completed' : ''; ?>">
                                    <div class="status-icon"><i class="fas fa-check"></i></div>
                                    <div>Pesanan Dibuat</div>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="status-line <?php echo $order['payment_status'] == 'paid' ? 'completed' : ''; ?>">
                                    <div class="status-icon"><i class="fas fa-check"></i></div>
                                    <div>Pembayaran Dikonfirmasi</div>
                                    <?php if($order['payment_status'] == 'paid'): ?>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($order['updated_at'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="status-line <?php echo $order['status'] == 'shipped' ? 'active' : ($order['status'] == 'completed' ? 'completed' : ''); ?>">
                                    <div class="status-icon">
                                        <?php if($order['status'] == 'shipped'): ?>
                                            <i class="fas fa-truck"></i>
                                        <?php elseif($order['status'] == 'completed'): ?>
                                            <i class="fas fa-check"></i>
                                        <?php else: ?>
                                            <i class="fas fa-clock"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>Sedang Dikirim</div>
                                    <?php if($order['status'] == 'shipped'): ?>
                                        <small class="text-muted">Pesanan dalam perjalanan</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="status-line <?php echo $order['status'] == 'completed' ? 'completed' : ''; ?>">
                                    <div class="status-icon">
                                        <?php if($order['status'] == 'completed'): ?>
                                            <i class="fas fa-home"></i>
                                        <?php else: ?>
                                            <i class="fas fa-hourglass-half"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>Pesanan Selesai</div>
                                    <?php if($order['status'] == 'completed'): ?>
                                        <small class="text-muted">Barang sudah diterima</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tombol Konfirmasi Penerimaan -->
                        <?php if($order['payment_status'] == 'paid' && $order['status'] == 'shipped'): ?>
                            <div class="text-center mt-3">
                                <button class="btn btn-confirm text-white" onclick="confirmReceived(<?php echo $order['id']; ?>, '<?php echo $order['order_number']; ?>', '<?php echo $order['payment_type'] ?? ''; ?>')">
                                    <i class="fas fa-check-circle"></i> Konfirmasi Barang Telah Diterima
                                </button>
                                <p class="text-muted small mt-2">
                                    <i class="fas fa-info-circle"></i> Klik tombol di atas jika barang sudah sampai di tangan Anda
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle"></i> Konfirmasi Penerimaan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-box-open fa-4x text-success mb-3"></i>
                    <h5>Apakah barang sudah sampai?</h5>
                    <p>Pastikan Anda sudah menerima pesanan dengan nomor: <strong id="orderNumberConfirm"></strong></p>
                    <p class="text-muted small" id="confirmNotice">Dengan mengkonfirmasi, pesanan akan ditandai sebagai <strong>SELESAI</strong>.</p>
                    
                    <!-- Alert pesan error / info Smart contract jika ada -->
                    <div id="contractErrorAlert" class="alert alert-warning text-start d-none" style="font-size: 13px;">
                        <div id="contractErrorMsg"></div>
                        <div class="mt-2 text-end">
                            <a href="#" id="forceConfirmLink" class="btn btn-sm btn-outline-danger">Konfirmasi Manual (Lewati Blockchain)</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmLink" class="btn btn-success">Ya, Barang Sudah Sampai</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Reset -->
    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Reset Pesanan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                    <h5>Hapus Semua Pesanan Selesai?</h5>
                    <p>Semua pesanan yang sudah selesai akan dihapus secara permanen.</p>
                    <p class="text-danger"><strong>Tindakan ini tidak dapat dibatalkan!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="?reset_completed=1" class="btn btn-danger">Ya, Reset Semua</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js"></script>
    <script>
        let contractAddress = '<?php echo trim($contract_address); ?>';
        let contractABI = <?php echo empty($contract_abi) ? '[]' : $contract_abi; ?>;
        
        // Konfirmasi penerimaan barang
        function confirmReceived(orderId, orderNumber, paymentType) {
            document.getElementById('orderNumberConfirm').textContent = orderNumber;
            const confirmBtn = document.getElementById('confirmLink');
            const alertBox = document.getElementById('contractErrorAlert');
            alertBox.classList.add('d-none');
            
            if (paymentType === 'crypto') {
                confirmBtn.href = "#";
                confirmBtn.onclick = function(e) {
                    e.preventDefault();
                    processWeb3Confirm(orderId, orderNumber);
                };
            } else {
                confirmBtn.href = '?confirm_received=' + orderId;
                confirmBtn.onclick = null;
            }
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }
        
        async function processWeb3Confirm(dbOrderId, orderNumber) {
            if (typeof window.ethereum === 'undefined') {
                alert('MetaMask tidak terinstall! Untuk pesanan Crypto, wajib menggunakan MetaMask.');
                return;
            }
            
            const confirmBtn = document.getElementById('confirmLink');
            const alertBox = document.getElementById('contractErrorAlert');
            const errorMsg = document.getElementById('contractErrorMsg');
            const forceConfirmBtn = document.getElementById('forceConfirmLink');
            
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memeriksa Smart Contract...';
            confirmBtn.classList.add('disabled');
            alertBox.classList.add('d-none');
            
            try {
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                const userAccount = accounts[0];
                const web3 = new Web3(window.ethereum);
                
                // Pastikan berada di jaringan Ganache (1337 / 0x539)
                const chainId = await window.ethereum.request({ method: 'eth_chainId' });
                if (chainId !== '0x539' && chainId !== 1337) {
                    throw new Error('Harap ganti jaringan MetaMask ke Ganache (Chain ID 1337 / 0x539) terlebih dahulu.');
                }
                
                const contract = new web3.eth.Contract(contractABI, contractAddress);
                
                // Cari ID pesanan di blockchain berdasarkan orderNumber
                const orderCount = await contract.methods.orderCount().call();
                let onChainOrderId = 0;
                let onChainOrder = null;
                
                for (let i = 1; i <= Number(orderCount); i++) {
                    const info = await contract.methods.getOrderInfo(i).call();
                    if (info.orderNumber === orderNumber) {
                        onChainOrderId = i;
                        onChainOrder = info;
                        break;
                    }
                }
                
                if (onChainOrderId === 0) {
                    throw new Error("Nomor pesanan (" + orderNumber + ") tidak ditemukan pada Smart Contract. Kemungkinan Ganache sempat di-restart.");
                }
                
                // Pengecekan 1: Pastikan akun MetaMask saat ini adalah buyer yang sah
                if (onChainOrder.buyer.toLowerCase() !== userAccount.toLowerCase()) {
                    throw new Error("Akun MetaMask yang aktif (" + userAccount.substring(0,6) + "...) berbeda dengan akun pembeli di kontrak (" + onChainOrder.buyer.substring(0,6) + "...). Silakan ganti ke akun MetaMask yang Anda gunakan saat membayar.");
                }
                
                // Pengecekan 2: Status pesanan di smart contract
                // Enum OrderStatus { 0: Created, 1: Paid, 2: Shipped, 3: Completed }
                const currentStatus = Number(onChainOrder.status);
                if (currentStatus === 1) { // Paid
                    throw new Error("Status pesanan di Smart Contract masih 'Paid' (belum 'Shipped'). Admin toko belum menandai pengiriman di blockchain. Mohon minta Admin mengubah status ke 'Shipped' terlebih dahulu.");
                } else if (currentStatus === 3) { // Completed
                    throw new Error("Dana pesanan ini sudah dicairkan sebelumnya di Smart Contract.");
                } else if (currentStatus !== 2) {
                    throw new Error("Status pesanan di Smart Contract saat ini belum siap dicairkan (Status: " + currentStatus + ").");
                }
                
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mencairkan Dana di MetaMask...';
                
                // Panggil fungsi confirmReceived di blockchain
                const encodedData = contract.methods.confirmReceived(onChainOrderId).encodeABI();
                
                await web3.eth.sendTransaction({
                    from: userAccount,
                    to: contractAddress,
                    data: encodedData,
                    gas: 300000
                });
                
                // Transaksi blockchain berhasil, update status di DB MySQL
                window.location.href = '?confirm_received=' + dbOrderId;
                
            } catch (error) {
                console.error("Web3 Error:", error);
                errorMsg.innerHTML = '<strong><i class="fas fa-exclamation-triangle"></i> Gagal Konfirmasi Blockchain:</strong><br>' + error.message;
                forceConfirmBtn.href = '?confirm_received=' + dbOrderId;
                alertBox.classList.remove('d-none');
                
                confirmBtn.innerHTML = originalText;
                confirmBtn.classList.remove('disabled');
            }
        }
        
        // Konfirmasi reset semua pesanan
        function confirmReset() {
            new bootstrap.Modal(document.getElementById('resetModal')).show();
        }
        
        // Hapus pesanan selesai
        function deleteCompletedOrder(orderId) {
            if(confirm('Apakah Anda yakin ingin menghapus pesanan ini?')) {
                window.location.href = '?delete_completed=' + orderId;
            }
        }
        
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
        
        // Confetti effect when order completed
        function showConfetti() {
            const duration = 3 * 1000;
            const animationEnd = Date.now() + duration;
            const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };
            
            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }
            
            const interval = setInterval(function() {
                const timeLeft = animationEnd - Date.now();
                
                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }
                
                const particleCount = 50 * (timeLeft / duration);
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        }
        
        // Check if just confirmed
        if(window.location.href.includes('confirm_received')) {
            showConfetti();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
</body>
</html>