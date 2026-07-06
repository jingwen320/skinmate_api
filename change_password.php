<?php
// change_password.php
header("Content-Type: application/json");
include 'db.php';

$userId = $_POST['user_id'] ?? '';
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

// 1. Fetch current hashed password from database
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $hashedPassword = $row['password'];

    // 2. Verify input matches current hash string sequence securely
    if (password_verify($currentPassword, $hashedPassword)) {
        
        // 3. Hash the new password securely
        $newSecureHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // 4. Update the database record row execution
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("ss", $newSecureHash, $userId);
        
        if ($updateStmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Password updated successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update record in database."]);
        }
        $updateStmt->close();
        
    } else {
        echo json_encode(["status" => "error", "message" => "Incorrect current password."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User record profile not found."]);
}

$stmt->close();
$conn->close();
?>