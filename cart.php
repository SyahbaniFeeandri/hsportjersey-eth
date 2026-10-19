<?php
require_once 'config/database.php';
require_once 'config/functions.php';
if(!isLoggedIn()) {
    redirect('login.php');
}
// Proses checkout
if($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['checkout_qris']) || isset($_POST['checkout_crypto']))) {
    $user_id = $_SESSION['user_id'];
    $shipping_method = $_POST['shipping_method'];
    $shipping_address = $_POST['shipping_address'];
    
    // Get cart items
    $stmt = $pdo->prepare("
        SELECT c.*, p.price, p.name 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
    
    if(empty($cart_items)) {
        redirect('cart.php');
    }
    
    // Calculate total
    $total = 0;
    foreach($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Create order
    $order_number = generateOrderNumber();
    $payment_type = isset($_POST['checkout_crypto']) ? 'crypto' : 'fiat';
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, shipping_method, shipping_address, status, payment_status, payment_type) 
        VALUES (?, ?, ?, ?, ?, 'pending', 'unpaid', ?)
    ");
    $stmt->execute([$user_id, $order_number, $total, $shipping_method, $shipping_address, $payment_type]);
    $order_id = $pdo->lastInsertId();
    
    // Create order items
    foreach($cart_items as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, size, color, custom_text) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id, 
            $item['product_id'], 
            $item['quantity'], 
            $item['price'], 
            $item['size'], 
            $item['color'], 
            $item['custom_text']
        ]);
    }
    
    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Redirect berdasarkan metode pembayaran
    if(isset($_POST['checkout_crypto'])) {
        redirect('crypto-payment.php?order_id=' . $order_id);
    } else {
        redirect('generate-qr.php?order_id=' . $order_id);
    }
}
// Get cart items for display
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();
$total = 0;
foreach($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - H-Sport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        .cart-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            position: sticky;
            top: 100px;
        }
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #004b9c;
            transform: translateY(-2px);
        }
        .payment-method.selected {
            border-color: #004b9c;
            background-color: rgba(92, 79, 74, 0.05);
        }
        .qris-logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #004b9c 0%, #3a302c 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .qris-logo i {
            font-size: 28px;
            color: white;
        }
        .crypto-logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #627eea 0%, #3b5998 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .crypto-logo i {
            font-size: 28px;
            color: white;
        }
        .btn-checkout {
            background: linear-gradient(135deg, #004b9c 0%, #3a302c 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
        }
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(92, 79, 74, 0.4);
        }
        .btn-crypto {
            background: linear-gradient(135deg, #627eea 0%, #3b5998 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
            color: white;
        }
        .btn-crypto:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(98, 126, 234, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4"><i class="fas fa-shopping-cart me-2"></i> Keranjang Belanja</h2>
        
        <?php if(empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
                <h4>Keranjang Belanja Kosong</h4>
                <p class="text-muted">Yuk, mulai belanja jersey impian Anda!</p>
                <a href="products.php" class="btn btn-primary mt-3">
                    <i class="fas fa-shopping-bag"></i> Belanja Sekarang
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table cart-table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Jumlah</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                                                        <img src="uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                                    <?php else: ?>
                                                        <img src="img/persib home.png" alt="Placeholder" style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div class="ms-3">
                                                        <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                                                        <small class="text-muted">Ukuran: <?php echo $item['size']; ?></small><br>
                                                        <?php if($item['custom_text']): ?>
                                                            <small class="text-muted">Custom: <?php echo $item['custom_text']; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <form method="POST" class="d-flex gap-2">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                           min="1" style="width: 70px;" class="form-control form-control-sm" onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                            <td>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="3" class="text-end">Total:</td>
                                            <td colspan="2">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="cart-summary">
                        <h5 class="mb-3">Ringkasan Belanja</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Harga:</span>
                            <span class="fw-bold">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                        </div>
                        
                        <form method="POST" id="checkoutForm">
                            <div class="mb-3">
                                <label class="form-label">Metode Pengiriman</label>
                                <select name="shipping_method" class="form-control" required>
                                    <option value="JNE Reguler">JNE Reguler</option>
                                    <option value="J&T Express">J&T Express</option>
                                    <option value="SiCepat">SiCepat</option>
                                    <option value="Ambil di Toko">Ambil di Toko (Gratis)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Alamat Pengiriman</label>
                                <textarea name="shipping_address" class="form-control" rows="3" required placeholder="Masukkan alamat lengkap Anda..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Metode Pembayaran</label>
                                
                                <!-- QRIS Option -->
                                <div class="payment-method selected mb-2" id="qrisMethod" onclick="selectPayment('qris')">
                                    <div class="d-flex align-items-center">
                                        <div class="qris-logo">
                                            <i class="fas fa-qrcode"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">QRIS</h6>
                                            <small class="text-muted">Bayar pakai Gopay, OVO, Dana, atau Mobile Banking</small>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" name="payment_method" value="qris" checked>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Crypto Option -->
                                <div class="payment-method" id="cryptoMethod" onclick="selectPayment('crypto')">
                                    <div class="d-flex align-items-center">
                                        <div class="crypto-logo">
                                            <i class="fab fa-ethereum"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">Crypto (ETH)</h6>
                                            <small class="text-muted">Bayar pakai Ethereum via MetaMask</small>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" name="payment_method" value="crypto">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="checkout_qris" class="btn btn-checkout w-100 text-white mb-2">
                                <i class="fas fa-qrcode"></i> Bayar dengan QRIS
                            </button>
                            <button type="button" class="btn btn-crypto w-100" onclick="checkoutCrypto()">
                                <i class="fab fa-ethereum"></i> Bayar dengan Crypto (ETH)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPayment(method) {
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            document.getElementById(method + 'Method').classList.add('selected');
            document.querySelector(`input[name="payment_method"][value="${method}"]`).checked = true;
        }
        
        function checkoutCrypto() {
            const shippingAddress = document.querySelector('textarea[name="shipping_address"]').value;
            
            if (!shippingAddress.trim()) {
                alert('Mohon isi alamat pengiriman!');
                return;
            }
            
            const form = document.getElementById('checkoutForm');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'checkout_crypto';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
    </script>
</body>
</html>