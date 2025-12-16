<?php
require_once __DIR__ . '/db.php';

// Admins can't add to cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!$product_id || !$qty) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Verify product exists in database before adding to cart
try {
    $verify_stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
    $verify_stmt->bind_param('i', $product_id);
    $verify_stmt->execute();
    $product_result = $verify_stmt->get_result();
    $product = $product_result->fetch_assoc();
    $verify_stmt->close();

    // If product doesn't exist, redirect back to index
    if (!$product) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// --- LOGIC: DB for logged-in users, Session for guests ---

if (!empty($_SESSION['loggedin']) && !empty($_SESSION['id'])) {
    // --- LOGGED-IN USER: Use database ---
    $user_id = (int)$_SESSION['id'];

    try {
        // Use INSERT...ON DUPLICATE KEY UPDATE to add or update quantity
        $sql = "INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('iii', $user_id, $product_id, $qty);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();

    } catch (Exception $e) {
        // Log error and redirect on failure
        error_log("Cart DB Error: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/index.php?error=add_cart_failed');
        exit;
    }

// ADD to session cart
if (!isset($_SESSION['cart'][$cart_key])) {
    $_SESSION['cart'][$cart_key] = ['product_id' => $id, 'size' => $size, 'color' => $color, 'qty' => $qty];
} else {
    // --- GUEST USER: Use session ---
    // Use verified product data from database instead of form input
    $name = $product['name'];
    $price = (float)$product['price'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = [
            'name' => $name,
            'price' => $price,
            'qty' => $qty
        ];
    } else {
        $_SESSION['cart'][$product_id]['qty'] += $qty;
    }
}

// REDIRECT + TOAST FLAG
$_SESSION['cart_success'] = true;
header('Location: ' . BASE_URL . '/index.php');
exit;

