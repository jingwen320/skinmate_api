<?php
// close_chat_room.php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Singapore');

include 'db.php';

// 1. Grab the JSON payload sent from either Flutter or Admin JS Dashboard
$data = json_decode(file_get_contents("php://input"), true);
$room_id = isset($data['room_id']) ? intval($data['room_id']) : 0;

if ($room_id == 0) {
    echo json_encode(["status" => "error", "message" => "Missing Room Identity"]);
    exit;
}

// 2. Mark the chat room row status as closed
$update = $conn->query("UPDATE chat_rooms SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE id = $room_id");

if ($update) {
    // 🚀 3. Broadcast a system event via Pusher so both platforms terminate the session immediately
    $pusher_app_id  = "2173285";
    $pusher_key     = "1b9d74bdae41a56142c8";
    $pusher_secret  = "366eb48437f577663f33";
    $pusher_cluster = "ap1";

    // 🌟 FIXED STRUCTURE: Nested matching Pusher REST API specification
    $payload = json_encode([
        "name"     => "new-message", // Kept identical to the event Flutter listens for
        "channels" => ["chat-room-" . $room_id],
        "data"     => json_encode([
            "sender_type" => "system",
            "message"     => "CHAT_CLOSED",
            "time"        => date("d M, h:i A")
        ])
    ]);

    // Format the signatures required by Pusher's secure REST API
    $timestamp    = time();
    $body_md5     = md5($payload);
    $query_string = "auth_key=$pusher_key&auth_timestamp=$timestamp&auth_version=1.0&body_md5=$body_md5";
    
    $auth_string  = "POST\n/apps/$pusher_app_id/events\n$query_string";
    $auth_signature = hash_hmac('sha256', $auth_string, $pusher_secret);
    
    $url = "https://api-$pusher_cluster.pusher.com/apps/$pusher_app_id/events?" . $query_string . "&auth_signature=" . $auth_signature;

    // Fire the webhook request out over cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($ch);
    curl_close($ch);

    echo json_encode([
        "status" => "success", 
        "message" => "Support room connection closed successfully."
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update database status table parameters."]);
}

$conn->close();
?>