<?php
// auth/reset_password.php
session_start();

// load config first so BASE_URL is stable for entire request
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}
// fallback if config missing
if (!defined('BASE_URL')) define('BASE_URL', '/MTP_ND');

// now load DB
require_once __DIR__ . '/../db.php';

$PROJECT_ROOT = rtrim(BASE_URL, '/');
$message = '';
$error = '';
$token = $_GET['token'] ?? '';

// Validate token parameter
if (empty($token)) {
    $error = 'Invalid or missing reset token.';
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($token)) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['password2'] ?? '';

    if ($new_password === '' || $confirm_password === '') {
        $message = 'Please fill in both password fields.';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        try {
            // Verify token is valid and not expired
            $stmt = $conn->prepare("
                SELECT prt.user_id, prt.token, prt.expires_at, prt.used, u.email 
                FROM password_reset_tokens prt
                INNER JOIN users u ON prt.user_id = u.id
                WHERE prt.token = ? AND prt.used = FALSE
                LIMIT 1
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $now = date('Y-m-d H:i:s');

                // Check if token is expired
                if (strtotime($row['expires_at']) < strtotime($now)) {
                    $error = 'This reset link has expired. Please request a new one.';
                    $stmt->close();
                } else {
                    $stmt->close();
                    $user_id = $row['user_id'];

                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update user password
                    $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $hashed_password, $user_id);

                    if ($update_stmt->execute()) {
                        // Mark token as used
                        $mark_stmt = $conn->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE token = ?");
                        $mark_stmt->bind_param("s", $token);
                        $mark_stmt->execute();
                        $mark_stmt->close();

                        // Delete all other unused tokens for this user
                        $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND used = FALSE");
                        $delete_stmt->bind_param("i", $user_id);
                        $delete_stmt->execute();
                        $delete_stmt->close();

                        $update_stmt->close();

                        // Success - redirect to login
                        header("Location: login.php?reset=success");
                        exit;
                    } else {
                        $message = 'Failed to update password. Please try again.';
                    }
                    $update_stmt->close();
                }
            } else {
                $error = 'Invalid or expired reset token. Please request a new password reset link.';
            }
        } catch (Exception $e) {
            $message = 'An error occurred. Please try again later.';
            // Log error in production: error_log($e->getMessage());
        }
    }
} elseif (!empty($token)) {
    // GET request - verify token is valid before showing form
    try {
        $stmt = $conn->prepare("
            SELECT prt.token, prt.expires_at, prt.used 
            FROM password_reset_tokens prt
            WHERE prt.token = ? AND prt.used = FALSE
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $now = date('Y-m-d H:i:s');

            if (strtotime($row['expires_at']) < strtotime($now)) {
                $error = 'This reset link has expired. Please request a new one.';
            }
        } else {
            $error = 'Invalid or expired reset token. Please request a new password reset link.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = 'An error occurred while validating the token.';
    }
}

if (file_exists(__DIR__ . '/../header.php')) include __DIR__ . '/../header.php';
?>

<div class="form-container">
    <h2>Reset Password</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <p style="margin-top: 20px;">
            <a href="forgot_password.php">Request New Reset Link</a> | 
            <a href="login.php">Back to Login</a>
        </p>
    <?php elseif (empty($token)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <p style="margin-top: 20px;">
            <a href="forgot_password.php">Request Reset Link</a> | 
            <a href="login.php">Back to Login</a>
        </p>
    <?php else: ?>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form action="reset_password.php?token=<?= htmlspecialchars(urlencode($token)) ?>" method="post">
            <input type="password" name="password" placeholder="New Password" required class="form-control" 
                   minlength="6" autofocus>
            <input type="password" name="password2" placeholder="Confirm New Password" required class="form-control" 
                   minlength="6">
            <input type="submit" value="Reset Password" class="btn btn-primary">
        </form>

        <p style="margin-top: 20px;">
            <a href="login.php">Back to Login</a>
        </p>
    <?php endif; ?>
</div>

<?php if (file_exists(__DIR__ . '/../footer.php')) include __DIR__ . '/../footer.php'; ?>
