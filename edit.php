<?php
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: list.php");
    exit;
}

$message = '';

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

    if ($name === '' || $price <= 0) {
        $message = "Please provide valid name and price.";
    } else {
        try {
            $sql = "UPDATE products 
                    SET name = ?, price = ?, category = ?, description = ?, image = ? 
                    WHERE id = ?";
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

include __DIR__ . '/../header.php';
?>

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
    body {
        background: #f5f6fa;
    }
    .form-card {
        max-width: 650px;
        margin: auto;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .preview-box {
        width: 100%;
        height: 240px;
        background: #efefef;
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .preview-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
</style>

<div class="container mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <h2 class="fw-bold">Edit Product</h2>
        <span></span>
    </div>

    <div class="form-card">

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($product): ?>

            <form method="post">

                <!-- Image Preview -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Image</label>
                    <div class="preview-box" id="previewBox">
                        <img id="previewImg"
                             src="<?= htmlspecialchars($product['image']) ?>"
                             style="display:block;">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Name</label>
                    <input class="form-control" name="name" required
                           value="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Price ($)</label>
                    <input class="form-control" name="price" type="number"
                           min="0" step="0.01" required
                           value="<?= htmlspecialchars($product['price']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Category</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio" name="category" value="male"
                               <?= ($product['category'] === 'male') ? 'checked' : '' ?>>
                        <label class="form-check-label">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio" name="category" value="female"
                               <?= ($product['category'] === 'female') ? 'checked' : '' ?>>
                        <label class="form-check-label">Female</label>
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

    input.addEventListener("input", () => {
        const url = input.value.trim();
        if (url.startsWith("http")) {
            img.src = url;
            img.style.display = "block";
        }
    });
</script>

<?php include __DIR__ . '/../footer.php'; ?>
