<?php
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CRITICAL SECURITY FIX: Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imagePath = trim($_POST['image'] ?? '');

        // Validate category server-side: only allow known values
        $allowedCategories = ['male', 'female'];
        if ($name === '' || $price <= 0 || !in_array($category, $allowedCategories, true)) {
            $message = "Please provide a valid product name, price, and category.";
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
                // SECURITY FIX: Do not expose raw database errors to the client
                $message = "A database error occurred. Please try again.";
            }
        }
    }
}

include __DIR__ . '/../header.php';
?>

<style>
.form-max-width {
    max-width: 600px;
}
.preview-placeholder, .preview-img {
    width: 300px;
    height: 300px;
    object-fit: contain;
}
.preview-placeholder {
    border: 1px solid #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #999;
}
</style>

<div class="container my-5 form-max-width">
    <div class="p-4 border rounded shadow-sm bg-white">
        <h1>Add New Product</h1>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Product Name</label>
                <input type="text" class="form-control" name="name" placeholder="Name" required 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Price ($)</label>
                <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00" required 
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Category</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" value="male" required
                               <?= (($_POST['category'] ?? '') === 'male') ? 'checked' : '' ?>>
                        <label class="form-check-label">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" value="female" required
                               <?= (($_POST['category'] ?? '') === 'female') ? 'checked' : '' ?>>
                        <label class="form-check-label">Female</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Image URL or Filename</label>
                <input type="text" id="imageInput" class="form-control" name="image" placeholder="https://example.com/image.jpg or my_file.jpg"
                       value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
                <div class="form-text">Enter a full URL or a filename from the `uploads` directory.</div>
            </div>

            <div class="d-flex justify-content-center mb-3">
                <div id="previewPlaceholder" class="preview-placeholder" style="display: block;">
                    Image Preview
                </div>
                <img id="previewImg" src="#" alt="Product Image Preview" class="preview-img" style="display: none;">
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
        let finalUrl = url;

        // Check if it's a full URL or a local file path
        const isFullUrl = url.startsWith("http://") || url.startsWith("https://");
        
        if (!isFullUrl && url) {
            // Assuming local files are relative to an 'uploads' directory from the web root
            finalUrl = `../uploads/${url}`;
        }

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