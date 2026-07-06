<?php
include 'db.php';

// Accept JSON payload
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? '';
$cart_id = $data['cart_id'] ?? ''; // This is the 'id' of the row in the cart table

if (!$user_id || !$cart_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// 🛡️ Security Check & Delete
// This ensures users can only delete items from their OWN cart!
$stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed from cart'
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to remove item or item not found.'
    ]);
}
?>