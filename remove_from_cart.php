<?php
require_once __DIR__ . '/db.php'; // Includes session_start() and DB connection

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($product_id) {
    // 1. Always remove from session (Handles Guests + ensures UI updates immediately)
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }

    // 2. If logged in, also remove from Database
    if (!empty($_SESSION['loggedin'])) {
        $user_id = $_SESSION['id'];
        // Use prepared statement for security
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: cart.php");
exit;
