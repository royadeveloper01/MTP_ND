<?php
require_once __DIR__ . '/db.php';

// Admins can't add to cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
<<<<<<< HEAD

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
=======
>>>>>>> 00620563f93c8868a6f9275f5bc47cde0a82ad74

// RECEIVE PRODUCT DATA
$id = isset($_POST['product_id']) ? trim($_POST['product_id']) : null;
$size = isset($_POST['size']) ? trim($_POST['size']) : 'default'; // Use 'default' if no size
$color = isset($_POST['color']) ? trim($_POST['color']) : 'default'; // Use 'default' if no color
$qty = 1; // Always add one at a time from the product page

if (!$id || (isset($_POST['size']) && $size === '') || (isset($_POST['color']) && $color === '')) {
    // Fallback redirect if no product ID, or if a size/color was required but not provided.
    // This prevents adding items with an empty selection.
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Verify product exists in database
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $stmt->close();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$stmt->close();

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
