<?php
// register.php
include 'db.php'; // Include the database connection file

$message = ''; // Variable to store messages for the user

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $fname = isset($_POST['fname']) ? trim($_POST['fname']) : '';
    $lname = isset($_POST['lname']) ? trim($_POST['lname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $phone_number = !empty($phone_number) ? $phone_number : null; // Set to null if empty

    // Basic validation
    if ($fname === '' || $lname === '' || $email === '' || $password === '') {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "An account with this email already exists.";
                $stmt->close();
            } else {
                // Hash the password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Prepare a simple, static INSERT statement
                $sql = "INSERT INTO users (fname, lname, email, password, phone_number) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $fname, $lname, $email, $hashed_password, $phone_number);

                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Registration successful! You can now <a href='login.php'>login</a>.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
                }
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - MTP Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">MTP Store</a>
        <div>
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </div>
    </nav>
    <div class="form-container">
        <h2>Register</h2>
        <?= $message ?>
        <form action="register.php" method="post">
            <input type="text" name="fname" placeholder="First Name" required class="form-control">
            <input type="text" name="lname" placeholder="Last Name" required class="form-control">
            <input type="email" name="email" placeholder="Email" required class="form-control">
            <input type="password" name="password" placeholder="Password (min 6 chars)" required class="form-control">
            <input type="text" name="phone_number" placeholder="Phone Number (Optional)" class="form-control">
            <input type="submit" value="Register" class="btn btn-success">
        </form>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</body>
</html>
