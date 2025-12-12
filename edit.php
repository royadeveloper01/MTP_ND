<?php
require_once __DIR__ . '/../auth/auth.php'; // Includes session_start()
require_once __DIR__ . '/../db.php';

// Auth check: require admin
if (empty($_SESSION['is_admin'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: list.php");
    exit;
}

$message = '';
$product = null;
$allowedCategories = ['male', 'female']; // Defined here for form and validation

// Load product
$stmt = $conn->prepare("SELECT id, name, price, category, description, image FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc() ?: null;
$stmt->close();

if (!$product) {
    $message = "Product not found.";
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imagePath = trim($_POST['image'] ?? $product['image']);

    // FIX: Server-side validation for category
    if ($name === '' || $price <= 0 || !in_array($category, $allowedCategories, true)) {
        $message = "Please provide a valid name, price, and category.";
    } else {
        try {
            $sql = "UPDATE products 
                    SET name = ?, price = ?, category = ?, description = ?, image = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsssi", $name, $price, $category, $description, $imagePath, $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Update the product array for display on this page
                $product['name'] = $name;
                $product['price'] = $price;
                $product['category'] = $category;
                $product['description'] = $description;
                $product['image'] = $imagePath;

                header("Location: list.php?updated=1");
                exit;
            } else {
                $message = "No changes were made to the product or a database error occurred.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

include __DIR__ . '/../header.php';
?>

<div class="container my-5">
    <div class="card p-4 mx-auto" style="max-width: 600px;">
        <h1 class="h3 card-title text-center">Edit Product: <?= htmlspecialchars($product['name'] ?? 'N/A') ?></h1>
        <a href="list.php" class="btn btn-secondary btn-sm mb-3">Back to List</a>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($product): ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label fw-semibold">Price ($)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="price" name="price" required
                           value="<?= htmlspecialchars($product['price']) ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="category" value="male" required
                                   <?= ($product['category'] === 'male') ? 'checked' : '' ?>>
                            <label class="form-check-label">Male</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="category" value="female"
                                   <?= ($product['category'] === 'female') ? 'checked' : '' ?>>
                            <label class="form-check-label">Female</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= 
                        htmlspecialchars($product['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Image URL</label>
                    <input type="url" id="imageInput" class="form-control"
                           name="image" placeholder="https://example.com/image.jpg"
                           value="<?= htmlspecialchars($product['image']) ?>">
                </div>

                <div class="mb-3 p-3 border text-center" style="min-height: 150px;">
                    <p id="previewPlaceholder" class="text-muted" style="display: <?= empty($product['image']) ? 'block' : 'none' ?>;">Image Preview will appear here</p>
                    <img id="previewImg" 
                         src="<?= htmlspecialchars($product['image']) ?>" 
                         alt="Image Preview" 
                         style="max-width: 100%; max-height: 150px; display: <?= empty($product['image']) ? 'none' : 'block' ?>;">
                </div>


                <button class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-save"></i> Save Changes
                </button>

            </form>

        <?php endif; ?>

    </div>
</div>

<script>
    const input = document.getElementById("imageInput");
    const img = document.getElementById("previewImg");
    const placeholder = document.getElementById("previewPlaceholder");

    input.addEventListener("input", () => {
        const url = input.value.trim();
        // FIX: More robust JS URL validation
        if (url.startsWith("http://") || url.startsWith("https://")) {
            img.src = url;
            img.style.display = "block";
            placeholder.style.display = "none";
        } else {
            img.style.display = "none";
            placeholder.style.display = "block";
        }
    });
</script>

<?php include __DIR__ . '/../footer.php'; ?>