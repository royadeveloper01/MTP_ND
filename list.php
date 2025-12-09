<?php
session_start();
include '../db.php';

if (empty($_SESSION['loggedin'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Generate CSRF token
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

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            background: #f7f7f7;
        }
    </style>
</head>
<body class="p-4">

<div class="container mt-4">

    <!-- Header / Home / Add -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="../index.php" class="btn btn-secondary">
            <i class="bi bi-house"></i> Home
        </a>
        <h2 class="fw-bold mb-0">Product List</h2>
        <a href="add.php" class="btn btn-primary">
            + Add New Product
        </a>
    </div>

    <!-- Product Grid -->
    <div class="row g-4">

        <?php foreach ($products as $p): ?>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">

                    <!-- Product Image -->
<div style="height: 240px; overflow: hidden;">

<?php 
$img = $p['image'] ?? '';  // ← CORRECT COLUMN NAME

// If it's a full URL (from Unsplash, etc.), use directly
if ($img && preg_match('/^https?:\/\//i', $img)) {
    $imgSrc = $img;
} 
// Otherwise, assume it's a local file in uploads/
else {
    $imgSrc = "../uploads/" . ($img ?: "no-image.png");
}
?>

<img src="<?= htmlspecialchars($imgSrc) ?>" 
     class="w-100 h-100 object-fit-contain"
     alt="<?= htmlspecialchars($p['name']) ?>">
</div>


                    <div class="card-body">

                        <!-- Name -->
                        <h5 class="fw-semibold mb-1">
                            <?= htmlspecialchars($p['name'] ?? 'No Name') ?>
                        </h5>

                        <!-- Description -->
                        <p class="text-muted small mb-2">
                            <?= htmlspecialchars($p['description'] ?? 'No description available') ?>
                        </p>

                        <!-- Price + Stock -->
                        <div class="d-flex justify-content-between fw-semibold mb-3">
                            <span class="text-primary">
                                $<?= number_format($p['price'] ?? 0, 2) ?>
                            </span>
                            <span class="text-muted">
                                Stock: <?= htmlspecialchars($p['stock'] ?? 0) ?>
                            </span>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <a href="edit.php?id=<?= $p['id'] ?>" 
                               class="btn btn-primary w-50">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>

                            <form method="POST" action="delete.php"
                                  onsubmit="return confirm('Delete this product?');"
                                  class="w-50">
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
