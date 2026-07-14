<?php
// delete_unverified.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'db.php'; 

$email = $_POST['email'] ?? '';

if (!empty($email)) {
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ? AND is_verified = 0");
    $stmt->bind_param("s", $email);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Pending account deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete record.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No email provided.']);
}
$conn->close();
?>