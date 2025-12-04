<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1️⃣ Nếu chưa login → chuyển tới login + message
if (empty($_SESSION['loggedin'])) {
    $msg = urlencode("Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.");
    header("Location: /MTP_ND/auth/login.php?msg=$msg");
    exit;
}

// 2️⃣ Nếu là admin → block luôn
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $msg = urlencode("Admin không được phép thêm sản phẩm vào giỏ hàng.");
    header("Location: /MTP_ND/index.php?msg=$msg");
    exit;
}

// === GIỮ NGUYÊN CODE CŨ CỦA NHÓM DƯỚI ĐÂY ===

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();

// RECEIVE PRODUCT DATA
$id = isset($_POST['product_id']) ? trim($_POST['product_id']) : null;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

if (!$id) {
    // fallback redirect
    header("Location: index.php");
    exit;
}

// ADD to session cart
if (!isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] = ['name' => $name, 'price' => $price, 'qty' => $qty];
} else {
    $_SESSION['cart'][$id]['qty'] += $qty;
}

// REDIRECT TO CART PAGE
header("Location: cart.php");
exit;
