<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 8";
$stmt = $pdo->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>H-Sport Jersey - Custom Jersey Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('img/banner.webp');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0;
            margin-top: 50px;
        }
        .product-price {
            font-weight: bold;
            color: #004b9c;
            font-size: 1.1rem;
            margin: 10px 0;
        }
        
        /* Tombol Detail */
        .btn-detail {
            background-color: #004b9c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 500;
            flex: 1;
        }
        .btn-detail:hover {
            background-color: #003366;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Tombol Pesan */
        .btn-order {
            background-color: #004b9c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 500;
            flex: 1;
        }
        .btn-order:hover {
            background-color: #003366;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-group-card {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .product-card-clickable {
            cursor: pointer;
        }
        
        /* Modal Quick Order */
        .modal-quick-order {
            border-radius: 15px;
        }
        .modal-quick-order .modal-header {
            background-color: #004b9c;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .size-option-quick {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .size-option-quick:hover, .size-option-quick.selected {
            background-color: #004b9c;
            color: white;
            border-color: #004b9c;
        }
        .btn-add-to-cart-quick {
            background-color: #004b9c;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-add-to-cart-quick:hover {
            background-color: #003366;
            transform: translateY(-2px);
        }
        .quick-order-img {
            max-width: 100%;
            border-radius: 10px;
        }
        .toast-header.bg-success {
            background-color: #004b9c !important;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<section class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-4">Custom Jersey Profesional</h1>
        <p class="lead mb-4">Desain sendiri jersey tim Anda dengan kualitas terbaik</p>
        <a href="products.php" class="btn btn-light btn-lg">Lihat Katalog</a>
        <a href="customize.php" class="btn btn-outline-light btn-lg ms-2">Custom Desain</a>
    </div>
</section>

<div class="container mt-5">
    <h2 class="text-center mb-4">Produk Terbaru</h2>
    <div class="row">
        <?php foreach($products as $product): ?>
        <div class="col-md-3 mb-4">
            <div class="product-card card h-100">
                <div class="product-card-clickable" onclick="goToProductDetail(<?php echo $product['id']; ?>)">
                    <?php if($product['image'] && file_exists('uploads/' . $product['image'])): ?>
                        <img src="uploads/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <img src="img/persib home.png" class="card-img-top" alt="Placeholder" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="category-badge"><?php echo $product['category']; ?></span>
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <p class="card-text text-muted">Bahan: <?php echo $product['material']; ?></p>
                        <p class="product-price fw-bold">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pb-3">
                    <div class="btn-group-card">
                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn-detail">
                            <i class="fas fa-eye me-1"></i> Detail
                        </a>
                        <button onclick="openQuickOrder(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="btn-order">
                            <i class="fas fa-shopping-cart me-1"></i> Pesan
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Quick Order -->
<div class="modal fade" id="quickOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-quick-order">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2"></i>Pesan Produk
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="quickOrderContent">
                <!-- Content will be filled by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-add-to-cart-quick" onclick="addToCartFromModal()">
                    <i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang
                </button>
            </div>
        </div>
    </div>
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
    let currentProduct = null;
    let selectedSize = '';
    let selectedQuantity = 1;
    
    function goToProductDetail(productId) {
        window.location.href = 'product-detail.php?id=' + productId;
    }
    
    function openQuickOrder(product) {
        event.stopPropagation();
        currentProduct = product;
        selectedSize = '';
        selectedQuantity = 1;
        
        const sizes = product.sizes ? product.sizes.split(',') : ['S', 'M', 'L', 'XL', 'XXL'];
        const colors = product.colors ? product.colors.split(',') : ['Merah', 'Biru', 'Hijau', 'Kuning'];
        
        const content = `
            <div class="row">
                <div class="col-md-5">
                    ${product.image && product.image !== '' && product.image !== null ? 
                        `<img src="uploads/${product.image}" class="quick-order-img" alt="${product.name}" style="width:100%; border-radius:10px;">` :
                        `<img src="img/persib home.png" class="quick-order-img" alt="Placeholder" style="width:100%; border-radius:10px;">`
                    }
                </div>
                <div class="col-md-7">
                    <h4>${product.name}</h4>
                    <p class="text-muted">Bahan: ${product.material}</p>
                    <p class="text-muted">Kategori: ${product.category}</p>
                    <h3 class="product-price mb-3">Rp ${formatRupiah(product.price)}</h3>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Ukuran:</label>
                        <div id="sizeOptions">
                            ${sizes.map(size => `
                                <div class="size-option-quick" data-size="${size.trim()}" onclick="selectSize('${size.trim()}')">
                                    ${size.trim()}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Warna:</label>
                        <select class="form-select" id="colorSelect">
                            ${colors.map(color => `<option value="${color.trim()}">${color.trim()}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jumlah:</label>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(-1)">-</button>
                            <input type="number" id="quantityInput" class="form-control text-center" style="width: 80px;" value="1" min="1" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Custom Text (Opsional):</label>
                        <input type="text" id="customText" class="form-control" placeholder="Nama / Nomor punggung">
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> 
                        <small>Butuh desain khusus? <a href="customize.php" target="_blank">Klik di sini</a> untuk custom desain</small>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('quickOrderContent').innerHTML = content;
        new bootstrap.Modal(document.getElementById('quickOrderModal')).show();
    }
    
    function selectSize(size) {
        selectedSize = size;
        document.querySelectorAll('.size-option-quick').forEach(opt => {
            opt.classList.remove('selected');
            if(opt.getAttribute('data-size') === size) {
                opt.classList.add('selected');
            }
        });
    }
    
    function changeQuantity(delta) {
        const input = document.getElementById('quantityInput');
        let newVal = parseInt(input.value) + delta;
        if(newVal >= 1) {
            input.value = newVal;
            selectedQuantity = newVal;
        }
    }
    
    function formatRupiah(amount) {
        return new Intl.NumberFormat('id-ID').format(amount);
    }
    
    function addToCartFromModal() {
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
        
        const color = document.getElementById('colorSelect') ? document.getElementById('colorSelect').value : '';
        const customText = document.getElementById('customText') ? document.getElementById('customText').value : '';
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', currentProduct.id);
        formData.append('quantity', selectedQuantity);
        formData.append('size', selectedSize);
        formData.append('color', color);
        formData.append('custom_text', customText);
        
        fetch('add-to-cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                bootstrap.Modal.getInstance(document.getElementById('quickOrderModal')).hide();
                
                const toast = new bootstrap.Toast(document.getElementById('liveToast'));
                toast.show();
                
                updateCartCount();
                
                setTimeout(() => {
                    if(confirm('Produk berhasil ditambahkan! Lihat keranjang belanja?')) {
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
    }
    
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
    
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
    });
</script>
</body>
</html>