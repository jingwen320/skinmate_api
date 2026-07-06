<?php
// send_message.php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Singapore');

include 'db.php';

// 1. Define Pusher credentials at the top
$pusher_app_id  = "2173285";
$pusher_key     = "1b9d74bdae41a56142c8";
$pusher_secret  = "366eb48437f577663f33";
$pusher_cluster = "ap1";

// 2. Grab the JSON payload sent from Flutter
$data = json_decode(file_get_contents("php://input"), true);

$user_id     = isset($data['user_id']) ? intval($data['user_id']) : 0;
$sender_type = $data['sender_type'] ?? 'user';
$message     = $data['message'] ?? '';

if ($user_id == 0 || empty($message)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// 3. Find or Create an active chat room for this user
$room_id = 0;
$is_new_room = false;
$room_check = $conn->query("SELECT id FROM chat_rooms WHERE user_id = $user_id AND status = 'open' LIMIT 1");

if ($room_check->num_rows > 0) {
    $room = $room_check->fetch_assoc();
    $room_id = $room['id'];
} else {
    // No open room found, open a brand new one
    $create_room = $conn->query("INSERT INTO chat_rooms (user_id, status) VALUES ($user_id, 'open')");
    $room_id = $conn->insert_id;
    $is_new_room = true;
}

if ($is_new_room) {
    $welcome_msg = "Hello! Welcome to SkinMate support. An admin will be with you shortly. How can we help you today?";
    
    // Save to DB logs permanently
    $conn->query("INSERT INTO chat_messages (room_id, sender_type, message) VALUES ($room_id, 'admin', '$welcome_msg')");
    
    // 🌟 FIXED STRUCTURE: Nested specifically for Pusher REST API specification
    $welcome_payload = json_encode([
        "name"     => "new-message",
        "channels" => ["chat-room-" . $room_id],
        "data"     => json_encode([
            "sender_type" => "admin",
            "message"     => $welcome_msg,
            "time"        => date("d M, h:i A")
        ])
    ]);

    // Send payload to Pusher using your curl setup
    $timestamp = time();
    $body_md5 = md5($welcome_payload);
    $query_string = "auth_key=$pusher_key&auth_timestamp=$timestamp&auth_version=1.0&body_md5=$body_md5";
    $auth_string = "POST\n/apps/$pusher_app_id/events\n$query_string";
    $auth_signature = hash_hmac('sha256', $auth_string, $pusher_secret);
    $url = "https://api-$pusher_cluster.pusher.com/apps/$pusher_app_id/events?" . $query_string . "&auth_signature=" . $auth_signature;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $welcome_payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($ch);
    curl_close($ch);
}

// 4. Save the message text into the timeline ONLY if it's a real user message
// if ($message === '检查连接') {
//     echo json_encode([
//         "status" => "success",
//         "message" => "Room initialized successfully.",
//         "room_id" => $room_id
//     ]);
//     $conn->close();
//     exit;
// }
if ($message === '检查连接') {
    // 🌟 Check if there are any existing messages in this room to determine if it's brand new
    $msg_check = $conn->query("SELECT message, sender_type, created_at FROM chat_messages WHERE room_id = $room_id ORDER BY created_at ASC");
    $initial_messages = [];
    
    while ($row = $msg_check->fetch_assoc()) {
        // This grabs the welcome message we inserted in Step 3!
        $initial_messages[] = [
            "sender_type" => "sender_type",
            "message"     => $row['message'],
            "time"        => date("d M, h:i A", strtotime($row['created_at']))
        ];
    }

    echo json_encode([
        "status" => "success",
        "message" => "Room initialized successfully.",
        "room_id" => $room_id,
        "initial_messages" => $initial_messages // 🚀 Send the welcome message down right here!
    ]);
    $conn->close();
    exit;
}

// Proceed with standard message handling for actual chats
$insert_message = $conn->query("INSERT INTO chat_messages (room_id, sender_type, message) VALUES ($room_id, '$sender_type', '$message')");

if ($insert_message) {    
    // 🌟 FIXED STRUCTURE: Nested matching real-time listener parameters 
    $payload = json_encode([
        "name"     => "new-message",
        "channels" => ["chat-room-" . $room_id],
        "data"     => json_encode([
            "sender_type" => $sender_type,
            "message"     => $message,
            "time"        => date("d M, h:i A")
        ])
    ]);

    // Format the authorization signatures required by Pusher's REST API
    $timestamp    = time();
    $body_md5     = md5($payload);
    $query_string = "auth_key=$pusher_key&auth_timestamp=$timestamp&auth_version=1.0&body_md5=$body_md5";
    
    $auth_string  = "POST\n/apps/$pusher_app_id/events\n$query_string";
    $auth_signature = hash_hmac('sha256', $auth_string, $pusher_secret);
    
    $url = "https://api-$pusher_cluster.pusher.com/apps/$pusher_app_id/events?" . $query_string . "&auth_signature=" . $auth_signature;

    // Fire the webhook request to Pusher
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
        "message" => "Message sent successfully!",
        "room_id" => $room_id
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save message."]);
}

$conn->close();
?>