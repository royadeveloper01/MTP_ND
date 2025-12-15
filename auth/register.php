<?php
// auth/register.php
session_start();
require_once __DIR__ . '/../db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');

    // basic validation
    if ($fname === '' || $lname === '' || $email === '' || $password === '' || $phone_number === '') {
        $message = "Please fill all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email.";
    } elseif ($password !== $password2) {
        $message = "Passwords do not match.";
    } else {
        // check duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Email already registered. Try login or use another email.";
            $stmt->close();
        } else {
            $stmt->close();
            // hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (fname, lname, email, password_hash, phone_number) VALUES (?, ?, ?, ?, ?)"); // Corrected SQL
            $ins->bind_param("sssss", $fname, $lname, $email, $hashed, $phone_number);
            if ($ins->execute()) {
                // success -> redirect to login
                header("Location: login.php?registered=1");
                exit;
            } else {
                $message = "Registration failed. Try again.";
            }
            $ins->close();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
<?php include '../header.php'; ?>

<div class="form-container">
    <h2>Create Account</h2>
    <?php if ($message): ?>
        <p class="alert-danger"><?=htmlspecialchars($message)?></p>
    <?php endif; ?>
    <form method="post">
        <input class="form-control" name="fname" placeholder="First name" required value="<?=htmlspecialchars($_POST['fname'] ?? '')?>">
        <input class="form-control" name="lname" placeholder="Last name" required value="<?=htmlspecialchars($_POST['lname'] ?? '')?>">
        <input class="form-control" name="email" type="email" placeholder="Email" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
        <input class="form-control" name="password" type="password" placeholder="Password" required>
        <input class="form-control" name="password2" type="password" placeholder="Confirm Password" required>
        <input type="tel"  class="form-control" name="phone_number" placeholder="Phone Number" required value="<?=htmlspecialchars($_POST['phone_number'] ?? '')?>">
        <button class="btn btn-primary" type="submit">Register</button>
    </form>
</div>

<?php include '../footer.php'; ?>
</body>
</html>
