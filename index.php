<?php
include 'db.php';
include 'header.php';

/**
 * This function displays a product grid.
 */
function displayProductGrid($products) {
    if (empty($products)) {
        return '<p>No products found for this category.</p>';
    }

    $output = '<div class="products-grid">';
    foreach ($products as $p) {

        $name = htmlspecialchars($p['name']);
        $priceDisplay = number_format($p['price'], 2);
        $priceRaw = isset($p['price']) ? floatval($p['price']) : 0;

        $image = !empty($p['image']) ? htmlspecialchars($p['image']) : '';
        $desc  = !empty($p['description']) 
                    ? htmlspecialchars(substr($p['description'], 0, 100)) . (strlen($p['description']) > 100 ? '...' : '')
                    : '';

        $output .= '<div class="product-card">';
        
        if (!empty($p['image'])) {
            $output .= '<img src="' . $image . '" alt="' . $name . '">';
        }

        $output .= '<h3>' . $name . '</h3>';
        $output .= '<div class="price">$' . $priceDisplay . '</div>';

        if (!empty($desc)) {
            $output .= '<p>' . $desc . '</p>';
        }

        $pid   = htmlspecialchars($p['id']);
        $pname = htmlspecialchars($p['name']);

        $output .= '<form method="post" action="add_to_cart.php" style="margin-top:10px;">';
        $output .= '<input type="hidden" name="product_id" value="' . $pid . '">';
        $output .= '<input type="hidden" name="name" value="' . $pname . '">';
        $output .= '<input type="hidden" name="price" value="' . $priceRaw . '">';
        $output .= '<input type="hidden" name="qty" value="1">';
        $output .= '<button type="submit" class="btn btn-sm btn-primary">Add to cart</button>';
        $output .= '</form>';

        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}

// ---------- MAIN LOGIC ----------
$category = strtolower($_GET['cat'] ?? 'all'); 
$errorMsg = '';

$products = [];
$maleProducts = [];
$femaleProducts = [];

try {

    $colrequest
