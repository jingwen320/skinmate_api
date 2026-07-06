<?php
include 'db.php';

$user_id = $_POST['user_id'] ?? '';
$product_id = $_POST['product_id'] ?? '';

if (!$user_id || !$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// Optional: Prevent duplicate entries
$check = $conn->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
$check->bind_param("ii", $user_id, $product_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Already in wishlist']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add to wishlist']);
}
?>