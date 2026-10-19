<div class="text-center py-4">
    <i class="fas fa-tshirt fa-3x text-white"></i>
    <h5 class="text-white mt-2">Admin Panel</h5>
</div>
<?php $cur = basename($_SERVER['PHP_SELF']); ?>
<a href="dashboard.php" class="<?=$cur=='dashboard.php'?'active':''?>"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
<a href="products.php" class="<?=$cur=='products.php'?'active':''?>"><i class="fas fa-box me-2"></i> Kelola Produk</a>
<a href="orders.php" class="<?=$cur=='orders.php'||$cur=='order-detail.php'?'active':''?>"><i class="fas fa-shopping-cart me-2"></i> Kelola Pesanan</a>
<a href="customers.php" class="<?=$cur=='customers.php'?'active':''?>"><i class="fas fa-users me-2"></i> Pelanggan</a>
<a href="payments.php" class="<?=$cur=='payments.php'?'active':''?>"><i class="fas fa-credit-card me-2"></i> Pembayaran</a>
<a href="reviews.php" class="<?=$cur=='reviews.php'?'active':''?>"><i class="fas fa-star me-2"></i> Ulasan</a>
<a href="reports.php" class="<?=$cur=='reports.php'?'active':''?>"><i class="fas fa-chart-line me-2"></i> Laporan</a>
<hr style="border-color:#4a6278; margin:8px 16px">
<a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
