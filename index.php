<?php
include 'db.php';

/**
 * [HÀM MỚI] Hàm này để hiển thị một lưới sản phẩm.
 * Chúng ta tạo hàm này để không phải lặp lại code HTML.
 */
function displayProductGrid($products) {
    if (empty($products)) {
        return '<p>No products found for this category.</p>';
    }

    $output = '<div class="products-grid">';
    foreach ($products as $p) {
        $name = htmlspecialchars($p['name']);
        $price = number_format($p['price'], 2);
        $image = !empty($p['image']) ? htmlspecialchars($p['image']) : '';
        $desc = !empty($p['description']) ? htmlspecialchars(substr($p['description'], 0, 100)) . (strlen($p['description']) > 100 ? '...' : '') : '';

        $output .= '<div class="product-card">';
        if (!empty($p['image'])) {
            $output .= '<img src="' . htmlspecialchars($p['image']) . '" alt="' . $name . '">';
        }
        $output .= '<h3>' . $name . '</h3>';
        $output .= '<div class="price">$' . $price . '</div>';
        if (!empty($p['description'])) {
            $output .= '<p>' . $desc . '</p>';
        }
        $output .= '</div>'; // close product-card
    }
    $output .= '</div>'; // close products-grid
    return $output;
}


// Logic chính bắt đầu
$category = strtolower($_GET['cat'] ?? 'all'); 
$errorMsg = '';

// [SỬA] Khởi tạo các mảng sản phẩm
$products = [];       // Dùng khi lọc 1 danh mục
$maleProducts = [];   // Dùng cho trang 'all'
$femaleProducts = []; // Dùng cho trang 'all'

try {
    // 1. Lấy thông tin cột (Giữ nguyên)
    $colsRes = $conn->query("SHOW COLUMNS FROM products");
    $availableCols = [];
    while ($c = $colsRes->fetch_assoc()) {
        $availableCols[] = $c['Field'];
    }

    // 2. Map tên cột (Giữ nguyên)
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
                $selectFields[] = "`" . str_replace("`", "", $col) . "` AS `" . str_replace("`", "", $alias) . "`";
                break; 
            }
        }
    }

    if (count($selectFields) > 1) {
        // Tạo câu SQL cơ bản
        $baseSql = "SELECT " . implode(', ', $selectFields) . " FROM products"; 

        // [SỬA] Logic lấy dữ liệu mới
        if ($category === 'all') {
            // Nếu là trang 'all', lấy cả 2 nhóm
            $pageTitle = 'All Products';
            
            // Lấy đồ nam
            $sqlMale = $baseSql . " WHERE category = 'male' ORDER BY id DESC";
            $stmtMale = $conn->prepare($sqlMale);
            if ($stmtMale) {
                $stmtMale->execute();
                $maleProducts = $stmtMale->get_result()->fetch_all(MYSQLI_ASSOC);
            }

            // Lấy đồ nữ
            $sqlFemale = $baseSql . " WHERE category = 'female' ORDER BY id DESC";
            $stmtFemale = $conn->prepare($sqlFemale);
            if ($stmtFemale) {
                $stmtFemale->execute();
                $femaleProducts = $stmtFemale->get_result()->fetch_all(MYSQLI_ASSOC);
            }

        } elseif (in_array($category, ['male', 'female'])) {
            // Nếu lọc 1 nhóm (như cũ)
            $pageTitle = ($category === 'male') ? "Men's Products" : "Women's Products";
            
            $sql = $baseSql . " WHERE category = ? ORDER BY id DESC";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Chỉ đổ vào mảng $products
            }
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

    <div class="category-nav" style="text-align: center; margin-bottom: 30px; font-size: 1.2em;">
        <a href="index.php?cat=all" 
           style="padding: 8px 15px; text-decoration: none; color: #333;
                  border-bottom: 3px solid <?= $category === 'all' ? '#007bff' : 'transparent' ?>;
                  font-weight: <?= $category === 'all' ? '600' : 'normal' ?>;">
           Tất cả
        </a>
        <a href="index.php?cat=male" 
           style="padding: 8px 15px; text-decoration: none; color: #333;
                  border-bottom: 3px solid <?= $category === 'male' ? '#007bff' : 'transparent' ?>;
                  font-weight: <?= $category === 'male' ? '600' : 'normal' ?>;">
           Đồ nam
        </a>
        <a href="index.php?cat=female" 
           style="padding: 8px 15px; text-decoration: none; color: #333;
                  border-bottom: 3px solid <?= $category === 'female' ? '#007bff' : 'transparent' ?>;
                  font-weight: <?= $category === 'female' ? '600' : 'normal' ?>;">
           Đồ nữ
        </a>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <p style="color:red"><?= $errorMsg ?></p>
    <?php endif; ?>

    <?php if ($category === 'all'): ?>
        
        <h2 style="text-align: left; margin-top: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            Men's Products
        </h2>
        <?= displayProductGrid($maleProducts) ?>

        <h2 style="text-align: left; margin-top: 40px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            Women's Products
        </h2>
        <?= displayProductGrid($femaleProducts) ?>

    <?php else: ?>
        
        <h2 style="text-align: center; margin-top: 0;"><?= $pageTitle ?></h2>
        <?= displayProductGrid($products) ?>
        
    <?php endif; ?>

</div>

</body>
</html>