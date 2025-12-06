<?php
// db.php - DB connection + safe session start + AUTH_SECRET
// NOTE: Change AUTH_SECRET to a secure random string before pushing to repo.

// If project config exists, load it first so it can set BASE_URL / AUTH_SECRET
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$local_hosts = ['localhost', '127.0.0.1', 'localhost:88', 'mtp_nd.test'];

// AUTH secret for remember-me HMAC
if (!defined('AUTH_SECRET')) {
    define('AUTH_SECRET', 'change_this_to_a_random_32+_char_secret_please!');
}

// ------------------------------
// Detect/ensure BASE_URL
// ------------------------------
// If config defined BASE_URL, keep it. Otherwise detect automatically.
$script_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($script_path === '') {
    $script_path = '/';
}
if (!defined('BASE_URL')) {
    // If the site lives in a subfolder like /MTP_ND, this will set that.
    define('BASE_URL', $script_path);
}

// Choose DB config based on host
if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $local_hosts)) {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $db   = 'mtp_db'; // ensure this matches your local DB name
} else {
    $host = 'sql204.infinityfree.com';
    $user = 'if0_40503929';
    $pass = 'thisiswebdesign';
    $db   = 'if0_40503929_mtpnd_database';
}

// --- Start session safely and force session-only cookie ---
// Only set session cookie params once, and use BASE_URL for path so it is consistent
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();

    // Use BASE_URL as cookie path to avoid cookies being set to subpaths like /auth
    $cookiePath = (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : ($cookieParams['path'] ?? '/'));

    session_set_cookie_params([
        'lifetime' => 0, // session-only
        'path'     => $cookiePath,
        'domain'   => $cookieParams['domain'] ?? '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// --- Database connection ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $user, $pass, $db);
if ($conn) {
    $conn->set_charset('utf8mb4');
}
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Fetch dashboard data
$stats = [];
$errors = [];

try {
    // Get product count
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $stats['products'] = $result ? $result->fetch_assoc()['count'] : 0;

    // Get cart count
   $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");  
$stmt->bind_param('i', $user_id);  
$stmt->execute();  
$result = $stmt->get_result();  
$stats['cart_items'] = $result ? $result->fetch_assoc()['count'] : 0;  

    // Get order count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['orders'] = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $errors[] = "Error loading dashboard data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MTP_ND</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Welcome to Dashboard, <?php echo htmlspecialchars($username); ?>!</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p class="stat-number"><?php echo $stats['products']; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Items in Cart</h3>
                <p class="stat-number"><?php echo $stats['cart_items']; ?></p>
                <a href="<?php echo BASE_URL; ?>/cart.php">View Cart</a>
            </div>
            
            <div class="stat-card">
                <h3>Your Orders</h3>
                <p class="stat-number"><?php echo $stats['orders']; ?></p>
            </div>
        </div>
        
        <div class="dashboard-actions">
            <h2>Quick Links</h2>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/index.php">Shop</a></li>
                <li><a href="<?php echo BASE_URL; ?>/cart.php">Shopping Cart</a></li>
                <li><a href="<?php echo BASE_URL; ?>/products/list.php">Product Management</a></li>
                <li><a href="<?php echo BASE_URL; ?>/auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>
