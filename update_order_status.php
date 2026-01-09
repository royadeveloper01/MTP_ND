<?php
// update_order_status.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/auth/orders.php');
    exit;
}

// Admins only
require_admin();

/*
|--------------------------------------------------------------------------
| Fetch and normalize input
|--------------------------------------------------------------------------
*/
$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$status   = strtolower(trim($_POST['status'] ?? ''));

/*
|--------------------------------------------------------------------------
| Allowed statuses
|--------------------------------------------------------------------------
*/
$allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if (!$order_id || $status === '' || !in_array($status, $allowed_statuses, true)) {
    $_SESSION['error_message'] = 'Invalid data provided for status update.';
    header('Location: ' . BASE_URL . '/auth/orders.php');
    exit;
}

try {
    $stmt = $conn->prepare(
        "UPDATE orders SET status = ? WHERE id = ?"
    );
    $stmt->bind_param('si', $status, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows === 1) {
        $_SESSION['success_message'] = 'Order status updated successfully.';
    } else {
        $_SESSION['error_message'] =
            'No changes were made. The order may already have this status or does not exist.';
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log('Order status update failed: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error occurred.';
}

// Redirect back to order view
header('Location: ' . BASE_URL . '/auth/view_order.php?id=' . $order_id);
exit;
