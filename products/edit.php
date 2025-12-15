<?php
// edit.php - Admin only (edit existing product)
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

// --- SECURITY: CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: list.php");
    exit;
}

$message = '';

// --- Load data ---

// 1. Product details (Use a placeholder array for form sticking if POST fails)
$product = []; 

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

// 3. This product's currently selected sizes and colors (used for initial form check)
// FIX: Use prepared statements for selecting related data
$stmt_sizes = $conn->prepare("SELECT size_id FROM product_sizes WHERE product_id = ?");
$stmt_sizes->bind_param("i", $id);
$stmt_sizes->execute();
$product_sizes = array_column($stmt_sizes->get_result()->fetch_all(MYSQLI_ASSOC), 'size_id');
$stmt_sizes->close();

$stmt_colors = $conn->prepare("SELECT color_id FROM product_colors WHERE product_id = ?");
$stmt_colors->bind_param("i", $id);
$stmt_colors->execute();
$product_colors = array_column($stmt_colors->get_result()->fetch_all(MYSQLI_ASSOC), 'color_id');
$stmt_colors->close();

// --- Handle Update (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    // --- SECURITY: CSRF Token Validation ---
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token. Please refresh the page and try again.";
        // Re-load POST data into $product and selections for sticky form behavior
        $product = array_merge($product, $_POST);
        $product_sizes = $_POST['sizes'] ?? [];
        $product_colors = $_POST['colors'] ?? [];

    } else {
        // Collect and sanitize inputs
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $selected_sizes = $_POST['sizes'] ?? [];
        $selected_colors = $_POST['colors'] ?? [];
        
        // Use new image path if submitted, otherwise keep existing
        $imagePath = trim($_POST['image'] ?? $product['image']); 

        // --- SECURITY: Image Path Sanitization ---
        $isUrl = preg_match('/^https?:\/\//i', $imagePath);
        if (!$isUrl && !empty($imagePath)) {
            // Remove directory components (e.g., ../../etc/passwd)
            $imagePath = basename($imagePath);
        }

        // --- SECURITY: Server-side Validation ---
        $allowedCategories = ['male', 'female'];
        $isValidCategory = in_array($category, $allowedCategories, true);

        if ($name === '' || $price <= 0 || !$isValidCategory) {
            $message = "Please provide valid name, price (>0), and category.";
            // Re-load POST data into $product and selections for sticky form behavior
            $product = array_merge($product, $_POST);
            $product_sizes = $_POST['sizes'] ?? [];
            $product_colors = $_POST['colors'] ?? [];

        } else {
            // All data is validated and sanitized, begin transaction
            $conn->begin_transaction();
            try {
                // 1. Update main product details
                $sql = "UPDATE products SET name = ?, price = ?, category = ?, description = ?, image = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdsssi", $name, $price, $category, $description, $imagePath, $id);
                $stmt->execute();
                $stmt->close();

                // 2. Update sizes (delete all then re-insert)
                // FIX: Use prepared statements for DELETE
                $stmt_del_size = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
                $stmt_del_size->bind_param("i", $id);
                $stmt_del_size->execute();
                $stmt_del_size->close();
                
                if (!empty($selected_sizes)) {
                    $stmt_size = $conn->prepare("INSERT INTO product_sizes (product_id, size_id) VALUES (?, ?)");
                    
                    // Filter submitted size IDs against valid IDs from the database
                    // FIX: Use array_flip and isset for O(1) lookup
                    $valid_size_ids = array_flip(array_column($sizes_from_db, 'id'));

                    foreach ($selected_sizes as $size_id) {
                        $size_id = (int)$size_id; // Ensure it's an integer
                        // FIX: Use isset on flipped array
                        if (isset($valid_size_ids[$size_id])) {
                            $stmt_size->bind_param('ii', $id, $size_id);
                            $stmt_size->execute();
                        }
                    }
                    $stmt_size->close();
                }

                // 3. Update colors (delete all then re-insert)
                // FIX: Use prepared statements for DELETE
                $stmt_del_color = $conn->prepare("DELETE FROM product_colors WHERE product_id = ?");
                $stmt_del_color->bind_param("i", $id);
                $stmt_del_color->execute();
                $stmt_del_color->close();
                
                if (!empty($selected_colors)) {
                    $stmt_color = $conn->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");

                    // Filter submitted color IDs against valid IDs from the database
                    // FIX: Use array_flip and isset for O(1) lookup
                    $valid_color_ids = array_flip(array_column($colors_from_db, 'id'));

                    foreach ($selected_colors as $color_id) {
                        $color_id = (int)$color_id; // Ensure it's an integer
                        // FIX: Use isset on flipped array
                        if (isset($valid_color_ids[$color_id])) {
                            $stmt_color->bind_param('ii', $id, $color_id);
                            $stmt_color->execute();
                        }
                    }
                    $stmt_color->close();
                }

                $conn->commit();
                header("Location: list.php?updated=1");
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                // --- SECURITY: Do not leak DB error details to user ---
                $message = "A database error occurred. Please try again.";
                // Re-load POST data for sticky form behavior
                $product = array_merge($product, $_POST);
                $product_sizes = $_POST['sizes'] ?? [];
                $product_colors = $_POST['colors'] ?? [];
            }
        }
    }
}

// Re-map product data to current POST values if update failed, otherwise use DB data
if (isset($_POST) && $message) {
    // If there was an error, ensure form fields show the data the user tried to submit
    $product['name'] = $_POST['name'] ?? $product['name'];
    $product['price'] = $_POST['price'] ?? $product['price'];
    $product['category'] = $_POST['category'] ?? $product['category'];
    $product['description'] = $_POST['description'] ?? $product['description'];
    $product['image'] = $_POST['image'] ?? $product['image'];
    
    // Size and color arrays were already updated in the error block
}

include __DIR__ . '/../header.php';
?>

<style>
.form-max-width {
    max-width: 600px;
}
.preview-placeholder,
.preview-img {
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
.hidden {
    display: none !important;
}
</style>

<?php
// Build safe preview URL for initial load
$initialImageUrl = '#';
$current_image_path = $product['image'] ?? '';
$hasImage = !empty($current_image_path);

if ($hasImage) {
    $isUrl = preg_match('/^https?:\/\//i', $current_image_path);
    $initialImageUrl = $isUrl
        ? $current_image_path
        : '../uploads/' . $current_image_path;
}
?>

<div class="container my-5 form-max-width">
    <div class="p-4 border rounded shadow-sm bg-white">
        <h1>Edit Product: <?= htmlspecialchars($product['name'] ?? 'Product') ?></h1>

    <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($product): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="d-flex justify-content-center mb-3">
                    <div id="previewPlaceholder"
                         class="preview-placeholder <?= $hasImage ? 'hidden' : '' ?>">
                        Image Preview
                    </div>
                    <img id="previewImg"
                         src="<?= htmlspecialchars($initialImageUrl) ?>"
                         alt="Product Image Preview"
                         class="preview-img <?= !$hasImage ? 'hidden' : '' ?>">
                </div>

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
                <div>
                        <?php $current_category = $product['category'] ?? ''; ?>
                    <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="category" value="male"
                                   <?= ($current_category === 'male') ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="category_male">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="category" value="female"
                                   <?= ($current_category === 'female') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="category_female">Female</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= 
                        htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                    <label class="form-label fw-semibold">Available Sizes</label>
                <div>
                    <?php foreach ($sizes_from_db as $size): ?>
                        <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $size['id'] ?>" id="size_<?= $size['id'] ?>" 
                                       <?= in_array($size['id'], $product_sizes) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="size_<?= $size['id'] ?>"><?= htmlspecialchars($size['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                    <label class="form-label fw-semibold">Available Colors</label>
                <div>
                    <?php foreach ($colors_from_db as $color): ?>
                        <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="colors[]" value="<?= $color['id'] ?>" id="color_<?= $color['id'] ?>" 
                                       <?= in_array($color['id'], $product_colors) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="color_<?= $color['id'] ?>"><?= htmlspecialchars($color['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                    <label class="form-label fw-semibold">Image URL or Filename</label>
                    <input type="text" id="imageInput" class="form-control"
                           name="image"
                           value="<?= htmlspecialchars($product['image']) ?>"
                           placeholder="https://example.com/image.jpg or file.jpg">
                    <div class="form-text">Enter a full URL or a filename from the `uploads` directory.</div>
            </div>

                <button class="btn btn-primary w-100 mt-3" type="submit">
                    <i class="bi bi-save"></i> Save Changes
                </button>
                <a class="btn btn-secondary w-100 mt-2" href="list.php">Back to list</a>
        </form>
    <?php endif; ?>
</div>
</div>

<script>
const input = document.getElementById("imageInput");
const img = document.getElementById("previewImg");
const placeholder = document.getElementById("previewPlaceholder");

function getPreviewUrl(url) {
    if (!url) return null;
    const isFullUrl = url.startsWith("http://") || url.startsWith("https://");
    return isFullUrl ? url : `../uploads/${url}`;
}

function updateImagePreview() {
    const url = input.value.trim();
    const finalUrl = getPreviewUrl(url);

    if (finalUrl) {
        img.src = finalUrl;
        img.classList.remove("hidden");
        placeholder.classList.add("hidden");
    } else {
        img.src = "#";
        img.classList.add("hidden");
        placeholder.classList.remove("hidden");
    }
}

// Initialize preview on load
updateImagePreview();

// Update on input
input.addEventListener("input", updateImagePreview);
</script>

<?php include __DIR__ . '/../footer.php'; ?>
