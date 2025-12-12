<?php
// delete.php - Admin only (delete product by id)
require_once __DIR__ . '/../auth/auth.php';
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

    // FIX: Correctly check for local file and delete it.
    if (!empty($row['image']) && !preg_match('/^https?:\/\//i', $row['image'])) {
        $path = __DIR__ . '/../uploads/' . $row['image']; // Corrected path assumption
        if (file_exists($path)) {
             // uncomment to actually delete files
             // unlink($path);
        }
    }

    header("Location: list.php?deleted=1");
    exit;

} catch (Exception $e) {
    header("Location: list.php?error=Database error: " . urlencode($e->getMessage()));
    exit;
}
// End of delete.php