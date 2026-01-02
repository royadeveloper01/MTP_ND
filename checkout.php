<?php
require_once __DIR__ . '/config.php';

require_once __DIR__ . '/auth/auth.php';

// User must be logged in and not an admin
if (empty($_SESSION['loggedin']) || !empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$user_id = $_SESSION['id'];
$cart_from_db = [];

// FETCH: Size and color included for variation support
$stmt = $conn->prepare("SELECT product_id, quantity, size, color FROM cart WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_from_db[] = $row;
}
$stmt->close();

if (empty($cart_from_db)) {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$final_items = [];
$final_total = 0;
$error = '';
$is_in_transaction = false;

try {
    $product_ids = array_unique(array_column($cart_from_db, 'product_id'));
    
    if (empty($product_ids)) {
        throw new Exception("No products found in your cart.");
    }

    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));

    $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $products_from_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $product_map = [];
    foreach ($products_from_db as $p) {
        $product_map[$p['id']] = $p;
    }

    foreach ($cart_from_db as $item) {
        $pid = $item['product_id'];
        if (isset($product_map[$pid])) {
            $product = $product_map[$pid];
            $qty = (int)$item['quantity'];
            
            $final_items[] = [
                'id'       => $pid,
                'name'     => $product['name'],
                'size'     => $item['size'],
                'color'    => $item['color'],
                'price'    => (float)$product['price'],
                'quantity' => $qty
            ];
            $final_total += $product['price'] * $qty;
        }
    }

    if (empty($final_items)) {
        throw new Exception("Invalid products in cart.");
    }

    // Database Transaction
    $conn->begin_transaction();
    $is_in_transaction = true;

    $shipping_address = 'Address on file';

    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)");
    $stmt->bind_param('ids', $user_id, $final_total, $shipping_address);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // INSERT: variations correctly recorded in order history
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, size, color, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($final_items as $item) {
        $stmt->bind_param('iissid', $order_id, $item['id'], $item['size'], $item['color'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $is_in_transaction = false;

} catch (Exception $e) {
    if ($is_in_transaction) {
        $conn->rollback();
    }
    // Log the real error for debugging (visible in server logs)
    error_log("Checkout failed for user {$user_id}: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    
    // Show only a generic, user-friendly message (no sensitive details leaked)
    $error = "We could not process your order at this time. Please try again later or contact support.";
}

include __DIR__ . '/header.php';
?>

<div class="container my-5">
    <?php if ($error): ?>
        <div class="alert alert-danger text-center">
            <h1><i class="bi bi-x-circle"></i> Order Failed</h1>
            <p><?= htmlspecialchars($error) ?></p>
            <a href="cart.php" class="btn btn-secondary">Return to Cart</a>
        </div>
    <?php else: ?>
        <div class="text-center mb-5">
            <h1 class="text-success"><i class="bi bi-check-circle-fill"></i> Order Success!</h1>
            <p class="lead">Thank you for your purchase. Your order number is <strong>#<?= (int)$order_id ?></strong>.</p>
        </div>

        <div class="card shadow-sm mx-auto checkout-summary-card">
            <div class="card-header bg-primary text-white">Order Summary</div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($final_items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                <small class="text-muted">
                                    Size: <?= htmlspecialchars($item['size']) ?>, Color: <?= htmlspecialchars($item['color']) ?><br>
                                    Qty: <?= $item['quantity'] ?> @ $<?= number_format($item['price'], 2) ?>
                                </small>
                            </div>
                            <span class="fw-bold">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between px-3">
                    <h4>Total</h4>
                    <h4 class="text-success">$<?= number_format($final_total, 2) ?></h4>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>