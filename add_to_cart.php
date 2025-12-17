<?php
require_once __DIR__ . '/db.php';

// Admins can't add to cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// RECEIVE PRODUCT DATA
$id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
// We trim here to remove accidental whitespace
$size = isset($_POST['size']) ? trim($_POST['size']) : 'default';
$color = isset($_POST['color']) ? trim($_POST['color']) : 'default';
$qty = 1; 

// FIXED: Now using the trimmed $size and $color variables for validation.
// This prevents inputs consisting only of spaces from passing the check.
if (!$id || (isset($_POST['size']) && $size === '') || (isset($_POST['color']) && $color === '')) {
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

// --- LOGIC START: DATABASE VS SESSION ---

if (!empty($_SESSION['loggedin']) && !empty($_SESSION['id'])) {
    // 1. LOGGED IN USER: Save directly to database
    $user_id = $_SESSION['id'];

    $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ? AND color = ?");
    $check_stmt->bind_param("iiss", $user_id, $id, $size, $color);
    $check_stmt->execute();
    $res = $check_stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $new_qty = $row['quantity'] + $qty;
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_qty, $row['id']);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, size, color, quantity) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("iissi", $user_id, $id, $size, $color, $qty);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();

} else {
    // 2. GUEST USER: Save to session cart
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
    
    // The keys will now be cleaner because $size and $color are trimmed
    $cart_key = $id . '-' . $size . '-' . $color;

    if (!isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $id, 
            'size' => $size, 
            'color' => $color, 
            'qty' => $qty
        ];
    } else {
        $_SESSION['cart'][$cart_key]['qty'] += $qty;
    }
}

// REDIRECT TO CART PAGE
header('Location: ' . BASE_URL . '/cart.php');
exit;