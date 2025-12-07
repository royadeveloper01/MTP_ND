<?php
require_once __DIR__ . '/auth/auth.php';

// Admins only
if (empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$search = trim($_GET['search'] ?? '');

// --- Pagination Logic ---
$per_page = 10; // Number of users per page
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $per_page;

$users = [];
$total_users = 0;
$total_pages = 0;
$error = '';

try {
    // --- Build queries with optional search ---
    $sql_where = "WHERE is_admin = 0";
    $params = [];
    $types = '';

    if ($search) {
        $sql_where .= " AND (fname LIKE ? OR lname LIKE ? OR email LIKE ?)";
        $wildcard_search = "%{$search}%";
        $params = [$wildcard_search, $wildcard_search, $wildcard_search];
        $types = 'sss';
    }

    // Get total count for pagination
    $stmt_count = $conn->prepare("SELECT COUNT(id) as total FROM users " . $sql_where);
    if ($search) $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_users = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_users / $per_page);
    $stmt_count->close();

    // Fetch paginated users
    $sql_select = "SELECT id, fname, lname, email, created_at FROM users " . $sql_where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql_select);
    $all_params = array_merge($params, [$per_page, $offset]);
    $all_types = $types . 'ii';
    $stmt->bind_param($all_types, ...$all_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/header.php';
?>

<div class="container">
    <h1>Customer Management</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" role="alert"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="mb-4">
        <form action="customers.php" method="GET" class="d-flex">
            <input type="search" name="search" class="form-control me-2" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <?php if (empty($users) && $search): ?>
        <p>No customers found matching your search for "<?= htmlspecialchars($search) ?>".</p>
    <?php elseif (empty($users)): ?>
         <p>No customers found.</p>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= (int)$user['id'] ?></td>
                    <td><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                    <td>
                        <a href="edit_customer.php?id=<?= (int)$user['id'] ?>" class="btn btn-sm btn-primary me-1">Edit</a>
                        <a href="delete_customer.php?id=<?= (int)$user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <?php $query_string = $search ? '&search=' . urlencode($search) : ''; ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 . $query_string ?>">Previous</a>
                </li>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i . $query_string ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 . $query_string ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>