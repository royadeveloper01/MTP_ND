<?php
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imagePath = trim($_POST['image'] ?? '');

    if ($name === '' || $price <= 0) {
        $message = "Please provide a valid product name and price.";
    } else {
        try {
            $sql = "INSERT INTO products (`name`, `price`, `category`, `description`, `image`) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsss", $name, $price, $category, $description, $imagePath);
            $stmt->execute();
            $stmt->close();

            header("Location: list.php?added=1");
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

    <!-- Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <h2 class="fw-bold">Add New Product</h2>
        <span></span>
    </div>

    <!-- Card -->
    <div class="form-card">

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">

            <!-- Image Preview -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Image Preview</label>
                <div class="preview-box" id="previewBox">
                    <img id="previewImg" style="display:none;">
                    <span id="previewPlaceholder" class="text-muted">Enter an image URL below...</span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Product Name</label>
                <input class="form-control" name="name" required 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Price ($)</label>
                <input class="form-control" name="price" type="number" min="0" step="0.01"
                       required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Category</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="category" value="male"
                           id="cat_male" <?= (($_POST['category'] ?? '') === 'male') ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="cat_male">Male</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="category" value="female"
                           id="cat_female" <?= (($_POST['category'] ?? '') === 'female') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cat_female">Female</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= 
                    htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Image URL</label>
                <input type="url" class="form-control" id="imageInput"
                       name="image" placeholder="https://example.com/image.jpg"
                       value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
            </div>

            <button class="btn btn-primary w-100 mt-3">
                <i class="bi bi-plus-circle"></i> Add Product
            </button>

        </form>
    </div>

</div>

<script>
    const input = document.getElementById("imageInput");
    const img = document.getElementById("previewImg");
    const placeholder = document.getElementById("previewPlaceholder");

    input.addEventListener("input", () => {
        const url = input.value.trim();
        if (url.startsWith("http")) {
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
