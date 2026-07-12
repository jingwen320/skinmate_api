<?php
// admin/settings.php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// If not verified in the last 5 minutes, force re-verification
$verify_timeout = 300; // 5 minutes
if (!isset($_SESSION['verified_at']) || (time() - $_SESSION['verified_at'] > $verify_timeout)) {
    $_SESSION['needs_verification'] = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_access'])) {
    $pass = $_POST['current_password'] ?? '';
    $stmt = $conn->prepare("SELECT password_hash FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['password_hash'];

    if (password_verify($pass, $hash)) {
        $_SESSION['verified_at'] = time();
        unset($_SESSION['needs_verification']);
        header("Location: settings.php"); // Refresh to show content
        exit;
    } else {
        $error = "Incorrect password.";
    }
}

// Fetch current admin details
$stmt = $conn->prepare("SELECT username, email FROM admin WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SkinMate Admin - Account Settings</title>
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
<body class="bg-surface text-on-background min-h-screen">

    <!-- Structural App Sidebar Component -->
    <?php include 'sidebar.php'; ?>

    <?php if (isset($_SESSION['needs_verification'])): ?>
        <div class="flex items-center justify-center min-h-screen bg-surface-container-low p-4">
            <div class="bg-surface-container-lowest p-8 rounded-2xl shadow-xl w-full max-w-sm border border-surface-variant/50 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-3xl text-primary">lock</span>
                </div>
                
                <h2 class="text-2xl font-bold text-on-surface mb-2">Re-authenticate</h2>
                <p class="text-on-surface-variant text-sm mb-8">Confirm your identity to access secure settings.</p>

                <form method="POST" class="text-left">
                    <div class="space-y-1.5 mb-6">
                        <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Current Password</label>
                        <input type="password" name="current_password" placeholder="••••••••" 
                            class="w-full bg-surface-container-low border-none rounded-lg focus:ring-2 focus:ring-primary/20 text-on-surface p-3" required/>
                    </div>
                    
                    <button type="submit" name="verify_access" 
                        class="w-full bg-primary text-on-primary font-bold py-3 rounded-full shadow-lg shadow-primary/20 hover:scale-[1.02] transition-transform">
                        Verify Identity
                    </button>
                </form>

                <?php if(isset($error)): ?>
                    <div class="mt-4 flex items-center justify-center text-error text-sm font-medium">
                        <span class="material-symbols-outlined text-base mr-1">error</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Workspace Canvas Area -->
        <main class="ml-72 pt-24 px-10 pb-20 min-h-screen bg-surface">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- Page Header Controls -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div>
                        <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">Account Settings</h2>
                        <p class="text-on-surface-variant mt-1">Manage account details.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Profile Settings -->
                    <section class="bg-surface-container-lowest p-8 rounded-xl shadow-sm" id="profile">
                        <h2 class="text-xl font-bold text-orange-900 mb-8">Profile Settings</h2>
                        <form action="backend/update_settings.php" method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Username</label>
                                    <input name="new_username" type="text" value="<?php echo htmlspecialchars($admin_data['username']); ?>" 
                                        class="w-full bg-surface-container-low border-none rounded-lg focus:ring-2 focus:ring-primary/20 text-on-surface" required/>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Email Address</label>
                                    <input name="new_email" type="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" 
                                        class="w-full bg-surface-container-low border-none rounded-lg focus:ring-2 focus:ring-primary/20 text-on-surface" required/>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Confirm with Current Password</label>
                                <div class="relative">
                                    <input name="current_password" type="password" id="confirm_current_password" placeholder="••••••••" 
                                        class="w-full bg-surface-container-low border-none rounded-lg focus:ring-2 focus:ring-primary/20 pr-12" required/>
                                    <button type="button" onclick="togglePassword('confirm_current_password')" 
                                        class="absolute right-3 top-1.5 text-stone-400 hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="bg-primary text-on-primary font-bold px-8 py-3 rounded-full shadow-lg shadow-primary/20 hover:scale-[1.02] transition-transform">
                                Save Profile Changes
                            </button>
                        </form>
                    </section>

                    <!-- Security (Password) -->
                    <section class="bg-surface-container-lowest p-8 rounded-xl shadow-sm" id="security">
                        <h2 class="text-xl font-bold text-orange-900 mb-8">Security & Password</h2>
                        <form action="backend/update_settings.php" method="POST" class="space-y-6" onsubmit="return validatePassword()">
                            <input type="hidden" name="action" value="update_password">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Current Password</label>
                                    <div class="relative">
                                        <input name="current_password" type="password" id="current_password" placeholder="••••••••" required class="w-full bg-surface-container-low border-none rounded-lg focus:ring-2 focus:ring-primary/20"/>
                                        <button type="button" onclick="togglePassword('current_password')" 
                                            class="absolute right-3 top-1.5 text-stone-400 hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </div>
                                </div>
                                <div></div> <!-- Grid spacer -->
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">New Password</label>
                                    <div class="relative">
                                        <input name="new_password" type="password" id="new_password" placeholder="••••••••" required class="w-full bg-surface-container-low border-none rounded-lg focus:ring-2 focus:ring-primary/20"/>
                                        <button type="button" onclick="togglePassword('new_password')" 
                                            class="absolute right-3 top-1.5 text-stone-400 hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-stone-500 uppercase tracking-wider">Confirm New Password</label>
                                    <div class="relative">
                                        <input name="confirm_password" type="password" id="confirm_password" placeholder="••••••••" required class="w-full bg-surface-container-low border-none rounded-lg focus:ring-2 focus:ring-primary/20"/>
                                        <button type="button" onclick="togglePassword('confirm_password')" 
                                            class="absolute right-3 top-1.5 text-stone-400 hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Changed to bg-error to match your theme -->
                            <button type="submit" class="bg-primary text-white font-bold px-8 py-3 rounded-full shadow-lg shadow-error/20 hover:scale-[1.02] transition-transform">
                                Update Password
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    <?php endif; ?>

    <!-- Structural Layout System Footer -->
    <?php include 'footer.php'; ?>

    <script>
        // Create a function to check for URL parameters and trigger the popup
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Handle Success
            if (urlParams.has('status')) {
                alert("Settings updated successfully!");
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            
            // Handle Errors
            if (urlParams.has('error')) {
                const errorMsg = urlParams.get('error').replace(/_/g, ' ');
                alert("Error: " + errorMsg);
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        };

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

        function validatePassword() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass.length < 8) {
                alert("Password must be at least 8 characters long.");
                return false;
            }
            if (newPass !== confirmPass) {
                alert("New passwords do not match.");
                return false;
            }
            return true;
        }
    </script>

</body>
</html>