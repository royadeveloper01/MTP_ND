<?php
require_once __DIR__ . '/auth/auth.php'; // Includes session, db, etc.

// User must be logged in and not an admin
if (empty($_SESSION['loggedin']) || !empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Cart must not be empty
if (empty($_SESSION['cart'])) {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$user_id = $_SESSION['id'];
$cart = $_SESSION['cart'];
$final_items = [];
$final_total = 0;
$error = '';
$is_in_transaction = false;

try {
    // --- Verify products and calculate final total from DB prices ---
    $product_ids = array_keys($cart);
    if (!empty($cart)) {
        $product_ids = [];
        foreach ($cart as $cart_key => $item) {
            $product_ids[] = $item['product_id'];
        }
        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $types = str_repeat('i', count($product_ids));

        $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$product_ids);
        $stmt->execute();
        $products_from_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Create a map of product_id => product_data for easy lookup
        $product_map = [];
        foreach ($products_from_db as $p) {
            $product_map[$p['id']] = $p;
        }

        // Build the final item list using DB prices and session quantities
        foreach ($cart as $cart_key => $item) {
            $product_id = $item['product_id'];
            if (isset($product_map[$product_id])) {
                $product = $product_map[$product_id];
                $quantity = (int)$item['qty'];
                if ($quantity > 0) {
                    $final_items[] = [
                        'id' => $product_id,
                        'name' => $product['name'],
                        'size' => $item['size'],
                        'price' => (float)$product['price'], // Use price from DB
                        'quantity' => $quantity
                    ];
                    $final_total += $product['price'] * $quantity;
                }
            }
        }
    }

    if (empty($final_items)) {
        throw new Exception("Your cart contains invalid items.");
    }

    // --- Create Order in a Transaction ---
    $conn->begin_transaction();
    $is_in_transaction = true;

    // Placeholder for shipping info until a form is created
    $shipping_address = '123 Main St, Anytown, USA';

    // 1. Insert into `orders` table
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)");
    $stmt->bind_param('ids', $user_id, $final_total, $shipping_address);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // 2. Insert into `order_items` table
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, size, quantity, price) VALUES (?, ?, ?, ?, ?)");
    foreach ($final_items as $item) {
        $stmt->bind_param('iisid', $order_id, $item['id'], $item['size'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    $stmt->close();

    // 3. Commit transaction
    $conn->commit();
    $is_in_transaction = false;

    // 4. Clear the cart
    unset($_SESSION['cart']);

} catch (Exception $e) {
    if ($is_in_transaction) {
        $conn->rollback();
    }
    $error = "Could not process your order. Please try again. Error: " . $e->getMessage();
}

include __DIR__ . '/header.php';
?>

<div class="container">
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <h1>Order Failed</h1>
            <p><?= htmlspecialchars($error) ?></p>
            <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
        </div>
    <?php else: ?>
        <h1>Checkout Complete!</h1>
        <p>Your order #<?= (int)$order_id ?> has been placed successfully.</p>

        <div class="card">
            <div class="card-header">Order Summary</div>
            <div class="card-body">
                <p><strong>Total Paid:</strong> $<?= number_format($final_total, 2) ?></p>
                <h5>Items Ordered:</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($final_items as $item): ?>
                        <li class="list-group-item">
                            <?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['size']) ?>) &times; <?= (int)$item['quantity'] ?>
                            (Subtotal: $<?= number_format($item['price'] * $item['quantity'], 2) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <p class="mt-4"><a href="index.php" class="btn btn-primary">Continue Shopping</a></p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
