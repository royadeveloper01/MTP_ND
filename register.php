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
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;

    // Basic validation
    if ($fname === '' || $lname === '' || $email === '' || $password === '') {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        try {
            // First, check what columns exist in the users table
            $colsRes = $conn->query("SHOW COLUMNS FROM users");
            $cols = [];
            while ($c = $colsRes->fetch_assoc()) {
                $cols[] = $c['Field'];
            }

            // Map form fields to possible column names
            $columnMap = [
                'fname' => ['fname', 'first_name', 'firstname'],
                'lname' => ['lname', 'last_name', 'lastname'],
                'name' => ['name', 'full_name', 'username'],
                'email' => ['email', 'user_email', 'mail'],
                'phone' => ['phone_number', 'phone', 'contact']
            ];

            // Find which columns actually exist
            $existingCols = [];
            $values = [];
            $types = '';

            // Email and password are required
            $emailCol = 'email'; // default
            foreach ($columnMap['email'] as $col) {
                if (in_array($col, $cols)) {
                    $emailCol = $col;
                    break;
                }
            }
            $existingCols[] = $emailCol;
            $values[] = $email;
            $types .= 's';

            // Add password
            if (in_array('password', $cols)) {
                $existingCols[] = 'password';
                $values[] = password_hash($password, PASSWORD_DEFAULT);
                $types .= 's';
            }

            // Try to find first name column
            foreach ($columnMap['fname'] as $col) {
                if (in_array($col, $cols)) {
                    $existingCols[] = $col;
                    $values[] = $fname;
                    $types .= 's';
                    break;
                }
            }

            // Try to find last name column
            foreach ($columnMap['lname'] as $col) {
                if (in_array($col, $cols)) {
                    $existingCols[] = $col;
                    $values[] = $lname;
                    $types .= 's';
                    break;
                }
            }

            // If no separate first/last name, try full name
            if (!in_array('fname', $existingCols) && !in_array('first_name', $existingCols)) {
                foreach ($columnMap['name'] as $col) {
                    if (in_array($col, $cols)) {
                        $existingCols[] = $col;
                        $values[] = trim($fname . ' ' . $lname);
                        $types .= 's';
                        break;
                    }
                }
            }

            // Phone is optional
            if ($phone_number) {
                foreach ($columnMap['phone'] as $col) {
                    if (in_array($col, $cols)) {
                        $existingCols[] = $col;
                        $values[] = $phone_number;
                        $types .= 's';
                        break;
                    }
                }
            }

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE $emailCol = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "An account with this email already exists.";
            } else {
                // Build the INSERT query dynamically based on existing columns
                $cols_str = implode(', ', array_map(function($col) { 
                    return "`" . str_replace("`", "", $col) . "`"; 
                }, $existingCols));
                
                $placeholders = str_repeat('?,', count($existingCols) - 1) . '?';
                $sql = "INSERT INTO users ($cols_str) VALUES ($placeholders)";

                // Prepare and execute the INSERT
                $stmt->close();
                $stmt = $conn->prepare($sql);
                
                // Bind all parameters dynamically
                $stmt->bind_param($types, ...$values);

                if ($stmt->execute()) {
                    $message = "Registration successful! You can now <a href='login.php'>login</a>.";
                } else {
                    $message = "Error: " . htmlspecialchars($stmt->error);
                }
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { width: 300px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 8px; margin: 10px 0; }
        input[type="submit"] { background-color: #4CAF50; color: white; padding: 10px; border: none; cursor: pointer; }
        .message { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($message)) { echo "<p class='message'>{$message}</p>"; } ?>
        <form action="register.php" method="post">
            <input type="text" name="fname" placeholder="First Name" required>
            <input type="text" name="lname" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="phone_number" placeholder="Phone Number (Optional)">
            <input type="submit" value="Register">
        </form>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</body>
</html>
