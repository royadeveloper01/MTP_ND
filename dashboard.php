<?php
require_once __DIR__ . '/auth/auth.php'; // Handles session, db, and auto-login

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Base info
$user_id  = $_SESSION['id'] ?? null;
$username = isset($_SESSION['fname']) && $_SESSION['fname'] !== '' ? $_SESSION['fname'] : 'User';
$is_admin = !empty($_SESSION['is_admin']);

// Prepare default stats to avoid undefined errors
$stats = [
    'products'   => 0,
    'orders'     => 0,
    'customers'  => 0,
    'attributes' => 0,
    'cart_items' => 0,
];

$errors = [];

try {
    if ($is_admin) {
        // Admin stats
        $result = $conn->query("SELECT COUNT(*) as count FROM products");
        if ($result) {
            $stats['products'] = (int)$result->fetch_assoc()['count'];
        }

        $result = $conn->query("SELECT COUNT(*) as count FROM orders");
        if ($result) {
            $stats['orders'] = (int)$result->fetch_assoc()['count'];
        }

        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
        if ($result) {
            $stats['customers'] = (int)$result->fetch_assoc()['count'];
        }

        // Calculate attributes count safely (Sizes + Colors)
        $resSizes = $conn->query("SELECT COUNT(*) FROM sizes");
        if ($resSizes) {
            $stats['attributes'] += (int)$resSizes->fetch_row()[0];
        }
        $resColors = $conn->query("SELECT COUNT(*) FROM colors");
        if ($resColors) {
            $stats['attributes'] += (int)$resColors->fetch_row()[0];
        }
    } else {
        // Regular user stats
        $result = $conn->query("SELECT COUNT(*) AS total_sizes, (SELECT COUNT(*) FROM colors) AS total_colors FROM sizes");
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['attributes'] = (int)$row['total_sizes'] + (int)$row['total_colors'];
        }

        // Get cart item count from session
        $stats['cart_items'] = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

        // Get order count
        if ($user_id !== null) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $stats['orders'] = (int)$result->fetch_assoc()['count'];
            }
        }
    }
} catch (Exception $e) {
    $errors[] = "Error loading dashboard data: " . $e->getMessage();
}

$page_css = '/assets/css/dashboard.css';
?>
<?php include 'header.php'; ?>
    

    <div class="container dashboard-wrapper">
        <div class="dashboard-title">
            <div>
                <h1>
                    <?php if ($is_admin): ?>
                        Admin Dashboard
                    <?php else: ?>
                        Welcome, <?php echo htmlspecialchars($username); ?>!
                    <?php endif; ?>
                </h1>
                <div class="dashboard-subtitle">
                    Here’s a quick overview of your store.
                </div>
            </div>

            <div>
                <?php if ($is_admin): ?>
                    <span class="role-badge role-admin">Admin</span>
                <?php else: ?>
                    <span class="role-badge role-user">Customer</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-stats">
            <?php if ($is_admin): ?>
                <div class="stat-card">
                    <div>
                        <h3>Total Products</h3>
                        <p class="stat-number"><?php echo $stats['products']; ?></p>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/products/list.php">Manage Products →</a>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <h3>Total Orders</h3>
                        <p class="stat-number"><?php echo $stats['orders']; ?></p>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/orders.php">Manage Orders →</a>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <h3>Total Customers</h3>
                        <p class="stat-number"><?php echo $stats['customers']; ?></p>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/customers/customers.php">Manage Customers →</a>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Attributes</h3>
                    <p class="stat-number"><?php echo $stats['attributes']; ?></p>
                    <a href="<?php echo BASE_URL; ?>/attributes.php">Manage Sizes & Colors</a>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <div>
                        <h3>Total Products</h3>
                        <p class="stat-number"><?php echo $stats['products']; ?></p>
                    </div>
                    <div>
                        <small class="text-muted">Browse all available items.</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <h3>Items in Cart</h3>
                        <p class="stat-number"><?php echo $stats['cart_items']; ?></p>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/cart.php">View Cart →</a>
                    </div>
                </div>
                <div class="stat-card">
                    <div>
                        <h3>Your Orders</h3>
                        <p class="stat-number"><?php echo $stats['orders']; ?></p>
                    </div>
                    <div>
                        <small class="text-muted">Track your purchase history.</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-actions row">
            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h2>Quick Links</h2>
                        <ul class="list-unstyled quick-links mb-0">
                            <li class="d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-shop me-2"></i>Shop</span>
                                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-sm btn-outline-primary">Go</a>
                            </li>
                            <?php if (!$is_admin): ?>
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-cart4 me-2"></i>Shopping Cart</span>
                                    <a href="<?php echo BASE_URL; ?>/cart.php" class="btn btn-sm btn-outline-secondary">View</a>
                                </li>
                            <?php endif; ?>
                            <li class="d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-box-arrow-right me-2"></i>Logout</span>
                                <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h2>Today’s Summary</h2>
                        <?php if ($is_admin): ?>
                            <p class="mb-1">
                                Products: <strong><?php echo $stats['products']; ?></strong><br>
                                Orders: <strong><?php echo $stats['orders']; ?></strong><br>
                                Customers: <strong><?php echo $stats['customers']; ?></strong>
                            </p>
                            <small class="text-muted">Use quick links to manage your store efficiently.</small>
                        <?php else: ?>
                            <p class="mb-1">
                                Cart items: <strong><?php echo $stats['cart_items']; ?></strong><br>
                                Orders placed: <strong><?php echo $stats['orders']; ?></strong>
                            </p>
                            <small class="text-muted">Continue shopping to discover more products.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>