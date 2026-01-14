<?php
require_once dirname(__DIR__) . '/config.php';

require_once __DIR__ . '/auth.php';

// Enforce login
if (empty($_SESSION['loggedin'])) {
    header("Location: " . BASE_URL . "/auth/login.php?redirect=checkout");
    exit;
}

$user_id = $_SESSION['id'];
$error = '';

// 1. Fetch Cart Items
$cart_items = [];
$total_amount = 0;

$sql_cart = "SELECT c.product_id, c.quantity, p.name, p.price 
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?";
if ($stmt = $conn->prepare($sql_cart)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_amount += ($row['price'] * $row['quantity']);
    }
    $stmt->close();
}

if (empty($cart_items)) {
    // Redirect if cart is empty
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// 2. Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $conn->begin_transaction();
    try {
        // A. Insert Order
        $status = 'pending';
        $sql_order = "INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql_order);
        $stmt->bind_param("ids", $user_id, $total_amount, $status);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // B. Insert Order Items
        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_item);
        foreach ($cart_items as $item) {
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        $stmt->close();

        // C. Clear Cart
        $sql_clear = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql_clear);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        
        // Redirect to Order Details
        header("Location: " . BASE_URL . "/orders/details.php?id=" . $order_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to place order: " . $e->getMessage();
    }
}
?>
<?php include dirname(__DIR__) . '/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">Order Summary</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($cart_items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="my-0"><?= htmlspecialchars($item['name']) ?></h6>
                                <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                            </div>
                            <span class="text-muted">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Total (USD)</strong>
                        <strong>$<?= number_format($total_amount, 2) ?></strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Payment Details</h5>
                    <p class="card-text text-muted">This is a demo checkout. No actual payment processing will occur.</p>
                    
                    <form method="post">
                        <button type="submit" name="place_order" class="btn btn-success w-100 btn-lg">Place Order</button>
                    </form>
                    <a href="<?= BASE_URL ?>/cart.php" class="btn btn-outline-secondary w-100 mt-2">Back to Cart</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/footer.php'; ?>
