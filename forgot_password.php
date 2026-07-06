<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    // Pro-Tip: To prevent hackers from guessing which emails are in your DB,
    // you should return "success" here too, even if the email doesn't exist.
    echo json_encode(['status' => 'success', 'message' => 'If that email exists, a link has been sent.']);
    exit;
}
$stmt->close();

// 2. Generate a secure, unique token
$token = bin2hex(random_bytes(32));
$expiry = date("Y-m-d H:i:s", strtotime('+1 hour')); // Link expires in 1 hour

// 3. Store the token in a table named `password_resets`
// (Create this table with columns: email, token, expiry)
$stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token=?, expiry=?");
$stmt->bind_param("sssss", $email, $token, $expiry, $token, $expiry);
$stmt->execute();
$stmt->close();

// 4. Send the reset link to the user
// Load PHPMailer files manually
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// $reset_link = "http://10.0.2.2/skinmate_api/reset_password.php?token=" . $token;
// $reset_link = "http://192.168.101.184/skinmate_api/reset_password.php?token=" . $token;
// $reset_link = "http://192.168.0.22/skinmate_api/reset_password.php?token=" . $token;
// $reset_link = "https://carwash-manhandle-sprinkler.ngrok-free.dev/skinmate_api/reset_password.php?token=" . $token;
$reset_link = "https://library-valium-riverboat.ngrok-free.dev/skinmate_api/reset_password.php?token=" . $token;

$mail = new PHPMailer(true);

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
    $mail->Subject = 'Reset your SkinMate Password';
    $mail->Body    = "Click the following link to reset your password:<br><br>
                      <a href='$reset_link'>$reset_link</a><br><br>
                      This link will expire in 1 hour.";
    $mail->AltBody = "Click the following link to reset your password: $reset_link";

    $mail->send();
    echo json_encode(['status' => 'success', 'message' => 'Reset link sent to your email!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}

$conn->close();
?>