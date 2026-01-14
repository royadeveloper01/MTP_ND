<?php
// orders/index.php - Admin order list
require_once __DIR__ . '/../auth/auth.php';

// Admins only
if (empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$orders = [];
$error = '';

try {
    // Fetch all orders with user details
    $sql = "SELECT o.id, o.user_id, o.total_amount, o.status, o.created_at, u.fname, u.lname 
            FROM orders o 
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC";
    $result = $conn->query($sql);
    $orders = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h1>Order Management</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p>No orders have been placed yet.</p>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= (int)$order['id'] ?></td>
                    <td><?= htmlspecialchars($order['fname'] . ' ' . $order['lname']) ?></td>
                    <td>$<?= number_format($order['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($order['created_at']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($order['status']) ?></span></td>
                    <td>
                        <a href="<?= BASE_URL ?>/orders/view.php?id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-info">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
