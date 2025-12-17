<?php
require_once __DIR__ . '/db.php';

// Admins don't use the cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty']) && is_array($_POST['qty'])) {
    
    $is_logged_in = !empty($_SESSION['loggedin']) && !empty($_SESSION['id']);

    if ($is_logged_in) {
        // --- LOGIC FOR LOGGED IN USER: Update Database ---
        $user_id = $_SESSION['id'];

        foreach ($_POST['qty'] as $product_id => $new_qty) {
            $new_qty = max(1, (int)$new_qty); // Ensure quantity is at least 1
            $product_id = (int)$product_id;

            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $new_qty, $user_id, $product_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // --- LOGIC FOR GUEST: Update Session ---
        foreach ($_POST['qty'] as $key => $new_qty) {
            $new_qty = max(1, (int)$new_qty);
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['qty'] = $new_qty;
            }
        }
    }

    $_SESSION['cart_success'] = "Cart quantities updated successfully.";
}

// Always redirect back to the cart page
header('Location: ' . BASE_URL . '/cart.php');
exit;