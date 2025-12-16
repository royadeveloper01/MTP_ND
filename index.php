<?php
include 'db.php';
include 'header.php';

/**
 * This function displays a product grid.
 */
function displayProductGrid($products) {
    if (empty($products)) {
        return '<p>No products found for this category.</p>';
    }

    $output = '<div class="products-grid">';
    foreach ($products as $p) {

        $name = htmlspecialchars($p['name']);
        $priceDisplay = number_format($p['price'], 2);
        $priceRaw = isset($p['price']) ? floatval($p['price']) : 0;

        $sizes = !empty($p['sizes']) ? explode(',', $p['sizes']) : [];
        $colors = !empty($p['colors']) ? explode(',', $p['colors']) : [];
        $image = !empty($p['image']) ? htmlspecialchars($p['image']) : '';
        $desc  = !empty($p['description']) 
                    ? htmlspecialchars(substr($p['description'], 0, 100)) . (strlen($p['description']) > 100 ? '...' : '')
                    : '';

        $output .= '<div class="product-card">';
        
        if (!empty($p['image'])) {
            $output .= '<img src="' . $image . '" alt="' . $name . '">';
        }

        $output .= '<h3>' . $name . '</h3>';
        $output .= '<div class="price">$' . $priceDisplay . '</div>';

        if (!empty($desc)) {
            $output .= '<p>' . $desc . '</p>';
        }

        $pid   = htmlspecialchars($p['id']);

        // Only show 'Add to cart' button if user is NOT an admin
        if (empty($_SESSION['is_admin'])) {
            $output .= '<form method="post" action="' . BASE_URL . '/add_to_cart.php" style="margin-top:10px;">';
            $output .= '<input type="hidden" name="product_id" value="' . $pid . '">';
            $output .= '<input type="hidden" name="qty" value="1">';
            $output .= '<button type="submit" class="btn btn-sm btn-primary">Add to cart</button>';
            $output .= '</form>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}

// ---------- MAIN LOGIC ----------
$category = strtolower($_GET['cat'] ?? 'all'); 
$errorMsg = '';

$products = [];
$maleProducts = [];
$femaleProducts = [];
$pageTitle = '';

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
        'description' => ['description', 'desc', 'details'],
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

        // Use GROUP_CONCAT to fetch sizes and colors in a single query per category
        $selectFields[] = "(SELECT GROUP_CONCAT(s.name) FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = p.id) AS sizes";
        $selectFields[] = "(SELECT GROUP_CONCAT(c.name) FROM product_colors pc JOIN colors c ON pc.color_id = c.id WHERE pc.product_id = p.id) AS colors";

        // Ensure 'p.id' is used to avoid ambiguity
        $selectFields[0] = 'p.id';
        foreach ($selectFields as $k => $v) {
            if (strpos($v, ' AS ') !== false && strpos($v, 'p.') !== 0 && strpos($v, '(') !== 0) {
                $selectFields[$k] = 'p.' . $v;
            }
        }
        $baseSql = "SELECT " . implode(', ', $selectFields) . " FROM products p";

        if ($category === 'all') {
            $pageTitle = 'All Products';

            $sqlMale = $baseSql . " WHERE category = 'male' ORDER BY id DESC";
            $stmtMale = $conn->prepare($sqlMale);
            if ($stmtMale) {
                $stmtMale->execute();
                $maleProducts = $stmtMale->get_result()->fetch_all(MYSQLI_ASSOC);
            }

            $sqlFemale = $baseSql . " WHERE category = 'female' ORDER BY id DESC";
            $stmtFemale = $conn->prepare($sqlFemale);
            if ($stmtFemale) {
                $stmtFemale->execute();
                $femaleProducts = $stmtFemale->get_result()->fetch_all(MYSQLI_ASSOC);
            }

        } elseif (in_array($category, ['male', 'female'])) {

            $pageTitle = $category === 'male' ? "Men's Products" : "Women's Products";

            $sql = $baseSql . " WHERE category = ? ORDER BY id DESC";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }

    } else {
        $errorMsg = "Could not find required product columns.";
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

    <style>
        body.home-page {
            background-color: #f5f7fb;
        }

        .hero-section {
            position: relative;
            overflow: hidden;
            padding: 3rem 1rem 2rem 1rem;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: #fff;
            margin-bottom: 1.5rem;
        }

        .hero-inner {
            max-width: 1140px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 2rem;
        }

        .hero-text {
            flex: 1 1 260px;
            z-index: 1;
        }

        .hero-text h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .hero-text p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.3rem;
            border-radius: 999px;
            background-color: #fff;
            color: #0d6efd;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .hero-cta:hover {
            background-color: #f1f5ff;
            text-decoration: none;
        }

        .hero-badge {
            display: inline-block;
            font-size: 0.75rem;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            margin-bottom: 0.6rem;
        }

        .hero-highlight {
            font-weight: 600;
        }

        .hero-graphic {
            flex: 0 1 260px;
            min-width: 230px;
            max-width: 320px;
            height: 180px;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            text-align: center;
        }

        .hero-graphic span {
            max-width: 80%;
            line-height: 1.4;
        }

        .products-wrapper {
            max-width: 1140px;
            margin: 0 auto 2.5rem auto;
            padding: 0 1rem;
        }

        .category-nav {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .category-pill {
            padding: 0.45rem 1.1rem;
            border-radius: 999px;
            border: 1px solid #d0d7e2;
            background-color: #ffffff;
            font-size: 0.9rem;
            text-decoration: none;
            color: #333;
            transition: all 0.15s ease;
        }

        .category-pill:hover {
            border-color: #0d6efd;
            color: #0d6efd;
            text-decoration: none;
        }

        .category-pill.active {
            background-color: #0d6efd;
            color: #ffffff;
            border-color: #0d6efd;
            font-weight: 600;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-top: 0.8rem;
            margin-bottom: 0.8rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .section-title span {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 400;
            margin-left: 0.35rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1.1rem;
        }

        .product-card {
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 0.9rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card img {
            border-radius: 0.8rem;
            width: 100%;
            height: 180px;
            object-fit: cover;
            margin-bottom: 0.6rem;
        }

        .product-card h3 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .product-card .price {
            font-weight: 700;
            margin-bottom: 0.3rem;
            color: #0d6efd;
        }

        .product-card p {
            font-size: 0.85rem;
            color: #6c757d;
            flex: 1;
        }

        .product-card form {
            margin-top: 0.4rem;
        }

        @media (max-width: 576px) {
            .hero-text h1 {
                font-size: 1.7rem;
            }
            .hero-section {
                padding-top: 2.2rem;
                padding-bottom: 1.7rem;
            }
        }
    </style>
</head>
<body class="home-page">
    <?php if (!empty($_SESSION['cart_success'])): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div class="toast align-items-center text-bg-success border-0 show">
    <div class="d-flex">
      <div class="toast-body">
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto"
              onclick="this.closest('.toast').remove()"></button>
    </div>
  </div>
</div>
<?php unset($_SESSION['cart_success']); endif; ?>


<!-- HERO -->
<div class="hero-section">
    <div class="hero-inner">
        <div class="hero-text">
            <div class="hero-badge">MTP Store • New Collection</div>
            <h1>Welcome to MTP Store</h1>
            <p>
                Find the best products at unbeatable prices.
                <span class="hero-highlight">Shop the latest trends for men and women.</span>
            </p>
            <a href="/MTP_ND/index.php?cat=all" class="hero-cta">
                Start shopping
                <span>→</span>
            </a>
        </div>
        <div class="hero-graphic">
            <span>
                Curated items, clean design, and a simple shopping experience – all in one place.
            </span>
        </div>
    </div>
</div>

<div class="products-wrapper">
    <?php
        $activeAll    = ($category === 'all')    ? 'active' : '';
        $activeMale   = ($category === 'male')   ? 'active' : '';
        $activeFemale = ($category === 'female') ? 'active' : '';
    ?>

    <!-- CATEGORY TABS -->
    <div class="category-nav">
        <a href="/MTP_ND/index.php?cat=all"
           class="category-pill <?= $activeAll ?>">
            ALL
        </a>
        <a href="/MTP_ND/index.php?cat=male"
           class="category-pill <?= $activeMale ?>">
            MEN
        </a>
        <a href="/MTP_ND/index.php?cat=female"
           class="category-pill <?= $activeFemale ?>">
            WOMEN
        </a>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <p style="color:red"><?= $errorMsg ?></p>
    <?php endif; ?>

    <?php if ($category === 'all'): ?>

        <h2 class="section-title">
            Men's Products <span>(latest items)</span>
        </h2>
        <?= displayProductGrid($maleProducts) ?>

        <h2 class="section-title" style="margin-top: 1.8rem;">
            Women's Products <span>(handpicked for you)</span>
        </h2>
        <?= displayProductGrid($femaleProducts) ?>

    <?php else: ?>

        <h2 class="section-title" style="border-bottom: none; text-align: left;">
            <?= htmlspecialchars($pageTitle) ?>
        </h2>
        <?= displayProductGrid($products) ?>

    <?php endif; ?>
</div>

<?php
include 'footer.php';
?>
</body>
</html>
