<?php
include 'db.php';
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }

$message = '';
if ($_POST) {
    $name        = trim($_POST['name'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $price       = $_POST['price'] ?? 0;
    $size        = trim($_POST['size'] ?? '');
    $color       = trim($_POST['color'] ?? '');
    $category    = $_POST['category'] ?? 'male';
    $image       = trim($_POST['image'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$name || $price <= 0) {
        $message = "<div class='alert alert-danger'>Name and Price required.</div>";
    } elseif ($image && !filter_var($image, FILTER_VALIDATE_URL)) {
        $message = "<div class='alert alert-danger'>Invalid Image URL.</div>";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO products (name, brand, price, size, color, category, image, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssdsssss", $name, $brand, $price, $size, $color, $category, $image, $description);
        $message = $stmt->execute()
            ? "<div class='alert alert-success'>Added!</div>"
            : "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width:600px;margin:50px auto;">
    <h2>Add Clothing Item</h2>
    <?= $message ?>

    <form method="post">
        <input name="name" placeholder="Product Name *" class="form-control" required><br>
        <input name="brand" placeholder="Brand" class="form-control"><br>
        <input name="price" type="number" step="0.01" placeholder="Price *" class="form-control" required><br>
        <input name="size" placeholder="Size" class="form-control"><br>
        <input name="color" placeholder="Color" class="form-control"><br>

        <div class="form-group">
            <label>Category *</label>
            <select name="category" class="form-control" required>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>

        <input name="image" placeholder="Image URL" class="form-control"><br>
        <textarea name="description" placeholder="Description" class="form-control" rows="3"></textarea><br>

        <button class="btn btn-success btn-block">Add Product</button>
        <a href="list.php" class="btn btn-default btn-block">Back</a>
    </form>
</div>
</body>
</html>