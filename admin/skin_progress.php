<?php
// admin/skin_progress.php
session_start();
include '../db.php';

// 1. Guard Authentication Barrier
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($target_user_id === 0) {
    die("Error: Missing or invalid user identity parameters context specification.");
}

// 2. Fetch Base User Identity Profile
$user_stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$user_stmt->bind_param("i", $target_user_id);
$user_stmt->execute();
$user_profile = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user_profile) {
    die("Error: Target platform profile metadata does not exist.");
}

// 3. Fetch Full Chronological Biometric Assessment Logs
$history_query = "SELECT 
                    id AS scan_id,
                    skin_type,
                    health_score,
                    acne_pct,
                    dark_spots_pct,
                    pigmentation_pct,
                    pores_pct,
                    redness_pct,
                    wrinkles_pct,
                    created_at AS scan_date
                  FROM skin_progress 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC, id DESC";

$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $target_user_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

$total_scans_count = $history_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SkinMate Admin - Skin Progress History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    
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
<body class="bg-background text-on-surface antialiased">

    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <div class="flex items-center gap-2 text-xs font-bold text-on-surface-variant/60 uppercase tracking-wider select-none">
                <a href="users.php?view_user=<?php echo $target_user_id; ?>" class="hover:text-primary transition-colors">User Directory</a>
                <span class="material-symbols-outlined text-sm text-outline-variant">chevron_right</span>
                <span class="text-on-surface-variant">Skin Progress History</span>
            </div>

            <div class="flex justify-between items-end border-b border-surface-container-high pb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-orange-900 tracking-tight">
                        Progress History: <?php echo htmlspecialchars($user_profile['name']); ?>
                    </h1>
                    <p class="text-on-surface-variant mt-1">
                        Timeline analysis of <?php echo $total_scans_count; ?> scan results for #SM-<?php echo $target_user_id; ?>-UA
                    </p>
                </div>
                <a href="users.php?view_user=<?php echo $target_user_id; ?>" 
                   class="px-5 py-2.5 bg-surface-container-lowest border border-surface-container-high rounded-full text-xs font-bold text-on-surface-variant hover:bg-surface-container-low transition-all flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Return to Profile
                </a>
            </div>

            <?php if ($total_scans_count > 0): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <?php while ($scan = $history_result->fetch_assoc()): ?>
                        <div class="bg-surface-container-lowest border border-surface-container-high rounded-xl p-6 shadow-xs hover:border-outline-variant transition-all flex flex-col justify-between">
                            
                            <div>
                                <div class="flex items-start justify-between gap-4 border-b border-surface-container-low pb-4 mb-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2.5 flex-wrap">
                                            <span class="text-[10px] font-black text-on-surface-variant/70 tracking-widest uppercase">
                                                <?php echo date('M d, Y · h:i A', strtotime($scan['scan_date'])); ?>
                                            </span>
                                            <span class="bg-primary-container text-on-primary-container text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">
                                                #SCN-<?php echo $scan['scan_id']; ?>
                                            </span>
                                        </div>
                                        <h3 class="text-base text-on-background tracking-tight mt-1">
                                            Skin Profile: <span class="text-primary font-bold"><?php echo htmlspecialchars(strtoupper($scan['skin_type'])); ?></span>
                                        </h3>
                                    </div>
                                    <div class="bg-tertiary-container text-on-tertiary-container px-3.5 py-1.5 rounded-xl text-center border border-outline-variant/10">
                                        <p class="text-[8px] font-black uppercase tracking-widest opacity-80">Score</p>
                                        <p class="text-lg font-black"><?php echo $scan['health_score']; ?>%</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    
                                    <div>
                                        <div class="flex justify-between text-xs font-bold mb-1 text-on-surface-variant">
                                            <span>Acne</span>
                                            <span class="font-extrabold text-on-surface"><?php echo $scan['acne_pct']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all" style="width: <?php echo $scan['acne_pct']; ?>%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex justify-between text-xs font-bold mb-1 text-on-surface-variant">
                                            <span>Dark Spots</span>
                                            <span class="font-extrabold text-on-surface"><?php echo $scan['dark_spots_pct']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all" style="width: <?php echo $scan['dark_spots_pct']; ?>%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex justify-between text-xs font-bold mb-1 text-on-surface-variant">
                                            <span>Pigmentation</span>
                                            <span class="font-extrabold text-on-surface"><?php echo $scan['pigmentation_pct']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all" style="width: <?php echo $scan['pigmentation_pct']; ?>%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex justify-between text-xs font-bold mb-1 text-on-surface-variant">
                                            <span>Pores</span>
                                            <span class="font-extrabold text-on-surface"><?php echo $scan['pores_pct']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all" style="width: <?php echo $scan['pores_pct']; ?>%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex justify-between text-xs font-bold mb-1 text-on-surface-variant">
                                            <span>Redness</span>
                                            <span class="font-extrabold text-on-surface"><?php echo $scan['redness_pct']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all" style="width: <?php echo $scan['redness_pct']; ?>%"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex justify-between text-xs font-bold mb-1 text-on-surface-variant">
                                            <span>Wrinkles</span>
                                            <span class="font-extrabold text-on-surface"><?php echo $scan['wrinkles_pct']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full transition-all" style="width: <?php echo $scan['wrinkles_pct']; ?>%"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    <?php endwhile; ?>

                </div>
            <?php else: ?>
                <div class="bg-surface-container-lowest border border-surface-container-high rounded-xl p-16 text-center text-on-surface-variant/60 max-w-2xl mx-auto">
                    <span class="material-symbols-outlined text-4xl block mb-3 text-outline-variant">history</span>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-on-surface">No Scan Histories Found</h3>
                    <p class="text-xs text-on-surface-variant mt-1 max-w-sm mx-auto">This customer has not recorded any biometric scans yet.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>
<?php
$history_stmt->close();
$conn->close();
?>