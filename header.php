<?php
require_once __DIR__ . '/db.php';

// Calculate cart item count
$cart_count = 0;
if (!empty($_SESSION['loggedin']) && !empty($_SESSION['id'])) {
    try {
        $user_id = (int)$_SESSION['id'];
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $cart_count = $row['total_qty'] ?? 0;
        $stmt->close();
    } catch (Exception $e) {
        $cart_count = 0;
    }
} else {
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['qty'] ?? 0;
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_css_map = [
    'dashboard.php' => 'dashboard.css',
    'index.php'     => 'shop.css',
    'cart.php'      => 'cart.css',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MTP ND Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <?php 
    // Add version timestamp to force cache refresh when files change
    $main_ver = file_exists(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
    ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css?v=<?= $main_ver ?>">

    <?php if (isset($page_css_map[$current_page])): ?>
        <?php 
        $page_css_path = __DIR__ . '/assets/css/' . $page_css_map[$current_page];
        $page_ver = file_exists($page_css_path) ? filemtime($page_css_path) : time();
        ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $page_css_map[$current_page] ?>?v=<?= $page_ver ?>">
    <?php endif; ?>
</head>
<body>

<video autoplay muted loop id="bgVideo">
    <source src="<?= BASE_URL ?>/videos/noel.mp4" type="video/mp4">
</video>

<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">MTP Store</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">

        <?php if (empty($_SESSION['is_admin'])): ?>
            <li class="nav-item position-relative" x-data="{ cartCount: <?= (int)$cart_count ?> }" @cart-updated.window="cartCount = $event.detail.count">
              <a class="nav-link" href="<?= BASE_URL ?>/cart/cart.php">
                <i class="bi bi-cart3"></i> Cart
                <template x-if="cartCount > 0">
                    <span class="badge bg-danger rounded-pill cart-badge" x-text="cartCount"></span>
                </template>
              </a>
            </li>
        <?php endif; ?>

        <?php if (!empty($_SESSION['loggedin'])): ?>
            <li class="nav-item dropdown" x-data="{ open: false }">
                <a class="nav-link dropdown-toggle" href="#" role="button" @click.prevent="open = !open" @click.outside="open = false" :aria-expanded="open">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['fname']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" :class="{ 'show': open }">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/orders/index.php">Manage Orders</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/orders/my_orders.php">My Orders</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">Login</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container mt-4">