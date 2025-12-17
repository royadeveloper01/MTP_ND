<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
    $is_logged_in = !empty($_SESSION['loggedin']) && !empty($_SESSION['id']);

    foreach ($_POST['qty'] as $key => $new_qty) {
        $new_qty = (int)$new_qty;
        
        if ($is_logged_in) {
            // 1. LOGGED IN USER: Update the database
            $user_id = $_SESSION['id'];
            
            if ($new_qty <= 0) {
                // Remove item if quantity is 0 or less
                $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $key, $user_id);
            } else {
                // Update quantity
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("iii", $new_qty, $key, $user_id);
            }
            $stmt->execute();
            $stmt->close();
            
        } else {
            // 2. GUEST USER: Update the session
            if (isset($_SESSION['cart'][$key])) {
                if ($new_qty <= 0) {
                    unset($_SESSION['cart'][$key]);
                } else {
                    $_SESSION['cart'][$key]['qty'] = $new_qty;
                }
            }
        }
    }
    $_SESSION['cart_success'] = "Cart quantities updated successfully.";
}

// Redirect back to the cart
header("Location: " . BASE_URL . "/cart.php");
exit;