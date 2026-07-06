<?php
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? '';
$cart_id = $data['cart_id'] ?? '';
$quantity = $data['quantity'] ?? '';

if (!$user_id || !$cart_id || !$quantity) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// 🛡️ Security Check & Update
$stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("iii", $quantity, $cart_id, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Quantity updated'
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to update quantity.'
    ]);
}
?>