<?php
// db.php - DB connection + safe session start + AUTH_SECRET
// NOTE: Change AUTH_SECRET to a secure random string before pushing to repo.

$local_hosts = ['localhost', '127.0.0.1', 'localhost:88', 'mtp_nd.test'];

// AUTH secret for remember-me HMAC
if (!defined('AUTH_SECRET')) {
    define('AUTH_SECRET', 'change_this_to_a_random_32+_char_secret_please!');
}

// ------------------------------
// NEW: Detect BASE_URL automatically (ONLY IF NOT DEFINED)
// ------------------------------
if (!defined('BASE_URL')) {
    $doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $dir_root = rtrim(str_replace('\\', '/', __DIR__), '/');
    
    $base = str_replace($doc_root, '', $dir_root);
    define('BASE_URL', $base);
}

// Choose DB config based on host
if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $local_hosts)) {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    $db   = 'mtp_db';
} else {
    // WARNING: Do not commit actual credentials to version control. Use environment variables.
    $host = 'sql204.infinityfree.com';
    $user = 'if0_40503929'; // Consider using getenv('DB_USER')
    $pass = 'thisiswebdesign'; // Consider using getenv('DB_PASS')
    $db   = 'if0_40503929_mtpnd_database';
}

// --- Start session safely and force session-only cookie ---
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();

    // NEW: force cookie path to BASE_URL
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => rtrim(BASE_URL, '/') . '/', // ensure trailing slash
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
