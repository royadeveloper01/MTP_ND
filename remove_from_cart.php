<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$key = $_GET['key'] ?? null;

if ($key && isset($_SESSION['cart'][$key])) {
    unset($_SESSION['cart'][$key]);
}

header("Location: cart.php");
exit;
