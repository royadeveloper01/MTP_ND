<?php
require_once __DIR__ . '/db.php'; // Includes session_start()

if (isset($_POST['qty']) && is_array($_POST['qty'])) {

    if (!empty($_SESSION['loggedin'])) {
        // --- LOGGED-IN USER: Update database ---
        $user_id = $_SESSION['id'];
        try {
            $conn->begin_transaction();

            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $delete_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");

            foreach ($_POST['qty'] as $product_id => $qty) {
                $product_id = (int)$product_id;
                $qty = (int)$qty;

                if ($qty <= 0) {
                    $delete_stmt->bind_param('ii', $user_id, $product_id);
                    $delete_stmt->execute();
                } else {
                    $update_stmt->bind_param('iii', $qty, $user_id, $product_id);
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
        foreach ($_POST['qty'] as $id => $qty) {
            if (isset($cart[$id])) {
                if ((int)$qty <= 0) {
                    unset($_SESSION['cart'][$id]);
                } else {
                    $_SESSION['cart'][$id]['qty'] = (int)$qty;
                }
            }
        }
    }
}

header("Location: cart.php");
exit;
