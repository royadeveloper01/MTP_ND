<?php
require_once __DIR__ . '/../auth/auth.php';

// Admins only
if (empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$status = trim($_POST['status'] ?? '');

// Define a whitelist of allowed statuses
$allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

if (!$order_id || !$status || !in_array($status, $allowed_statuses)) {
    $_SESSION['error_message'] = "Invalid data provided for status update.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'orders.php'));
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Order status updated successfully to '{$status}'.";
    } else {
        $_SESSION['error_message'] = "No changes were made. The order might already have this status or does not exist.";
    }
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to the order details page
header('Location: view_order.php?id=' . $order_id);
exit;
