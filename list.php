<?php
include 'db.php';
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }

$products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product List</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="margin:50px auto;">
    <h2>Product List</h2>
    <a href="add.php" class="btn btn-success">Add New</a>
    <a href="index.php" class="btn btn-default">Home</a>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Deleted!</div>
    <?php endif; ?>

    <table class="table table-bordered" style="margin-top:20px;">
        <thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Category</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td>$<?= number_format($p['price'], 2) ?></td>
                    <td><?= ucfirst($p['category']) ?></td>
                    <td>
                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-xs">Edit</a>
                        <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-xs">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>