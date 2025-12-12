<?php
session_start();
include '../db.php';

// FIX: Require admin access (was only checking for loggedin)
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

    <style>
        body { background: #f7f7f7; }
        /* ensure full-image (no cropping) */
        .image-thumb { width:100%; height:240px; display:flex; align-items:center; justify-content:center; background:#f5f5f5; }
        .image-thumb img { max-width:100%; max-height:100%; object-fit:contain; }
    </style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Product Management</h1>
        <a href="add.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add New Product
        </a>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">Product added successfully!</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Product updated successfully!</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-warning">Product deleted.</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>


    <div class="row row-cols-1 row-cols-md-3 g-4">

        <?php foreach ($products as $p): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="image-thumb">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= htmlspecialchars($p['image']) ?>" 
                                 alt="<?= htmlspecialchars($p['name']) ?>" 
                                 class="card-img-top">
                        <?php else: ?>
                            <span class="text-muted">No Image</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                        <p class="text-muted small mb-2">
                            <?= htmlspecialchars($p['category'] ?? 'Uncategorized') ?>
                        </p>
                        <p class="text-muted small mb-2">
                            <?= htmlspecialchars($p['description'] ?? 'No description available') ?>
                        </p>

                        <div class="d-flex justify-content-between fw-semibold mb-3">
                            <span class="text-primary">
                                $<?= number_format($p['price'] ?? 0, 2) ?>
                            </span>
                            
                            <?php 
                                // REMOVED:
                                // <span class="text-muted">
                                //     Stock: <?= htmlspecialchars($p['stock'] ?? 0) ?>
                                // </span>
                            ?>
                            
                        </div>

                        <div class="d-flex gap-2 mt-auto">
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

<?php include '../footer.php'; ?>