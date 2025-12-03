<?php
// db.php - DB connection + safe session start + AUTH_SECRET
// NOTE: Change AUTH_SECRET to a secure random string before pushing to repo.

// If project config exists, load it first so it can set BASE_URL / AUTH_SECRET
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$local_hosts = ['localhost', '127.0.0.1', 'localhost:88', 'mtp_nd.test'];

// AUTH secret for remember-me HMAC
if (!defined('AUTH_SECRET')) {
    define('AUTH_SECRET', 'change_this_to_a_random_32+_char_secret_please!');
}

// ------------------------------
// Detect/ensure BASE_URL
// ------------------------------
// If config defined BASE_URL, keep it. Otherwise detect automatically.
$script_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($script_path === '') {
    $script_path = '/';
}
if (!defined('BASE_URL')) {
    // If the site lives in a subfolder like /MTP_ND, this will set that.
    define('BASE_URL', $script_path);
}

// Choose DB config based on host
if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $local_hosts)) {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $db   = 'mtp_db'; // ensure this matches your local DB name
} else {
    $host = 'sql204.infinityfree.com';
    $user = 'if0_40503929';
    $pass = 'thisiswebdesign';
    $db   = 'if0_40503929_mtpnd_database';
}

// --- Start session safely and force session-only cookie ---
// Only set session cookie params once, and use BASE_URL for path so it is consistent
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();

    // Use BASE_URL as cookie path to avoid cookies being set to subpaths like /auth
    $cookiePath = (defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : ($cookieParams['path'] ?? '/'));

    session_set_cookie_params([
        'lifetime' => 0, // session-only
        'path'     => $cookiePath,
        'domain'   => $cookieParams['domain'] ?? '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// --- Database connection ---
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $user, $pass, $db);
if ($conn) {
    $conn->set_charset('utf8mb4');
}
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}
