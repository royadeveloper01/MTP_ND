<?php
// edit.php - Admin only (edit existing product)
require_once __DIR__ . '/auth_admin.php';
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: list.php");
    exit;
}

$message = '';

// load current product
$stmt = $conn->prepare("SELECT id, name, price, category, description, image FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc() ?: null;
$stmt->close();

if (!$product) {
    $message = "Product not found.";
}

// handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // image upload (optional)
    $imagePath = $product['image'];
    if (!empty($_FILES['image']['name'])) {
        $uploadsDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

        $tmp = $_FILES['image']['tmp_name'];
        $origName = basename($_FILES['image']['name']);
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $safeName = uniqid('img_') . '.' . $ext;
        $dest = $uploadsDir . $safeName;

        if (move_uploaded_file($tmp, $dest)) {
            $imagePath = 'uploads/' . $safeName;
            // optional: unlink old image file (skip to avoid accidental deletion)
        }
    }

    if ($name === '' || $price <= 0) {
        $message = "Please provide valid name and price.";
    } else {
        try {
            $sql = "UPDATE products SET name = ?, price = ?, category = ?, description = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsssi", $name, $price, $category, $description, $imagePath, $id);
            $stmt->execute();
            $stmt->close();

            header("Location: list.php?updated=1");
            exit;
        } catch (Exception $e) {
            $message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="container">
    <h2>Edit Product</h2>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($product): ?>
        <form method="post" enctype="multipart/form-data" style="max-width:700px;">
            <div class="mb-3">
                <label class="form-label">Product name</label>
                <input class="form-control" name="name" required value="<?= htmlspecialchars($product['name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Price</label>
                <input class="form-control" name="price" type="number" step="0.01" required value="<?= htmlspecialchars($product['price'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Category</label>
                <input class="form-control" name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Image</label><br>
                <?php if (!empty($product['image'])): ?>
                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="Current image" style="max-width:180px;">
                <?php else: ?>
                    <div>No image</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Replace image (optional)</label>
                <input class="form-control" type="file" name="image" accept="image/*">
            </div>

            <button class="btn btn-primary" type="submit">Save Changes</button>
            <a class="btn btn-secondary" href="list.php">Back to list</a>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
