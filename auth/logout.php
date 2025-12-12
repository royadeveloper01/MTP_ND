<?php
// auth/logout.php  (REPLACE file)
session_start();

// load config first
if (file_exists(__DIR__ . '/../config.php')) require_once __DIR__ . '/../config.php';
if (!defined('BASE_URL')) define('BASE_URL', '/MTP_ND');

$PROJECT_ROOT = rtrim(BASE_URL, '/');
$COOKIE_PATH = $PROJECT_ROOT . '/';

// include db if needed (optional)
if (file_exists(__DIR__ . '/../db.php')) require_once __DIR__ . '/../db.php';

// Clear session
$_SESSION = [];

// Destroy session cookie with same path
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $COOKIE_PATH,
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}

// Destroy session
session_destroy();

// Delete remember cookie (same path)
setcookie('rememberme', '', time() - 3600, $COOKIE_PATH, "", false, true);

// Redirect to homepage
header("Location: " . $PROJECT_ROOT . "/index.php");
exit;
