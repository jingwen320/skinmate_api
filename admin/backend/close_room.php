<?php
// admin/backend/close_room.php
header('Content-Type: application/json');
include '../../db.php';

// Check for authentic POST body arguments
$input = json_decode(file_get_contents('php://input'), true);
$roomId = isset($input['room_id']) ? (int)$input['room_id'] : 0;

if ($roomId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid room reference parameters']);
    exit;
}

// 1. Permanently update the status configuration flag inside MySQL mapping paths
$query = "UPDATE chat_rooms SET status = 'closed' WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $roomId);

if ($stmt->execute()) {
    
    // 2. OPTIONAL: Trigger a Pusher broadcast alert out to the user interface here 
    // if your server framework hosts curl/pusher-php-server event dispatch pipes.
    // (e.g., $pusher->trigger('chat-room-' . $roomId, 'room-archived', ['status' => 'closed']);)

    echo json_encode(['status' => 'success', 'message' => 'Ticket closed successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database persistence layer failed']);
}

$stmt->close();
$conn->close();
?>