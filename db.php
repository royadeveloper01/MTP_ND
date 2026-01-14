<?php
// db.php - DB connection + safe session start + AUTH_SECRET
// NOTE: Change AUTH_SECRET to a secure random string before pushing to repo.

$local_hosts = ['localhost', '127.0.0.1', 'localhost:88', 'mtp_nd.test'];

// Load .env file if it exists (Simple parser)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!getenv($name)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// AUTH secret for remember-me HMAC
if (!defined('AUTH_SECRET')) {
    define('AUTH_SECRET', getenv('AUTH_SECRET') ?: 'change_this_to_a_random_32+_char_secret_please!');
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

// Database Connection Variables
// Detect environment prefix based on HTTP_HOST
$env_prefix = (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $local_hosts)) ? 'LOCAL_' : 'LIVE_';

$get_env = function($key) {  
    $val = getenv($key);  
    if ($val !== false) {  
        return $val;  
    }  
    if (isset($_ENV[$key])) {  
        return $_ENV[$key];  
    }  
    if (isset($_SERVER[$key])) {  
        return $_SERVER[$key];  
    }  
    return null;  
};  

// Fetch variables with prefix, fallback to standard names if prefixed ones are missing  
$host = $get_env($env_prefix . 'DB_HOST') ?? $get_env('DB_HOST');  
$user = $get_env($env_prefix . 'DB_USER') ?? $get_env('DB_USER');  
$pass = $get_env($env_prefix . 'DB_PASS') ?? $get_env('DB_PASS');  
$db = $get_env($env_prefix . 'DB_NAME') ?? $get_env('DB_NAME'); 

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
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} 
catch (Exception $e) {  
    // Display error to help debug connection issues on InfinityFree  
    // TODO: In production, log this error and show a generic message to the user.  
    die("<h3>Database Connection Failed</h3><p>Error: " . htmlspecialchars($e->getMessage()) . "</p><p>Attempted Host: " . htmlspecialchars((string)$host) . " <br> Attempted User: " . htmlspecialchars((string)$user) . "</p>");  
}