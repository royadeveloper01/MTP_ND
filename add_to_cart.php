<?php
require_once __DIR__ . '/db.php';

// Admins can't add to cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// RECEIVE PRODUCT DATA
$id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
// Trim to remove accidental whitespace
$size = isset($_POST['size']) ? trim($_POST['size']) : 'default';
$color = isset($_POST['color']) ? trim($_POST['color']) : 'default';
$qty = 1; 

// Validation: prevent empty strings or missing IDs
if (!$id || $size === '' || $color === '') {
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

    /* Using an atomic query to prevent race conditions.
       This works because of the UNIQUE INDEX (user_id, product_id, size, color) 
       defined in your SQL migration.
    */
    $sql = "INSERT INTO cart (user_id, product_id, size, color, quantity) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
            
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissi", $user_id, $id, $size, $color, $qty);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Add to cart DB Error: " . $e->getMessage());
    }

} else {
    // 2. GUEST USER: Save to session cart
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Create a unique key for the session array based on variations
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