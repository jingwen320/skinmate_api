<?php
include 'db.php';

// Accept JSON payload or standard POST data
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? $_POST['user_id'] ?? '';
$name = $data['name'] ?? $_POST['name'] ?? '';
$email = $data['email'] ?? $_POST['email'] ?? '';

if (!$user_id || !$name || !$email) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// 🔍 1. Check if the email is already taken by a DIFFERENT user
$email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$email_check->bind_param("ss", $email, $user_id);
$email_check->execute();
$result = $email_check->get_result();

if ($result->num_rows > 0) {
    // Stop right here and tell the user!
    echo json_encode([
        'status' => 'error',
        'message' => 'This email address is already registered to another account.'
    ]);
    exit;
}

// 📝 2. If it's not taken, proceed to update the user details
$stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
$stmt->bind_param("sss", $name, $email, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
}
?>