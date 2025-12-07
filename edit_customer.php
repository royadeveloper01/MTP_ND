<?php
require_once __DIR__ . '/auth/auth.php';

// Admins only
if (empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user = null;
$errors = [];
$success_message = '';

if (!$user_id) {
    header('Location: customers.php');
    exit;
}

try {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (empty($fname)) $errors[] = "First name is required.";
        if (empty($lname)) $errors[] = "Last name is required.";
        if (empty($email)) $errors[] = "A valid email is required.";

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET fname = ?, lname = ?, email = ? WHERE id = ? AND is_admin = 0");
            $stmt->bind_param('sssi', $fname, $lname, $email, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success_message = "Customer details updated successfully!";
            } else {
                $errors[] = "No changes were made or user not found.";
            }
            $stmt->close();
        }
    }

    // Fetch user data for the form
    $stmt = $conn->prepare("SELECT fname, lname, email FROM users WHERE id = ? AND is_admin = 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // Redirect if user not found
        header('Location: customers.php');
        exit;
    }
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

include __DIR__ . '/header.php';
?>

<div class="container">
    <h1>Edit Customer</h1>
    <a href="customers.php" class="btn btn-secondary mb-3">Back to Customer List</a>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if ($user): ?>
    <form method="POST">
        <div class="mb-3">
            <label for="fname" class="form-label">First Name</label>
            <input type="text" class="form-control" id="fname" name="fname" value="<?= htmlspecialchars($user['fname']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="lname" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lname" name="lname" value="<?= htmlspecialchars($user['lname']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Customer</button>
    </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>