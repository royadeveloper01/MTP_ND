<?php
// list.php - Admin only (product list)

// Ensure session is started before using $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// --- SECURITY: Require Admin ---
if (empty($_SESSION['loggedin']) || empty($_SESSION['is_admin'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Generate CSRF token if missing (essential for delete forms)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch products
$products = $conn->query("SELECT id, name, price, category, description, image FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management List</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../assets/css/products.css"> 
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Product Management</h1>
        <div class="d-flex gap-2">
            <a href="add.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add New Product
            </a>
            <a href="../index.php" class="btn btn-secondary">
                <i class="bi bi-house"></i> Home
            </a>
        </div>
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
        <div class="alert alert-danger">Error: <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-warning text-center">No products found. Click "Add New Product" to create one.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($products as $p): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="image-thumb">
                            <?php if (!empty($p['image'])): 
                                // Logic to determine if image is local (../uploads/) or a full URL
                                $image_src = preg_match('/^https?:\/\//i', $p['image'])
                                    ? $p['image'] 
                                    : '../uploads/' . htmlspecialchars($p['image']); // Use htmlspecialchars on the image path for safety
                            ?>
                                <img src="<?= $image_src ?>" 
                                    class="card-img-top" 
                                    alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <span class="text-muted text-center p-5 d-block">No Image</span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($p['name'] ?? 'N/A') ?></h5>
                                <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($p['category'] ?? '')) ?></span>
                            </div>

                            <p class="text-muted small mb-3 flex-grow-1">
                                <?= htmlspecialchars(substr($p['description'] ?? 'No description available', 0, 100)) ?>
                                <?= (strlen($p['description'] ?? '') > 100) ? '...' : '' ?>
                            </p>

                            <div class="d-flex justify-content-between fw-semibold mb-3">
                                <span class="text-primary fs-5">
                                    $<?= number_format($p['price'] ?? 0, 2) ?>
                                </span>
                            </div>

                            <div class="d-flex gap-2 mt-auto">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-primary w-50">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>

                                <form method="POST" action="delete.php" onsubmit="return confirm('Are you sure you want to permanently delete \'<?= htmlspecialchars($p['name']) ?>\'?');" class="w-50">
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
    <?php endif; ?>
</div>

</body>
</html>