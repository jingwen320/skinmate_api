<?php
include 'db.php';

// Get POST data
$user_id = $_POST['user_id'] ?? '';
$items = $_POST['items'] ?? '';
$order_type = $_POST['order_type'] ?? 'Takeaway';

if (!$user_id || !$items) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// Optionally validate that $items is valid JSON
$decoded_items = json_decode($items, true);
if ($decoded_items === null || !is_array($decoded_items)) {
    echo json_encode(['status' => 'error', 'message' => 'Items must be a valid JSON array']);
    exit;
}

// Insert order
$stmt = $conn->prepare("INSERT INTO orders (user_id, items, order_type) VALUES (?,?,?)");
$stmt->bind_param("iss", $user_id, $items, $order_type);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'order_id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create order']);
}
?>