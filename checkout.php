<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$cart = $_SESSION['cart'];
$total = 0;

foreach ($cart as $it) {
    $total += $it['price'] * $it['qty'];
}

// Clear cart after checkout (demo)
unset($_SESSION['cart']);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Checkout</title></head>
<body>
<h1>Checkout Complete</h1>

<p><strong>Total paid:</strong> $<?= number_format($total,2) ?></p>

<h3>Items:</h3>
<ul>
<?php foreach ($cart as $it): ?>
    <li><?= htmlspecialchars($it['name']) ?> × <?= intval($it['qty']) ?> — $<?= number_format($it['price'],2) ?></li>
<?php endforeach; ?>
</ul>

<p><a href="index.php">Back to shop</a></p>
</body>
</html>
