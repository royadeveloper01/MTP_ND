<?php
require_once __DIR__ . '/../auth/auth.php'; // Includes session_start()
require_once __DIR__ . '/../db.php';

// Auth check: require admin (assuming auth.php defines require_admin() or similar)
if (empty($_SESSION['is_admin'])) {
    header("Location: ../auth/login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imagePath = trim($_POST['image'] ?? '');

    // FIX: Validate category server-side: only allow known values
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
            $message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

include __DIR__ . '/../header.php';
?>

<div class="container my-5">
    <div class="card p-4 mx-auto" style="max-width: 600px;">
        <h1 class="h3 card-title text-center">Add New Product</h1>
        <a href="list.php" class="btn btn-secondary btn-sm mb-3">Back to List</a>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-semibold">Price ($)</label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="price" name="price" required
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Category</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" value="male" required
                               <?= (($_POST['category'] ?? 'male') === 'male') ? 'checked' : '' ?>>
                        <label class="form-check-label">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" value="female"
                               <?= (($_POST['category'] ?? '') === 'female') ? 'checked' : '' ?>>
                        <label class="form-check-label">Female</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= 
                    htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="imageInput" class="form-label fw-semibold">Image URL (For local image, place in `uploads/`)</label>
                <input type="url" id="imageInput" class="form-control" name="image" 
                       placeholder="https://example.com/image.jpg or my_local_file.jpg"
                       value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
            </div>

            <div class="mb-3 p-3 border text-center" style="min-height: 150px;">
                <p id="previewPlaceholder" class="text-muted">Image Preview will appear here</p>
                <img id="previewImg" src="" alt="Image Preview" style="max-width: 100%; max-height: 150px; display: none;">
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

<?php 
// Note: You must ensure your header.php and footer.php exist and contain the necessary HTML structure.
// If your original code was missing </div> or </body>/</html>, you need to verify where they are.
// Based on the provided snippets, `header.php` likely opens `<body>` and `footer.php` closes it.

// Include footer
include __DIR__ . '/../footer.php'; 
?>