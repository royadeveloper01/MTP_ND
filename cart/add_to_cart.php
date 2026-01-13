<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/auth.php';

/**
 * Gets the total number of items in the cart for a given user.
 * @param mysqli $conn The database connection.
 * @param int $user_id The user's ID.
 * @return int The total item count.
 */
function get_cart_count($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return (int)($row['total_qty'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

if ($is_ajax) {
    header('Content-Type: application/json');
}

// User must be logged in to add to cart
if (empty($_SESSION['loggedin'])) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Please log in to add items to your cart.']);
        exit;
    }
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $size = trim($_POST['size'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $user_id = $_SESSION['id'];

    if (!$product_id) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Invalid Product ID.']);
            exit;
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php'));
        exit;
    }

    try {
        // This query requires a UNIQUE index on (user_id, product_id, size, color)
        $sql = "INSERT INTO cart (user_id, product_id, quantity, size, color) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", $user_id, $product_id, $quantity, $size, $color);
        $stmt->execute();
        $stmt->close();

        if ($is_ajax) {
            $new_cart_count = get_cart_count($conn, $user_id);
            echo json_encode(['success' => true, 'cart_count' => $new_cart_count]);
            exit;
        }
    } catch (Exception $e) {
        if ($is_ajax) {
            error_log("Add to cart error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Could not add item to cart. Please try again.']);
            exit;
        }
    }
}

// Fallback redirect for non-AJAX requests
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php'));
exit;