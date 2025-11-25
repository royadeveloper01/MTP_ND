<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_POST['qty'])) {
    foreach ($_POST['qty'] as $id => $qty) {
        $qty = intval($qty);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['qty'] = $qty;
        }
    }
}

header("Location: cart.php");
exit;
