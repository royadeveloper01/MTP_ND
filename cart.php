<?php
require_once __DIR__ . '/db.php'; // Includes session_start()

// Admins should not see a cart page
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$cart = [];
$total = 0;

if (!empty($_SESSION['loggedin'])) {
    // --- LOGGED-IN USER: Fetch from database ---
    $user_id = $_SESSION['id'];
    try {
        $sql = "SELECT c.product_id, c.quantity, p.name, p.price 
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Re-key the array by product_id for consistency
            $cart[$row['product_id']] = [
                'name' => $row['name'],
                'price' => $row['price'],
                'qty' => $row['quantity']
            ];
        }
        $stmt->close();
    } catch (Exception $e) { /* Handle DB error if needed */ }

} else {
    // --- GUEST USER: Fetch from session ---
    $cart = $_SESSION['cart'] ?? [];
}
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

<div class="container">
    <h1>Your Cart</h1>

    <?php if (empty($cart)): ?>
      <p>Your cart is empty. <a href="index.php">Continue shopping</a></p>

    <?php else: ?>

      <form method="post" action="update_cart.php">
      <table class="table">
        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($cart as $id => $it):
            $subtotal = $it['price'] * $it['qty'];
            $total += $subtotal;
        ?>
        <tr>
          <td><?= htmlspecialchars($it['name']) ?></td>
          <td>$<?= number_format($it['price'],2) ?></td>
          <td><input type="number" class="form-control" name="qty[<?= htmlspecialchars($id) ?>]" value="<?= intval($it['qty']) ?>" min="0" style="width:80px"></td>
          <td>$<?= number_format($subtotal,2) ?></td>
          <td><a class="btn btn-danger btn-sm" href="remove_from_cart.php?id=<?= urlencode($id) ?>">Remove</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3" class="text-end"><strong>Total:</strong></td><td colspan="2"><strong>$<?= number_format($total,2) ?></strong></td></tr></tfoot>
      </table>

      <div class="mt-3 d-flex justify-content-between">
        <a class="btn btn-secondary" href="index.php">Continue Shopping</a>
        <div>
            <button class="btn btn-primary" type="submit">Update Quantities</button>
            <a class="btn btn-success" href="checkout.php">Proceed to Checkout</a>
        </div>
      </div>
      </form>
    <?php endif; ?>
</div>

</body>
</html>
