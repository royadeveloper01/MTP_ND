<?php
require_once __DIR__ . '/db.php'; // Includes session_start()

// Admins should not see a cart page
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// luôn luôn khởi tạo các biến để tránh undefined
$cart_items = [];
$total      = 0;
$error      = '';

try {
    // --- Use Session Cart and Verify with DB ---
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $session_cart = $_SESSION['cart'];
        $product_ids  = array_keys($session_cart);

        if (!empty($product_ids)) {
            // Create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $types        = str_repeat('i', count($product_ids));

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
                        'id'       => $product_id,
                        'name'     => $product['name'],
                        'price'    => (float)$product['price'], // Authoritative price from DB
                        'quantity' => (int)$item['qty']
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    $error = "Error loading cart details: " . $e->getMessage();
}

// số lượng item để show badge, luôn safe
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

<style>
    body {
        background-color: #f5f7fb;
    }

    .cart-page {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .cart-header h1 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 600;
    }

    .cart-subtitle {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .cart-badge {
        padding: 0.35rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 500;
        background-color: #e7f1ff;
        color: #0d6efd;
    }

    .cart-card {
        border-radius: 1rem;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
        border: none;
    }

    .cart-card .card-header {
        border-bottom: none;
        background-color: #ffffff;
        border-radius: 1rem 1rem 0 0;
        padding-bottom: 0;
    }

    .cart-table thead th {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        border-bottom: 1px solid rgba(148, 163, 184, 0.3);
    }

    .cart-table td {
        vertical-align: middle;
    }

    .cart-summary-title {
        font-size: 1rem;
        font-weight: 600;
    }

    .cart-total-label {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .cart-total-value {
        font-size: 1.4rem;
        font-weight: 700;
    }

    .empty-cart-card {
        border-radius: 1rem;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
        border: none;
        text-align: center;
        padding: 2.5rem 1.5rem;
        background-color: #ffffff;
    }

    .empty-cart-card h2 {
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .empty-cart-card p {
        color: #6c757d;
        margin-bottom: 1rem;
    }

    @media (max-width: 576px) {
        .cart-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

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

        <form method="post" action="update_cart.php">
            <div class="row g-3">
                <!-- Left: items table -->
                <div class="col-lg-8">
                    <div class="card cart-card">
                        <div class="card-header">
                            <h2 class="h6 mb-3">Items in your cart</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table cart-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Price</th>
                                            <th scope="col">Quantity</th>
                                            <th scope="col">Subtotal</th>
                                            <th scope="col" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <?php
                                                $subtotal = $item['price'] * $item['quantity'];
                                                $total   += $subtotal;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['name']) ?></td>
                                                <td>$<?= number_format($item['price'], 2) ?></td>
                                                <td style="max-width: 100px;">
                                                    <input
                                                        type="number"
                                                        name="qty[<?= $item['id'] ?>]"
                                                        value="<?= (int)$item['quantity'] ?>"
                                                        min="1"
                                                        class="form-control form-control-sm"
                                                    >
                                                </td>
                                                <td>$<?= number_format($subtotal, 2) ?></td>
                                                <td class="text-center">
                                                    <a
                                                        href="remove_from_cart.php?id=<?= $item['id'] ?>"
                                                        class="btn btn-outline-danger btn-sm"
                                                    >
                                                        Remove
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: summary -->
                <div class="col-lg-4">
                    <div class="card cart-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="cart-summary-title">Order Summary</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="cart-total-label">Total</span>
                                <span class="cart-total-value">
                                    $<?= number_format($total, 2) ?>
                                </span>
                            </div>
                            <hr>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-secondary" type="submit">
                                    Update Quantities
                                </button>
                                <a class="btn btn-success" href="checkout.php">
                                    Proceed to Checkout
                                </a>
                                <a class="btn btn-link text-decoration-none" href="index.php">
                                    ← Continue shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    <?php endif; ?>
</div>

</body>
</html>
