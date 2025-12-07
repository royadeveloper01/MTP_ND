<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

// Redirect if not logged in or if user is an admin
if (empty($_SESSION['loggedin'])) {
    header("Location: auth/login.php");
    exit;
}
if (!empty($_SESSION['is_admin'])) {
    header("Location: dashboard.php");
    exit;
}

$cart_items = [];
$total = 0;
$error = '';

try {
    // --- Use Session Cart and Verify with DB ---
    if (!empty($_SESSION['cart'])) {
        $session_cart = $_SESSION['cart'];
        $product_ids = array_keys($session_cart);

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $types = str_repeat('i', count($product_ids));

        $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$product_ids);
        $stmt->execute();
        $products_from_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Create a map for easy lookup
        $product_map = [];
        foreach ($products_from_db as $p) {
            $product_map[$p['id']] = $p;
        }

        // Build the final cart item list, ensuring products exist and using DB price
        foreach ($session_cart as $product_id => $item) {
            if (isset($product_map[$product_id])) {
                $product = $product_map[$product_id];
                $cart_items[] = [
                    'id' => $product_id,
                    'name' => $product['name'],
                    'price' => (float)$product['price'], // Authoritative price from DB
                    'quantity' => (int)$item['qty']
                ];
            }
        }
    }
} catch (Exception $e) {
    $error = "Error loading cart details: " . $e->getMessage();
}

include 'header.php';
?>

<div class="container">
    <h1>Your Cart</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <p>Your cart is empty. <a href="index.php">Continue shopping</a></p>

    <?php else: ?>

        <form method="post" action="update_cart.php"> <!-- This form correctly updates the session -->
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal; // Calculate total based on verified prices
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td>
                            <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= (int)$item['quantity'] ?>" min="1" class="form-control" style="width: 80px;">
                        </td>
                        <td>$<?= number_format($subtotal, 2) ?></td>
                        <td>
                            <a href="remove_from_cart.php?id=<?= $item['id'] ?>" class="btn btn-danger btn-sm">Remove</a> <!-- This correctly removes from session -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td colspan="2"><strong>$<?= number_format($total, 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>

            <div class="mt-3">
                <button class="btn btn-primary" type="submit">Update Quantities</button>
                <a class="btn btn-success" href="checkout.php">Proceed to Checkout</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
