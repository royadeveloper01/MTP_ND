<?php
require_once 'db.php';

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

        // Only show 'Add to cart' button for non-admin users who are logged in
        if (!empty($_SESSION['loggedin']) && empty($_SESSION['is_admin'])) {
            $js_config = json_encode([
                'productId' => (int)$p['id'],
                'hasSizes' => !empty($sizes),
                'hasColors' => !empty($colors),
                'baseUrl' => BASE_URL
            ]);

            $output .= '<div x-data="addToCartForm(' . htmlspecialchars($js_config, ENT_QUOTES) . ')" class="mt-2">';
            if (!empty($sizes) || !empty($colors)) {
                $output .= '<div class="input-group input-group-sm">';
                if (!empty($sizes)) {
                    $output .= '<select x-model="size" class="form-select" required>';
                    $output .= '<option value="">Size</option>';
                    foreach ($sizes as $size) {
                        $output .= '<option value="' . htmlspecialchars($size) . '">' . htmlspecialchars($size) . '</option>';
                    }
                    $output .= '</select>';
                }
                if (!empty($colors)) {
                    $output .= '<select x-model="color" class="form-select" required>';
                    $output .= '<option value="">Color</option>';
                    foreach ($colors as $color) {
                        $output .= '<option value="' . htmlspecialchars($color) . '">' . htmlspecialchars($color) . '</option>';
                    }
                    $output .= '</select>';
                }
                $output .= '<button @click="submit" :disabled="loading" class="btn btn-primary" style="min-width: 60px;">';
                $output .= '<span x-show="!loading && !feedbackMessage">Add</span>';
                $output .= '<span x-show="loading" class="spinner-border spinner-border-sm" role="status"></span>';
                $output .= '<span x-show="feedbackMessage" x-text="feedbackMessage"></span>';
                $output .= '</button>';
                $output .= '</div>';
            } else {
                $output .= '<button @click="submit" :disabled="loading" class="btn btn-sm btn-primary" style="min-width: 90px;"><span x-show="!loading && !feedbackMessage">Add to cart</span><span x-show="loading" class="spinner-border spinner-border-sm" role="status"></span><span x-show="feedbackMessage" x-text="feedbackMessage"></span></button>';
            }
            $output .= '<div x-show="errorMessage" x-text="errorMessage" class="text-danger small mt-1" x-transition></div>';
            $output .= '</div>';
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

$page_css = '/assets/css/home.css';
include 'header.php';
?>

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
            <a href="<?= BASE_URL ?>/index.php?cat=all" class="hero-cta">
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
        <a href="<?= BASE_URL ?>/index.php?cat=all"
           class="category-pill <?= $activeAll ?>">
            ALL
        </a>
        <a href="<?= BASE_URL ?>/index.php?cat=male"
           class="category-pill <?= $activeMale ?>">
            MEN
        </a>
        <a href="<?= BASE_URL ?>/index.php?cat=female"
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
