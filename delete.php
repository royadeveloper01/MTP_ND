<?php
// delete.php - Admin only (delete product by id)
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: list.php?error=Invalid request method.");
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: list.php?error=Invalid CSRF token.");
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header("Location: list.php?error=Invalid product ID.");
    exit;
}

try {
    // optionally fetch image path to unlink
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    // remove DB row
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // optionally delete image file (uncomment if you want)
    // FIX: Correctly check for local file and delete it.
    if (!empty($row['image']) && !preg_match('/^https?:\/\//i', $row['image'])) {
        // CRITICAL SECURITY FIX: Use basename() to prevent path traversal
        $path = __DIR__ . '/../uploads/' . basename($row['image']);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    header("Location: list.php?deleted=1");
    exit;

} catch (Exception $e) {
    // SECURITY FIX: Log detailed error and redirect with generic message
    error_log("Product Deletion Database Error: " . $e->getMessage());
    header("Location: list.php?error=An error occurred during deletion.");
    exit;
}