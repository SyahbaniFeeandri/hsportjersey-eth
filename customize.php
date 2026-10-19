<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['design_image'])) {
    $target_dir = "uploads/custom/";
    if(!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . $_FILES['design_image']['name'];
    $target_file = $target_dir . $file_name;
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    if(in_array($_FILES['design_image']['type'], $allowed_types)) {
        if(move_uploaded_file($_FILES['design_image']['tmp_name'], $target_file)) {
            $stmt = $pdo->prepare("INSERT INTO custom_designs (user_id, design_image) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $file_name]);
            $success = "Desain berhasil diupload!";
        } else {
            $error = "Gagal mengupload desain!";
        }
    } else {
        $error = "Format file tidak didukung! Gunakan JPG, PNG, atau GIF.";
    }
}

// Get user's custom designs
$stmt = $pdo->prepare("SELECT * FROM custom_designs WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$custom_designs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Desain Jersey - H-Sport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            border: 2px solid #ddd;
            transition: all 0.3s;
        }
        .color-preview:hover {
            transform: scale(1.1);
            border-color: #004b9c;
        }
        .design-canvas {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            min-height: 300px;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }
        .design-preview-item {
            position: relative;
            display: inline-block;
            margin: 5px;
        }
        .remove-text {
            position: absolute;
            top: -8px;
            right: -8px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }
        .selected-design {
            border: 2px solid #004b9c;
            background-color: #f0f0f0;
        }
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
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
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4"><i class="fas fa-palette me-2"></i> Custom Desain Jersey</h2>
                <p class="text-muted">Buat desain jersey sesuai keinginan Anda. Pilih warna, tambahkan teks, atau upload desain sendiri!</p>
            </div>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-paint-brush me-2"></i> Desain Jersey Anda</h5>
                    </div>
                    <div class="card-body">
                        <div class="design-canvas text-center" id="designCanvas">
                            <div id="jerseyTemplate">
                                <i class="fas fa-tshirt fa-5x text-muted mb-3 d-block"></i>
                                <svg id="jerseySvg" width="300" height="300" viewBox="0 0 300 300" style="display: none;">
                                    <rect id="jerseyBody" x="50" y="50" width="200" height="200" rx="20" fill="#f0f0f0" stroke="#ccc" stroke-width="2"/>
                                    <path id="leftSleeve" d="M50,70 L20,50 L20,100 L50,120 Z" fill="#f0f0f0" stroke="#ccc" stroke-width="2"/>
                                    <path id="rightSleeve" d="M250,70 L280,50 L280,100 L250,120 Z" fill="#f0f0f0" stroke="#ccc" stroke-width="2"/>
                                    <circle id="neckCollor" cx="150" cy="50" r="20" fill="none" stroke="#ccc" stroke-width="2"/>
                                </svg>
                            </div>
                            <div id="designPreview" class="mt-3"></div>
                        </div>
                        
                        <hr>
                        
                        <h6><i class="fas fa-palette me-2"></i> Pilih Warna Jersey:</h6>
                        <div class="d-flex flex-wrap mb-3">
                            <?php
                            $colors = [
                                'Merah' => '#FF0000', 
                                'Biru' => '#0000FF', 
                                'Hijau' => '#00FF00', 
                                'Kuning' => '#FFFF00', 
                                'Hitam' => '#000000', 
                                'Putih' => '#FFFFFF',
                                'Ungu' => '#800080',
                                'Orange' => '#FFA500',
                                'Abu-abu' => '#808080',
                                'Biru Persib' => '#004b9c'
                            ];
                            foreach($colors as $name => $code):
                            ?>
                            <div class="color-preview" style="background-color: <?php echo $code; ?>; border: <?php echo $code == '#FFFFFF' ? '1px solid #ddd' : 'none'; ?>;" 
                                 title="<?php echo $name; ?>" onclick="changeColor('<?php echo $code; ?>')"></div>
                            <?php endforeach; ?>
                            <div class="d-flex align-items-center ms-2" title="Pilih Warna Kustom">
                                <input type="color" id="customColorPicker" class="form-control form-control-color p-1" value="#004b9c" oninput="changeColor(this.value)" style="width: 40px; height: 40px; cursor: pointer;" title="Color Wheel">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-font me-2"></i> Teks Kustom:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="customText" placeholder="Masukkan nama / nomor punggung">
                                <button class="btn btn-secondary" type="button" onclick="addText()">
                                    <i class="fas fa-plus"></i> Tambah
                                </button>
                            </div>
                            <small class="text-muted">Contoh: NAMA TIM, NO PUNGGUNG, dll.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-upload me-2"></i> Upload Desain Sendiri:</label>
                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                <input type="file" class="form-control mb-2" name="design_image" accept="image/*" id="designImage">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-cloud-upload-alt"></i> Upload Desain
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i> Info Pemesanan</h5>
                    </div>
                    <div class="card-body">
                        <form id="customOrderForm">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-tshirt me-2"></i> Jenis Jersey *</label>
                                <select class="form-control" id="product_id" required>
                                    <option value="">-- Pilih Jenis Jersey --</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT MIN(id) as id, category, MIN(price) as price FROM products GROUP BY category ORDER BY category");
                                    while($row = $stmt->fetch()):
                                    ?>
                                    <option value="<?php echo $row['id']; ?>" data-price="<?php echo $row['price']; ?>" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-ruler-combined me-2"></i> Ukuran *</label>
                                <select class="form-control" id="size" required>
                                    <option value="">-- Pilih Ukuran --</option>
                                    <option value="S">S (Small)</option>
                                    <option value="M">M (Medium)</option>
                                    <option value="L">L (Large)</option>
                                    <option value="XL">XL (Extra Large)</option>
                                    <option value="XXL">XXL (Double Extra Large)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i> Jumlah *</label>
                                <input type="number" class="form-control" id="quantity" value="1" min="1" required>
                            </div>
                            
                            <input type="hidden" id="design_data" value="">
                            <input type="hidden" id="selected_color" value="">
                            
                            <button type="button" class="btn btn-primary w-100" onclick="addToCart()" style="background-color: #004b9c; border: none;">
                                <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                            </button>
                        </form>
                        
                        <?php if(!empty($custom_designs)): ?>
                        <hr>
                        <h6><i class="fas fa-history me-2"></i> Desain Sebelumnya:</h6>
                        <div class="row">
                            <?php foreach($custom_designs as $design): ?>
                            <div class="col-4 mb-2">
                                <img src="uploads/custom/<?php echo $design['design_image']; ?>" 
                                     class="img-fluid rounded border" 
                                     style="cursor: pointer; height: 80px; width: 100%; object-fit: cover;"
                                     onclick="usePreviousDesign('uploads/custom/<?php echo $design['design_image']; ?>')"
                                     title="Klik untuk menggunakan desain ini">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedColor = '#f0f0f0';
        let textElements = [];
        
        // Change jersey color
        function changeColor(color) {
            selectedColor = color;
            document.getElementById('designCanvas').style.backgroundColor = color;
            document.getElementById('selected_color').value = color;
            
            // Add visual feedback
            const preview = document.getElementById('designPreview');
            const colorIndicator = document.createElement('div');
            colorIndicator.className = 'alert alert-info mt-2';
            colorIndicator.innerHTML = '<i class="fas fa-palette"></i> Warna jersey diubah';
            preview.appendChild(colorIndicator);
            setTimeout(() => colorIndicator.remove(), 2000);
        }
        
        // Add text to design
        function addText() {
            const text = document.getElementById('customText').value.trim();
            if(text) {
                const preview = document.getElementById('designPreview');
                const textId = 'text_' + Date.now();
                const textDiv = document.createElement('div');
                textDiv.id = textId;
                textDiv.className = 'alert alert-info mt-2 design-preview-item';
                textDiv.innerHTML = `
                    <i class="fas fa-font"></i> ${text}
                    <span class="remove-text" onclick="removeText('${textId}')">×</span>
                `;
                preview.appendChild(textDiv);
                textElements.push({id: textId, text: text});
                document.getElementById('customText').value = '';
                
                // Show success
                showToast('Teks berhasil ditambahkan!', 'success');
            } else {
                showToast('Masukkan teks terlebih dahulu!', 'warning');
            }
        }
        
        // Remove text
        function removeText(textId) {
            const element = document.getElementById(textId);
            if(element) {
                element.remove();
                textElements = textElements.filter(t => t.id !== textId);
                showToast('Teks dihapus!', 'info');
            }
        }
        
        // Use previous design
        function usePreviousDesign(imageUrl) {
            const preview = document.getElementById('designPreview');
            const imgDiv = document.createElement('div');
            imgDiv.className = 'mt-2';
            imgDiv.innerHTML = `<img src="${imageUrl}" class="img-fluid rounded" style="max-height: 150px;">`;
            preview.appendChild(imgDiv);
            showToast('Desain sebelumnya digunakan!', 'success');
        }
        
        // Update cart badge function
        function updateCartBadge() {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge && data.count !== undefined) {
                        cartBadge.textContent = data.count;
                        if (data.count === 0) {
                            cartBadge.style.display = 'inline-block';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Add to cart function
        function addToCart() {
            const product_id = document.getElementById('product_id').value;
            const size = document.getElementById('size').value;
            const quantity = document.getElementById('quantity').value;
            const custom_text = textElements.map(t => t.text).join(', ');
            const color = selectedColor;
            
            // Validation
            if(!product_id) {
                showToast('Silahkan pilih jenis jersey terlebih dahulu!', 'warning');
                return;
            }
            
            if(!size) {
                showToast('Silahkan pilih ukuran jersey!', 'warning');
                return;
            }
            
            if(quantity < 1) {
                showToast('Jumlah minimal 1!', 'warning');
                return;
            }
            
            // Show loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', product_id);
            formData.append('quantity', quantity);
            formData.append('size', size);
            formData.append('color', color);
            formData.append('custom_text', custom_text);
            
            fetch('add-to-cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                if(data.success) {
                    showToast('Produk berhasil ditambahkan ke keranjang!', 'success');
                    
                    // Update cart badge with the new count from response
                    if(data.cart_count !== undefined) {
                        const cartBadge = document.getElementById('cartCount');
                        if (cartBadge) {
                            cartBadge.textContent = data.cart_count;
                        }
                    } else {
                        // Fallback: fetch updated count
                        updateCartBadge();
                    }
                    
                    // Reset form after successful add (optional)
                    if(confirm('Produk ditambahkan! Lihat keranjang sekarang?')) {
                        window.location.href = 'cart.php';
                    }
                } else {
                    showToast(data.message || 'Gagal menambahkan ke keranjang!', 'danger');
                }
            })
            .catch(error => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                console.error('Error:', error);
                showToast('Terjadi kesalahan pada server!', 'danger');
            });
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show`;
            toast.style.animation = 'slideIn 0.3s ease';
            toast.style.marginTop = '10px';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'exclamation-triangle' : 'times-circle')} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            toastContainer.appendChild(toast);
            
            setTimeout(() => {
                toast.style.transition = 'opacity 0.5s ease';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
        
        // Auto update cart badge on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();
            
            // Auto select product price display
            document.getElementById('product_id').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if(selectedOption.value !== "") {
                    const category = selectedOption.getAttribute('data-category');
                    updateJerseyShape(category);
                } else {
                    updateJerseyShape('');
                }
            });
        });
        
        // Update SVG shape based on category
        function updateJerseyShape(category) {
            const leftSleeve = document.getElementById('leftSleeve');
            const rightSleeve = document.getElementById('rightSleeve');
            const body = document.getElementById('jerseyBody');
            
            // Default (Sepak Bola / Futsal - short sleeves)
            leftSleeve.style.display = 'block';
            rightSleeve.style.display = 'block';
            leftSleeve.setAttribute('d', 'M50,70 L20,50 L20,100 L50,120 Z');
            rightSleeve.setAttribute('d', 'M250,70 L280,50 L280,100 L250,120 Z');
            body.setAttribute('rx', '20');
            
            if (!category) return;
            
            const cat = category.toLowerCase();
            if (cat.includes('basket') || cat.includes('lari')) {
                // Sleeveless (Tank top)
                leftSleeve.style.display = 'none';
                rightSleeve.style.display = 'none';
                body.setAttribute('rx', '40'); // rounder shoulders
            } else if (cat.includes('sepeda')) {
                // Tight fit, longer aerodynamic sleeves
                leftSleeve.setAttribute('d', 'M50,70 L10,70 L10,140 L50,130 Z');
                rightSleeve.setAttribute('d', 'M250,70 L290,70 L290,140 L250,130 Z');
                body.setAttribute('rx', '15');
            } else if (cat.includes('padel')) {
                // Shorter sleeves
                leftSleeve.setAttribute('d', 'M50,70 L25,60 L25,90 L50,100 Z');
                rightSleeve.setAttribute('d', 'M250,70 L275,60 L275,90 L250,100 Z');
            }
        }
        
        // Reset button loading state if needed
        window.addEventListener('beforeunload', function() {
            // Cleanup if needed
        });
    </script>
</body>
</html>