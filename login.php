<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Fallback for raw JSON input
if (empty($email) && empty($password)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
}

if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        echo json_encode([
            'status' => 'success',
            'user' => [
                'id' => $row['id'],
                'name' => $row['name']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Wrong password']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>