<?php
// view_order.php
if (file_exists(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
if (!defined('BASE_URL')) define('BASE_URL', '/MTP_ND');

require_once __DIR__ . '/auth.php';

// Admins only
if (empty($_SESSION['loggedin']) || empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Fetch Order Details
$order = null;
$items = [];

// Get order info + user info
$sql = "SELECT o.*, u.fname, u.lname, u.email, u.phone_number 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $order = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$order) {
    include dirname(__DIR__) . '/header.php';
    echo '<div class="container my-5">
            <div class="alert alert-danger">Order not found.</div>
            <a href="orders.php" class="btn btn-secondary">Back</a>
          </div>';
    include dirname(__DIR__) . '/footer.php';
    exit;
}

// Fetch Items
$sql_items = "SELECT oi.*, p.name, p.image 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
if ($stmt = $conn->prepare($sql_items)) {
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
}

include dirname(__DIR__) . '/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Order #<?= $order['id'] ?> Details</h1>
        <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Order Items Column -->
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Items Ordered</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($item['image']) ?>"
                                         class="rounded order-item-img">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center order-item-img">No Img</div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($item['name'] ?? 'Unknown Product') ?></h6>
                                    <small class="text-muted">
                                        <?php if (!empty($item['size'])): ?>Size: <?= htmlspecialchars($item['size']) ?><?php endif; ?>
                                        <?php if (!empty($item['color'])): ?> | Color: <?= htmlspecialchars($item['color']) ?><?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small"><?= $item['quantity'] ?> × $<?= number_format($item['price'], 2) ?></div>
                                <div class="fw-bold">$<?= number_format($item['quantity'] * $item['price'], 2) ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-footer text-end bg-white">
                    <h5 class="mb-0">Total: <span class="text-success">$<?= number_format($order['total_amount'], 2) ?></span></h5>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($order['fname'].' '.$order['lname']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone_number'] ?? 'N/A') ?></p>
                    <hr>
                    <p><strong>Order Date:</strong><br><?= htmlspecialchars($order['created_at']) ?></p>
                    <p><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'] ?? '')) ?></p>
                </div>
            </div>

            <!-- Status Update -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Status</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= BASE_URL ?>/update_order_status.php">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/footer.php'; ?>
