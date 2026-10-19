<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

// Handle submit review
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $order_id   = (int)$_POST['order_id'];
    $product_id = (int)$_POST['product_id'];
    $rating     = (int)$_POST['rating'];
    $comment    = trim($_POST['comment']);

    if($rating < 1 || $rating > 5) {
        $message = 'Rating harus antara 1 - 5 bintang.';
        $msg_type = 'danger';
    } else {
        // Verify order belongs to user and is completed
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id=? AND user_id=? AND status='completed'");
        $stmt->execute([$order_id, $user_id]);
        if($stmt->fetch()) {
            // Check duplicate
            $stmt2 = $pdo->prepare("SELECT id FROM reviews WHERE order_id=? AND product_id=? AND user_id=?");
            $stmt2->execute([$order_id, $product_id, $user_id]);
            if($stmt2->fetch()) {
                $message = 'Anda sudah memberikan ulasan untuk produk ini.';
                $msg_type = 'warning';
            } else {
                $stmt3 = $pdo->prepare("INSERT INTO reviews (order_id, product_id, user_id, rating, comment) VALUES (?,?,?,?,?)");
                $stmt3->execute([$order_id, $product_id, $user_id, $rating, $comment]);
                $message = 'Terima kasih! Ulasan Anda berhasil dikirim.';
                $msg_type = 'success';
            }
        } else {
            $message = 'Pesanan tidak valid atau belum selesai.';
            $msg_type = 'danger';
        }
    }
}

// Completed orders that can be reviewed
$stmt = $pdo->prepare("
    SELECT o.id as order_id, o.order_number, o.created_at,
           oi.product_id, p.name as product_name, p.image,
           oi.size, oi.quantity,
           (SELECT id FROM reviews r WHERE r.order_id=o.id AND r.product_id=oi.product_id AND r.user_id=?) as reviewed_id
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id=? AND o.status='completed'
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$reviewable = $stmt->fetchAll();

// Existing reviews by user
$stmt = $pdo->prepare("
    SELECT r.*, p.name as product_name, p.image, o.order_number
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN orders o ON r.order_id = o.id
    WHERE r.user_id=?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$my_reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ulasan Saya - H-Sport</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    .star-input { display:flex; flex-direction:row-reverse; justify-content:flex-end; }
    .star-input input { display:none; }
    .star-input label { cursor:pointer; font-size:2rem; color:#ccc; transition:.2s; }
    .star-input input:checked ~ label,
    .star-input label:hover,
    .star-input label:hover ~ label { color:#f39c12; }
    .review-card { border-left:4px solid #e74c3c; }
    .product-thumb { width:60px; height:60px; object-fit:cover; border-radius:8px; }
</style>
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>
<div class="container py-5">
    <h2 class="mb-4"><i class="fas fa-star text-warning me-2"></i>Ulasan Saya</h2>

    <?php if($message): ?>
    <div class="alert alert-<?=$msg_type?> alert-dismissible fade show">
        <?=$message?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Produk yang bisa diulas -->
    <?php $pending_review = array_filter($reviewable, fn($r) => !$r['reviewed_id']); ?>
    <?php if(!empty($pending_review)): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-pen me-2"></i>Produk yang Perlu Diulas
        </div>
        <div class="card-body">
        <?php foreach($pending_review as $item): ?>
        <div class="d-flex align-items-start gap-3 mb-4 p-3 bg-white rounded shadow-sm">
            <?php if($item['image']): ?>
            <img src="uploads/<?=htmlspecialchars($item['image'])?>" class="product-thumb" alt="">
            <?php else: ?>
            <div class="product-thumb bg-secondary d-flex align-items-center justify-content-center text-white rounded">
                <i class="fas fa-tshirt"></i>
            </div>
            <?php endif; ?>
            <div class="flex-grow-1">
                <strong><?=htmlspecialchars($item['product_name'])?></strong>
                <small class="text-muted d-block">Order #<?=htmlspecialchars($item['order_number'])?> &bull; Ukuran: <?=$item['size']?> &bull; Qty: <?=$item['quantity']?></small>
                <form method="POST" class="mt-2">
                    <input type="hidden" name="order_id" value="<?=$item['order_id']?>">
                    <input type="hidden" name="product_id" value="<?=$item['product_id']?>">
                    <div class="star-input mb-2">
                        <?php for($s=5;$s>=1;$s--): ?>
                        <input type="radio" name="rating" id="star<?=$s?>_<?=$item['order_id']?>_<?=$item['product_id']?>" value="<?=$s?>">
                        <label for="star<?=$s?>_<?=$item['order_id']?>_<?=$item['product_id']?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comment" class="form-control form-control-sm mb-2" rows="2"
                        placeholder="Tulis ulasan Anda..." required></textarea>
                    <button type="submit" name="submit_review" class="btn btn-sm btn-danger">
                        <i class="fas fa-paper-plane me-1"></i>Kirim Ulasan
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ulasan yang sudah diberikan -->
    <h5 class="mb-3">Ulasan Saya Sebelumnya</h5>
    <?php if(empty($my_reviews)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-star fa-3x mb-3 opacity-25"></i>
            <p>Belum ada ulasan. Selesaikan pesanan untuk memberikan ulasan!</p>
            <a href="products.php" class="btn btn-danger">Lihat Produk</a>
        </div>
    <?php else: ?>
    <div class="row g-3">
    <?php foreach($my_reviews as $r): ?>
    <div class="col-md-6">
        <div class="card review-card h-100">
            <div class="card-body">
                <div class="d-flex gap-3">
                    <?php if($r['image']): ?>
                    <img src="uploads/<?=htmlspecialchars($r['image'])?>" class="product-thumb" alt="">
                    <?php endif; ?>
                    <div>
                        <strong><?=htmlspecialchars($r['product_name'])?></strong>
                        <small class="text-muted d-block">Order #<?=htmlspecialchars($r['order_number'])?></small>
                        <div class="my-1">
                            <?php for($s=1;$s<=5;$s++): ?>
                            <i class="fas fa-star <?=$s<=$r['rating']?'text-warning':'text-muted'?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-1 text-muted"><?=htmlspecialchars($r['comment'])?></p>
                        <small class="text-muted"><?=date('d M Y', strtotime($r['created_at']))?></small>
                        <?php if($r['admin_reply']): ?>
                        <div class="alert alert-light mt-2 mb-0 py-1 px-2">
                            <small><strong><i class="fas fa-reply text-danger me-1"></i>Balasan Admin:</strong> <?=htmlspecialchars($r['admin_reply'])?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
