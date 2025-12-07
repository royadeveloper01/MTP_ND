<?php
// auth/login.php  (REPLACE file)
session_start();

// load config first so BASE_URL is stable for entire request
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}
// fallback if config missing
if (!defined('BASE_URL')) define('BASE_URL', '/MTP_ND');
if (!defined('AUTH_SECRET')) define('AUTH_SECRET', 'change_this_to_a_random_string_32_chars_long_!@#');

// now load DB (db.php must NOT override BASE_URL; if it does, we still keep BASE_URL)
require_once __DIR__ . '/../db.php';

// helper
function remember_token_for($user_id) {
    return hash_hmac('sha256', (string)$user_id, AUTH_SECRET);
}

$PROJECT_ROOT = rtrim(BASE_URL, '/'); // e.g. /MTP_ND
$COOKIE_PATH = $PROJECT_ROOT . '/';

// Auto-login via cookie (before output)
if (!isset($_SESSION['loggedin']) && !empty($_COOKIE['rememberme'])) {
    $val = $_COOKIE['rememberme'];
    $parts = explode(':', $val);
    if (count($parts) === 2) {
        $uid_b64 = $parts[0];
        $hmac    = $parts[1];
        $uid     = base64_decode($uid_b64);
        if ($uid && hash_equals(remember_token_for($uid), $hmac)) {
            $stmt = $conn->prepare("SELECT id, fname, role FROM users WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id']       = $row['id'];
                    $_SESSION['fname']    = $row['fname'];
                    $_SESSION['role']     = $row['role'] ?? 'user';
                    header("Location: " . $PROJECT_ROOT . "/index.php");
                    exit;
                }
                $stmt->close();
            }
        } else {
            setcookie('rememberme', '', time() - 3600, $COOKIE_PATH, "", false, true);
        }
    }
}

// Handle POST
$message = '';
$success_message = '';

// Check for registration success message
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success_message = 'Registration successful! You can now log in.';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($email === '' || $password === '') {
        $message = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        try {
            $sql = "SELECT id, fname, password_hash FROM users WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $fname, $hashed_password);
                $stmt->fetch();

                if ($hashed_password !== null && password_verify($password, $hashed_password)) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id']       = $id;
                    $_SESSION['fname']    = $fname;
                    $_SESSION['role']     = 'user'; // Set a default role

                    if ($remember) {
                        $uid   = (int)$id;
                        $token = remember_token_for($uid);
                        $value = base64_encode($uid) . ':' . $token;
                        setcookie('rememberme', $value, time() + (30*24*60*60), $COOKIE_PATH, "", false, true);
                    }

                    // IMPORTANT: redirect to site index (main UI), NOT auth/dashboard
                    header("Location: " . $PROJECT_ROOT . "/index.php");
                    exit;
                } else {
                    $message = "The password you entered was not valid.";
                }
            } else {
                $message = "No account found with that email.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = "Database error.";
        }
    }
}

if (file_exists(__DIR__ . '/../header.php')) include __DIR__ . '/../header.php';
?>

<div class="form-container">
    <h2>Login</h2>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <form action="login.php" method="post">
        <input type="email" name="email" placeholder="Email" required class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Password" required class="form-control">

        <label style="display:block;margin:8px 0;">
            <input type="checkbox" name="remember" value="1" <?= isset($_POST['remember']) ? 'checked' : '' ?>> Remember me
        </label>

        <input type="submit" value="Login" class="btn btn-primary">
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
</div>

<?php if (file_exists(__DIR__ . '/../footer.php')) include __DIR__ . '/../footer.php'; ?>
