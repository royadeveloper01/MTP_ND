<?php
require_once __DIR__ . '/db.php'; 

if (!empty($_SESSION['is_admin'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// RECEIVE PRODUCT DATA
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
    'options' => ['default' => 1, 'min_range' => 1]
]);

if (!$product_id || !$quantity) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// VERIFY PRODUCT
$stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// LOGIC: USER -> DB | GUEST -> SESSION
if (!empty($_SESSION['loggedin']) && !empty($_SESSION['id'])) {

    $user_id = (int)$_SESSION['id'];

    $sql = "INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $user_id, $product_id, $quantity);
    $stmt->execute();
    $stmt->close();

} else {

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = [
            'name'  => $product['name'],
            'price'=> (float)$product['price'],
            'quantity'  => $quantity
        ];
    } else {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    }
}

// REDIRECT
header("Location: " . BASE_URL . "/cart.php");
exit;
