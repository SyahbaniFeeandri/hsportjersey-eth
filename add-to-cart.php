<?php
require_once 'config/database.php';
require_once 'config/functions.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Harap login terlebih dahulu', 'cart_count' => 0]);
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = isset($_POST['size']) ? $_POST['size'] : '';
    $color = isset($_POST['color']) ? $_POST['color'] : '';
    $custom_text = isset($_POST['custom_text']) ? $_POST['custom_text'] : '';
    
    // Validate product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if(!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan!', 'cart_count' => 0]);
        exit();
    }
    
    // Cek apakah produk sudah ada di cart dengan size yang sama
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
    $stmt->execute([$user_id, $product_id, $size]);
    $existing = $stmt->fetch();
    
    if($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $existing['id']]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, size, color, custom_text) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity, $size, $color, $custom_text]);
    }
    
    // Get updated cart count (total quantity of items)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $cart_count = (int)$result['total'];
    
    echo json_encode([
        'success' => true, 
        'cart_count' => $cart_count,
        'message' => 'Produk berhasil ditambahkan ke keranjang'
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request', 'cart_count' => 0]);
?>