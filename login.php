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
            // Prepare a simple, static SELECT statement based on the new schema
            $sql = "SELECT id, fname, password FROM users WHERE email = ? LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // Bind result variables
                $stmt->bind_result($id, $fname, $hashed_password);
                $stmt->fetch();

                // Verify the submitted password against the hashed password in the database
                if ($hashed_password !== null && password_verify($password, (string)$hashed_password)) {
                    // Password is correct, so create session variables
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $id;
                    $_SESSION['fname'] = $fname;
                    
                    // Redirect user to the dashboard page
                    header("Location: dashboard.php"); // Redirect to dashboard on successful login
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
            $message = "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        // Do not explicitly close $conn here; let the request lifecycle handle it.
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">MTP Store</a>
        <div>
            <a href="index.php">Home</a>
            <a href="register.php">Register</a>
        </div>
    </nav>
    <div class="form-container">
        <h2>Login</h2>
        <?= $message ?>
        <form action="login.php" method="post">
            <input type="email" name="email" placeholder="Email" required class="form-control">
            <input type="password" name="password" placeholder="Password" required class="form-control">
            <input type="submit" value="Login" class="btn btn-primary">
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    </div>
</body>
</html>
