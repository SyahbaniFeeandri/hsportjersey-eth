<?php
require_once '../config/database.php';
require_once '../config/functions.php';
if(!isAdmin()) redirect('../login.php');

// Handle admin reply
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['reply_review'])) {
    $review_id = (int)$_POST['review_id'];
    $reply = trim($_POST['admin_reply']);
    $stmt = $pdo->prepare("UPDATE reviews SET admin_reply=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$reply ?: null, $review_id]);
    $_SESSION['message'] = 'Balasan berhasil disimpan!';
    redirect('reviews.php');
}

// Handle delete
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id=?");
    $stmt->execute([(int)$_GET['delete']]);
    $_SESSION['message'] = 'Ulasan dihapus!';
    redirect('reviews.php');
}

// Filter rating
$filter_rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$where = $filter_rating ? "AND r.rating = $filter_rating" : "";

$reviews = $pdo->query("
    SELECT r.*, u.username, p.name as product_name, o.order_number
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    JOIN orders o ON r.order_id = o.id
    WHERE 1=1 $where
    ORDER BY r.created_at DESC
")->fetchAll();

$avg_rating = $pdo->query("SELECT AVG(rating) FROM reviews")->fetchColumn();
$total_reviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$five_star = $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating=5")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Ulasan - Admin H-Sport</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    .sidebar{min-height:100vh;background:#2c3e50}
    .sidebar a{color:white;text-decoration:none;padding:10px 20px;display:block;transition:.3s}
    .sidebar a:hover,.sidebar a.active{background:#34495e}
    .stat-card{background:white;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,.1)}
</style>
</head>
<body class="bg-light">
<div class="container-fluid"><div class="row">
<div class="col-md-2 p-0 sidebar"><?php include 'includes/sidebar.php'; ?></div>
<div class="col-md-10 p-4">
    <h2 class="mb-4"><i class="fas fa-star text-warning me-2"></i>Kelola Ulasan Pelanggan</h2>

    <?php if(isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?=$_SESSION['message']?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['message']); endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-comments fa-2x text-primary mb-2"></i>
            <h4><?=$total_reviews?></h4><small class="text-muted">Total Ulasan</small>
        </div></div>
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-star fa-2x text-warning mb-2"></i>
            <h4><?=number_format($avg_rating,1)?> / 5</h4><small class="text-muted">Rating Rata-rata</small>
        </div></div>
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-thumbs-up fa-2x text-success mb-2"></i>
            <h4><?=$five_star?></h4><small class="text-muted">Ulasan Bintang 5</small>
        </div></div>
        <div class="col-md-3"><div class="stat-card text-center">
            <i class="fas fa-reply fa-2x text-info mb-2"></i>
            <h4><?=$pdo->query("SELECT COUNT(*) FROM reviews WHERE admin_reply IS NOT NULL")->fetchColumn()?></h4>
            <small class="text-muted">Sudah Dibalas</small>
        </div></div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex gap-2 flex-wrap">
                <a href="reviews.php" class="btn btn-sm <?=!$filter_rating?'btn-danger':'btn-outline-secondary'?>">Semua</a>
                <?php for($r=5;$r>=1;$r--): ?>
                <a href="?rating=<?=$r?>" class="btn btn-sm <?=$filter_rating==$r?'btn-warning':'btn-outline-warning'?>">
                    <?=$r?> <i class="fas fa-star"></i>
                </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Tabel ulasan -->
    <div class="card">
        <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr><th>Pelanggan</th><th>Produk</th><th>Order</th><th>Rating</th><th>Ulasan</th><th>Tanggal</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if(empty($reviews)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada ulasan</td></tr>
            <?php else: ?>
            <?php foreach($reviews as $rev): ?>
            <tr>
                <td><i class="fas fa-user text-primary me-1"></i><?=htmlspecialchars($rev['username'])?></td>
                <td><?=htmlspecialchars($rev['product_name'])?></td>
                <td><small class="badge bg-secondary"><?=htmlspecialchars($rev['order_number'])?></small></td>
                <td>
                    <?php for($s=1;$s<=5;$s++): ?>
                    <i class="fas fa-star <?=$s<=$rev['rating']?'text-warning':'text-muted'?>" style="font-size:.8rem"></i>
                    <?php endfor; ?>
                </td>
                <td>
                    <span><?=htmlspecialchars(substr($rev['comment'],0,60)).(strlen($rev['comment'])>60?'...':'')?></span>
                    <?php if($rev['admin_reply']): ?>
                    <br><small class="text-info"><i class="fas fa-reply me-1"></i><?=htmlspecialchars(substr($rev['admin_reply'],0,40))?></small>
                    <?php endif; ?>
                </td>
                <td><small><?=date('d M Y', strtotime($rev['created_at']))?></small></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                        data-bs-target="#replyModal<?=$rev['id']?>">
                        <i class="fas fa-reply"></i>
                    </button>
                    <a href="?delete=<?=$rev['id']?>" class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Hapus ulasan ini?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>

            <!-- Modal Reply -->
            <div class="modal fade" id="replyModal<?=$rev['id']?>" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">Balas Ulasan - <?=htmlspecialchars($rev['username'])?></h5>
                        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted"><strong>Ulasan:</strong> <?=htmlspecialchars($rev['comment'])?></p>
                        <form method="POST">
                            <input type="hidden" name="review_id" value="<?=$rev['id']?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Balasan Admin</label>
                                <textarea name="admin_reply" class="form-control" rows="3"
                                    placeholder="Tulis balasan..."><?=htmlspecialchars($rev['admin_reply'] ?? '')?></textarea>
                            </div>
                            <button type="submit" name="reply_review" class="btn btn-danger w-100">
                                <i class="fas fa-paper-plane me-1"></i> Simpan Balasan
                            </button>
                        </form>
                    </div>
                </div></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
