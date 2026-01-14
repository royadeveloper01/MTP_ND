<?php
// orders/details.php - User order details
// Load config to ensure BASE_URL is available
require_once __DIR__ . '/../config.php';

// Include auth.php for session start and DB connection
require_once __DIR__ . '/../auth/auth.php';

// Enforce login
if (empty($_SESSION['loggedin'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['id'];

// Redirect if invalid ID
if ($order_id <= 0) {
    header("Location: " . BASE_URL . "/orders/my_orders.php");
    exit;
}

$order = null;
$items = [];

// 1. Fetch Order Details (Verify ownership)
$sql = "SELECT id, created_at, status, total_amount FROM orders WHERE id = ? AND user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $order = $result->fetch_assoc();
    }
    $stmt->close();
}

// 2. Fetch Order Items
if ($order) {
    // Assumes 'order_items' table exists with columns: order_id, product_id, quantity, price
    // Assumes 'products' table exists with columns: id, name, image
    $sql_items = "SELECT oi.quantity, oi.price, p.name, p.image 
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = ?";
    if ($stmt = $conn->prepare($sql_items)) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order Details #<?= $order_id ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="py-3">
    <?php if (!$order): ?>
        <div class="alert alert-danger">Order not found or you do not have permission to view it.</div>
        <a href="<?= BASE_URL ?>/orders/my_orders.php" class="btn btn-secondary">Back to Orders</a>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Order #<?= htmlspecialchars($order['id']) ?></h2>
            <a href="<?= BASE_URL ?>/orders/my_orders.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Orders</a>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><strong>Date:</strong> <?= htmlspecialchars($order['created_at']) ?></div>
                    <div class="col-md-4"><strong>Status:</strong> <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></div>
                    <div class="col-md-4"><strong>Total:</strong> <span class="text-success fw-bold">$<?= number_format($order['total_amount'], 2) ?></span></div>
                </div>
            </div>
        </div>

        <h4 class="mb-3">Items</h4>
        <ul class="list-group shadow-sm">
            <?php foreach ($items as $item): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="my-0"><?= htmlspecialchars($item['name'] ?? 'Product Unavailable') ?></h6>
                        <small class="text-muted">Qty: <?= (int)$item['quantity'] ?> x $<?= number_format($item['price'], 2) ?></small>
                    </div>
                    <span class="text-muted">$<?= number_format($item['quantity'] * $item['price'], 2) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
