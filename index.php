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
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>Welcome to MTP Store</title>
</head>
<body>

    <nav class="navbar">
        <div class="logo"> <a href="index.php">MTP Store</a></div>
        <div>
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <div class="hero">
        <h1>Your One-Stop Shop</h1>
        <p>Find the best products at unbeatable prices.</p>
    </div>

    <div class="container" style="background:transparent; box-shadow:none;">
        <?php if (!empty($errorMsg)) : ?>
            <p style="color: red;"><?= $errorMsg ?></p>
        <?php endif; ?>

        <?php if (count($products) > 0) : ?>
            <div class="products-grid">
                <?php foreach ($products as $p) : ?>
                    <div class="product-card">
                        <?php
                            // Determine display name
                            $name = null;
                            foreach (['brand','name','title','product_name'] as $k) {
                                if (isset($p[$k]) && $p[$k] !== null && $p[$k] !== '') { $name = $p[$k]; break; }
                            }

                            // Determine price
                            $price = null;
                            foreach (['price','cost'] as $k) { if (isset($p[$k]) && $p[$k] !== null) { $price = $p[$k]; break; } }

                            // Determine image
                            $img = null;
                            foreach (['image','img','image_url','photo'] as $k) { if (isset($p[$k]) && $p[$k] !== null && $p[$k] !== '') { $img = $p[$k]; break; } }

                            // Determine description
                            $desc = null;
                            foreach (['description','desc','details'] as $k) { if (isset($p[$k]) && $p[$k] !== null && $p[$k] !== '') { $desc = $p[$k]; break; } }
                        ?>

                        <?php if ($img) : ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name ?? 'Product image') ?>">
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($name ?? 'Product') ?></h3>

                        <?php if ($price !== null) : ?>
                            <div class="price"><?= htmlspecialchars($price) ?></div>
                        <?php endif; ?>

                        <?php if ($desc) : ?>
                            <p><?= htmlspecialchars($desc) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p>No products found.</p>
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