<?php
// delete.php - Admin only (delete product by id)
require_once __DIR__ . '/auth_admin.php';
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: list.php");
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
    if (!empty($row['image'])) {
        $path = __DIR__ . '/' . $row['image'];
        if (file_exists($path)) {
            // unlink($path); // uncomment to actually delete files
        }
    }

    header("Location: list.php?deleted=1");
    exit;
} catch (Exception $e) {
    header("Location: list.php?error=" . urlencode($e->getMessage()));
    exit;
}
