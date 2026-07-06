<?php
include 'db.php'; // Ensure your DB connection is working

// Use $_POST because your Flutter ApiService uses body: {}
$user_id = $_POST['user_id'] ?? null;
$m_time = $_POST['morning_time'] ?? '08:00:00';
$m_on   = $_POST['morning_enabled'] ?? '0';
$e_time = $_POST['evening_time'] ?? '20:00:00';
$e_on   = $_POST['evening_enabled'] ?? '0';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "No user_id provided"]);
    exit;
}

// Convert "1"/"0" strings to integers for the DB
$m_enabled = ($m_on === "1" || $m_on === "true") ? 1 : 0;
$e_enabled = ($e_on === "1" || $e_on === "true") ? 1 : 0;

// IMPORTANT: This requires a UNIQUE index on the user_id column
$query = "INSERT INTO user_reminders (user_id, morning_enabled, morning_time, evening_enabled, evening_time) 
          VALUES (?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE 
          morning_enabled = VALUES(morning_enabled), 
          morning_time = VALUES(morning_time), 
          evening_enabled = VALUES(evening_enabled), 
          evening_time = VALUES(evening_time)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iisss", $user_id, $m_enabled, $m_time, $e_enabled, $e_time);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Sync complete"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
?>