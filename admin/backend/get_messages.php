<?php
// admin/backend/get_messages.php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Singapore'); // Matches your admin_send.php timezone

include '../../db.php'; 

// Fetch via room_id to ensure histories from different tickets don't blend together
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid Chat Room Identity']);
    exit;
}

$update_query = "UPDATE chat_messages 
                 SET is_read = 1 
                 WHERE room_id = ? AND sender_type = 'user' AND is_read = 0";

$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $room_id);
$update_stmt->execute();
$update_stmt->close();

$query = "SELECT sender_type, message, is_read, created_at 
          FROM chat_messages 
          WHERE room_id = ? 
          ORDER BY created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        "sender_type" => $row['sender_type'],
        "message"     => $row['message'],
        "is_read"     => (int)$row['is_read'],
        // Explicitly formats timestamp for your frontend UI bubbles
        "time"        => date("d M, h:i A", strtotime($row['created_at']))
    ];
}

echo json_encode(["status" => "success", "messages" => $messages]);

$stmt->close();
$conn->close();
?>