<?php
// db.php
// Laragon-friendly mysqli connection wrapper

// Laragon default MySQL settings typically are:
// host: 127.0.0.1 (use 127.0.0.1 to avoid socket/pipe issues),
// user: root
// password: (empty)
// port: 3306

// Database configuration - change these if your setup differs
$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbUser = 'root';
$dbPass = '';
$dbName = 'mtp_db';

// Enable mysqli exceptions for easier error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create a new mysqli object to connect to the database
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    // Set charset to utf8mb4
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // If there is an error, stop the script and display a friendly message
    // For production, consider logging $e->getMessage() instead of echoing it
    die("Database connection failed: " . $e->getMessage());
}

// Start a session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
