<?php
// admin/verify_tac.php
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();
include '../db.php';

if (!isset($_SESSION['temp_admin_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = trim($_POST['otp'] ?? '');
    
    $stmt = $conn->prepare("SELECT id, username, otp_code, otp_expiry FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['temp_admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($input_otp, $row['otp_code'])) {
            if (strtotime($row['otp_expiry']) < time()) {
                $error = "Your TAC has expired. Please log in again to request a new one.";
            } else {
                $conn->query("UPDATE admin SET otp_code = NULL, otp_expiry = NULL WHERE id = " . $row['id']);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_user'] = $row['username'];
                unset($_SESSION['temp_admin_id']);
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Invalid TAC code.";
        }
    } else {
        $error = "Authentication error. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>SkinMate Admin - Verify TAC</title>
</head>
<body class="bg-[#f7f6f3] flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm bg-white p-8 rounded-2xl shadow-sm border border-stone-100">
        <h2 class="text-xl font-bold text-[#91462e] mb-4 text-center">Verify Identity</h2>
        <p class="text-stone-500 text-xs text-center mb-6">Enter the 6-digit code sent to your email.</p>
        
        <form action="verify_tac.php" method="POST" class="space-y-4">
            <?php if (!empty($error)): ?>
                <div class="p-3 bg-red-50 text-red-700 text-xs rounded-lg text-center font-bold">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <input type="text" name="otp" maxlength="6" required class="w-full text-center text-2xl tracking-[0.5em] py-3 border-stone-200 rounded-xl" placeholder="000000">
            <button type="submit" class="w-full py-3 bg-[#91462e] text-white rounded-xl font-bold">VERIFY</button>
            <div class="mt-6 pt-6 border-t border-stone-100 text-center">
                <a href="login.php" class="text-stone-400 hover:text-[#91462e] text-xs font-semibold uppercase tracking-widest transition-colors">
                    &larr; Back to Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>