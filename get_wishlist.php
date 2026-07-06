<?php
include 'db.php';

$user_id = $_GET['user_id'] ?? '';
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id', 'wishlist' => []]);
    exit;
}

$stmt = $conn->prepare("
    SELECT w.id as wishlist_id, p.*
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$wishlist = [];
while($row = $result->fetch_assoc()) {
    $wishlist[] = $row;
}

echo json_encode(['status' => 'success', 'wishlist' => $wishlist]);
?>