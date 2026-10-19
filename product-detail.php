<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if(!$product) {
    redirect('products.php');
}

// Get related products
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? LIMIT 4");
$stmt->execute([$product['category'], $product['id']]);
$related = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - H-Sport Jersey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-image {
            max-width: 100%;
            border-radius: 10px;
        }
        .size-option {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        .size-option:hover {
            border-color: #004b9c;
            background-color: rgba(92, 79, 74, 0.1);
        }
        .size-option.selected {
            background-color: #004b9c;
            color: white;
            border-color: #004b9c;
        }
        .color-option {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .color-option:hover {
            transform: scale(1.1);
        }
        .color-option.selected {
            border-color: #004b9c;
            box-shadow: 0 0 0 2px white, 0 0 0 4px #004b9c;
        }
        .quantity-input {
            width: 100px;
            text-align: center;
            font-size: 1.1rem;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .btn-add-to-cart {
            background-color: #004b9c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-add-to-cart:hover {
            background-color: #003366;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(92, 79, 74, 0.3);
        }
        .btn-customize {
            background-color: transparent;
            border: 2px solid #004b9c;
            color: #004b9c;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-customize:hover {
            background-color: #004b9c;
            color: white;
            transform: translateY(-2px);
        }
        .price {
            font-size: 2rem;
            font-weight: bold;
            color: #004b9c;
        }
        .toast-header.bg-success {
            background-color: #004b9c !important;
        }
        .product-info-section {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
        }
        .related-product-card {
            transition: transform 0.3s;
            border-radius: 10px;
            overflow: hidden;
        }
        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <?php if($product['image'] && file_exists('uploads/' . $product['image'])): ?>
                    <img src="uploads/<?php echo $product['image']; ?>" class="product-image" alt="<?php echo $product['name']; ?>">
                <?php else: ?>
                    <img src="img/persib home.png" class="product-image" alt="Placeholder">
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <span class="badge bg-secondary mb-2" style="background-color: #004b9c !important;"><?php echo $product['category']; ?></span>
                <h2><?php echo $product['name']; ?></h2>
                <p class="text-muted">
                    <i class="fas fa-fabric"></i> Bahan: <?php echo $product['material']; ?>
                </p>
                <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                
                <div class="mb-4">
                    <h5>Deskripsi</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <div class="mb-4">
                    <h5>Pilih Ukuran:</h5>
                    <div id="sizeOptions">
                        <?php 
                        $sizes = explode(',', $product['sizes']);
                        foreach($sizes as $size):
                        ?>
                        <div class="size-option" data-size="<?php echo trim($size); ?>">
                            <?php echo trim($size); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5>Pilih Warna:</h5>
                    <div id="colorOptions">
                        <?php 
                        $colorMap = [
                            'Merah' => '#FF0000',
                            'Biru' => '#0000FF',
                            'Hijau' => '#00FF00',
                            'Kuning' => '#FFFF00',
                            'Hitam' => '#000000',
                            'Putih' => '#FFFFFF',
                            'Abu-abu' => '#808080',
                            'Orange' => '#FFA500'
                        ];
                        $colors = explode(',', $product['colors']);
                        foreach($colors as $color):
                            $colorName = trim($color);
                            $colorCode = isset($colorMap[$colorName]) ? $colorMap[$colorName] : '#CCCCCC';
                        ?>
                        <div class="color-option" data-color="<?php echo $colorName; ?>" style="background-color: <?php echo $colorCode; ?>; border: <?php echo $colorName == 'Putih' ? '1px solid #ddd' : 'none'; ?>;" title="<?php echo $colorName; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selectedColor" value="">
                </div>
                
                <div class="mb-4">
                    <h5>Custom Text (Opsional):</h5>
                    <input type="text" id="customText" class="form-control" placeholder="Contoh: Nama / Nomor punggung / Nama Tim" style="border: 2px solid #ddd; border-radius: 8px; padding: 10px;">
                    <small class="text-muted">Bisa diisi nama pemain, nomor punggung, atau teks khusus lainnya</small>
                </div>
                
                <div class="mb-4">
                    <h5>Jumlah Pesanan:</h5>
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-secondary btn-minus" style="border-radius: 8px; padding: 8px 15px;">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantityInput" class="quantity-input" value="1" min="1" readonly>
                        <button class="btn btn-outline-secondary btn-plus" style="border-radius: 8px; padding: 8px 15px;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid gap-3">
                    <button id="addToCartBtn" class="btn-add-to-cart">
                        <i class="fas fa-shopping-cart me-2"></i> Tambah ke Keranjang
                    </button>
                    <a href="customize.php" class="btn-customize text-center">
                        <i class="fas fa-palette me-2"></i> Custom Desain Khusus
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Additional Info Section -->
        <div class="product-info-section">
            <div class="row text-center">
                <div class="col-md-4">
                    <i class="fas fa-truck fa-2x" style="color: #004b9c;"></i>
                    <h5 class="mt-2">Free Ongkir</h5>
                    <p class="text-muted">Minimal pembelian 12 pcs</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-palette fa-2x" style="color: #004b9c;"></i>
                    <h5 class="mt-2">Free Custom Desain</h5>
                    <p class="text-muted">Desain sesuai keinginan</p>
                </div>
                <div class="col-md-4">
                    <i class="fas fa-clock fa-2x" style="color: #004b9c;"></i>
                    <h5 class="mt-2">Cepat Selesai</h5>
                    <p class="text-muted">Pengerjaan 3-5 hari</p>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if(!empty($related)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="mb-4">Produk Terkait</h4>
            </div>
            <?php foreach($related as $rel): ?>
            <div class="col-md-3 mb-4">
                <div class="related-product-card card h-100">
                    <?php if($rel['image'] && file_exists('uploads/' . $rel['image'])): ?>
                        <img src="uploads/<?php echo $rel['image']; ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <img src="img/persib home.png" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Placeholder">
                    <?php endif; ?>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo $rel['name']; ?></h6>
                        <p class="text-primary fw-bold" style="color: #004b9c !important;">Rp <?php echo number_format($rel['price'], 0, ',', '.'); ?></p>
                        <a href="product-detail.php?id=<?php echo $rel['id']; ?>" class="btn btn-sm" style="background-color: #004b9c; color: white;">Lihat Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Toast Notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Berhasil!</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                Produk berhasil ditambahkan ke keranjang!
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSize = null;
        let selectedColor = null;
        let quantity = 1;
        
        // Size selection
        document.querySelectorAll('.size-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.size-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectedSize = this.dataset.size;
            });
        });
        
        // Color selection
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                selectedColor = this.dataset.color;
                document.getElementById('selectedColor').value = selectedColor;
            });
        });
        
        // Quantity buttons
        const quantityInput = document.getElementById('quantityInput');
        const btnMinus = document.querySelector('.btn-minus');
        const btnPlus = document.querySelector('.btn-plus');
        
        btnMinus.addEventListener('click', function() {
            if(quantity > 1) {
                quantity--;
                quantityInput.value = quantity;
            }
        });
        
        btnPlus.addEventListener('click', function() {
            quantity++;
            quantityInput.value = quantity;
        });
        
        // Add to cart function
        document.getElementById('addToCartBtn').addEventListener('click', function() {
            <?php if(!isLoggedIn()): ?>
                if(confirm('Anda harus login terlebih dahulu. Login sekarang?')) {
                    window.location.href = 'login.php';
                }
                return;
            <?php endif; ?>
            
            if(!selectedSize) {
                alert('Silahkan pilih ukuran terlebih dahulu!');
                return;
            }
            
            const customText = document.getElementById('customText').value;
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', <?php echo $product['id']; ?>);
            formData.append('quantity', quantity);
            formData.append('size', selectedSize);
            formData.append('color', selectedColor || '');
            formData.append('custom_text', customText);
            
            fetch('add-to-cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Show toast notification
                    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                    toast.show();
                    
                    // Update cart count
                    updateCartCount();
                    
                    // Ask to go to cart
                    setTimeout(() => {
                        if(confirm('Produk berhasil ditambahkan ke keranjang! Lihat keranjang belanja?')) {
                            window.location.href = 'cart.php';
                        }
                    }, 500);
                } else {
                    alert(data.message || 'Gagal menambahkan ke keranjang!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan, silahkan coba lagi!');
            });
        });
        
        function updateCartCount() {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadges = document.querySelectorAll('.cart-badge');
                    cartBadges.forEach(badge => {
                        if(badge) badge.textContent = data.count || 0;
                    });
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>