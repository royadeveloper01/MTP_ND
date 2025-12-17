<?php
require_once __DIR__ . '/db.php';

$key = $_GET['key'] ?? null;

if ($key) {
    if (!empty($_SESSION['loggedin']) && !empty($_SESSION['id'])) {
        // 1. LOGGED IN USER: Delete from database
        try {
            $user_id = $_SESSION['id'];
            // The $key passed from cart.php for logged-in users is the primary ID of the cart row
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $key, $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['cart_success'] = "Item removed from your cart.";
        } catch (Exception $e) {
            error_log("Error removing from DB cart (key: {$key}, user_id: {$user_id}): " . $e->getMessage());
            $_SESSION['cart_error'] = "Could not remove the item from your cart. Please try again.";
        }
    } else {
        // 2. GUEST USER: Remove from session
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart_success'] = "Item removed from your cart.";
        } else {
            // Optional: if key not found for guest, still provide feedback
            $_SESSION['cart_error'] = "Item not found in your cart.";
        }
    }
}

// Redirect back to the cart page
header("Location: " . BASE_URL . "/cart.php");
exit;