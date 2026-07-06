<?php
include 'db.php';

$user_id = $_GET['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User ID required']);
    exit;
}

// 🌟 THE FIX: Use an INNER JOIN to look up the brand and category from your products table
$query = "
    SELECT c.id, p.id AS product_id, c.product_name, c.product_price, c.product_image, c.quantity, p.brand, p.category, p.skin_suitability 
    FROM cart c
    INNER JOIN products p ON c.product_name = p.name
    WHERE c.user_id = ?
    ORDER BY c.id ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[] = [
        'id' => $row['id'],
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'product_price' => (float)$row['product_price'], 
        'product_image' => $row['product_image'],
        'quantity' => (int)$row['quantity'],
        // 🌟 ADDED: Send the brand and category parameters directly to Flutter
        'brand' => $row['brand'] ?? '',
        'category' => $row['category'] ?? 'The Essentials',
        'skin_suitability' => $row['skin_suitability'] ?? 'All',
    ];
}

echo json_encode([
    'status' => 'success',
    'cart' => $cart_items
]);
?>