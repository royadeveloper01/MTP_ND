<?php
require_once __DIR__ . '/auth/auth_admin.php';
require_once __DIR__ . '/db.php';

$errors = [];
$success = '';

// Handle POST requests for adding/deleting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    try {
        if ($action === 'add_size' && !empty($name)) {
            $stmt = $conn->prepare("INSERT INTO sizes (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $success = "Size '{$name}' added successfully.";
        } elseif ($action === 'add_color' && !empty($name)) {
            $stmt = $conn->prepare("INSERT INTO colors (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $success = "Color '{$name}' added successfully.";
        } elseif ($action === 'delete_size' && $id) {
            $stmt = $conn->prepare("DELETE FROM sizes WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $success = "Size deleted successfully.";
        } elseif ($action === 'delete_color' && $id) {
            $stmt = $conn->prepare("DELETE FROM colors WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $success = "Color deleted successfully.";
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) { // Duplicate entry
            $errors[] = "This item already exists.";
        } else {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all sizes and colors for display
$sizes = $conn->query("SELECT * FROM sizes ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$colors = $conn->query("SELECT * FROM colors ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="container">
    <h1>Manage Product Attributes</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Sizes Management -->
        <div class="col-md-6">
            <h2>Sizes</h2>
            <form method="POST" class="mb-3">
                <input type="hidden" name="action" value="add_size">
                <div class="input-group">
                    <input type="text" name="name" class="form-control" placeholder="New size name..." required>
                    <button type="submit" class="btn btn-primary">Add Size</button>
                </div>
            </form>
            <ul class="list-group">
                <?php foreach ($sizes as $size): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($size['name']) ?>
                        <div>
                            <a href="edit_attribute.php?type=size&id=<?= $size['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? This will remove the size from all products.');">
                                <input type="hidden" name="action" value="delete_size">
                                <input type="hidden" name="id" value="<?= $size['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Colors Management -->
        <div class="col-md-6">
            <h2>Colors</h2>
            <form method="POST" class="mb-3">
                <input type="hidden" name="action" value="add_color">
                <div class="input-group">
                    <input type="text" name="name" class="form-control" placeholder="New color name..." required>
                    <button type="submit" class="btn btn-primary">Add Color</button>
                </div>
            </form>
            <ul class="list-group">
                <?php foreach ($colors as $color): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($color['name']) ?>
                        <div>
                            <a href="edit_attribute.php?type=color&id=<?= $color['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? This will remove the color from all products.');">
                                <input type="hidden" name="action" value="delete_color">
                                <input type="hidden" name="id" value="<?= $color['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>