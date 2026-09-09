<?php
// subscribe_restock.php
include 'db.php';

$user_id = $_POST['user_id'] ?? '';
$product_id = $_POST['product_id'] ?? '';

if (!$user_id || !$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$query = "INSERT INTO product_notifications (user_id, product_id) VALUES (?, ?) 
          ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Subscribed successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>