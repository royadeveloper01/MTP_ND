<?php
require_once __DIR__ . '/../auth/auth.php';

// Admins only
if (empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id) {
    // No ID provided, redirect back
    header('Location: customers.php');
    exit;
}

try {
    // We must ensure we are not deleting an admin account or oneself.
    // The `is_admin = 0` clause prevents deleting other admins.
    // The `id != ?` check for the current session ID is an extra layer of safety.
    $current_admin_id = $_SESSION['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0 AND id != ?");
    $stmt->bind_param('ii', $user_id, $current_admin_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Set a success message in the session to display on the customer list page
        $_SESSION['success_message'] = "Customer deleted successfully.";
    } else {
        // Set an error message if no rows were deleted (e.g., user not found or was an admin)
        $_SESSION['error_message'] = "Could not delete customer. They may not exist or are an administrator.";
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to the customer list
header('Location: customers.php');
exit;