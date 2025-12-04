<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();

// RECEIVE PRODUCT DATA
$id = isset($_POST['product_id']) ? trim($_POST['product_id']) : null;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

if (!$id) {
    // fallback redirect
    header("Location: index.php");
    exit;
}

// ADD to session cart
if (!isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] = ['name' => $name, 'price' => $price, 'qty' => $qty];
} else {
    $_SESSION['cart'][$id]['qty'] += $qty;
}

// REDIRECT TO CART PAGE
header("Location: cart.php");
exit;
