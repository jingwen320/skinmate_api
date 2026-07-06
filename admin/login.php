<?php
// admin/login.php
session_start();
include '../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_user'] = $row['username'];
                
                header("Location: index.php");
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