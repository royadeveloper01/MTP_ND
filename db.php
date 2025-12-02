<?php
session_start();

// Define an array of hostnames that are considered "local"
$local_hosts = ['localhost', '127.0.0.1', 'mtp_nd.test']; // Add your local virtual host if you use one

// Check if the current server host is in our local list
if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $local_hosts)) {
    // Localhost Configuration
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // Default Laragon password is often empty. Change if needed.
    $db   = 'mtp_db';
} else {
    // Live Server Configuration
    $host = 'sql204.infinityfree.com';
    $user = 'if0_40503929';
    $pass = 'thisiswebdesign';
    $db   = 'if0_40503929_mtpnd_database';
}

// --- Database Connection ---
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>