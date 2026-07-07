<?php
// admin/backend/admin_send.php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Singapore');
include '../../db.php';

$pusher_app_id  = "2173285";
$pusher_key     = "1b9d74bdae41a56142c8";
$pusher_secret  = "366eb48437f577663f33";
$pusher_cluster = "ap1";

$data = json_decode(file_get_contents("php://input"), true);
$room_id = isset($data['room_id']) ? intval($data['room_id']) : 0;
$message = $data['message'] ?? '';

if ($room_id === 0 || empty(trim($message))) {
    echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
    exit;
}

// 1. Secure permanent DB log entry via Prepared Statement
$stmt = $conn->prepare("INSERT INTO chat_messages (room_id, sender_type, message) VALUES (?, 'admin', ?)");
$stmt->bind_param("is", $room_id, $message);
$insert_success = $stmt->execute();
$stmt->close();

if ($insert_success) {
    // 2. Critical fix: Force update the parent room's timestamp to lift it to the top of the queue
    $update_room = $conn->prepare("UPDATE chat_rooms SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $update_room->bind_param("i", $room_id);
    $update_room->execute();
    $update_room->close();

    $formatted_time = date("d M, h:i A");
    
    // Format Pusher Payload exactly like your customer app expects
    $payload = json_encode([
        "name"     => "new-message",
        "channels" => ["chat-room-" . $room_id],
        "data"     => json_encode([
            "sender_type" => "admin",
            "message"     => $message,
            "time"        => $formatted_time
        ])
    ]);

    $timestamp = time();
    $body_md5 = md5($payload);
    $query_string = "auth_key=$pusher_key&auth_timestamp=$timestamp&auth_version=1.0&body_md5=$body_md5";
    $auth_string = "POST\n/apps/$pusher_app_id/events\n$query_string";
    $auth_signature = hash_hmac('sha256', $auth_string, $pusher_secret);
    $url = "https://api-$pusher_cluster.pusher.com/apps/$pusher_app_id/events?" . $query_string . "&auth_signature=" . $auth_signature;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($ch);
    curl_close($ch);

    echo json_encode(["status" => "success", "time" => $formatted_time]);
} else {
    echo json_encode(["status" => "error", "message" => "Database insert error"]);
}
$conn->close();
?>