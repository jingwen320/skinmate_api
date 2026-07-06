<?php
include 'db.php';

// Accept JSON payload or standard POST data
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? $_POST['user_id'] ?? '';
$order_id = $data['order_id'] ?? $_POST['order_id'] ?? '';

if (!$user_id || !$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// 🛡️ Security Check & Update
// This ensures that the order is ONLY marked as completed if:
// 1. It actually belongs to this specific user.
// 2. Its current state is strictly 'Delivered' (preventing spamming or illegal state jumps).
$stmt = $conn->prepare("UPDATE orders SET status='Completed' WHERE order_id=? AND user_id=? AND status='Delivered'");
$stmt->bind_param("si", $order_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Order marked as completed!'
    ]);
} else {
    // If affected_rows is 0, it means either the order didn't exist or it wasn't in 'Delivered' state
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to complete order. Ensure it is currently marked as Delivered.'
    ]);
}
?>