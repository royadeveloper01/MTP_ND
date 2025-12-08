<?php
require_once __DIR__ . '/db.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MTP ND Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0a437cff;">
  <div class="container-fluid">

    <a class="navbar-brand" href="/MTP_ND/index.php">MTP Store</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">

      <ul class="navbar-nav">

        <li class="nav-item">
          <a class="nav-link" href="/MTP_ND/dashboard.php">Dashboard</a>
        </li>

        <?php if (!empty($_SESSION['is_admin'])): ?>
            <li class="nav-item">
              <a class="nav-link" href="/MTP_ND/products/list.php">Products</a>
            </li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="/MTP_ND/cart.php">Cart</a></li>
        <?php endif; ?>

        <?php if (!empty($_SESSION['loggedin'])): ?>
            <li class="nav-item">
              <a class="nav-link" href="/MTP_ND/auth/logout.php">
                Logout (<?= htmlspecialchars($_SESSION['fname']) ?>)
              </a>
            </li>
        <?php else: ?>
            <li class="nav-item">
              <a class="nav-link" href="/MTP_ND/auth/login.php">Login</a>
            </li>
        <?php endif; ?>

      </ul>

    </div>

  </div>
</nav>

<main class="container mt-4">
