<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = $_POST['password'] ?? ''; 

if (!$name || !$email || !$password) {
    echo json_encode(['status'=>'error','message'=>'All fields required']);
    exit;
}

//  Server-side email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status'=>'error','message'=>'Invalid email address format']);
    exit;
}

// Server-side password length validation
if (strlen($password) < 8) {
    echo json_encode(['status'=>'error','message'=>'Password must be at least 8 characters long']);
    exit;
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM users WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close(); // Close before exiting
    echo json_encode(['status'=>'error','message'=>'Email already exists']);
    exit;
}
$check->close();

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hashed_password); 

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => $stmt->insert_id,
            'name' => $name,
            'email' => $email
        ]
    ]);
} else {
    echo json_encode(['status'=>'error','message'=>'Registration failed']);
}

$stmt->close();
$conn->close();
?>