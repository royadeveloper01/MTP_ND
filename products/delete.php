<?php
// delete.php - Admin only (delete product by id)
require_once __DIR__ . '/../auth/auth_admin.php';
require_once __DIR__ . '/../db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/products/list.php?error=Invalid request method.');
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Location: ' . BASE_URL . '/products/list.php?error=Invalid CSRF token.');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/products/list.php?error=Invalid product ID.');
    exit;
}

// Use a transaction for deletion (good practice, though not strictly required for one row)
$conn->begin_transaction();
try {
    // 1. Fetch image path (if exists) before deleting the main row
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    // 2. Remove associated records (sizes and colors) - crucial for cleanup
    // FIX: Use prepared statements for consistency and security
    $stmt_del = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
    $stmt_del->bind_param("i", $id);
    $stmt_del->execute();
    $stmt_del->close();

    $stmt_del = $conn->prepare("DELETE FROM product_colors WHERE product_id = ?");
    $stmt_del->bind_param("i", $id);
    $stmt_del->execute();
    $stmt_del->close();
    
    // 3. Delete the main product row
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();

    // 4. Optionally delete image file (Crucial Security Fix applied here)
    if (!empty($row['image'])) {
        $imagePath = $row['image'];

        // Check if the image path is NOT a full URL (i.e., it's a local filename)
        if (!preg_match('/^https?:\/\//i', $imagePath)) {
            
            // CRITICAL SECURITY FIX: Use basename() to strip any directory components
            // e.g., 'uploads/../../etc/passwd' becomes just 'passwd'
            $cleanFilename = basename($imagePath);

            // Construct the absolute path to the 'uploads' directory
            $path = __DIR__ . '/../uploads/' . $cleanFilename;

            if (file_exists($path)) {
                // Perform the actual file deletion
                unlink($path); 
            }
        }
    }

    header('Location: ' . BASE_URL . '/products/list.php?deleted=1');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    
    // SECURITY FIX: Log detailed error and redirect with generic message
    error_log("Product Deletion Database Error (ID: {$id}): " . $e->getMessage());
    header('Location: ' . BASE_URL . '/products/list.php?error=An error occurred during deletion.');
    exit;
}
