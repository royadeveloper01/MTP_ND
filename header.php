<?php
require_once __DIR__ . '/db.php';

/* ===== CART BADGE COUNT ===== */
$cartCount = 0;

if (!empty($_SESSION['loggedin']) && !empty($_SESSION['id'])) {
    // LOGIN USER → COUNT FROM DB
    $uid = (int)$_SESSION['id'];
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(quantity),0) AS cnt 
         FROM cart 
         WHERE user_id=?"
    );
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $cartCount = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();
} elseif (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    // GUEST → COUNT FROM SESSION
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['qty'] ?? 0);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MTP ND Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#0a437cff;">
  <div class="container-fluid">

    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">MTP Store</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-lg-center">

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
        </li>

        <?php if (!empty($_SESSION['is_admin'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/products/list.php">Manage Products</a>
          </li>
        <?php else: ?>

          <!-- CART -->
          <li class="nav-item">
            <a class="nav-link position-relative" href="<?= BASE_URL ?>/cart.php">
              <i class="bi bi-cart3"></i> Cart
              <?php if ($cartCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                  <?= $cartCount ?>
                </span>
              <?php endif; ?>
            </a>
          </li>
        <?php endif; ?>

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
