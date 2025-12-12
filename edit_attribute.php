<?php
require_once __DIR__ . '/auth/auth_admin.php';
require_once __DIR__ . '/db.php';

$type = $_GET['type'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validate input
if (!$id || !in_array($type, ['size', 'color'])) {
    header('Location: attributes.php');
    exit;
}

$table = ($type === 'size') ? 'sizes' : 'colors';
$page_title = "Edit " . ucfirst($type);
$errors = [];
$success = '';

// Fetch the current item
$stmt = $conn->prepare("SELECT name FROM {$table} WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header('Location: attributes.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $errors[] = "Name cannot be empty.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE {$table} SET name = ? WHERE id = ?");
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            $success = ucfirst($type) . " updated successfully!";
            // Update item name for display after successful update
            $item['name'] = $name;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $errors[] = "This name already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="container">
    <h1><?= htmlspecialchars($page_title) ?></h1>
    <a href="attributes.php" class="btn btn-secondary mb-3">Back to Attributes</a>

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

    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label"><?= ucfirst($type) ?> Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>