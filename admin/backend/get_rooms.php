<?php
header('Content-Type: application/json');
include '../../db.php';

$requestedStatus = isset($_GET['status']) ? $_GET['status'] : 'open';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$allowedStatuses = ($requestedStatus === 'closed') ? ['closed'] : ['open', 'active'];
$placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));

// 1. Build the base query
$query = "SELECT r.id AS room_id, r.user_id, r.status, r.created_at, u.name AS user_name,
          (SELECT message FROM chat_messages WHERE room_id = r.id ORDER BY created_at DESC LIMIT 1) AS last_message,
          (SELECT COUNT(*) FROM chat_messages WHERE room_id = r.id AND sender_type = 'user' AND is_read = 0) AS has_unread,
          COALESCE(
             (SELECT created_at FROM chat_messages WHERE room_id = r.id ORDER BY created_at DESC LIMIT 1), 
             r.created_at
          ) AS latest_message_time
          FROM chat_rooms r 
          LEFT JOIN users u ON r.user_id = u.id
          WHERE r.status IN ($placeholders)";

// 2. Append search if it exists
$params = $allowedStatuses;
$types = str_repeat('s', count($allowedStatuses));

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.id LIKE ? OR r.id LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$query .= " ORDER BY latest_message_time DESC";

// 3. Prepare and bind AFTER the query is fully constructed
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
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
            "last_message" => $row['last_message'] ?? 'No messages yet.',
            "has_unread" => (int)$row['has_unread']
        ];
    }
    echo json_encode(["status" => "success", "rooms" => $rooms]);
} else {
    echo json_encode(["status" => "error", "message" => "Database query failed"]);
}

$stmt->close();
$conn->close();
?>