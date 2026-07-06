<?php
include 'db.php';

// 1. Validate Input
if (!isset($_GET['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing user_id"]);
    exit;
}

$user_id = $_GET['user_id'];

// 2. Prepared Statement
$stmt = $conn->prepare("SELECT * FROM skin_progress WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];

// 3. ONE loop to collect all data
while($row = $result->fetch_assoc()) {
    // Optional: Add the full image URL if you want to use it in Flutter
    if (!empty($row['image'])) {
        // $row['image_url'] = "http://192.168.0.22/skinmate/uploads/" . $row['image'];
        // $row['image_url'] = "http://192.168.101.170/skinmate/uploads/" . $row['image'];
        // $row['image_url'] = "http://10.0.2.2/skinmate/uploads/" . $row['image'];
        // $row['image_url'] = "https://carwash-manhandle-sprinkler.ngrok-free.dev/skinmate/uploads/" . $row['image'];
        $row['image_url'] = "https://library-valium-riverboat.ngrok-free.dev/skinmate/uploads/" . $row['image'];
    }
    
    $history[] = $row;
}

// 4. ONE echo at the very end (Outside the loop!)
// This ensures Flutter receives a single, valid JSON object.
echo json_encode([
    "status" => "success", 
    "data" => $history
]);

// Ensure no other text is printed after this
exit;
?>