<?php
// login.php
include 'db.php'; // Include the database connection file

$message = ''; // Variable to store messages for the user

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize inputs
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($email === '' || $password === '') {
        $message = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        try {
            // Detect available name column in users table to avoid Unknown column errors
            $colsRes = $conn->query("SHOW COLUMNS FROM users");
            $cols = [];
            while ($c = $colsRes->fetch_assoc()) { $cols[] = $c['Field']; }

            // Preferred name fields in order
            $nameCandidates = ['fname', 'first_name', 'name', 'full_name', 'lname', 'lastname'];
            $nameField = null;
            foreach ($nameCandidates as $nc) {
                if (in_array($nc, $cols)) { $nameField = $nc; break; }
            }

            // Build select columns
            $selectCols = ['id', 'password'];
            if ($nameField) { $selectCols[] = $nameField; }

            $safeCols = array_map(function($c){ return "`" . str_replace("`","",$c) . "`"; }, $selectCols);
            $sql = "SELECT " . implode(', ', $safeCols) . " FROM users WHERE email = ? LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            // Check if a user with that email exists
            if ($stmt->num_rows > 0) {
                // Bind results dynamically
                if ($nameField) {
                    $stmt->bind_result($id, $hashed_password, ${$nameField});
                    $stmt->fetch();
                    // Map the dynamic name to $fname for session compatibility
                    $fname = ${$nameField};
                } else {
                    $stmt->bind_result($id, $hashed_password);
                    $stmt->fetch();
                    $fname = '';
                }

                // Verify the submitted password against the hashed password in the database
                if (password_verify($password, $hashed_password)) {
                    // Password is correct, so create session variables
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $id;
                    $_SESSION['fname'] = $fname;

                    // Redirect user to the dashboard page
                    header("Location: dashboard.php");
                    exit;
                } else {
                    // Incorrect password
                    $message = "The password you entered was not valid.";
                }
            } else {
                // Incorrect email
                $message = "No account found with that email.";
            }

            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Don't expose raw DB errors in production; sanitize for display
            $message = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
        // Do not explicitly close $conn here; let the request lifecycle handle it.
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 300px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; }
        input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin: 10px 0; }
        input[type="submit"] { background-color: #008CBA; color: white; padding: 10px; border: none; cursor: pointer; }
        .message { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($message)) { echo "<p class='message'>{$message}</p>"; } ?>
        <form action="login.php" method="post">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>
</body>
</html>
