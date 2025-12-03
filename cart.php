<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
$cart = $_SESSION['cart'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cart</title>
  <style>
    body{font-family: Arial;padding:20px;}
    table{border-collapse:collapse;width:100%;}
    th,td{padding:8px;border:1px solid #ddd;text-align:left;}
    .btn{padding:6px 10px;background:#2d8cff;color:#fff;border:none;border-radius:4px;text-decoration:none;}
  </style>
</head>
<body>

<h1>Your Cart</h1>

<?php if (empty($cart)): ?>
  <p>Your cart is empty. <a href="index.php">Continue shopping</a></p>

<?php else: ?>

  <form method="post" action="update_cart.php">
  <table>
    <tr>
      <th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th>Action</th>
    </tr>

    <?php $total = 0;
    foreach ($cart as $id => $it):
        $subtotal = $it['price'] * $it['qty'];
        $total += $subtotal;
    ?>
    <tr>
      <td><?= htmlspecialchars($it['name']) ?></td>
      <td>$<?= number_format($it['price'],2) ?></td>
      <td><input type="number" name="qty[<?= htmlspecialchars($id) ?>]" value="<?= intval($it['qty']) ?>" min="1" style="width:70px"></td>
      <td>$<?= number_format($subtotal,2) ?></td>
      <td><a class="btn" href="remove_from_cart.php?id=<?= urlencode($id) ?>">Remove</a></td>
    </tr>
    <?php endforeach; ?>

    <tr>
      <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
      <td colspan="2"><strong>$<?= number_format($total,2) ?></strong></td>
    </tr>

  </table>

  <p style="margin-top:12px;">
    <button class="btn" type="submit">Update quantities</button>
    <a class="btn" href="checkout.php" style="background:#28a745;">Checkout (demo)</a>
  </p>

  </form>
<?php endif; ?>

</body>
</html>
