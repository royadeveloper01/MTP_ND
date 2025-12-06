<?php
require_once __DIR__ . '/auth/auth.php'; // Handles session, db, and auto-login

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['id'];
$username = $_SESSION['fname'] ?? 'User';

// Fetch dashboard data
$stats = [];
$errors = [];

try {
    // Get total product count
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $stats['products'] = $result ? $result->fetch_assoc()['count'] : 0;

    // Get cart item count from session
    $stats['cart_items'] = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

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
