<?php
require_once __DIR__ . '/db.php';

// Admins should not see a cart page
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Always initialize variables to avoid `undefined` errors.
$cart_items = [];
$total      = 0;
$error      = '';

try {
    // --- Use Session Cart and Verify with DB ---
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $session_cart = $_SESSION['cart'];
        // Extract unique product IDs from the session cart using array_column
        $product_ids = array_unique(array_column($session_cart, 'product_id'));

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
        foreach ($session_cart as $cart_key => $item) {
            $product_id = $item['product_id'];
            if (isset($product_map[$product_id])) {
                $product = $product_map[$product_id];
                $cart_items[] = [
                    'cart_key' => $cart_key,
                    'name' => $product['name'],
                    'price' => (float)$product['price'], // Authoritative price from DB
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => (int)$item['qty']
                ];
            }
        }
    }
} catch (Exception $e) {
    $error = "Error loading cart details: " . $e->getMessage();
}

// Get the number of items to display in the badge, ensuring it's always safe. 
$cartCount = is_array($cart_items) ? count($cart_items) : 0;

include 'header.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{padding:20px;}
    .btn{text-decoration:none;}
  </style>
</head>
<body>

<link rel="stylesheet" href="assets/css/cart.css">

<div class="container cart-page">
    <!-- Header -->
    <div class="cart-header">
        <div>
            <h1>Your Cart</h1>
            <div class="cart-subtitle">
                Review and update the items before checkout.
            </div>
        </div>
        <div>
            <span class="cart-badge">
                <?= $cartCount ?> item(s)
            </span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart-card">
            <h2>Your cart is empty</h2>
            <p>Looks like you haven’t added anything to your cart yet.</p>
            <a href="index.php" class="btn btn-primary">
                Continue shopping
            </a>
        </div>
    <?php else: ?>

        <form method="post" action="update_cart.php"> <!-- This form correctly updates the session -->
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th><th>Size</th><th>Color</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal; // Calculate total based on verified prices
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['size'] !== 'default' ? htmlspecialchars($item['size']) : 'N/A' ?></td>
                        <td><?= $item['color'] !== 'default' ? htmlspecialchars($item['color']) : 'N/A' ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td>
                            <input type="number" name="qty[<?= $item['cart_key'] ?>]" value="<?= (int)$item['quantity'] ?>" min="1" class="form-control" style="width: 80px;">
                        </td>
                        <td>$<?= number_format($subtotal, 2) ?></td>
                        <td>
                            <a href="remove_from_cart.php?key=<?= $item['cart_key'] ?>" class="btn btn-danger btn-sm">Remove</a> <!-- This correctly removes from session -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end"><strong>Total:</strong></td>
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

</body>
</html>
