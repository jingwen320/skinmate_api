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
                    $_SERVER['REQUEST_METHOD'] = 'GET';                
                }
                
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
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "tertiary-fixed": "#fed07f",
                    "background": "#f7f6f3",
                    "surface-bright": "#f7f6f3",
                    "error-dim": "#9f0519",
                    "outline": "#767775",
                    "inverse-primary": "#fe9d7f",
                    "tertiary-dim": "#694a02",
                    "tertiary": "#77560f",
                    "primary-fixed": "#fe9d7f",
                    "secondary": "#7d4d5f",
                    "on-tertiary-fixed": "#4b3400",
                    "surface-variant": "#ddddd9",
                    "primary": "#91462e",
                    "inverse-surface": "#0d0f0d",
                    "primary-dim": "#823a23",
                    "on-tertiary-container": "#634500",
                    "on-surface-variant": "#5b5c5a",
                    "on-error": "#ffefee",
                    "on-primary-container": "#5f200b",
                    "surface-container-high": "#e3e3df",
                    "surface": "#f7f6f3",
                    "primary-container": "#fe9d7f",
                    "on-primary-fixed": "#3e0c00",
                    "on-secondary-fixed-variant": "#714355",
                    "on-background": "#2e2f2d",
                    "on-primary-fixed-variant": "#6a2913",
                    "surface-container-highest": "#ddddd9",
                    "secondary-fixed-dim": "#efb3c8",
                    "on-primary": "#ffefeb",
                    "tertiary-fixed-dim": "#efc373",
                    "error": "#b31b25",
                    "tertiary-container": "#fed07f",
                    "secondary-fixed": "#fec1d6",
                    "surface-dim": "#d4d5d1",
                    "on-tertiary-fixed-variant": "#6e4f07",
                    "error-container": "#fb5151",
                    "inverse-on-surface": "#9d9d9a",
                    "primary-fixed-dim": "#ee9073",
                    "secondary-container": "#fec1d6",
                    "on-secondary-fixed": "#502738",
                    "surface-tint": "#91462e",
                    "surface-container-lowest": "#ffffff",
                    "on-secondary-container": "#663a4b",
                    "on-secondary": "#ffeff2",
                    "on-error-container": "#570008",
                    "outline-variant": "#adadab",
                    "surface-container-low": "#f1f1ee",
                    "on-tertiary": "#fff1de",
                    "secondary-dim": "#6f4253",
                    "on-surface": "#2e2f2d",
                    "surface-container": "#e8e8e5"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "fontFamily": {
                    "headline": ["Plus Jakarta Sans"],
                    "body": ["Manrope"],
                    "label": ["Manrope"]
            }
          },
        },
      }
    </script>
    <style>
        body { font-family: 'Manrope', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
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
                <div class="relative">
                    <input type="password" id="password" name="password" id="password" required class="w-full rounded-xl border-stone-200 focus:border-[#91462e] focus:ring focus:ring-[#91462e]/20 text-sm px-4 py-3">
                    <button type="button" onclick="togglePassword('password')" 
                        class="absolute right-3 top-3 text-stone-400 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full py-3.5 bg-[#91462e] hover:bg-[#823a23] text-white font-bold text-sm rounded-xl tracking-wide shadow-lg shadow-[#91462e]/10 transition-colors mt-2">
                LOG IN
            </button>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('.material-symbols-outlined');
            
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "visibility_off";
            } else {
                input.type = "password";
                icon.textContent = "visibility";
            }
        }
    </script>
</body>
</html>