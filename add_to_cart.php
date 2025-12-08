<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();

// RECEIVE PRODUCT DATA
$id = isset($_POST['product_id']) ? trim($_POST['product_id']) : null;
$size = isset($_POST['size']) ? trim($_POST['size']) : 'default'; // Use 'default' if no size
$qty = 1; // Always add one at a time from the product page

if (!$id) {
    // fallback redirect
    header("Location: index.php");
    exit;
}
// Create a unique key for the cart item based on product ID and size
$cart_key = $id . '-' . $size;

// ADD to session cart
if (!isset($_SESSION['cart'][$cart_key])) {
    $_SESSION['cart'][$cart_key] = ['product_id' => $id, 'size' => $size, 'qty' => $qty];
} else {
    $_SESSION['cart'][$cart_key]['qty'] += $qty;
}

// REDIRECT TO CART PAGE
header("Location: cart.php");
exit;
