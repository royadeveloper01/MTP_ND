<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Product Grid Renderer
 * (ALREADY ADDED "ADD TO CART" BUTTON)
 */
function displayProductGrid($products) {
    if (empty($products)) {
        return '<p>No products found for this category.</p>';
    }

    $output = '<div class="products-grid">';
    foreach ($products as $p) {
        $id = $p['id'] ?? '';
        $name = htmlspecialchars($p['name']);
        $price = htmlspecialchars($p['price']);
        $image = !empty($p['image']) ? htmlspecialchars($p['image']) : '';
        $desc = !empty($p['description'])
                ? htmlspecialchars(substr($p['description'], 0, 100)) . (strlen($p['description']) > 100 ? '...' : '')
                : '';

        $output .= '<div class="product-card">';

        if (!empty($image)) {
            $output .= '<img src="' . $image . '" alt="' . $name . '">';
        }

        $output .= '<h3>' . $name . '</h3>';
        $output .= '<div class="price">$' . number_format($price, 2) . '</div>';

        if (!empty($desc)) {
            $output .= '<p>' . $desc . '</p>';
        }

        // 🔥 ADD TO CART BUTTON (English)
        $output .= '
            <button class="add-to-cart"
                data-id="' . htmlspecialchars($id) . '"
                data-name="' . $name . '"
                data-price="' . $price . '">
                Add to cart
            </button>
        ';

        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}

// ===============================
// DATA LOADING
// ===============================

$category = strtolower($_GET['cat'] ?? 'all');
$errorMsg = '';

$products = [];
$maleProducts = [];
$femaleProducts = [];

try {
    $colsRes = $conn->query("SHOW COLUMNS FROM products");
    $availableCols = [];
    while ($c = $colsRes->fetch_assoc()) {
        $availableCols[] = $c['Field'];
    }

    $fieldToColumnMap = [
        'name'        => ['name', 'product_name', 'title'],
        'price'       => ['price', 'cost'],
        'image'       => ['image', 'img', 'image_url', 'photo'],
        'description' => ['description', 'desc', 'details']
    ];

    $selectFields = ['id'];
    foreach ($fieldToColumnMap as $alias => $possibleCols) {
        foreach ($possibleCols as $col) {
            if (in_array($col, $availableCols)) {
                $selectFields[] = "`$col` AS `$alias`";
                break;
            }
        }
    }

    if (count($selectFields) > 1) {
        $baseSql = "SELECT " . implode(', ', $selectFields) . " FROM products";

        if ($category === 'all') {
            $sqlMale = $baseSql . " WHERE category = 'male' ORDER BY id DESC";
            $maleProducts = $conn->query($sqlMale)->fetch_all(MYSQLI_ASSOC);

            $sqlFemale = $baseSql . " WHERE category = 'female' ORDER BY id DESC";
            $femaleProducts = $conn->query($sqlFemale)->fetch_all(MYSQLI_ASSOC);

        } elseif (in_array($category, ['male', 'female'])) {
            $pageTitle = ($category === 'male') ? "Men's Products" : "Women's Products";
            $sql = $baseSql . " WHERE category = '$category' ORDER BY id DESC";
            $products = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

    } else {
        $errorMsg = "Missing essential product columns in database.";
    }

} catch (mysqli_sql_exception $e) {
    $errorMsg = "Database error: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MTP Store</title>
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
            <a href="cart.php">Cart</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
            <a href="cart.php">Cart</a>
        <?php endif; ?>
    </div>
</nav>

<div class="hero">
    <h1>Welcome to MTP Store</h1>
    <p>Find the best products at unbeatable prices.</p>
</div>

<div class="container">

    <div class="category-nav" style="text-align:center; margin-bottom:30px;">
        <a href="index.php?cat=all">All</a>
        <a href="index.php?cat=male">Men</a>
        <a href="index.php?cat=female">Women</a>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <p style="color:red;"><?= $errorMsg ?></p>
    <?php endif; ?>

    <?php if ($category === 'all'): ?>
        <h2>Men's Products</h2>
        <?= displayProductGrid($maleProducts) ?>

        <h2>Women's Products</h2>
        <?= displayProductGrid($femaleProducts) ?>

    <?php else: ?>
        <h2><?= $pageTitle ?></h2>
        <?= displayProductGrid($products) ?>
    <?php endif; ?>

</div>

<!-- 🔥 JS Add to cart -->
<script>
document.addEventListener('click', function(e){
    if (e.target.matches('.add-to-cart')) {

        const id = e.target.dataset.id;
        const name = e.target.dataset.name;
        const price = e.target.dataset.price;

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                id:id,
                name:name,
                price:price,
                qty:1
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert("Added to cart!");
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(err => {
            alert("Server connection error");
        });
    }
});
</script>

</body>
</html>
