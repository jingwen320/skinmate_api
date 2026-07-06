<?php
include 'db.php';

// Collect POST parameters
$user_id = $_POST['user_id'] ?? '';
$product_id = $_POST['product_id'] ?? '';
$review = $_POST['review'] ?? '';
$rating = $_POST['rating'] ?? '';

// Validate input
if (!$user_id || !$product_id || !$review || !$rating) {
    echo json_encode(['status' => 'error', 'message' => 'All fields required']);
    exit;
}

$rating = (int)$rating;

// Prepare and execute statement
$stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, review, rating) VALUES (?,?,?,?)");
$stmt->bind_param("iisi", $user_id, $product_id, $review, $rating);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Review added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add review']);
}
?>