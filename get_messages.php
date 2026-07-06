<?php
// get_messages.php
header('Content-Type: application/json');

include 'db.php';

$user_id = $_GET['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing User Identity']);
    exit;
}

// Fetch all historical messages linked to this user's open chat room
$query = "SELECT m.sender_type, m.message, m.created_at 
          FROM chat_messages m
          JOIN chat_rooms r ON m.room_id = r.id
          WHERE r.user_id = $user_id AND r.status = 'open'
          ORDER BY m.created_at ASC";

$result = $conn->query($query);
$messages = [];

while($row = $result->fetch_assoc()) {
    $messages[] = [
        "sender_type" => $row['sender_type'],
        "message" => $row['message'],
        "time" => date("d M, h:i A", strtotime($row['created_at'])) // Formats timestamp safely to '10:15 AM'
    ];
}

echo json_encode(["status" => "success", "messages" => $messages]);
$conn->close();
?>