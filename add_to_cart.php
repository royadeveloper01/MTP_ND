<?php
require_once __DIR__ . '/db.php';

// Admins can't add to cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// RECEIVE PRODUCT DATA
$id = isset($_POST['product_id']) ? trim($_POST['product_id']) : null;
$size = isset($_POST['size']) ? trim($_POST['size']) : 'default'; // Use 'default' if no size
$color = isset($_POST['color']) ? trim($_POST['color']) : 'default'; // Use 'default' if no color
$qty = 1; // Always add one at a time from the product page

if (!$id || (isset($_POST['size']) && $size === '') || (isset($_POST['color']) && $color === '')) {
    // Fallback redirect if no product ID, or if a size/color was required but not provided.
    // This prevents adding items with an empty selection.
    header("Location: index.php");
    exit;
}
// Create a unique key for the cart item based on product ID, size, and color
$cart_key = $id . '-' . $size . '-' . $color;

// ADD to session cart
if (!isset($_SESSION['cart'][$cart_key])) {
    $_SESSION['cart'][$cart_key] = ['product_id' => $id, 'size' => $size, 'color' => $color, 'qty' => $qty];
} else {
    $_SESSION['cart'][$cart_key]['qty'] += $qty;
}

// REDIRECT TO CART PAGE
header('Location: ' . BASE_URL . '/cart.php');
exit;
