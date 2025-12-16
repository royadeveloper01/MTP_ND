<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/header.php';

// Admins should not see cart
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Success message
$cart_success_message = $_SESSION['cart_success'] ?? '';
unset($_SESSION['cart_success']);

// Error message
$cart_error_message = '';

$cart_items = [];
$total = 0;

try {
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $session_cart = $_SESSION['cart'];
        $product_ids = array_unique(array_column($session_cart, 'product_id'));

        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $types = str_repeat('i', count($product_ids));

            $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$product_ids);
            $stmt->execute();
            $products_from_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $products_map = [];
            foreach ($products_from_db as $p) {
                $products_map[$p['id']] = $p;
            }

            foreach ($session_cart as $key => $item) {
                $prod_id = $item['product_id'];
                if (isset($products_map[$prod_id])) {
                    $product = $products_map[$prod_id];
                    $quantity = $item['qty'];
                    $subtotal = $product['price'] * $quantity;

                    $cart_items[] = [
                        'cart_key'  => $key,
                        'name'      => $product['name'],
                        'image'     => $product['image'],
                        'price'     => $product['price'],
                        'size'      => $item['size'] === 'default' ? '-' : $item['size'],
                        'color'     => $item['color'] === 'default' ? '-' : $item['color'],
                        'quantity'  => $quantity,
                        'subtotal'  => $subtotal
                    ];
                    $total += $subtotal;
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
    $cart_error_message = 'There was a problem loading your cart. Please try again later.';
}
?>

<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-cart4"></i> Your Shopping Cart</h1>

    <!-- Success Message -->
    <?php if (!empty($cart_success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($cart_success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- MAIN CONDITIONAL LOGIC -->
    <?php if (!empty($cart_error_message)): ?>
        <!-- ERROR STATE -->
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($cart_error_message) ?>
        </div>
        <div class="text-center py-5">
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-left-circle"></i> Back to Shop
            </a>
        </div>

    <?php elseif (!empty($cart_items)): ?>
        <!-- NORMAL CART WITH ITEMS -->
        <form method="POST" action="<?= BASE_URL ?>/update_cart.php">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Image</th>
                            <th>Price</th>
                            <th>Size</th>
                            <th>Color</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                <td>
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="img-thumbnail cart-item-image">
                                    <?php else: ?>
                                        <div class="cart-item-placeholder">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($item['price'], 2) ?></td>
                                <td><?= htmlspecialchars($item['size']) ?></td>
                                <td><?= htmlspecialchars($item['color']) ?></td>
                                <td>
                                    <input type="number" name="qty[<?= $item['cart_key'] ?>]" value="<?= $item['quantity'] ?>" min="1" class="form-control w-75">
                                </td>
                                <td><strong>$<?= number_format($item['subtotal'], 2) ?></strong></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/remove_from_cart.php?key=<?= urlencode($item['cart_key']) ?>" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-success">
                            <td colspan="6" class="text-end"><strong>Total:</strong></td>
                            <td colspan="2"><strong>$<?= number_format($total, 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-arrow-left-circle"></i> Continue Shopping
                </a>
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-secondary btn-lg">Update Quantities</button>
                    <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-success btn-lg">Proceed to Checkout</a>
                </div>
            </div>
        </form>

    <?php else: ?>
        <!-- EMPTY CART STATE -->
        <div class="text-center py-5">
            <i class="bi bi-cart-x empty-cart-icon"></i>
            <h3 class="mt-3 text-muted">Your cart is empty</h3>
            <p class="text-muted">Looks like you haven't added anything yet.</p>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-lg mt-3">
                <i class="bi bi-shop"></i> Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>