<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$user_id = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? 'fetch'; // 'fetch' or 'mark_read'

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing User Identity']);
    exit;
}

if ($action === 'mark_read') {
    // Mark all unread elements as read instantly when clicking the bell container
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    echo json_encode(['status' => 'success']);
    exit;
}

// Otherwise, fetch all items for the log dashboard feed view
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread_count = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    if ((int)$row['is_read'] === 0) {
        $unread_count++;
    }
}

echo json_encode([
    'status' => 'success',
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);
?>