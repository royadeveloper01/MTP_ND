<?php
// add.php - Admin only (add new product)
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

// Handle POST (add product)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imagePath = trim($_POST['image'] ?? ''); // Get image URL from POST
    
    if ($name === '' || $price <= 0) {
        $message = "Please provide a valid product name and price.";
    } else {
        try {
            $sql = "INSERT INTO products (`name`, `price`, `category`, `description`, `image`) VALUES (?, ?, ?, ?, ?)";
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

<div class="container">
    <h2>Add Product</h2>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" style="max-width:700px;">
        <div class="mb-3">
            <label class="form-label">Product name</label>
            <input class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Price</label>
            <input class="form-control" name="price" type="number" step="0.01" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="category" id="category_male" value="male" <?= (($_POST['category'] ?? '') === 'male') ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="category_male">Male</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="category" id="category_female" value="female" <?= (($_POST['category'] ?? '') === 'female') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="category_female">Female</label>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="url" placeholder="https://example.com/image.jpg" class="form-control" name="image" value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
        </div>

        <button class="btn btn-primary" type="submit">Add Product</button>
        <a class="btn btn-secondary" href="list.php">Back to list</a>
    </form>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
