<?php
include 'db.php';
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) { header("Location: list.php"); exit; }

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
            "UPDATE products SET name=?, brand=?, price=?, size=?, color=?, category=?, image=?, description=? WHERE id=?"
        );
        $stmt->bind_param("ssdsssssi", $name, $brand, $price, $size, $color, $category, $image, $description, $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Updated!</div>";
            $product = array_merge($product, [
                'name' => $name, 'brand' => $brand, 'price' => $price,
                'size' => $size, 'color' => $color, 'category' => $category,
                'image' => $image, 'description' => $description
            ]);
        } else {
            $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit Product</title>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width:600px;margin:50px auto;">
    <h2>Edit Product</h2>
    <?= $message ?>

    <form method="post">
        <input name="name" value="<?= htmlspecialchars($product['name']) ?>" class="form-control" required><br>
        <input name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>" class="form-control"><br>
        <input name="price" type="number" step="0.01" value="<?= $product['price'] ?>" class="form-control" required><br>
        <input name="size" value="<?= htmlspecialchars($product['size'] ?? '') ?>" class="form-control"><br>
        <input name="color" value="<?= htmlspecialchars($product['color'] ?? '') ?>" class="form-control"><br>

        <div class="form-group">
            <label>Category *</label>
            <select name="category" class="form-control" required>
                <option value="male" <?= $product['category'] === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $product['category'] === 'female' ? 'selected' : '' ?>>Female</option>
            </select>
        </div>

        <input name="image" value="<?= htmlspecialchars($product['image'] ?? '') ?>" placeholder="Image URL" class="form-control"><br>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea><br>

        <button class="btn btn-primary">Update</button>
        <a href="list.php" class="btn btn-info">List</a>
        <a href="index.php" class="btn btn-default">Back</a>
    </form>
</div>
</body>
</html>