<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kuala_Lumpur');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'db.php';

if (empty($_POST)) {
    $_POST = json_decode(file_get_contents("php://input"), true) ?? [];
}

$email = $_POST['email'] ?? '';
$user_tac = $_POST['otp'] ?? '';

if (empty($email) || empty($user_tac)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and TAC required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, otp_code, otp_expires FROM users WHERE email=? AND is_verified=0");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close(); // Close early

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found or already verified.']);
    exit;
}

// 1. Verify TAC (Hash Check)
if (!password_verify($user_tac, $user['otp_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid TAC.']);
    exit;
}

// 2. Check Expiry
if (strtotime($user['otp_expires']) < time()) {
    echo json_encode(['status' => 'error', 'message' => 'TAC has expired.']);
    exit;
}

// 3. Success Path
$update = $conn->prepare("UPDATE users SET is_verified=1, otp_code=NULL, otp_expires=NULL WHERE id=?");
$update->bind_param("i", $user['id']);

if ($update->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
}

$update->close();
$conn->close();
?>