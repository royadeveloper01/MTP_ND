<?php
include 'db.php';
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }

$message = '';
if ($_POST) {
    $form_data = [
        'name'        => trim($_POST['name'] ?? ''),
        'brand'       => trim($_POST['brand'] ?? ''),
        'price'       => $_POST['price'] ?? 0,
        'size'        => trim($_POST['size'] ?? ''),
        'color'       => trim($_POST['color'] ?? ''),
        'category'    => $_POST['category'] ?? 'male',
        'image'       => trim($_POST['image'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'quantity'    => 0 // Default quantity to 0 if not provided by form
    ];

    if (empty($form_data['name']) || $form_data['price'] <= 0) {
        $message = "<div class='alert alert-danger'>Name and Price required.</div>";
    } elseif (!empty($form_data['image']) && !filter_var($form_data['image'], FILTER_VALIDATE_URL)) {
        $message = "<div class='alert alert-danger'>Invalid Image URL.</div>";
    } else {
        try {
            // Get available columns from the products table
            $colsRes = $conn->query("SHOW COLUMNS FROM products");
            $availableCols = [];
            while ($c = $colsRes->fetch_assoc()) {
                $availableCols[] = $c['Field'];
            }

            // Map form fields to possible database column names
            $fieldToColumnMap = [
                'name'        => ['name', 'product_name', 'title'],
                'brand'       => ['brand'],
                'price'       => ['price', 'cost'],
                'size'        => ['size'],
                'color'       => ['color'],
                'category'    => ['category'],
                'image'       => ['image', 'img', 'image_url', 'photo'],
                'description' => ['description', 'desc', 'details'],
                'quantity'    => ['quantity'] // Add quantity to the map
            ];

            $insertCols = [];
            $insertValues = [];
            $bindTypes = '';

            foreach ($fieldToColumnMap as $field => $possibleCols) {
                if (!empty($form_data[$field]) || is_numeric($form_data[$field])) {
                    foreach ($possibleCols as $col) {
                        if (in_array($col, $availableCols)) {
                            $insertCols[] = "`" . str_replace("`", "", $col) . "`";
                            $insertValues[] = $form_data[$field];
                            if ($field === 'price') {
                                $bindTypes .= 'd'; // double for price
                            } elseif ($field === 'quantity') {
                                $bindTypes .= 'i'; // integer for quantity
                            } else {
                                $bindTypes .= 's'; // string for others
                            }
                            break; // Move to the next field
                        }
                    }
                }
            }

            if (count($insertCols) > 0) {
                $cols_str = implode(', ', $insertCols);
                $placeholders = rtrim(str_repeat('?,', count($insertCols)), ',');
                $sql = "INSERT INTO products ($cols_str) VALUES ($placeholders)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($bindTypes, ...$insertValues);
                $message = $stmt->execute()
                    ? "<div class='alert alert-success'>Product added successfully!</div>"
                    : "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
            } else {
                $message = "<div class='alert alert-danger'>Could not determine which columns to insert into.</div>";
            }
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product - MTP Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>Add Clothing Item</h2>
    <?= $message ?>

    <form method="post">
        <input name="name" placeholder="Product Name" class="form-control" required><br>
        <input name="brand" placeholder="Brand" class="form-control"><br>
        <input name="price" type="number" step="0.01" placeholder="Price" class="form-control" required><br>
        <input name="size" placeholder="Size" class="form-control"><br>
        <input name="color" placeholder="Color" class="form-control"><br>

        <label for="category" style="margin-bottom: 5px; display:block;">Category *</label>
        <select name="category" id="category" class="form-control" required style="margin-bottom:15px;">
            <option value="male">Men's</option>
            <option value="female">Women's</option>
        </select>

        <input name="image" placeholder="Image URL" class="form-control"><br>
        <textarea name="description" placeholder="Description" class="form-control" rows="3"></textarea><br>

        <button class="btn btn-success">Add Product</button>
        <a href="list.php" class="btn btn-default">Back to List</a>
    </form>
</div>
</body>
</html>