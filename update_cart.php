<?php
require_once __DIR__ . '/db.php';

// Admins don't use the cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty']) && is_array($_POST['qty'])) {
    
    $is_logged_in = !empty($_SESSION['loggedin']) && !empty($_SESSION['id']);

    foreach ($_POST['qty'] as $key => $new_qty) {
        $new_qty = (int)$new_qty; // Allow 0 for deletion
        
        if ($is_logged_in) {
            // --- LOGIC FOR LOGGED IN USER ---
            // The $key is the unique 'id' of the row in the cart table
            $cart_id = (int)$key;
            $user_id = (int)$_SESSION['id'];

            if ($new_qty <= 0) {
                // Restore deletion logic: If qty is 0 or less, remove the item
                $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $cart_id, $user_id);
            } else {
                // Update specific row quantity (handles variations correctly by ID)
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("iii", $new_qty, $cart_id, $user_id);
            }
            $stmt->execute();
            $stmt->close();

        } else {
            // --- LOGIC FOR GUEST ---
            // The $key is 'product_id-size-color'
            if ($new_qty <= 0) {
                unset($_SESSION['cart'][$key]);
            } elseif (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['qty'] = $new_qty;
            }
        }
    }

    $_SESSION['cart_success'] = "Cart updated successfully.";
}

// Always redirect back to the cart page
header('Location: ' . BASE_URL . '/cart.php');
exit;