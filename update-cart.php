<?php
require_once 'config/database.php';
require_once 'config/functions.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Harap login terlebih dahulu']);
    exit();
}

$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if($action === 'update') {
        $cart_id = $_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];
        
        // Update quantity
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $user_id]);
        
        // Get updated subtotal
        $stmt = $pdo->prepare("
            SELECT c.quantity, p.price 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$cart_id, $user_id]);
        $item = $stmt->fetch();
        
        $new_subtotal = $item['quantity'] * $item['price'];
        
        // Get new total
        $stmt = $pdo->prepare("
            SELECT SUM(c.quantity * p.price) as total 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        $new_total = $result['total'];
        
        echo json_encode([
            'success' => true,
            'new_subtotal' => $new_subtotal,
            'new_total' => $new_total
        ]);
        
    } elseif($action === 'remove') {
        $cart_id = $_POST['cart_id'];
        
        // Delete item
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        
        // Get new total
        $stmt = $pdo->prepare("
            SELECT SUM(c.quantity * p.price) as total 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        $new_total = $result['total'] ? $result['total'] : 0;
        
        echo json_encode([
            'success' => true,
            'new_total' => $new_total
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>