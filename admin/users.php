<?php
// admin/users.php
session_start();
include '../db.php';

// 🛡️ Security Gatekeeper
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// 📊 Pull Total Statistics Row Metrics
$total_count = 0;
$active_count = 0;
if ($count_res = $conn->query("SELECT COUNT(*) as total FROM users")) {
    $total_count = $count_res->fetch_assoc()['total'];
}

// 🔍 Fetch Main Directory Records (Safely leaving out password fields)
$query = "SELECT id, name, email, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($query);

// 🎯 Handle Side Profile Focus Context (Default to first user row if none clicked)
$focused_user_id = isset($_GET['view_user']) ? intval($_GET['view_user']) : 0;
if ($focused_user_id === 0 && $result && $result->num_rows > 0) {
    // Auto-focus first entry if landing fresh
    $result->data_seek(0);
    $first = $result->fetch_assoc();
    $focused_user_id = $first['id'];
    $result->data_seek(0); // Reset loop index pointer
}

// 🧬 Fetch Isolated Metric Aggregates for the focused user's panel
$focused_profile = null;
$scans_history = [];
if ($focused_user_id > 0) {
    $prof_stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
    $prof_stmt->bind_param("i", $focused_user_id);
    $prof_stmt->execute();
    $focused_profile = $prof_stmt->get_result()->fetch_assoc();

    if ($focused_profile) {
        // Fetch matching chronological skin progress timeline logs
        $scan_stmt = $conn->prepare("SELECT id, skin_type, health_score, acne_pct, dark_spots_pct, pigmentation_pct, pores_pct, redness_pct, wrinkles_pct FROM skin_progress WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
        $scan_stmt->bind_param("i", $focused_user_id);
        $scan_stmt->execute();
        $scans_res = $scan_stmt->get_result();
        while ($scan_row = $scans_res->fetch_assoc()) {
            $scans_history[] = $scan_row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SkinMate Admin - Users Management</title>
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
    <?php include 'sidebar.php'; ?>
</head>
<body class="bg-surface text-on-background min-h-screen">

    <!-- Main Workspace Canvas Area -->
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Page Identity Header & Stats Indicators -->
            <div class="flex justify-between items-end">
                <div>
                    <!-- <h1 class="text-3xl font-extrabold text-on-background tracking-tight">User Directory</h1> -->
                    <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">User Directory</h2>
                    <p class="text-on-surface-variant mt-1">Manage registered skincare profiles, clinical diagnostic records, and telemetry.</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-surface-container-low px-6 py-3 rounded-xl border-l-4 border-primary">
                        <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-widest">Total Users</p>
                        <p class="text-xl font-bold text-primary"><?php echo $total_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Bento Layout Module Split Grid -->
            <div class="grid grid-cols-12 gap-8">
                
                <!-- Left Section: Search Results Directory (Col 8) -->
                <div class="col-span-8 space-y-6">
                    <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm shadow-stone-200/50">
                        
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex gap-2">
                                <span class="bg-primary/10 text-primary px-4 py-1.5 rounded-full text-xs font-bold border border-primary/20 cursor-pointer">All Accounts</span>
                            </div>
                        </div>

                        <!-- Modern Border-free Structural List Frame -->
                        <div class="space-y-4">
                            <!-- Column Labels Reference Line -->
                            <div class="grid grid-cols-6 px-4 py-2 text-[10px] uppercase font-black text-stone-400 tracking-widest">
                                <div class="col-span-2">User Identity</div>
                                <div>Skin Type</div>
                                <div>Status</div>
                                <div>Reg. Date</div>
                                <div class="text-right">Actions</div>
                            </div>

                            <!-- PHP Dynamic Iteration Engine -->
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($user = $result->fetch_assoc()): 
                                    $is_selected = ($user['id'] === $focused_user_id);
                                    $row_classes = $is_selected 
                                        ? 'bg-primary-container/10 border-2 border-primary-container/30' 
                                        : 'hover:bg-surface-container-low border-2 border-transparent';
                                ?>
                                    <div onclick="window.location.href='users.php?view_user=<?php echo $user['id']; ?>'" 
                                         class="grid grid-cols-6 items-center px-4 py-4 rounded-xl transition-all cursor-pointer <?php echo $row_classes; ?>">
                                        
                                        <!-- Profile Badge & Identity fields -->
                                        <div class="col-span-2 flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-xs uppercase">
                                                <?php echo substr($user['username'], 0, 2); ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-sm text-on-surface"><?php echo htmlspecialchars($user['username']); ?></p>
                                                <p class="text-xs text-on-surface-variant truncate max-w-[160px]"><?php echo htmlspecialchars($user['email']); ?></p>
                                            </div>
                                        </div>

                                        <!-- Categorized Skin Type Attribute Tag -->
                                        <div>
                                            <span class="bg-tertiary-container text-on-tertiary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                                                <?php echo htmlspecialchars($user['skin_type'] ?? 'Pending'); ?>
                                            </span>
                                        </div>

                                        <!-- Network Connectivity Context state indicator -->
                                        <div>
                                            <span class="flex items-center gap-1.5 text-xs font-medium text-green-600">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
                                            </span>
                                        </div>

                                        <!-- Registration Timestamp Formatting row -->
                                        <div class="text-xs text-on-surface-variant font-medium">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </div>

                                        <!-- Target View Deep Links -->
                                        <div class="flex justify-end gap-2" onclick="event.stopPropagation();">
                                            <a href="users.php?view_user=<?php echo $user['id']; ?>" class="p-2 hover:bg-primary/10 rounded-lg text-primary transition-colors">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center p-12 text-sm text-stone-400 font-medium">No registered platform accounts discovered.</p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- Right Section: Interactive Profile Detail View Canvas Panel (Col 4) -->
                <div class="col-span-4 space-y-6">
                    <?php if ($focused_profile): ?>
                        <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-xl shadow-orange-900/5 relative border border-stone-200/30">
                            
                            <!-- Header Gradient Canvas -->
                            <div class="h-24 bg-gradient-to-r from-primary to-primary-container"></div>
                            
                            <div class="px-6 pb-6 relative">
                                <div class="flex justify-between items-start -mt-10 mb-4">
                                    <div class="w-20 h-20 rounded-2xl bg-white shadow-lg flex items-center justify-center border-4 border-white font-black text-2xl text-primary tracking-tighter bg-orange-50">
                                        <?php echo strtoupper(substr($focused_profile['username'], 0, 2)); ?>
                                    </div>
                                    <button class="bg-white/80 backdrop-blur-sm p-2 rounded-full text-primary hover:bg-white shadow-sm mt-12">
                                        <span class="material-symbols-outlined">more_horiz</span>
                                    </button>
                                </div>

                                <h3 class="text-xl font-extrabold text-on-surface"><?php echo htmlspecialchars($focused_profile['username']); ?></h3>
                                <p class="text-sm font-medium text-on-surface-variant mb-6">User Reference: #SM-<?php echo $focused_profile['id']; ?>-UA</p>

                                <!-- AI Biometric Telemetry Matrix Card block -->
                                <div class="bg-tertiary-container/20 rounded-2xl p-4 mb-6 border border-tertiary-container/30">
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="material-symbols-outlined text-tertiary">query_stats</span>
                                        <h4 class="text-xs font-black uppercase tracking-widest text-tertiary">Latest Skin Metric Averages</h4>
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div class="bg-white/50 p-2 rounded-xl">
                                            <p class="text-[10px] text-stone-500 font-bold">Hydration</p>
                                            <p class="text-sm font-black text-on-surface">
                                                <?php echo !empty($scans_history) ? $scans_history[0]['hydration'] . '%' : '--'; ?>
                                            </p>
                                        </div>
                                        <div class="bg-white/50 p-2 rounded-xl">
                                            <p class="text-[10px] text-stone-500 font-bold">Elasticity</p>
                                            <p class="text-sm font-black text-on-surface">
                                                <?php echo !empty($scans_history) ? $scans_history[0]['elasticity'] . '%' : '--'; ?>
                                            </p>
                                        </div>
                                        <div class="bg-white/50 p-2 rounded-xl">
                                            <p class="text-[10px] text-stone-500 font-bold">Luster</p>
                                            <p class="text-sm font-black text-on-surface">
                                                <?php echo !empty($scans_history) ? $scans_history[0]['luster'] . '%' : '--'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Architectural AI Diagnostics Frame Block -->
                                <div class="mt-8 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Skin Progress Timeline</h4>
                                        <a href="skin_progress.php?user_id=<?php echo $focused_profile['id']; ?>" class="text-[10px] font-bold text-primary hover:underline flex items-center gap-1">
                                            View Progress Page <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                        </a>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <?php if (!empty($scans_history)): ?>
                                            <?php foreach ($scans_history as $index => $scan): ?>
                                                <div class="group relative rounded-xl overflow-hidden aspect-square border border-stone-200/40 bg-stone-100 flex flex-col items-center justify-center text-center p-3">
                                                    <span class="material-symbols-outlined text-primary text-3xl mb-1">biotech</span>
                                                    <p class="text-[10px] font-black text-stone-700 uppercase tracking-tight truncate max-w-full">
                                                        <?php echo htmlspecialchars($scan['condition_thumb'] ?? 'Analysis'); ?>
                                                    </p>
                                                    <div class="absolute bottom-0 left-0 right-0 p-2 bg-black/40 backdrop-blur-xs text-center">
                                                        <p class="text-[8px] font-bold text-white uppercase"><?php echo date('M d, Y', strtotime($scan['scan_date'])); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-span-2 py-8 text-center text-xs text-stone-400 font-medium bg-stone-50 rounded-xl border border-dashed border-stone-200">
                                                No historical scans logged yet.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <button class="w-full mt-8 border-2 border-primary text-primary py-3 rounded-full font-bold text-sm hover:bg-primary hover:text-on-primary transition-all">
                                    Send System Notification
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-surface-container-lowest rounded-xl p-12 text-center text-stone-400 border border-stone-200/40">
                            <span class="material-symbols-outlined text-3xl block mb-2">face</span>
                            Select an account row to view detailed report data.
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>

    <!-- Structural Layout System Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>