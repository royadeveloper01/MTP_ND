<?php
require_once __DIR__ . '/db.php';

if (isset($_POST['qty'])) {
    foreach ($_POST['qty'] as $cart_key => $qty) {
        $qty = intval($qty);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$cart_key]);
        } else {
            $_SESSION['cart'][$cart_key]['qty'] = $qty;
        }
    }
}

header("Location: cart.php");
exit;
