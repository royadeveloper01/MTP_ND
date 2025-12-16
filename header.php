<?php
require_once __DIR__ . '/db.php';

// Calculate cart item count for badge
$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['qty'] ?? 1;
    }
}

// Determine current page
$current_page = basename($_SERVER['PHP_SELF']);

// Page-specific CSS mapping (clean & scalable)
$page_css_map = [
    'dashboard.php' => 'dashboard.css',
    'index.php'     => 'shop.css',
    'cart.php'      => 'cart.css',
];

$admin_pages = ['list.php', 'add.php', 'edit.php'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MTP ND Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">

    <!-- Page-specific CSS -->
    <?php if (isset($page_css_map[$current_page])): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $page_css_map[$current_page] ?>">
    <?php endif; ?>

    <!-- Admin pages CSS -->
    <?php if (in_array($current_page, $admin_pages)): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <?php endif; ?>
</head>
<body>

<!-- Background Video -->
<video autoplay muted loop id="bgVideo">
    <source src="<?= BASE_URL ?>/videos/noel.mp4" type="video/mp4">
</video>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0a437cff;">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">MTP Store</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
        </li>

        <!-- Manage Products for Admins -->
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/products/list.php">Manage Products</a>
            </li>
        <?php endif; ?>

        <!-- Cart with Badge for Customers -->
        <?php if (!empty($_SESSION['loggedin']) && empty($_SESSION['is_admin'])): ?>
            <li class="nav-item position-relative">
              <a class="nav-link" href="<?= BASE_URL ?>/cart.php">
                <i class="bi bi-cart3"></i> Cart
                <?php if ($cart_count > 0): ?>
                    <span class="badge bg-danger rounded-pill cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
              </a>
            </li>
        <?php endif; ?>

        <!-- Login / Logout -->
        <?php if (!empty($_SESSION['loggedin'])): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= BASE_URL ?>/auth/logout.php">
                Logout (<?= htmlspecialchars($_SESSION['fname']) ?>)
              </a>
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