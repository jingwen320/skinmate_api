<?php
// get_products.php
include 'db.php';

// Prioritizes available items at the top, sorted by latest additions first
$query = "SELECT * FROM products ORDER BY (stock_quantity = 0) ASC, id DESC";
$result = $conn->query($query);
$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Enforce strict type casting for standard mobile fields
        $row['id'] = intval($row['id']);
        $row['stock_quantity'] = intval($row['stock_quantity']);
        
        // Format title exactly as: BRAND Name (Brand name in ALL CAPS)
        $brand_prefix = !empty($row['brand']) ? strtoupper(trim($row['brand'])) : 'GENERIC';
        $product_name = !empty($row['name']) ? trim($row['name']) : '';
        $row['name'] = $brand_prefix . ' ' . $product_name;

        // Force price format to exactly 2 decimal places as a safe string
        $row['price'] = number_format(floatval($row['price']), 2, '.', '');

        // Just pass the raw database value 
        $row['image'] = !empty($row['image']) ? trim($row['image']) : 'placeholder-default.png';

        $products[] = $row;
    }
    echo json_encode([
        'status' => 'success',
        'products' => $products
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch products',
        'products' => []
    ]);
}
?>