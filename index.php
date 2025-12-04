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

    if (session_status() === PHP_SESSION_NONE) session_start();

    $isLoggedIn = !empty($_SESSION['loggedin']);
    $role = $_SESSION['role'] ?? 'user'; // default user

    $output = '<div class="products-grid">';

    foreach ($products as $p) {

        $name = htmlspecialchars($p['name']);
        $priceDisplay = number_format($p['price'], 2);
        $priceRaw = floatval($p['price']);
        $image = !empty($p['image']) ? htmlspecialchars($p['image']) : '';
        $desc  = !empty($p['description']) 
                    ? htmlspecialchars(substr($p['description'], 0, 100)) . (strlen($p['description']) > 100 ? '...' : '')
                    : '';

        $pid   = htmlspecialchars($p['id']);
        $pname = htmlspecialchars($p['name']);

        $output .= '<div class="product-card">';

        if (!empty($image)) {
            $output .= '<img src="' . $image . '" alt="' . $name . '">';
        }

        $output .= '<h3>' . $name . '</h3>';
        $output .= '<div class="price">$' . $priceDisplay . '</div>';

        if (!empty($desc)) {
            $output .= '<p>' . $desc . '</p>';
        }

        // ---------- BUTTON LOGIC ----------
        // USER → working Add to cart
        if ($isLoggedIn && $role !== 'admin') {

            $output .= '<form method="post" action="add_to_cart.php" style="margin-top:10px;">';
            $output .= '<input type="hidden" name="product_id" value="' . $pid . '">';
            $output .= '<input type="hidden" name="name" value="' . $pname . '">';
            $output .= '<input type="hidden" name="price" value="' . $priceRaw . '">';
            $output .= '<input type="hidden" name="qty" value="1">';
            $output .= '<button type="submit" class="btn btn-sm btn-primary">Add to cart</button>';
            $output .= '</form>';
        }
        // GUEST → show button but open modal to sign in/register
        elseif (!$isLoggedIn) {
            // pass current page + query so login can redirect back
            $currentUrl = htmlspecialchars($_SERVER['REQUEST_URI']);
            $output .= '<button class="btn btn-sm btn-primary guest-add-btn" data-return="' . $currentUrl . '" style="margin-top:10px;">Add to cart</button>';
        }
        // ADMIN → disabled
        else {
            $output .= '<button class="btn btn-sm btn-secondary" style="margin-top:10px;" disabled>
                        Add to cart</button>';
        }

        $output .= '</div>'; // product-card
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

    <!-- Modal CSS (fashion-style) -->
    <style>
    /* Backdrop */
    .fw-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10,10,10,0.55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
    }
    /* Modal card */
    .fw-modal {
        max-width: 640px;
        width: 100%;
        background: #111217;
        color: #f6f6f6;
        border-radius: 12px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        overflow: hidden;
        transform: translateY(-10px);
        opacity: 0;
        transition: all 200ms ease;
        font-family: 'Poppins', sans-serif;
    }
    .fw-modal.show { transform: translateY(0); opacity: 1; }

    .fw-modal .fw-content {
        padding: 28px 36px;
    }
    .fw-modal .fw-title {
        font-size: 22px;
        letter-spacing: 0.6px;
        margin: 0 0 8px;
        font-weight: 700;
    }
    .fw-modal .fw-sub {
        font-size: 14px;
        color: #cfcfcf;
        margin-bottom: 18px;
    }

    .fw-modal .fw-cta {
        display:flex;
        gap:12px;
        align-items:center;
        margin-top: 18px;
    }

    .fw-btn {
        display:inline-block;
        padding: 10px 16px;
        border-radius: 8px;
        font-weight:600;
        cursor:pointer;
        border: none;
    }
    .fw-btn.primary {
        background: #ff4d6d;
        color: #fff;
        box-shadow: 0 6px 18px rgba(255,77,109,0.18);
    }
    .fw-btn.ghost {
        background: transparent;
        color: #f6f6f6;
        border: 1px solid rgba(255,255,255,0.08);
    }
    .fw-btn.link {
        background: transparent;
        color: #cfcfcf;
        padding: 8px 10px;
        font-weight:500;
    }

    .fw-modal .fw-close {
        position:absolute;
        right:12px;
        top:12px;
        background:transparent;
        border:none;
        color:#999;
        font-size:18px;
        cursor:pointer;
    }

    @media (max-width:600px){
        .fw-modal { padding: 0; }
        .fw-modal .fw-content { padding:18px; }
        .fw-modal .fw-title { font-size:18px; }
    }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="/MTP_ND/index.php" class="logo">MTP Store</a>

    <div>
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
            <a href="/MTP_ND/dashboard.php">Dashboard</a>
            <a href="/MTP_ND/products/list.php">Products</a>
            <a href="/MTP_ND/auth/logout.php">Logout (<?= htmlspecialchars($_SESSION['fname']) ?>)</a>
        <?php else: ?>
            <a href="/MTP_ND/auth/login.php">Login</a>
            <a href="/MTP_ND/auth/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

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

<!-- FASHION POPUP: modal HTML -->
<div id="fwBackdrop" class="fw-modal-backdrop" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="fw-modal" role="document" id="fwModal">
        <button class="fw-close" aria-label="Close" onclick="closeFWModal()">✕</button>
        <div class="fw-content">
            <h2 class="fw-title">MTP welcomes you!</h2>
            <p class="fw-sub">Let's be the better version of yourself — first, please sign in to continue adding items to your cart.</p>

            <div class="fw-cta">
                <button class="fw-btn primary" id="fwSignInBtn">Sign in</button>
                <button class="fw-btn ghost" id="fwRegisterBtn">Register</button>
                <button class="fw-btn link" onclick="closeFWModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JS: modal control + hookup guest buttons -->
<script>
(function(){
    // helpers
    function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

    const backdrop = qs('#fwBackdrop');
    const modal = qs('#fwModal');
    const signInBtn = qs('#fwSignInBtn');
    const regBtn = qs('#fwRegisterBtn');

    // open modal and remember return URL
    window.openFWModal = function(returnUrl) {
        backdrop.style.display = 'flex';
        setTimeout(()=> modal.classList.add('show'), 10);
        backdrop.setAttribute('aria-hidden', 'false');
        // store return in dataset
        backdrop.dataset.return = returnUrl || window.location.pathname + window.location.search;
    };

    window.closeFWModal = function() {
        modal.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
        setTimeout(()=> { backdrop.style.display = 'none'; delete backdrop.dataset.return; }, 220);
    };

    // sign in -> redirect to login with next param
    signInBtn.addEventListener('click', function(){
        const next = encodeURIComponent(backdrop.dataset.return || window.location.pathname + window.location.search);
        window.location.href = '/MTP_ND/auth/login.php?next=' + next;
    });

    // register -> to register page (no next param)
    regBtn.addEventListener('click', function(){
        window.location.href = '/MTP_ND/auth/register.php';
    });

    // close on backdrop click
    backdrop.addEventListener('click', function(e){
        if (e.target === backdrop) closeFWModal();
    });

    // hook all guest buttons (added dynamically in PHP)
    function hookGuestButtons() {
        const guestBtns = qsa('.guest-add-btn');
        guestBtns.forEach(btn => {
            // avoid double-binding
            if (btn.dataset.hooked) return;
            btn.dataset.hooked = '1';
            btn.addEventListener('click', function(e){
                const ret = btn.getAttribute('data-return') || window.location.pathname + window.location.search;
                window.openFWModal(ret);
            });
        });
    }

    // initial
    document.addEventListener('DOMContentLoaded', hookGuestButtons);
    // if products are loaded after (AJAX), you can call hookGuestButtons() again

    // expose for debugging if needed
    window._fw = { openFWModal, closeFWModal, hookGuestButtons };
})();
</script>

</body>
</html>

<?php
include 'footer.php';
?>
