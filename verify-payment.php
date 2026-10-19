<?php
require_once 'config/database.php';
require_once 'config/functions.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Harap login terlebih dahulu']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$payment_code = isset($data['payment_code']) ? $data['payment_code'] : '';

if(!$order_id || !$payment_code) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit();
}

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
    exit();
}

// Check if already paid
if($order['payment_status'] == 'paid') {
    echo json_encode(['success' => true, 'message' => 'Pembayaran sudah dikonfirmasi']);
    exit();
}

// Proses pembayaran (simulasi berhasil)
// Update status: payment_status = paid, status = shipped
$stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', status = 'shipped', updated_at = NOW() WHERE id = ?");
$stmt->execute([$order_id]);

// Insert payment record
$stmt = $pdo->prepare("INSERT INTO payments (order_id, payment_method, amount, status, verified_at) VALUES (?, 'QRIS', ?, 'verified', NOW())");
$stmt->execute([$order_id, $order['total_amount']]);

echo json_encode([
    'success' => true, 
    'message' => 'Pembayaran berhasil dikonfirmasi! Pesanan akan segera dikirim.'
]);
?>