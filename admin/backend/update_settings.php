<?php
//admin/backend/update_settings.php
session_start();
include '../../db.php';

require '../../PHPMailer/Exception.php';
require '../../PHPMailer/PHPMailer.php';
require '../../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }

// Initialize Mailer
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'jingwenng0320@gmail.com';
$mail->Password   = 'ihyq rjjg mmxx odvc';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port       = 465;
$mail->setFrom('jingwenng0320@gmail.com', 'SkinMate Support');

function sendSecurityEmail($to, $subject, $body, $mail) {
    try {
        $mail->clearAddresses();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
    } catch (Exception $e) { /* Log silently */ }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $current_pass = $_POST['current_password'] ?? '';
    $admin_id = $_SESSION['admin_id'];

    $stmt = $conn->prepare("SELECT password_hash FROM admin WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['password_hash'];

    if (!password_verify($current_pass, $hash)) {
        header("Location: ../settings.php?error=incorrect_password");
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($action === 'update_profile') {
            $new_username = trim($_POST['new_username'] ?? '');
            $new_email    = trim($_POST['new_email'] ?? '');

            $stmt = $conn->prepare("SELECT email FROM admin WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $old_email = $stmt->get_result()->fetch_assoc()['email'];

            if ($old_email !== $new_email) {
                $body = "<p>Hello, your SkinMate Admin account email address is being changed from <b>$old_email</b> to <b>$new_email</b>.</p>";
                
                sendSecurityEmail($old_email, "Security Alert: Email Change Requested", $body, $mail);
            }

            $stmt = $conn->prepare("UPDATE admin SET username = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_username, $new_email, $admin_id);
            $stmt->execute();

            $body = "<p>Hello, your SkinMate Admin account profile was updated successfully.</p>";
            sendSecurityEmail($old_email, "Security Alert: Profile Updated", $body, $mail);
            // if ($old_email !== $new_email) {
            //     sendSecurityEmail($new_email, "Security Alert: Email Updated", "Your email has been changed from $old_email to $new_email.", $mail);
            // }

            $log_action = "Updated profile information";
        } 
        
        elseif ($action === 'update_password') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($new_password !== $confirm_password) throw new Exception("passwords_do_not_match");
            if (strlen($new_password) < 8) throw new Exception("password_too_short");

            $stmt = $conn->prepare("SELECT email FROM admin WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $user_email = $stmt->get_result()->fetch_assoc()['email'];

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $new_hash, $admin_id);
            $stmt->execute();

            $body = "<p>Hello, your SkinMate Admin account password was updated successfully.</p>";
            sendSecurityEmail($user_email, "Security Alert: Password Changed", $body, $mail);

            $log_action = "Updated security password";
        }

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log->bind_param("is", $admin_id, $log_action);
        $log->execute();

        $conn->commit();
        header("Location: ../settings.php?status=success");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../settings.php?error=" . $e->getMessage());
    }
}
?>