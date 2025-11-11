<?php
include 'db.php';
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }

// Get stats
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalUsers    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$maleProducts  = $conn->query("SELECT COUNT(*) FROM products WHERE category = 'male'")->fetch_row()[0];
$femaleProducts= $conn->query("SELECT COUNT(*) FROM products WHERE category = 'female'")->fetch_row()[0];

// Recent products
$recent = $conn->query("SELECT id, name, price, category, created_at FROM products ORDER BY id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - MTP Store</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; }
        .navbar { background: #2c3e50; padding: 15px 5%; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin: 0 10px; font-weight: bold; }
        .navbar a:hover { color: #1abc9c; }
        .container { max-width: 1100px; margin: 30px auto; padding: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-card i { font-size: 2em; color: #1abc9c; margin-bottom: 10px; }
        .stat-card h3 { margin: 10px 0; font-size: 1.8em; color: #2c3e50; }
        .stat-card p { color: #7f8c8d; margin: 0; }
        .panel { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        .panel h3 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #1abc9c; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #2c3e50; }
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.8em; }
        .badge-male { background: #3498db; color: white; }
        .badge-female { background: #e74c3c; color: white; }
        .footer { text-align: center; padding: 20px; color: #7f8c8d; font-size: 0.9em; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div>
        <strong>MTP Store Admin</strong>
    </div>
    <div>
        <a href="dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
        <a href="list.php"><i class="fa fa-list"></i> Products</a>
        <a href="add.php"><i class="fa fa-plus"></i> Add</a>
        <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout (<?= htmlspecialchars($_SESSION['fname']) ?>)</a>
    </div>
</nav>

<div class="container">

    <!-- Welcome -->
    <h2 style="color:#2c3e50;">Welcome back, <strong><?= htmlspecialchars($_SESSION['fname']) ?></strong>!</h2>
    <p style="color:#7f8c8d;">Here’s what’s happening in your store today.</p>

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
    <div class="panel">
        <h3>Recent Products</h3>
        <?php if ($recent): ?>
            <table>
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
                            <td>
                                <span class="badge badge-<?= $p['category'] ?>"><?= ucfirst($p['category']) ?>'s</span>
                            </td>
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
    MTP Store Admin Panel © 2025 | Vietnam Time: <?= date('d M Y, h:i A') ?> (UTC+7)
</div>

</body>
</html>