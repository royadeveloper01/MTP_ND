<?php
// edit.php - Admin only (edit existing product)
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: list.php");
    exit;
}

$message = '';

// load current product
$stmt = $conn->prepare("SELECT id, name, price, category, description, sizes, image FROM products WHERE id = ? LIMIT 1");
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
    $sizes = trim($_POST['sizes'] ?? '');
    // Handle image URL. If new image is posted, use it, otherwise keep the old one.
    // If a new image URL is submitted, use it. Otherwise, keep the existing one.
    $imagePath = trim($_POST['image'] ?? $product['image']);

    if ($name === '' || $price <= 0) {
        $message = "Please provide valid name and price.";
    } else {
        try {
            $sql = "UPDATE products SET name = ?, price = ?, category = ?, description = ?, sizes = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdssssi", $name, $price, $category, $description, $sizes, $imagePath, $id);
            $stmt->execute();
            $stmt->close();

            header("Location: list.php?updated=1");
            exit;
        } catch (Exception $e) {
            $message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

include __DIR__ . '/../header.php';
?>

<div class="container">
    <h2>Edit Product</h2>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($product): ?>
        <form method="post" style="max-width:700px;">
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
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" id="category_male" value="male" <?= (($product['category'] ?? '') === 'male') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="category_male">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" id="category_female" value="female" <?= (($product['category'] ?? '') === 'female') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="category_female">Female</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Sizes</label>
                <input type="text" class="form-control" name="sizes" placeholder="e.g., S, M, L, XL" value="<?= htmlspecialchars($product['sizes'] ?? '') ?>">
                <div class="form-text">Enter available sizes, separated by commas.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Image URL</label>
                <input type="url" class="form-control" name="image" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($product['image'] ?? '') ?>">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="Current image" style="max-width:180px; margin-top: 10px;">
                <?php endif; ?>
            </div>

            <button class="btn btn-primary" type="submit">Save Changes</button>
            <a class="btn btn-secondary" href="list.php">Back to list</a>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
