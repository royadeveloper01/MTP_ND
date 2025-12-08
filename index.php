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
        $pname = htmlspecialchars($p['name']);

        // Only show 'Add to cart' button for non-admin users who are logged in
        if (!empty($_SESSION['loggedin']) && empty($_SESSION['is_admin'])) {
            $output .= '<form method="post" action="add_to_cart.php" class="mt-2">';
            $output .= '<input type="hidden" name="product_id" value="' . $pid . '">';
            if (!empty($sizes) || !empty($colors)) {
                $output .= '<div class="input-group input-group-sm">';
                if (!empty($sizes)) {
                    $output .= '<select name="size" class="form-select" required>';
                    $output .= '<option value="">Size</option>';
                    foreach ($sizes as $size) {
                        $output .= '<option value="' . htmlspecialchars($size) . '">' . htmlspecialchars($size) . '</option>';
                    }
                    $output .= '</select>';
                }
                if (!empty($colors)) {
                    $output .= '<select name="color" class="form-select" required>';
                    $output .= '<option value="">Color</option>';
                    foreach ($colors as $color) {
                        $output .= '<option value="' . htmlspecialchars($color) . '">' . htmlspecialchars($color) . '</option>';
                    }
                    $output .= '</select>';
                }
                $output .= '<button type="submit" class="btn btn-primary">Add</button>';
                $output .= '</div>';
            } else {
                $output .= '<button type="submit" class="btn btn-sm btn-primary">Add to cart</button>';
            }
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
</head>
<body>

<div class="hero">
    <h1>Welcome to MTP Store</h1>
    <p>Find the best products at unbeatable prices.</p>
</div>

<div class="container" style="background:transparent; box-shadow:none;">

    <div class="category-nav" style="text-align: center; margin-bottom: 30px; font-size: 1.2em;">
        <a href="/MTP_ND/index.php?cat=all"
           style="padding: 8px 15px; text-decoration: none; color: #333;
                  border-bottom: 3px solid <?= $category === 'all' ? '#007bff' : 'transparent' ?>;
                  font-weight: <?= $category === 'all' ? '600' : 'normal' ?>;">
           ALL 
        </a>
        <a href="/MTP_ND/index.php?cat=male"
           style="padding: 8px 15px; text-decoration: none; color: #333;
                  border-bottom: 3px solid <?= $category === 'male' ? '#007bff' : 'transparent' ?>;
                  font-weight: <?= $category === 'male' ? '600' : 'normal' ?>;">
           MEN
        </a>
        <a href="/MTP_ND/index.php?cat=female"
           style="padding: 8px 15px; text-decoration: none; color: #333;
                  border-bottom: 3px solid <?= $category === 'female' ? '#007bff' : 'transparent' ?>;
                  font-weight: <?= $category === 'female' ? '600' : 'normal' ?>;">
           WOMEN
        </a>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <p style="color:red"><?= $errorMsg ?></p>
    <?php endif; ?>

    <?php if ($category === 'all'): ?>

        <h2 style="text-align:left; margin-top:20px; border-bottom:2px solid #eee; padding-bottom:10px;">
            Men's Products
        </h2>
        <?= displayProductGrid($maleProducts) ?>

        <h2 style="text-align:left; margin-top:40px; border-bottom:2px solid #eee; padding-bottom:10px;">
            Women's Products
        </h2>
        <?= displayProductGrid($femaleProducts) ?>

    <?php else: ?>

        <h2 style="text-align:center; margin-top:0;"><?= $pageTitle ?></h2>
        <?= displayProductGrid($products) ?>

    <?php endif; ?>

</div>

</body>
</html>

<?php
include 'footer.php';
?>
