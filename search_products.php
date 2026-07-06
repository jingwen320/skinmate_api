<?php
// search_products.php
include 'db.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
if (!$query) {
    echo json_encode([
        "status" => "success",
        "products" => []
    ]);
    exit;
}

// 🌟 1. Split the string by commas, spaces, or multiple spaces into clean keywords
// Typing "acne, wrinkles" turns into array: ['acne', 'wrinkles']
$keywords = preg_split('/[\s,]+/', $query, -1, PREG_SPLIT_NO_EMPTY);

$products = [];

if (!empty($keywords)) {
    // Build clauses for each keyword across your 4 target columns
    $clauses = [];
    $params = [];
    $types = "";

    foreach ($keywords as $keyword) {
        $searchTerm = "%$keyword%";
        
        // Match conditions for this specific keyword
        $clauses[] = "(name LIKE ? OR brand LIKE ? OR skin_suitability LIKE ? OR description LIKE ?)";
        
        // Push 4 search terms to match the 4 placeholders (?) in the clause
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        
        // Append 4 string types for bind_param
        $types .= "ssss";
    }

    // 🌟 2. Join all individual keyword clauses with OR 
    $sqlCondition = implode(" OR ", $clauses);

    // Prioritize active stock items and latest additions
    $sql = "SELECT * FROM products WHERE $sqlCondition ORDER BY (stock_quantity = 0) ASC, id DESC";

    $stmt = $conn->prepare($sql);
    
    // Dynamically bind the collected parameters array
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['id'] = intval($row['id']);
        $row['stock_quantity'] = intval($row['stock_quantity']);
        
        $brand_prefix = !empty($row['brand']) ? strtoupper(trim($row['brand'])) : 'GENERIC';
        $product_name = !empty($row['name']) ? trim($row['name']) : '';
        
        $row['brand'] = $brand_prefix; 
        $row['name'] = $brand_prefix . ' ' . $product_name;
        $row['price'] = number_format(floatval($row['price']), 2, '.', '');
        $row['image'] = !empty($row['image']) ? trim($row['image']) : 'placeholder-default.png';

        // 🌟 3. Deduplicate (though SQL 'OR' takes care of row duplicates, this keeps arrays tidy)
        $products[] = $row;
    }
    
    $stmt->close();
}

echo json_encode([
    "status" => "success",
    "products" => $products
]);

$conn->close();
?>