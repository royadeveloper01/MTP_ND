<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// helper HMAC token
function remember_token_for($user_id) {
    if (!defined('AUTH_SECRET')) {
        define('AUTH_SECRET', 'change_this_random_secret_please_32chars+');
    }
    return hash_hmac('sha256', (string)$user_id, AUTH_SECRET);
}

// Auto-login via rememberme cookie
if (!isset($_SESSION['loggedin']) && isset($_COOKIE['rememberme'])) {
    $val = $_COOKIE['rememberme'];
    $parts = explode(':', $val);
    if (count($parts) === 2) {
        $uid = base64_decode($parts[0]);
        $hmac = $parts[1];

        if ($uid && hash_equals(remember_token_for($uid), $hmac)) {
            $stmt = $conn->prepare("SELECT id, fname, is_admin FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $row = $res->fetch_assoc();
                $_SESSION['loggedin'] = true;
                $_SESSION['id']       = $row['id'];
                $_SESSION['fname']    = $row['fname'];
                $_SESSION['is_admin'] = (bool)($row['is_admin'] ?? false);
            }
            $stmt->close();
        } else {
            // invalid cookie -> remove
            setcookie('rememberme', '', time() - 3600, BASE_URL . '/', "", false, true);
        }
    }
}

// helper: require login
function require_login() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
}

// helper: require admin
function require_admin() {
    if (!isset($_SESSION['loggedin'])) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
    if (empty($_SESSION['is_admin'])) {
        header("HTTP/1.1 403 Forbidden");
        echo "403 Forbidden - you do not have permission to access this page.";
        exit;
    }
}
?>
