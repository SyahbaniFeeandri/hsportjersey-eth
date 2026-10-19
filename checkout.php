<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $shipping_method = $_POST['shipping_method'];
    $shipping_address = $_POST['shipping_address'];
    $payment_method = $_POST['payment_method'];
    
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
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, shipping_method, shipping_address, status, payment_status) 
        VALUES (?, ?, ?, ?, ?, 'pending', 'unpaid')
    ");
    $stmt->execute([$user_id, $order_number, $total, $shipping_method, $shipping_address]);
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
    
    // Redirect to QRIS payment page
    redirect('generate-qr.php?order_id=' . $order_id);
}

redirect('cart.php');
?>