<?php
// admin/login.php
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();
include '../db.php';

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, email, password_hash FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // if (password_verify($password, $row['password_hash'])) {
            //     $_SESSION['admin_logged_in'] = true;
            //     $_SESSION['admin_id'] = $row['id'];
            //     $_SESSION['admin_user'] = $row['username'];
                
            //     header("Location: index.php");
            //     exit;
            // } else {
            //     $error = "Invalid username or password. hint: check your credentials and try again.";
            // }
            if (password_verify($password, $row['password_hash'])) {
                $otp = rand(100000, 999999);
                $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

                $hashed_otp = password_hash((string)$otp, PASSWORD_DEFAULT);
                
                // Store in DB
                $update = $conn->prepare("UPDATE admin SET otp_code = ?, otp_expiry = ? WHERE id = ?");
                $update->bind_param("ssi", $hashed_otp, $expiry, $row['id']);
                $update->execute();

                // Send email (Mockup: You can log it for now)
                // error_log("TAC for Admin: $otp"); // Check your server logs
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
                    $mail->addAddress($row['email']);

                    // Content
                    $mail->isHTML(true);                                  
                    $mail->Subject = 'Your Admin Access TAC';
                    $mail->Body    = "Your verification code is: $otp. <br><br>This code expires in 5 minutes.";
                    $mail->AltBody = "Your verification code is: $otp. <br><br>This code expires in 5 minutes.";

                    $mail->send();
                } catch (Exception $e) {
                    $error = "Mail could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $_SERVER['REQUEST_METHOD'] = 'GET';                }
                
                $_SESSION['temp_admin_id'] = $row['id'];
                header("Location: verify_tac.php");
                exit;
            } else {
                $error = "Invalid username or password. hint: check your credentials and try again.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkinMate Admin - Login</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#91462e", background: "#f7f6f3" }
                }
            }
        }
    </script>
</head>
<body class="bg-[#f7f6f3] font-['Manrope'] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-stone-100 p-8 md:p-10">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-extrabold text-[#91462e] font-['Plus_Jakarta_Sans']">SkinMate Portal</h2>
            <p class="text-stone-400 text-xs uppercase tracking-widest mt-1">Management Administration</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 text-sm rounded-xl font-medium text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label for="username" class="block text-xs font-semibold text-stone-600 uppercase tracking-wider mb-2">Username</label>
                <input type="text" id="username" name="username" required class="w-full rounded-xl border-stone-200 focus:border-[#91462e] focus:ring focus:ring-[#91462e]/20 text-sm px-4 py-3">
            </div>
            <div>
                <label for="password" class="block text-xs font-semibold text-stone-600 uppercase tracking-wider mb-2">Password</label>
                <input type="password" id="password" name="password" required class="w-full rounded-xl border-stone-200 focus:border-[#91462e] focus:ring focus:ring-[#91462e]/20 text-sm px-4 py-3">
            </div>
            <button type="submit" class="w-full py-3.5 bg-[#91462e] hover:bg-[#823a23] text-white font-bold text-sm rounded-xl tracking-wide shadow-lg shadow-[#91462e]/10 transition-colors mt-2">
                LOG IN
            </button>
        </form>
    </div>
</body>
</html>