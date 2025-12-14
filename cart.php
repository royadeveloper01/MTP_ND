<?php
require_once __DIR__ . '/db.php'; 


if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$cart_items = [];
$total      = 0;
$error      = '';

try {
    if (!empty($_SESSION['loggedin']) && !empty($_SESSION['id'])) {
      
        $user_id = (int)$_SESSION['id'];

        $stmt = $conn->prepare("
            SELECT 
                c.product_id,
                c.quantity,
                p.name,
                p.price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $quantity = (int)($row['quantity'] ?? 1);

            $cart_items[$row['product_id']] = [
                'id'       => $row['product_id'], 
                'name'     => $row['name'],
                'price'    => (float)$row['price'],
                'quantity' => $quantity,
            ];

            $total += $row['price'] * $quantity;
        }
        $stmt->close();

    } else {
        // ===== GUEST → SESSION =====
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $pid => $item) {
                $quantity = (int)($item['quantity'] ?? 1);

                $cart_items[$pid] = [
                    'id'       => $pid,
                    'name'     => $item['name'],
                    'price'    => (float)$item['price'],
                    'quantity' => $quantity,
                ];

                $total += $item['price'] * $quantity;
            }
        }
    }
} catch (Exception $e) {
    $error = 'Error loading cart: ' . $e->getMessage();
}

$cartCount = count($cart_items);
include 'header.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Cart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/cart.css">
</head>

<body>
<div class="container cart-page">

<div class="cart-header">
    <div>
        <h1>Your Cart</h1>
        <div class="cart-subtitle">Review and update the items before checkout.</div>
    </div>
    <span class="cart-badge"><?= $cartCount ?> item(s)</span>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <div class="empty-cart-card">
        <h2>Your cart is empty</h2>
        <a href="index.php" class="btn btn-primary">Continue shopping</a>
    </div>
<?php else: ?>

<form method="post" action="update_cart.php">
<div class="row g-3">

<div class="col-lg-8">
<div class="card cart-card">
<div class="card-body">
<table class="table cart-table align-middle">
<thead>
<tr>
<th>Product</th>
<th>Price</th>
<th>Quantity</th>
<th>Subtotal</th>
<th class="text-center">Action</th>
</tr>
</thead>
<tbody>

<?php foreach ($cart_items as $item): ?>
<tr>
<td><?= htmlspecialchars($item['name']) ?></td>
<td>$<?= number_format($item['price'], 2) ?></td>
<td style="max-width:100px">
<input type="number"
       name="quantity[<?= $item['id'] ?>]"
       value="<?= $item['quantity'] ?>"
       min="1"
       class="form-control form-control-sm">
</td>
<td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
<td class="text-center">
<a href="remove_from_cart.php?id=<?= $item['id'] ?>"
   class="btn btn-outline-danger btn-sm">Remove</a>
</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>
</div>
</div>

<div class="col-lg-4">
<div class="card cart-card">
<div class="card-body">
<div class="d-flex justify-content-between mb-2">
<strong>Total</strong>
<strong>$<?= number_format($total, 2) ?></strong>
</div>
<button class="btn btn-outline-secondary w-100 mb-2">Update Quantities</button>
<a href="checkout.php" class="btn btn-success w-100">Proceed to Checkout</a>
<a href="index.php" class="btn btn-link w-100">← Continue shopping</a>
</div>
</div>
</div>

</div>
</form>

<?php endif; ?>
</div>
</body>
</html>