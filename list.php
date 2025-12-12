<?php
session_start();
include '../db.php';

// Require admin
if (empty($_SESSION['loggedin']) || empty($_SESSION['is_admin'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Generate CSRF token if missing (for delete forms)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product List</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../assets/css/products.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Product Management</h1>
        <a href="add.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add New Product
        </a>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">Product added successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Product updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Product deleted successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($products as $p): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    
                    <div class="image-thumb">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= htmlspecialchars($p['image']) ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <span class="text-muted">No Image</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($p['name'] ?? 'N/A') ?></h5>
                            <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($p['category'] ?? '')) ?></span>
                        </div>

                        <p class="text-muted small mb-2">
                            <?= htmlspecialchars($p['description'] ?? 'No description available') ?>
                        </p>

                        <div class="d-flex justify-content-between fw-semibold mb-3">
                            <span class="text-primary">
                                $<?= number_format($p['price'] ?? 0, 2) ?>
                            </span>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-primary w-50">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>

                            <form method="POST" action="delete.php" onsubmit="return confirm('Delete this product?');" class="w-50">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button class="btn btn-danger w-100">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>

</body>
</html>