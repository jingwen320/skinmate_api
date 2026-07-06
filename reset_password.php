<?php
include 'db.php';

$message = "";
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!$token) {
    die("Invalid or missing reset token.");
}

// Check if token is valid and not expired
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

if (!$email) {
    die("This reset link is invalid or has expired.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update user's password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Success! Delete the token so it can't be used again
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();
            
            $message = "Password changed successfully! You can now log in via the app.";
        } else {
            $message = "An error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body { font-family: sans-serif; background: #F7F6F3; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 350px; }
        input { width: 100%; padding: 12px; margin: 8px 0; box-sizing: border-box; border-radius: 8px; border: 1px solid #ccc; }
        button { width: 100%; padding: 12px; background: #91462E; color: white; border: none; border-radius: 24px; cursor: pointer; font-weight: bold; }
        .msg { color: #91462E; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Reset Your Password</h2>
        <?php if ($message) echo "<p class='msg'>$message</p>"; ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label>New Password</label>
            <input type="password" name="password" required>
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
            <button type="submit">Change Password</button>
        </form>
    </div>
</body>
</html>