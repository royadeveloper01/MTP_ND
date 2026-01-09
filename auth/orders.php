<?php
// orders.php
// Load config to ensure BASE_URL is available
require_once dirname(__DIR__) . '/config.php';

// Include auth.php for session start, DB connection, and auto-login check
require_once __DIR__ . '/auth.php';

// Enforce login: Redirect to login if not logged in
require_login();

$user_id = $_SESSION['id'];
$orders = [];

// Fetch orders from database
// Note: This assumes you have an 'orders' table with columns: id, user_id, status, total_amount, created_at
$sql = "SELECT id, created_at, status, total_amount FROM orders WHERE user_id = ? ORDER BY created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}
?>
<?php include dirname(__DIR__) . '/header.php'; ?>

<div class="container orders-container">
    <h2>My Orders</h2>
    <?php if (empty($orders)): ?>
        <p>You have not placed any orders yet.</p>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($order['id']) ?></td>
                        <td><?= htmlspecialchars($order['created_at']) ?></td>
                        <td>
                            <span class="status-badge">
                                <?= htmlspecialchars(ucfirst($order['status'])) ?>
                            </span>
                        </td>
                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/auth/view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">View</a> 
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/footer.php'; ?>
