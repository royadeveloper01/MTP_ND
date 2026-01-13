<?php
// auth/forgot_password.php
session_start();

// load config first so BASE_URL is stable for entire request
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}
// fallback if config missing
if (!defined('BASE_URL')) define('BASE_URL', '/MTP_ND');
if (!defined('AUTH_SECRET')) define('AUTH_SECRET', 'change_this_to_a_random_string_32_chars_long_!@#');

// now load DB
require_once __DIR__ . '/../db.php';

$PROJECT_ROOT = rtrim(BASE_URL, '/'); // e.g. /MTP_ND
$message = '';
$success_message = '';

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $message = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        try {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id, fname, email FROM users WHERE email = ? LIMIT 1");
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user_id = $user['id'];

                // Generate secure token
                $token = bin2hex(random_bytes(32)); // 64 character hex string
                $expires_at = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour from now

                // Delete any existing unused tokens for this user
                $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND used = FALSE");
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();
                $delete_stmt->close();

                // Insert new token
                $insert_stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iss", $user_id, $token, $expires_at);
                
                if ($insert_stmt->execute()) {
                    // Generate reset link
                    $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                                  "://" . $_SERVER['HTTP_HOST'] . $PROJECT_ROOT . 
                                  "/auth/reset_password.php?token=" . urlencode($token);

                    // Send email (you can customize this function)
                    require_once __DIR__ . '/email_helper.php';
                    $email_sent = send_password_reset_email($user['email'], $user['fname'], $reset_link);

                    if ($email_sent) {
                        $success_message = 'Password reset instructions have been sent to your email address.';
                    } else {
                        // For development: show the link directly if email fails
                        $message = 'Email sending failed. For development, use this link: <a href="' . htmlspecialchars($reset_link) . '">Reset Password</a>';
                    }
                } else {
                    $message = 'Failed to generate reset token. Please try again.';
                }
                $insert_stmt->close();
            } else {
                // Don't reveal if email exists (security best practice)
                $success_message = 'If an account exists with that email, password reset instructions have been sent.';
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = 'An error occurred. Please try again later.';
            // Log error in production: error_log($e->getMessage());
        }
    }
}

if (file_exists(__DIR__ . '/../header.php')) include __DIR__ . '/../header.php';
?>

<div class="form-container">
    <h2>Forgot Password</h2>
    <p>Enter your email address and we'll send you a link to reset your password.</p>
    
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if (!$success_message): ?>
        <form action="forgot_password.php" method="post">
            <input type="email" name="email" placeholder="Email" required class="form-control" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus>
            <input type="submit" value="Send Reset Link" class="btn btn-primary">
        </form>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="login.php">Back to Login</a> | 
        <a href="register.php">Create Account</a>
    </p>
</div>

<?php if (file_exists(__DIR__ . '/../footer.php')) include __DIR__ . '/../footer.php'; ?>
