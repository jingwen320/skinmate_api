<?php
// admin/backend/get_rooms.php
header('Content-Type: application/json');
include '../../db.php';

$requestedStatus = isset($_GET['status']) ? $_GET['status'] : 'open';

if ($requestedStatus === 'closed') {
    $allowedStatuses = ['closed'];
} else {
    $allowedStatuses = ['open', 'active']; // Default fallback context
}

$placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));

$query = "SELECT r.id AS room_id, r.user_id, r.status, r.created_at, u.name AS user_name,
          (SELECT message FROM chat_messages WHERE room_id = r.id ORDER BY created_at DESC LIMIT 1) AS last_message
          FROM chat_rooms r 
          LEFT JOIN users u ON r.user_id = u.id
          WHERE r.status IN ($placeholders)
          ORDER BY r.updated_at DESC";

$stmt = $conn->prepare($query);
$types = str_repeat('s', count($allowedStatuses));
$stmt->bind_param($types, ...$allowedStatuses);
$stmt->execute();
$result = $stmt->get_result();
$rooms = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = [
            "room_id" => (int)$row['room_id'],
            "user_id" => (int)$row['user_id'],
            "user_name" => $row['user_name'] ?? 'Unknown User',
            "status" => $row['status'],
            "last_message" => $row['last_message'] ?? 'No messages yet.'
        ];
    }
    echo json_encode(["status" => "success", "rooms" => $rooms]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to pull queues"]);
}

$stmt->close();
$conn->close();
?>