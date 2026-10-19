<?php
require_once 'config/database.php';
require_once 'config/functions.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get total quantity of items in cart (SUM of all quantities)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch();

$count = (int)$result['total'];

echo json_encode(['count' => $count]);
?>