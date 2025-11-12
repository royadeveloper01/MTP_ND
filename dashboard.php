<?php
include 'db.php';
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }

// Get stats
$totalProducts = 0;
$totalUsers = 0;
$maleProducts = 0;
$femaleProducts = 0;
$recent = [];
$errorMsg = '';

try {
    // Get available columns from the products table
    $colsRes = $conn->query("SHOW COLUMNS FROM products");
    $availableCols = [];
    while ($c = $colsRes->fetch_assoc()) {
        $availableCols[] = $c['Field'];
    }

    // Find the correct column name for 'category' and 'name'
    $categoryCol = array_intersect(['category', 'product_category', 'type'], $availableCols)[0] ?? null;
    $nameCol = array_intersect(['name', 'product_name', 'title'], $availableCols)[0] ?? 'id';

    // Get stats
    $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
    $totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

    if ($categoryCol) {
        $maleProducts = $conn->query("SELECT COUNT(*) FROM products WHERE `$categoryCol` = 'male'")->fetch_row()[0];
        $femaleProducts = $conn->query("SELECT COUNT(*) FROM products WHERE `$categoryCol` = 'female'")->fetch_row()[0];
    }

    // Recent products query
    $selectFields = ["`id`", "`$nameCol` AS `name`", "`price`", "`created_at`"];
    if ($categoryCol) {
        $selectFields[] = "`$categoryCol` AS `category`";
    }
    $sql = "SELECT " . implode(', ', $selectFields) . " FROM products ORDER BY id DESC LIMIT 5";
    $recent = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $e) {
    $errorMsg = "Database error: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - MTP Store</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a href="index.php" class="logo">MTP Store Admin</a>
    <div>
        <a href="dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
        <a href="list.php"><i class="fa fa-list"></i> Products</a>
        <a href="add.php"><i class="fa fa-plus"></i> Add</a>
        <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout (<?= htmlspecialchars($_SESSION['fname']) ?>)</a>
    </div>
</nav>

<div class="container">
    <h2>Welcome back, <strong><?= htmlspecialchars($_SESSION['fname']) ?></strong>!</h2>
    <p>Here’s what’s happening in your store today.</p>
    <?php if ($errorMsg): ?><div class="alert alert-danger"><?= $errorMsg ?></div><?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats">
        <div class="stat-card">
            <i class="fa fa-box"></i>
            <h3><?= $totalProducts ?></h3>
            <p>Total Products</p>
        </div>
        <div class="stat-card">
            <i class="fa fa-users"></i>
            <h3><?= $totalUsers ?></h3>
            <p>Total Users</p>
        </div>
        <div class="stat-card">
            <i class="fa fa-male"></i>
            <h3><?= $maleProducts ?></h3>
            <p>Men's Items</p>
        </div>
        <div class="stat-card">
            <i class="fa fa-female"></i>
            <h3><?= $femaleProducts ?></h3>
            <p>Women's Items</p>
        </div>
    </div>

    <!-- Recent Products -->
    <div class="recent-products">
        <h3>Recent Products</h3>
        <?php if ($recent): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $p): ?>
                        <tr>
                            <td>#<?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td>$<?= number_format($p['price'], 2) ?></td>
                            <?php if (isset($p['category'])): ?>
                                <td>
                                    <span class="badge badge-<?= $p['category'] ?>"><?= ucfirst($p['category']) ?>'s</span>
                                </td>
                            <?php endif; ?>
                            <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No products yet.</p>
        <?php endif; ?>
    </div>

</div>

<div class="footer">
    MTP Store Admin Panel © <?= date('Y') ?>
</div>

</body>
</html>