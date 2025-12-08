<?php
require_once __DIR__ . '/auth/auth.php';

// Admins only
if (empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$order = null;
$items = [];
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Define possible order statuses
$order_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
$error = '';

if (!$order_id) {
    header('Location: orders.php');
    exit;
}

try {
    // Fetch the main order details along with customer info
    $stmt = $conn->prepare(
        "SELECT o.id, o.total_amount, o.status, o.created_at, u.fname, u.lname, u.email 
         FROM orders o 
         JOIN users u ON o.user_id = u.id 
         WHERE o.id = ?"
    );
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        // If order doesn't exist, redirect back to the list
        header('Location: orders.php');
        exit;
    }

    // Fetch the items associated with this order
    $stmt = $conn->prepare(
        "SELECT oi.quantity, oi.price, oi.size, p.name 
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id 
         WHERE oi.order_id = ?"
    );
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/header.php';
?>

<div class="container">
    <h1>Order Details #<?= (int)$order_id ?></h1>
    <a href="orders.php" class="btn btn-secondary mb-4">Back to Order List</a>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="card mb-4">
            <div class="card-header">Summary</div>
            <div class="card-body">
                <p><strong>Customer:</strong> <?= htmlspecialchars($order['fname'] . ' ' . $order['lname']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                <p><strong>Order Date:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                <p><strong>Order Total:</strong> $<?= number_format($order['total_amount'], 2) ?></p>
                <p><strong>Current Status:</strong> <span class="badge bg-primary"><?= htmlspecialchars($order['status']) ?></span></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Update Order Status</div>
            <div class="card-body">
                <form action="admin/update_order_status.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <?php foreach ($order_statuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>" <?= ($status === $order['status']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Items Ordered</div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <p>No items found for this order.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Quantity</th>
                                <th>Price per Item</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= htmlspecialchars($item['size']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td>$<?= number_format($item['price'], 2) ?></td>
                                <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>