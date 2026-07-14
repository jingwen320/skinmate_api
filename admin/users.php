<?php
// admin/users.php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$total_count = 0;
if ($count_res = $conn->query("SELECT COUNT(*) as total FROM users")) {
    $total_count = $count_res->fetch_assoc()['total'];
}

$allowed_sort_columns = [
    'name' => 'u.name',
    'skin_type' => 'sp.skin_type',
    'health' => 'sp.health_score',
    'date' => 'u.created_at'
];

$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sort_columns) ? $_GET['sort'] : 'date';
$sort_order = isset($_GET['direction']) && strtoupper($_GET['direction']) === 'ASC' ? 'ASC' : 'DESC';

$next_direction = ($sort_order === 'DESC') ? 'ASC' : 'DESC';
$db_sort_column = $allowed_sort_columns[$sort_key];

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_sql = "";
$params = [];
$types = "";

if ($search_query !== '') {
    $where_sql = " WHERE (u.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $like_param = '%' . $search_query . '%';
    $params = [$like_param, $like_param, $like_param];
    $types = "sss";
}

// $query = "SELECT 
//             u.id AS user_id, 
//             u.name, 
//             u.email, 
//             u.created_at AS user_created_at,
//             sp.id AS scan_id,
//             sp.skin_type, 
//             sp.health_score, 
//             sp.acne_pct, 
//             sp.dark_spots_pct, 
//             sp.pigmentation_pct, 
//             sp.pores_pct, 
//             sp.redness_pct, 
//             sp.wrinkles_pct
//           FROM users u
//           LEFT JOIN skin_progress sp ON sp.id = (
//               SELECT id FROM skin_progress 
//               WHERE user_id = u.id 
//               ORDER BY created_at DESC, id DESC 
//               LIMIT 1
//           )
//           ORDER BY {$db_sort_column} {$sort_order}";

$query = "SELECT u.id AS user_id, u.name, u.email, u.created_at AS user_created_at,
            sp.id AS scan_id, sp.skin_type, sp.health_score, sp.acne_pct, 
            sp.dark_spots_pct, sp.pigmentation_pct, sp.pores_pct, sp.redness_pct, sp.wrinkles_pct
          FROM users u
          LEFT JOIN skin_progress sp ON sp.id = (
              SELECT id FROM skin_progress WHERE user_id = u.id 
              ORDER BY created_at DESC, id DESC LIMIT 1
          )
          {$where_sql}
          ORDER BY {$db_sort_column} {$sort_order}";

// $result = $conn->query($query);

$stmt = $conn->prepare($query);
if ($search_query !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$focused_user_id = isset($_GET['view_user']) ? intval($_GET['view_user']) : 0;

$users_list = [];
$focused_profile = null;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users_list[] = $row;
        
        if ($focused_user_id === 0) {
            $focused_user_id = $row['user_id'];
        }
        
        if (intval($row['user_id']) === $focused_user_id) {
            $focused_profile = $row;
        }
    }
}

function getSortIcon($column, $current_sort, $current_direction) {
    if ($current_sort !== $column) {
        return '<span class="material-symbols-outlined text-[12px] opacity-40 group-hover:opacity-100 transition-opacity">unfold_more</span>';
    }
    return $current_direction === 'ASC' 
        ? '<span class="material-symbols-outlined text-[12px] text-primary">arrow_upward</span>' 
        : '<span class="material-symbols-outlined text-[12px] text-primary">arrow_downward</span>';
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
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen bg-surface">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">User Directory</h2>
                    <p class="text-on-surface-variant mt-1">Manage Registered User Accounts & Skincare Profiles</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-surface-container-low px-6 py-3 rounded-xl border-l-4 border-primary">
                        <p class="text-[10px] uppercase font-bold text-on-surface-variant tracking-widest">Total Users</p>
                        <p class="text-xl font-bold text-primary"><?php echo $total_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <form method="GET" action="users.php" class="flex gap-2 w-full md:w-auto">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_key); ?>">
                    <input type="hidden" name="direction" value="<?php echo htmlspecialchars($sort_order); ?>">
                    
                    <div class="relative min-w-[320px] flex-1 md:flex-initial">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-xl">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                            placeholder="Search by User ID, Name, or Email..." 
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-container-lowest border border-surface-container-high rounded-xl text-xs font-semibold placeholder:text-on-surface-variant/40 focus:outline-none focus:border-primary focus:ring-0 transition-all">
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-sm hover:bg-primary-dim transition-all">Search</button>
                    
                    <?php if ($search_query !== ''): ?>
                        <a href="users.php" class="px-4 py-2.5 bg-surface-container-low border border-surface-container-high text-on-surface-variant text-xs font-bold rounded-xl flex items-center justify-center hover:bg-surface-container-high transition-colors">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="grid grid-cols-12 gap-8">
                
                <div class="col-span-8 space-y-6">
                    <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm shadow-stone-200/50">
                        
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex gap-2">
                                <span class="bg-primary/10 text-primary px-4 py-1.5 rounded-full text-xs font-bold border border-primary/20 cursor-pointer">All Accounts</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-10 px-4 py-2 text-[10px] uppercase font-black text-stone-400 tracking-widest">
                                <!-- <div class="col-span-2">User Identity</div>
                                <div>Skin Type</div>
                                <div>Status</div> 
                                <div>Reg. Date</div>
                                <div class="text-right">Actions</div> -->

                                <a href="?sort=name&direction=<?php echo ($sort_key === 'name') ? $next_direction : 'ASC'; ?>&view_user=<?php echo $focused_user_id; ?>&search=<?php echo urlencode($search_query); ?>" 
                                class="col-span-4 flex items-center gap-1 group cursor-pointer hover:text-stone-700 select-none">
                                    User Identity <?php echo getSortIcon('name', $sort_key, $sort_order); ?>
                                </a>

                                <a href="?sort=skin_type&direction=<?php echo ($sort_key === 'skin_type') ? $next_direction : 'ASC'; ?>&view_user=<?php echo $focused_user_id; ?>&search=<?php echo urlencode($search_query); ?>" 
                                class="col-span-3 flex items-center gap-1 group cursor-pointer hover:text-stone-700 select-none">
                                    Skin Type <?php echo getSortIcon('skin_type', $sort_key, $sort_order); ?>
                                </a>

                                <a href="?sort=date&direction=<?php echo ($sort_key === 'date') ? $next_direction : 'ASC'; ?>&view_user=<?php echo $focused_user_id; ?>&search=<?php echo urlencode($search_query); ?>" 
                                class="col-span-2 flex items-center gap-1 group cursor-pointer hover:text-stone-700 select-none">
                                    Reg. Date <?php echo getSortIcon('date', $sort_key, $sort_order); ?>
                                </a>
                                <div class="col-span-1 text-right">Action</div>
                            </div>

                            <?php if (!empty($users_list)): ?>
                                <?php foreach ($users_list as $user): 
                                    $is_selected = (intval($user['user_id']) === $focused_user_id);
                                    $row_classes = $is_selected 
                                        ? 'bg-primary-container/10 border-2 border-primary-container/30' 
                                        : 'hover:bg-surface-container-low border-2 border-transparent';
                                ?>
                                    <!-- <div onclick="window.location.href='users.php?view_user=<?php echo $user['user_id']; ?>&sort=<?php echo $sort_key; ?>&direction=<?php echo $sort_order; ?>'" class="grid grid-cols-10 items-center px-4 py-3 rounded-xl border transition-all cursor-pointer <?php echo $row_classes; ?>"> -->
                                    <div onclick="saveScrollAndRedirect('users.php?view_user=<?php echo $user['user_id']; ?>&sort=<?php echo $sort_key; ?>&direction=<?php echo $sort_order; ?>&search=<?php echo urlencode($search_query); ?>')" 
                                        class="grid grid-cols-10 items-center px-4 py-3 rounded-xl border transition-all cursor-pointer <?php echo $row_classes; ?>">
                                        <div class="col-span-4 flex items-center gap-3 pr-2">
                                            <div class="w-9 h-9 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-xs uppercase flex-shrink-0">
                                                <?php echo substr($user['name'] ?? 'U', 0, 1); ?>
                                            </div>
                                            <div class="truncate">
                                                <p class="font-bold text-sm text-on-surface truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                                                <p class="text-xs text-on-surface-variant truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                                            </div>
                                        </div>

                                        <div class="col-span-3">
                                            <span class="bg-tertiary-container text-on-tertiary-container px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block truncate max-w-full">
                                                <?php echo htmlspecialchars($user['skin_type'] ?? 'Unknown'); ?>
                                            </span>
                                        </div>

                                        <div class="col-span-2 text-xs text-on-surface-variant font-medium">
                                            <?php echo date('M d, Y', strtotime($user['user_created_at'])); ?>
                                        </div>

                                        <div class="col-span-1 flex justify-end" onclick="event.stopPropagation();">
                                            <!-- <a href="users.php?view_user=<?php echo $user['user_id']; ?>&sort=<?php echo $sort_key; ?>&direction=<?php echo $sort_order; ?>" class="p-1.5 hover:bg-primary/10 rounded-lg text-primary transition-colors">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                            </a> -->
                                            <a href="users.php?view_user=<?php echo $user['user_id']; ?>&sort=<?php echo $sort_key; ?>&direction=<?php echo $sort_order; ?>&search=<?php echo urlencode($search_query); ?>" 
                                            class="p-1.5 hover:bg-primary/10 rounded-lg text-primary transition-colors">
                                                <span class="material-symbols-outlined text-lg">visibility</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center p-12 text-sm text-stone-400 font-medium">No registered platform accounts discovered.</p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <div class="col-span-4 space-y-6">
                    <?php if ($focused_profile): ?>
                        <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-xl shadow-orange-900/5 relative border border-stone-200/30">
                            
                            <div class="h-24 bg-gradient-to-r from-primary to-primary-container"></div>
                            
                            <div class="px-6 pb-6 relative">
                                <div class="flex justify-between items-start -mt-10 mb-4">
                                    <div class="w-20 h-20 rounded-2xl bg-white shadow-lg flex items-center justify-center border-4 border-white font-black text-2xl text-primary tracking-tighter bg-orange-50 uppercase select-none">
                                        <?php echo substr(htmlspecialchars($focused_profile['name'] ?? 'U'), 0, 1); ?>
                                    </div>
                                    <!-- <button class="bg-white/80 backdrop-blur-sm p-2 rounded-full text-primary hover:bg-white shadow-sm mt-12 focus:outline-none">
                                        <span class="material-symbols-outlined">more_horiz</span>
                                    </button> -->
                                </div>

                                <h3 class="text-xl font-extrabold text-on-surface"><?php echo htmlspecialchars($focused_profile['name']); ?></h3>
                                <p class="text-sm font-medium text-on-surface-variant mb-6">User Reference: #SK-<?php echo $focused_profile['user_id']; ?>-UA</p>

                                <?php if ($focused_profile['scan_id']): ?>
                                    <div class="mb-6 p-4 bg-stone-50 rounded-2xl border border-stone-200/40 flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] text-stone-400 font-black uppercase tracking-wider">Overall Skin Health</p>
                                            <p class="text-xs text-stone-600 mt-0.5">Classification: <span class="font-bold text-primary uppercase"><?php echo htmlspecialchars($focused_profile['skin_type']); ?></span></p>
                                        </div>
                                        <span class="text-2xl font-black text-primary bg-primary/10 px-3 py-1 rounded-xl border border-primary/20">
                                            <?php echo $focused_profile['health_score']; ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="bg-tertiary-container/20 rounded-2xl p-4 mb-6 border border-tertiary-container/30">
                                    <div class="flex items-center gap-3 mb-4">
                                        <span class="material-symbols-outlined text-tertiary">query_stats</span>
                                        <h4 class="text-xs font-black uppercase tracking-widest text-tertiary">Skin Health Report</h4>
                                    </div>
                                    
                                    <?php if ($focused_profile['scan_id']): ?>
                                        <div class="space-y-3.5">
                                            <div>
                                                <div class="flex justify-between text-xs font-bold mb-1 text-stone-700">
                                                    <span>Acne</span>
                                                    <span><?php echo $focused_profile['acne_pct']; ?>%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 h-2 rounded-full overflow-hidden"><div class="bg-primary h-full rounded-full" style="width: <?php echo $focused_profile['acne_pct']; ?>%"></div></div>
                                            </div>
                                            <div>
                                                <div class="flex justify-between text-xs font-bold mb-1 text-stone-700">
                                                    <span>Dark Spots</span>
                                                    <span><?php echo $focused_profile['dark_spots_pct']; ?>%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 h-2 rounded-full overflow-hidden"><div class="bg-primary h-full rounded-full" style="width: <?php echo $focused_profile['dark_spots_pct']; ?>%"></div></div>
                                            </div>
                                            <div>
                                                <div class="flex justify-between text-xs font-bold mb-1 text-stone-700">
                                                    <span>Pigmentation</span>
                                                    <span><?php echo $focused_profile['pigmentation_pct']; ?>%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 h-2 rounded-full overflow-hidden"><div class="bg-primary h-full rounded-full" style="width: <?php echo $focused_profile['pigmentation_pct']; ?>%"></div></div>
                                            </div>
                                            <div>
                                                <div class="flex justify-between text-xs font-bold mb-1 text-stone-700">
                                                    <span>Pores</span>
                                                    <span><?php echo $focused_profile['pores_pct']; ?>%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 h-2 rounded-full overflow-hidden"><div class="bg-primary h-full rounded-full" style="width: <?php echo $focused_profile['pores_pct']; ?>%"></div></div>
                                            </div>
                                            <div>
                                                <div class="flex justify-between text-xs font-bold mb-1 text-stone-700">
                                                    <span>Redness</span>
                                                    <span><?php echo $focused_profile['redness_pct']; ?>%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 h-2 rounded-full overflow-hidden"><div class="bg-primary h-full rounded-full" style="width: <?php echo $focused_profile['redness_pct']; ?>%"></div></div>
                                            </div>
                                            <div>
                                                <div class="flex justify-between text-xs font-bold mb-1 text-stone-700">
                                                    <span>Wrinkles</span>
                                                    <span><?php echo $focused_profile['wrinkles_pct']; ?>%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 h-2 rounded-full overflow-hidden"><div class="bg-primary h-full rounded-full" style="width: <?php echo $focused_profile['wrinkles_pct']; ?>%"></div></div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="py-4 text-center text-xs text-stone-500 italic bg-white/40 rounded-xl">
                                            No scanning indexes mapped yet.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-8 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Skin Progress Timeline</h4>
                                        <a href="skin_progress.php?user_id=<?php echo $focused_profile['user_id']; ?>" class="text-[10px] font-bold text-primary hover:underline flex items-center gap-1">
                                            View Progress Page <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                        </a>
                                    </div>
                                    
                                    <div class="p-6 text-center text-xs text-stone-400 font-medium bg-stone-50 rounded-xl border border-dashed border-stone-200">
                                        Click progress link to view historic scan entries.
                                    </div>
                                </div>

                                <div class="mt-8 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-xs font-black uppercase tracking-widest text-on-surface-variant">Recent Orders</h4>
                                        <a href="order_history.php?user_id=<?php echo $focused_profile['user_id']; ?>" class="text-[10px] font-bold text-primary hover:underline flex items-center gap-1">
                                            View All Orders <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                        </a>
                                    </div>

                                    <div id="async-order-card-container" data-inspect-user-id="<?php echo $focused_profile['user_id']; ?>">
                                        <div class="p-6 text-center text-xs text-stone-400 font-medium bg-stone-50 rounded-xl animate-pulse">
                                            Loading purchased items history...
                                        </div>
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
                            Select an account row to view detailed data.
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>

    <!-- Structural Layout System Footer -->
    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetchActiveUserLatestOrderSummary();
            const scrollPos = sessionStorage.getItem('scrollPos');
            if (scrollPos) {
                window.scrollTo(0, parseInt(scrollPos));
                // Clear it so it doesn't jump on manual refreshes
                sessionStorage.removeItem('scrollPos');
            }
        });

        function saveScrollAndRedirect(url) {
            sessionStorage.setItem('scrollPos', window.scrollY);
            window.location.href = url;
        }

        function fetchActiveUserLatestOrderSummary() {
            const container = document.getElementById('async-order-card-container');
            if (!container) return;

            const userId = container.getAttribute('data-inspect-user-id');
            if (!userId || userId == 0) return;

            fetch(`backend/get_latest_order.php?user_id=${userId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'success' || !data.has_order) {
                        container.innerHTML = `
                            <div class="p-6 text-center text-xs text-stone-400 font-medium bg-stone-50 rounded-xl border border-dashed border-stone-200">
                                No transactions made by this account.
                            </div>`;
                        return;
                    }

                    const order = data.order;
                    
                    // 1. Establish Status Badge Styling Profiles
                    let badgeClass = 'bg-stone-100 text-stone-700';
                    const normalizedStatus = order.status.toLowerCase();
                    if (['delivered', 'refunded'].includes(normalizedStatus)) {
                        badgeClass = 'bg-green-500/10 text-green-600';
                    } else if (['completed'].includes(normalizedStatus)) {
                        badgeClass = 'bg-primary/10 text-primary';
                    } else if (['cancelled', 'refund rejected'].includes(normalizedStatus)) {
                        badgeClass = 'bg-red-500/10 text-red-600';
                    }

                    // 2. Map structural lists item elements
                    let itemsMarkup = '';
                    data.items.forEach(item => {
                        itemsMarkup += `
                            <div class="flex items-center gap-3 pt-2.5 mt-2.5 border-t border-stone-100">
                                <img src="../images/${item.image}" alt="" class="w-8 h-8 rounded-lg object-cover bg-stone-50 error-fallback-thumb" onerror="this.src='../assets/placeholder-box.png'">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-stone-800 truncate">${item.product_name}</p>
                                    <p class="text-[10px] text-stone-400 font-medium">RM ${item.unit_price.toFixed(2)} &middot; Qty: ${item.quantity}</p>
                                </div>
                            </div>`;
                    });

                    // 3. Assemble components completely together
                    container.innerHTML = `
                        <div onclick="window.location.href='order.php?order_id=${order.id}'" 
                            class="p-4 bg-white border border-stone-200/60 rounded-2xl shadow-xs hover:shadow-sm transition-all cursor-pointer group">
                            
                            <div class="flex items-start justify-between mb-3">
                                <div class="space-y-0.5">
                                    <p class="text-xs font-black text-stone-900 tracking-tight group-hover:text-primary transition-colors">
                                        #${order.order_id}
                                    </p>
                                    <p class="text-[10px] text-stone-400 font-medium">${order.order_date}</p>
                                </div>
                                <div class="text-right space-y-1">
                                    <p class="text-sm font-black text-primary">RM ${order.final_total.toFixed(2)}</p>
                                    <span class="${badgeClass} text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full inline-block">
                                        ${order.status}
                                    </span>
                                </div>
                            </div>

                            <div class="bg-stone-50/50 rounded-xl px-3 pb-3 pt-0.5 mt-2">
                                ${itemsMarkup}
                            </div>
                        </div>`;
                })
                .catch(err => {
                    console.error("Latest Order Fetch Failure:", err);
                    container.innerHTML = `<p class="text-[11px] text-red-400 italic text-center">Failed to load order history items.</p>`;
                });
        }
    </script>

</body>
</html>