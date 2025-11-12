<?php
include 'db.php';

$category = $_GET['cat'] ?? 'male';
$category = in_array($category, ['male', 'female']) ? $category : 'male';

$errorMsg = '';
$products = [];

try {
    // Get available columns from the products table
    $colsRes = $conn->query("SHOW COLUMNS FROM products");
    $availableCols = [];
    while ($c = $colsRes->fetch_assoc()) {
        $availableCols[] = $c['Field'];
    }

    // Map desired fields to possible database column names and create SELECT aliases
    $fieldToColumnMap = [
        'name'        => ['name', 'product_name', 'title'],
        'price'       => ['price', 'cost'],
        'image'       => ['image', 'img', 'image_url', 'photo'],
        'description' => ['description', 'desc', 'details']
    ];

    $selectFields = ['id']; // 'id' is almost always present

    foreach ($fieldToColumnMap as $alias => $possibleCols) {
        foreach ($possibleCols as $col) {
            if (in_array($col, $availableCols)) {
                // Add the found column with a consistent alias (e.g., `product_name` AS `name`)
                $selectFields[] = "`" . str_replace("`", "", $col) . "` AS `" . str_replace("`", "", $alias) . "`";
                break; // Move to the next field
            }
        }
    }

    if (count($selectFields) > 1) {
        $sql = "SELECT " . implode(', ', $selectFields) . " FROM products WHERE category = ? ORDER BY id DESC LIMIT 8";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $errorMsg = "Could not find required product columns in the database.";
    }
} catch (mysqli_sql_exception $e) {
    $errorMsg = "Database error: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to MTP Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">MTP Store</a>
    <div>
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="list.php">Products</a>
            <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['fname']) ?>)</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<div class="hero">
    <h1>Welcome to MTP Store</h1>
    <p>Find the best products at unbeatable prices.</p>
</div>

<div class="container" style="background:transparent; box-shadow:none;">
    <?php if (!empty($errorMsg)): ?>
        <p style="color:red"><?= $errorMsg ?></p>
    <?php endif; ?>

    <?php if ($products): ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <div class="price">$<?= number_format($p['price'], 2) ?></div>
                    <?php if (!empty($p['description'])): ?>
                        <p><?= htmlspecialchars(substr($p['description'], 0, 100)) . (strlen($p['description']) > 100 ? '...' : '') ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (empty($errorMsg)): ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>

</body>
</html>