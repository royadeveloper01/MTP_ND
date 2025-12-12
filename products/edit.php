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

// --- Load data ---
// 1. Product details
$stmt = $conn->prepare("SELECT id, name, price, category, description, image FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();

if (!$product) {
    $message = "Product not found.";
}

// 2. All available sizes and colors from DB
$sizes_from_db = $conn->query("SELECT id, name FROM sizes ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$colors_from_db = $conn->query("SELECT id, name FROM colors ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// 3. This product's currently selected sizes and colors
$product_sizes_res = $conn->query("SELECT size_id FROM product_sizes WHERE product_id = $id");
$product_sizes = array_column($product_sizes_res->fetch_all(MYSQLI_ASSOC), 'size_id');

$product_colors_res = $conn->query("SELECT color_id FROM product_colors WHERE product_id = $id");
$product_colors = array_column($product_colors_res->fetch_all(MYSQLI_ASSOC), 'color_id');

// handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $selected_sizes = $_POST['sizes'] ?? [];
    $selected_colors = $_POST['colors'] ?? [];
    // Handle image URL. If new image is posted, use it, otherwise keep the old one.
    // If a new image URL is submitted, use it. Otherwise, keep the existing one.
    $imagePath = trim($_POST['image'] ?? $product['image']);

    if ($name === '' || $price <= 0) {
        $message = "Please provide valid name and price.";
    } else {
        $conn->begin_transaction();
        try {
            // 1. Update main product details
            $sql = "UPDATE products SET name = ?, price = ?, category = ?, description = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsssi", $name, $price, $category, $description, $imagePath, $id);
            $stmt->execute();
            $stmt->close();

            // 2. Update sizes (delete all then re-insert)
            $conn->query("DELETE FROM product_sizes WHERE product_id = $id");
            if (!empty($selected_sizes)) {
                $stmt_size = $conn->prepare("INSERT INTO product_sizes (product_id, size_id) VALUES (?, ?)");
                foreach ($selected_sizes as $size_id) {
                    $stmt_size->bind_param('ii', $id, $size_id);
                    $stmt_size->execute();
                }
                $stmt_size->close();
            }

            // 3. Update colors (delete all then re-insert)
            $conn->query("DELETE FROM product_colors WHERE product_id = $id");
            if (!empty($selected_colors)) {
                $stmt_color = $conn->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
                foreach ($selected_colors as $color_id) {
                    $stmt_color->bind_param('ii', $id, $color_id);
                    $stmt_color->execute();
                }
                $stmt_color->close();
            }

            $conn->commit();
            header("Location: list.php?updated=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
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
                <label class="form-label">Available Sizes</label>
                <div>
                    <?php foreach ($sizes_from_db as $size): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $size['id'] ?>" id="size_<?= $size['id'] ?>" <?= in_array($size['id'], $product_sizes) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="size_<?= $size['id'] ?>"><?= htmlspecialchars($size['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Available Colors</label>
                <div>
                    <?php foreach ($colors_from_db as $color): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="colors[]" value="<?= $color['id'] ?>" id="color_<?= $color['id'] ?>" <?= in_array($color['id'], $product_colors) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="color_<?= $color['id'] ?>"><?= htmlspecialchars($color['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
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
