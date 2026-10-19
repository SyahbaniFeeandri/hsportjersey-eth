<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateOrderNumber() {
    return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
}
?>