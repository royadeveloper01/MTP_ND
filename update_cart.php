<?php
require_once __DIR__ . '/db.php'; // Includes session_start()

if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {

    if (!empty($_SESSION['loggedin'])) {
        // --- LOGGED-IN USER: Update database ---
        $user_id = $_SESSION['id'];
        try {
            $conn->begin_transaction();

            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $delete_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");

            foreach ($_POST['quantity'] as $product_id => $quantity) {
                $product_id = (int)$product_id;
                $quantity = (int)$quantity;

                if ($quantity <= 0) {
                    $delete_stmt->bind_param('ii', $user_id, $product_id);
                    $delete_stmt->execute();
                } else {
                    $update_stmt->bind_param('iii', $quantity, $user_id, $product_id);
                    $update_stmt->execute();
                }
            }
            $update_stmt->close();
            $delete_stmt->close();
            $conn->commit();

        } catch (Exception $e) {
            $conn->rollback();
        }

    } else {
        // --- GUEST USER: Update session ---
        $cart = $_SESSION['cart'] ?? [];
        foreach ($_POST['quantity'] as $id => $quantity) {
            if (isset($cart[$id])) {
                if ((int)$quantity <= 0) {
                    unset($_SESSION['cart'][$id]);
                } else {
                    $_SESSION['cart'][$id]['quantity'] = (int)$quantity;
                }
            }
        }
    }
}

header("Location: cart.php");
exit;
