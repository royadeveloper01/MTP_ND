<?php
// orders/update_status.php - Update order status

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/orders/index.php');
    exit;
}

// Admins only
if (empty($_SESSION['loggedin']) || empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

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
    header('Location: ' . BASE_URL . '/orders/index.php');
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
    $_SESSION['error_message'] = 'Database error occurred.';
}

// Redirect back to order view
header('Location: ' . BASE_URL . '/orders/view.php?id=' . $order_id);
exit;
