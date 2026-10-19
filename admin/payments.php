<?php
require_once '../config/database.php';
require_once '../config/functions.php';
if(!isAdmin()) redirect('../login.php');

// Verify / reject payment
if(isset($_POST['verify_payment'])) {
    $payment_id = (int)$_POST['payment_id'];
    $action = $_POST['action']; // 'verified' or 'rejected'
    $stmt = $pdo->prepare("UPDATE payments SET status=?, verified_by=?, verified_at=NOW() WHERE id=?");
    $stmt->execute([$action, $_SESSION['user_id'], $payment_id]);
    if($action === 'verified') {
        // Update order payment_status
        $stmt2 = $pdo->prepare("UPDATE orders SET payment_status='paid', status='production', updated_at=NOW() WHERE id=(SELECT order_id FROM payments WHERE id=?)");
        $stmt2->execute([$payment_id]);
    }
    $_SESSION['message'] = $action === 'verified' ? 'Pembayaran berhasil diverifikasi!' : 'Pembayaran ditolak.';
    redirect('payments.php');
}

$filter = isset($_GET['status']) ? $_GET['status'] : '';
$where  = $filter ? "WHERE p.status='$filter'" : '';

$payments = $pdo->query("
    SELECT p.*, o.order_number, o.total_amount, o.status as order_status, u.username, u.email
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    $where
    ORDER BY p.created_at DESC
")->fetchAll();

$pending_count = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='pending'")->fetchColumn();
$verified_count = $pdo->query("SELECT COUNT(*) FROM payments WHERE status='verified'")->fetchColumn();
$total_verified_amount = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pembayaran - Admin H-Sport</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    .sidebar{min-height:100vh;background:#2c3e50}
    .sidebar a{color:white;text-decoration:none;padding:10px 20px;display:block;transition:.3s}
    .sidebar a:hover,.sidebar a.active{background:#34495e}
    .stat-card{background:white;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,.1)}
    .proof-img{max-width:100%;max-height:300px;object-fit:contain;border-radius:8px}
</style>
</head>
<body class="bg-light">
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0 sidebar"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-4">
    <h2 class="mb-4"><i class="fas fa-credit-card text-success me-2"></i>Kelola Pembayaran</h2>

    <?php if(isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?=$_SESSION['message']?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['message']); endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
            <h4 class="text-warning"><?=$pending_count?></h4><small class="text-muted">Menunggu Verifikasi</small>
        </div></div>
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
            <h4 class="text-success"><?=$verified_count?></h4><small class="text-muted">Terverifikasi</small>
        </div></div>
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-money-bill fa-2x text-primary mb-2"></i>
            <h4 class="text-primary">Rp <?=number_format($total_verified_amount,0,',','.')?></h4>
            <small class="text-muted">Total Diterima</small>
        </div></div>
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-list fa-2x text-secondary mb-2"></i>
            <h4><?=$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn()?></h4>
            <small class="text-muted">Total Transaksi</small>
        </div></div>
    </div>

    <!-- Filter -->
    <div class="card mb-3"><div class="card-body py-2">
        <div class="d-flex gap-2">
            <a href="payments.php" class="btn btn-sm <?=!$filter?'btn-danger':'btn-outline-secondary'?>">Semua</a>
            <a href="?status=pending" class="btn btn-sm <?=$filter=='pending'?'btn-warning':'btn-outline-warning'?>">
                Pending <?=$pending_count>0?"<span class='badge bg-danger'>$pending_count</span>":""?>
            </a>
            <a href="?status=verified" class="btn btn-sm <?=$filter=='verified'?'btn-success':'btn-outline-success'?>">Terverifikasi</a>
            <a href="?status=rejected" class="btn btn-sm <?=$filter=='rejected'?'btn-danger':'btn-outline-danger'?>">Ditolak</a>
        </div>
    </div></div>

    <!-- Tabel pembayaran -->
    <div class="card"><div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-dark">
            <tr><th>No. Order</th><th>Pelanggan</th><th>Metode</th><th>Jumlah</th><th>Bukti Bayar</th><th>Status</th><th>Tgl. Bayar</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php if(empty($payments)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data pembayaran</td></tr>
        <?php else: ?>
        <?php foreach($payments as $pay): ?>
        <tr>
            <td><strong><?=htmlspecialchars($pay['order_number'])?></strong></td>
            <td><?=htmlspecialchars($pay['username'])?><br><small class="text-muted"><?=htmlspecialchars($pay['email'])?></small></td>
            <td><span class="badge bg-dark"><?=htmlspecialchars($pay['payment_method'] ?? 'QRIS')?></span></td>
            <td class="text-success fw-bold">Rp <?=number_format($pay['amount'],0,',','.')?></td>
            <td>
                <?php if($pay['payment_proof']): ?>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                    data-bs-target="#proofModal<?=$pay['id']?>">
                    <i class="fas fa-image me-1"></i>Lihat Bukti
                </button>
                <!-- Modal Proof -->
                <div class="modal fade" id="proofModal<?=$pay['id']?>" tabindex="-1">
                    <div class="modal-dialog modal-lg"><div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Bukti Pembayaran - <?=htmlspecialchars($pay['order_number'])?></h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="../uploads/payments/<?=htmlspecialchars($pay['payment_proof'])?>" class="proof-img">
                        </div>
                    </div></div>
                </div>
                <?php else: ?>
                <span class="text-muted"><i class="fas fa-minus"></i> Auto/QRIS</span>
                <?php endif; ?>
            </td>
            <td>
                <?php
                $badges = ['pending'=>'warning','verified'=>'success','rejected'=>'danger'];
                $labels = ['pending'=>'Menunggu','verified'=>'Terverifikasi','rejected'=>'Ditolak'];
                $s = $pay['status'];
                ?>
                <span class="badge bg-<?=$badges[$s]?>"><?=$labels[$s]?></span>
            </td>
            <td><small><?=date('d M Y H:i', strtotime($pay['created_at']))?></small></td>
            <td>
                <?php if($pay['status'] === 'pending'): ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="payment_id" value="<?=$pay['id']?>">
                    <button name="action" value="verified" type="submit" class="btn btn-sm btn-success"
                        onclick="return confirm('Verifikasi pembayaran ini?')">
                        <i class="fas fa-check"></i>
                    </button>
                    <button name="action" value="rejected" type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Tolak pembayaran ini?')">
                        <i class="fas fa-times"></i>
                    </button>
                    <input type="hidden" name="verify_payment" value="1">
                </form>
                <?php else: ?>
                <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    </div></div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
