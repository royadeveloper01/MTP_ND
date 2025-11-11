<?php
include 'db.php';
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) { header("Location: list.php"); exit; }

if ($_POST && isset($_POST['confirm_delete'])) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: list.php?deleted=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Product</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width:500px;margin:50px auto;">
    <h3>Confirm Delete</h3>
    <div class="alert alert-warning">
        Delete <strong><?= htmlspecialchars($product['name']) ?></strong>? This cannot be undone.
    </div>
    <form method="post" style="display:inline;">
        <button name="confirm_delete" class="btn btn-danger">Yes, Delete</button>
    </form>
    <a href="list.php" class="btn btn-default">Cancel</a>
</div>
</body>
</html>