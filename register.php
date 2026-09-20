<?php
// register.php
date_default_timezone_set('Asia/Kuala_Lumpur');
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
// $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
// $stmt->bind_param("sss", $name, $email, $hashed_password); 

// if ($stmt->execute()) {
//     echo json_encode([
//         'status' => 'success',
//         'user' => [
//             'id' => $stmt->insert_id,
//             'name' => $name,
//             'email' => $email
//         ]
//     ]);
// } else {
//     echo json_encode(['status'=>'error','message'=>'Registration failed']);
// }

$otp = rand(100000, 999999);
$hashed_otp = password_hash((string)$otp, PASSWORD_DEFAULT);
$expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$stmt = $conn->prepare("INSERT INTO users (name, email, password, otp_code, otp_expires, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
$stmt->bind_param("sssss", $name, $email, $hashed_password, $hashed_otp, $expiry);

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

if ($stmt->execute()) {
    $userId = $stmt->insert_id;
    $stmt->close();

    // 🌟 Insert Welcome Voucher (10% discount, valid for 1 month)
    $welcomeCode = 'WELCOME-' . strtoupper(substr(md5(mt_rand()), 0, 6));
    $title = 'Welcome 10% Off Voucher';
    $discountType = 'percentage';
    $discountValue = 10.00;
    $voucherExpiry = date("Y-m-d H:i:s", strtotime("+1 month"));

    $voucherStmt = $conn->prepare("INSERT INTO user_vouchers (user_id, voucher_code, title, discount_type, discount_value, status, expires_at) VALUES (?, ?, ?, ?, ?, 'active', ?)");
    $voucherStmt->bind_param("isssds", $userId, $welcomeCode, $title, $discountType, $discountValue, $voucherExpiry);
    $voucherStmt->execute();
    $voucherStmt->close();

    // Send email using PHPMailer or mail() here with the $otp
    try {
        // Server settings
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'jingwenng0320@gmail.com';      
        $mail->Password   = 'ihyq rjjg mmxx odvc';      
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465;                                    

        // Recipients
        $mail->setFrom('jingwenng0320@gmail.com', 'SkinMate Support');
        $mail->addAddress($email); // The user's email pulled from the DB

        // Content
        $mail->isHTML(true);                                  
        $mail->Subject = 'Your SkinMate TAC';
        $mail->Body    = "Your verification code is: $otp. <br><br>This code expires in 5 minutes.";
        $mail->AltBody = "Your verification code is: $otp. <br><br>This code expires in 5 minutes.";

        $mail->send();
        echo json_encode(['status' => 'pending', 'message' => 'OTP sent to email!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

// $stmt->close();
$conn->close();
?>