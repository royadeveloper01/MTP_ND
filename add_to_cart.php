<?php
// add_to_cart.php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();

$id = isset($_POST['id']) ? trim($_POST['id']) : null;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product id']);
    exit;
}

if (!isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] = ['name' => $name, 'price' => $price, 'qty' => $qty];
} else {
    $_SESSION['cart'][$id]['qty'] += $qty;
}

$totalQty = array_sum(array_column($_SESSION['cart'], 'qty'));

echo json_encode(['success' => true, 'cart_count' => $totalQty]);
exit;
