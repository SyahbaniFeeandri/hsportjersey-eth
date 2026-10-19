<?php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/config/functions.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
if ($action === 'save_crypto_order') {
    $order_id = (int)($input['order_id'] ?? 0);
    $tx_hash = $input['tx_hash'] ?? '';
    $wallet_address = $input['wallet_address'] ?? '';
    if (!$order_id || !$tx_hash || !$wallet_address) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit();
    }
    $stmt = $pdo->prepare("UPDATE orders SET payment_type='crypto', payment_status='paid', tx_hash=?, wallet_address=?, status='paid', updated_at=NOW() WHERE id=?");
    $stmt->execute([$tx_hash, $wallet_address, $order_id]);
    echo json_encode(['success' => true, 'message' => 'Pembayaran tersimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}