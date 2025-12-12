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
    // Validate CSRF token (This was already present from the previous fix, which is correct)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imagePath = trim($_POST['image'] ?? $product['image']);

        $allowedCategories = ['male', 'female'];

        if ($name === '' || $price <= 0 || !in_array($category, $allowedCategories, true)) {
            $message = "Please provide a valid product name, price, and category.";
        } else {
            try {
                $sql = "UPDATE products 
                        SET name = ?, price = ?, category = ?, description = ?, image = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdsssi", $name, $price, $category, $description, $imagePath, $id);
                
                // LOGIC SIMPLIFICATION: Remove the affected_rows check and local variable update
                $stmt->execute();
                $stmt->close();

                header("Location: list.php?updated=1");
                exit;
            } catch (Exception $e) {
                // SECURITY FIX: Log detailed error and show generic user-friendly message
                error_log("Product Edit Database Error: " . $e->getMessage());
                $message = "An error occurred while updating the product. Please try again.";
            }
        }
    }

    // Preserve post data for display if an error occurred
    $product['name'] = $name;
    $product['price'] = $price;
    $product['category'] = $category;
    $product['description'] = $description;
    $product['image'] = $imagePath;
}

include __DIR__ . '/../header.php';
?>

<div class="container my-5">
    <div class="card p-4 mx-auto form-max-width">
        <h1 class="card-title text-center mb-4">Edit Product</h1>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($product): ?>

            <form method="POST">
                
                <?php if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Name</label>
                    <input type="text" class="form-control" name="name" required 
                           value="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Price ($)</label>
                    <input type="number" step="0.01" class="form-control" name="price" required 
                           value="<?= htmlspecialchars($product['price']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Category</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="category" id="categoryMale" value="male"
                               <?= ($product['category'] === 'male') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="categoryMale">Male</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="category" id="categoryFemale" value="female"
                               <?= ($product['category'] === 'female') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="categoryFemale">Female</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= 
                        htmlspecialchars($product['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Image URL or Local Filename</label>
                    <input type="text" id="imageInput" class="form-control"
                           name="image" placeholder="https://example.com/image.jpg OR my_local_file.jpg"
                           value="<?= htmlspecialchars($product['image']) ?>">
                </div>

                <div class="d-flex justify-content-center mb-3">
                    <div id="previewPlaceholder" class="preview-placeholder"
                         style="display: <?= empty($product['image']) ? 'flex' : 'none' ?>;">
                        Image Preview
                    </div>
                    <img id="previewImg" src="<?= htmlspecialchars($product['image']) ?>" 
                         alt="Product Image Preview" class="preview-img"
                         style="display: <?= empty($product['image']) ? 'none' : 'block' ?>;">
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

    // Function to calculate the correct URL for preview
    function getPreviewUrl(url) {
        if (!url) return null;

        // Check if it's a full URL
        const isFullUrl = url.startsWith("http://") || url.startsWith("https://");
        
        if (!isFullUrl) {
            // Assuming local files are relative to an 'uploads' directory from the web root
            return `../uploads/${url}`;
        }
        return url;
    }

    // Set initial preview state
    const initialUrl = getPreviewUrl(input.value.trim());
    if (initialUrl) {
        img.src = initialUrl;
        img.style.display = "block";
        placeholder.style.display = "none";
    }

    input.addEventListener("input", () => {
        const url = input.value.trim();
        const finalUrl = getPreviewUrl(url);

        if (finalUrl) {
            img.src = finalUrl;
            img.style.display = "block";
            placeholder.style.display = "none";
        } else {
            img.src = "#";
            img.style.display = "none";
            placeholder.style.display = "block";
        }
    });
</script>

<?php include __DIR__ . '/../footer.php'; ?>