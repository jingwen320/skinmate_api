<?php
include 'db.php';

$user_id = $_GET['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
    exit;
}

// 🛍️ Upgraded query to pull our new custom columns + count items from order_items table!
$query = "SELECT 
            o.order_id, 
            o.order_date, 
            o.status, 
            o.final_total, 
            o.shipping_fee,
            (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) AS total_items
          FROM orders o 
          WHERE o.user_id = ? 
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'order_id' => $row['order_id'],
        'date' => date("M d, Y", strtotime($row['order_date'])), 
        'status' => $row['status'],
        'price' => "RM " . number_format($row['final_total'], 2),
        'shipping_fee' => $row['shipping_fee'],
        'total_items' => (int)($row['total_items'] ?? 0) // Cast to int for safety
    ];
}

echo json_encode([
    'status' => 'success',
    'orders' => $orders
]);
?>