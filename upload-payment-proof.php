<?php
require_once 'config/database.php';
require_once 'config/functions.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success'=>false,'message'=>'Harap login terlebih dahulu']);
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if(!$order_id || !isset($_FILES['payment_proof'])) {
    echo json_encode(['success'=>false,'message'=>'Data tidak valid']);
    exit();
}

// Verify order belongs to user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    echo json_encode(['success'=>false,'message'=>'Pesanan tidak ditemukan']);
    exit();
}

if($order['payment_status'] === 'paid') {
    echo json_encode(['success'=>true,'message'=>'Pembayaran sudah dikonfirmasi sebelumnya']);
    exit();
}

// Validate file
$file = $_FILES['payment_proof'];
$allowed_types = ['image/jpeg','image/png','image/webp','image/gif'];
$max_size = 2 * 1024 * 1024; // 2MB

if($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'Gagal mengupload file']);
    exit();
}
if(!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success'=>false,'message'=>'Format file tidak didukung (JPG/PNG/WEBP saja)']);
    exit();
}
if($file['size'] > $max_size) {
    echo json_encode(['success'=>false,'message'=>'Ukuran file terlalu besar (maks 2MB)']);
    exit();
}

// Save file
$upload_dir = 'uploads/payments/';
if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = time() . '_' . $order_id . '.' . $ext;
$filepath = $upload_dir . $filename;

if(!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success'=>false,'message'=>'Gagal menyimpan file']);
    exit();
}

// Check if payment record exists
$stmt = $pdo->prepare("SELECT id FROM payments WHERE order_id=?");
$stmt->execute([$order_id]);
$existing = $stmt->fetch();

if($existing) {
    $stmt = $pdo->prepare("UPDATE payments SET payment_proof=?, status='pending', payment_method='Transfer/QRIS' WHERE order_id=?");
    $stmt->execute([$filename, $order_id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, payment_method, payment_proof, amount, status) VALUES (?, 'Transfer/QRIS', ?, ?, 'pending')");
    $stmt->execute([$order_id, $filename, $order['total_amount']]);
}

// Update order to waiting verification
$stmt = $pdo->prepare("UPDATE orders SET payment_status='unpaid', status='pending', updated_at=NOW() WHERE id=?");
$stmt->execute([$order_id]);

echo json_encode([
    'success' => true,
    'message' => 'Bukti pembayaran berhasil dikirim! Admin akan memverifikasi dalam 1x24 jam.'
]);
?>
