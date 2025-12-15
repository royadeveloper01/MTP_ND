<?php
// add.php - Admin only (add new product)
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';


// --- SECURITY: CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get sizes and colors from DB for the form
$sizes_from_db = $conn->query("SELECT id, name FROM sizes ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$colors_from_db = $conn->query("SELECT id, name FROM colors ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle POST (add product)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- SECURITY: CSRF Token Validation ---
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token. Please refresh the page and try again.";
    } else {
        // Collect and sanitize inputs
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $selected_sizes = $_POST['sizes'] ?? []; // Array of size IDs
        $selected_colors = $_POST['colors'] ?? []; // Array of color IDs
        $imagePath = trim($_POST['image'] ?? ''); // Get image URL or filename from POST

        // --- SECURITY: Image Path Sanitization & Validation ---
        $isUrl = preg_match('/^https?:\/\//i', $imagePath);
        if (!$isUrl && !empty($imagePath)) {
            // Strip any directory components (e.g., '../../') to mitigate path traversal
            $imagePath = basename($imagePath);
        }
        
        // --- SECURITY: Server-side Validation ---
        $allowedCategories = ['male', 'female'];
        $isValidCategory = in_array($category, $allowedCategories, true);

        if ($name === '' || $price <= 0 || !$isValidCategory) {
            $message = "Please provide a valid product name, price (>0), and select a category.";
        } else {
            $conn->begin_transaction();
            try {
                // 1. Insert product
                $sql = "INSERT INTO products (`name`, `price`, `category`, `description`, `image`) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdsss", $name, $price, $category, $description, $imagePath);
                $stmt->execute();
                $product_id = $stmt->insert_id;
                $stmt->close();

                // 2. Insert sizes (only for valid IDs)
                if (!empty($selected_sizes)) {
                    $stmt_size = $conn->prepare("INSERT INTO product_sizes (product_id, size_id) VALUES (?, ?)");
                    
                    // Filter submitted size IDs against valid IDs from the database
                    // FIX: Use array_flip and isset for O(1) lookup
                    $valid_size_ids = array_flip(array_column($sizes_from_db, 'id'));
                    
                    foreach ($selected_sizes as $size_id) {
                        $size_id = (int)$size_id; // Ensure it's an integer
                        // FIX: Use isset on flipped array
                        if (isset($valid_size_ids[$size_id])) {
                            $stmt_size->bind_param('ii', $product_id, $size_id);
                            $stmt_size->execute();
                        }
                    }
                    $stmt_size->close();
                }

                // 3. Insert colors (only for valid IDs)
                if (!empty($selected_colors)) {
                    $stmt_color = $conn->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
                    
                    // Filter submitted color IDs against valid IDs from the database
                    // FIX: Use array_flip and isset for O(1) lookup
                    $valid_color_ids = array_flip(array_column($colors_from_db, 'id'));

                    foreach ($selected_colors as $color_id) {
                        $color_id = (int)$color_id; // Ensure it's an integer
                        // FIX: Use isset on flipped array
                        if (isset($valid_color_ids[$color_id])) {
                            $stmt_color->bind_param('ii', $product_id, $color_id);
                            $stmt_color->execute();
                        }
                    }
                    $stmt_color->close();
                }

                $conn->commit();
                header("Location: list.php?added=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                // --- SECURITY: Do not leak DB error details to user ---
                $message = "A database error occurred while adding the product. Please try again.";
                // Optionally log $e->getMessage() here for admin review
            }
        }
    }
}

include __DIR__ . '/../header.php';
?>

<link rel="stylesheet" href="../assets/css/admin-forms.css">

<div class="container my-5 form-max-width">
    <div class="p-4 border rounded shadow-sm bg-white">
        <h1>Add New Product</h1>
        
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="mb-3">
                <label class="form-label fw-semibold">Product name</label>
                <input class="form-control" name="name" type="text" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>

        <div class="mb-3">
                <label class="form-label fw-semibold">Price ($)</label>
            <input class="form-control" name="price" type="number" step="0.01" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
        </div>

        <div class="mb-3">
                <label class="form-label fw-semibold">Category</label>
            <div>
                    <?php $current_category = $_POST['category'] ?? ''; ?>
                <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" id="category_male" value="male" <?= ($current_category === 'male') ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="category_male">Male</label>
                </div>
                <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" id="category_female" value="female" <?= ($current_category === 'female') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="category_female">Female</label>
                </div>
            </div>
        </div>

        <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
                <label class="form-label fw-semibold">Available Sizes</label>
            <div>
                    <?php 
                    $selected_sizes_posted = $_POST['sizes'] ?? []; 
                    foreach ($sizes_from_db as $size): 
                        $is_checked = in_array($size['id'], $selected_sizes_posted) ? 'checked' : '';
                    ?>
                    <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $size['id'] ?>" id="size_<?= $size['id'] ?>" <?= $is_checked ?>>
                        <label class="form-check-label" for="size_<?= $size['id'] ?>"><?= htmlspecialchars($size['name']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-3">
                <label class="form-label fw-semibold">Available Colors</label>
            <div>
                    <?php 
                    $selected_colors_posted = $_POST['colors'] ?? []; 
                    foreach ($colors_from_db as $color): 
                        $is_checked = in_array($color['id'], $selected_colors_posted) ? 'checked' : '';
                    ?>
                    <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="colors[]" value="<?= $color['id'] ?>" id="color_<?= $color['id'] ?>" <?= $is_checked ?>>
                        <label class="form-check-label" for="color_<?= $color['id'] ?>"><?= htmlspecialchars($color['name']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-3">
                <label class="form-label fw-semibold">Image URL or Filename</label>
                <input type="text" id="imageInput" class="form-control" name="image"
                    placeholder="https://example.com/image.jpg or my_file.jpg"
                    value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
                <div class="form-text">Enter a full URL or a filename from the `uploads` directory.</div>
        </div>

            <div class="d-flex justify-content-center mb-3">
                <div id="previewPlaceholder" class="preview-placeholder">
                    Image Preview
                </div>
                <img id="previewImg" src="#" class="preview-img hidden">
            </div>

            <button class="btn btn-primary w-100 mt-3" type="submit">
                <i class="bi bi-plus-circle"></i> Add Product
            </button>
            <a class="btn btn-secondary w-100 mt-2" href="list.php">Back to list</a>
    </form>
</div>
</div>

<script>
const input = document.getElementById("imageInput");
const img = document.getElementById("previewImg");
const placeholder = document.getElementById("previewPlaceholder");

function updateImagePreview() {
    const url = input.value.trim();
    let finalUrl = url;

    // Check if the current value looks like a full URL
    const isFullUrl = url.startsWith("http://") || url.startsWith("https://");

    // If it's not a full URL but not empty, prepend the local path
    if (!isFullUrl && url) {
        // NOTE: The basename security fix is server-side, this JS is just for visual preview.
        finalUrl = `../uploads/${url}`; 
    }
    
    // Set up the image source and visibility
    // FIX: Use classList.add/remove('hidden') instead of style.display
    if (finalUrl) {
        img.src = finalUrl;
        img.classList.remove("hidden");
        placeholder.classList.add("hidden");
    } else {
        img.src = "#"; // Added for robustness, consistent with edit.php
        img.classList.add("hidden");
        placeholder.classList.remove("hidden");
    }
}

input.addEventListener("input", updateImagePreview);

// Run on load to display previously entered value if form submission failed
updateImagePreview();
</script>

<?php include __DIR__ . '/../footer.php'; ?>
