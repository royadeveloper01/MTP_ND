<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
    $is_logged_in = !empty($_SESSION['loggedin']) && !empty($_SESSION['id']);
    $has_error = false;

    foreach ($_POST['qty'] as $key => $new_qty) {
        $new_qty = (int)$new_qty;

        if ($is_logged_in) {
            // 1. LOGGED IN USER: Update the database safely
            try {
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

            } catch (Exception $e) {
                // Log the real error for debugging
                error_log("Failed to update cart item (key: {$key}) for user_id: {$user_id}. Error: " . $e->getMessage());

                // Set a user-friendly error message
                $_SESSION['cart_error'] = "Could not update cart quantities. Please try again.";

                // Stop processing further items to avoid partial updates and repeated error overwrites
                $has_error = true;
                break;
            }

        } else {
            // 2. GUEST USER: Update the session (safe, no DB involved)
            if (isset($_SESSION['cart'][$key])) {
                if ($new_qty <= 0) {
                    unset($_SESSION['cart'][$key]);
                } else {
                    $_SESSION['cart'][$key]['qty'] = $new_qty;
                }
            }
        }
    }

    // Only set success message if no error occurred
    if (!$has_error) {
        // For guests, we can always assume success since it's just session manipulation
        $_SESSION['cart_success'] = "Cart quantities updated successfully.";
    }
    // If there was an error, the error message is already set in the catch block
}

// Redirect back to the cart
header("Location: " . BASE_URL . "/cart/cart.php");
exit;