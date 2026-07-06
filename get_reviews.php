<?php
include 'db.php';

// Get product_id from GET
$product_id = $_GET['product_id'] ?? '';

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Product ID required', 'reviews' => []]);
    exit;
}

// Prepare statement to prevent SQL injection
// 🌟 THE FIX: Added DATE_FORMAT() to alias created_at directly into Month Day, Year format
$stmt = $conn->prepare("SELECT 
                            r.id, 
                            r.user_id, 
                            r.review, 
                            r.rating, 
                            DATE_FORMAT(r.created_at, '%b %d, %Y') AS created_at, 
                            u.name 
                        FROM reviews r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.product_id = ? 
                        ORDER BY r.id DESC");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    // Cast integers explicitly so Flutter reads them cleanly
    $row['id'] = intval($row['id']);
    $row['user_id'] = intval($row['user_id']);
    $row['rating'] = intval($row['rating']);
    
    $reviews[] = $row;
}

// Return structured JSON
echo json_encode(['status' => 'success', 'reviews' => $reviews]);
?>