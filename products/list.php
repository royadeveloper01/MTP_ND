<?php
include '../db.php';

if (empty($_SESSION['loggedin'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (empty($_SESSION['is_admin'])) {
    header("Location: ../index.php");
    exit;
}

// Generate a CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product List</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container" style="margin:50px auto;">
    <h2>Product List</h2>
    <a href="add.php" class="btn btn-success">Add New</a>
    <a href="../index.php" class="btn btn-default">Home</a>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Deleted!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
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
                        <form method="POST" action="delete.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="btn btn-danger btn-xs">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
