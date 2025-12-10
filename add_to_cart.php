<?php
require_once __DIR__ . '/db.php'; // Includes session_start()

// Admins can't add to cart
if (!empty($_SESSION['is_admin'])) {
    header("Location: index.php");
    exit;
}

// RECEIVE PRODUCT DATA
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

if (!$product_id) {
    header("Location: index.php");
    exit;
}

// --- LOGIC: DB for logged-in users, Session for guests ---

if (!empty($_SESSION['loggedin'])) {
    // --- LOGGED-IN USER: Use database ---
    $user_id = $_SESSION['id'];

    try {
        // Use INSERT...ON DUPLICATE KEY UPDATE to add or update quantity
        $sql = "INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iii', $user_id, $product_id, $qty);
        $stmt->execute();
        $stmt->close();

    } catch (Exception $e) {
        // Optional: handle DB error, e.g., log it or show a message
    }

} else {
    // --- GUEST USER: Use session ---
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = ['name' => $name, 'price' => $price, 'qty' => $qty];
    } else {
        $_SESSION['cart'][$product_id]['qty'] += $qty;
    }
}

// REDIRECT TO CART PAGE
header("Location: cart.php");
exit;
