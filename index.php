<?php
include 'db.php';

$category = $_GET['cat'] ?? 'male';
$category = in_array($category, ['male', 'female']) ? $category : 'male';

$products = [];
$sql = "SELECT id, name, price, image, description FROM products WHERE category = ? ORDER BY id DESC LIMIT 8";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $errorMsg = "Database error: " . htmlspecialchars($conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MTP Store</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; }
        .navbar { background: #35424a; padding: 10px 5%; display: flex; justify-content: space-between; align-items: center; color: white; }
        .navbar .logo { font-weight: bold; font-size: 1.5em; }
        .navbar a { color: white; text-decoration: none; padding: 5px 15px; border-radius: 3px; }
        .navbar a:hover { background: #576a75; }
        .category-links { display: flex; gap: 12px; margin: 0 20px; }
        .cat-btn { color: #fff; font-weight: bold; padding: 8px 20px; border-radius: 25px; background: rgba(255,255,255,0.15); transition: 0.3s; }
        .cat-btn:hover { background: rgba(255,255,255,0.3); }
        .cat-btn.active { background: #fff; color: #35424a; }
        .hero { background: #35424a; color: white; padding: 60px 20px; text-align: center; }
        .container { width: 90%; margin: auto; padding: 20px 0; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background: white; border: 1px solid #ddd; border-radius: 5px; padding: 15px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .product-card img { max-width: 100%; height: 200px; object-fit: cover; border-radius: 5px; }
        .price { color: #e8491d; font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">MTP Store</div>
    <div class="category-links">
        <a href="index.php?cat=male"   class="cat-btn <?= $category === 'male' ? 'active' : '' ?>">Men's</a>
        <a href="index.php?cat=female" class="cat-btn <?= $category === 'female' ? 'active' : '' ?>">Women's</a>
    </div>
    <div>
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
            <a href="index.php">Home</a>
            <a href="list.php">List</a>
            <a href="add.php">Add</a>
            <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['fname']) ?>)</a>
        <?php else: ?>
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<div class="hero">
    <h1>MTP Store</h1>
    <p>Quality clothing for everyone.</p>
</div>

<div class="container">
    <?php if (isset($errorMsg)): ?>
        <p style="color:red"><?= $errorMsg ?></p>
    <?php endif; ?>

    <?php if ($products): ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <?php if ($p['image']): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <div class="price">$<?= number_format($p['price'], 2) ?></div>
                    <?php if ($p['description']): ?>
                        <p><?= htmlspecialchars($p['description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>

</body>
</html>