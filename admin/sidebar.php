<?php
// admin/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security safeguard: bounce if accessed directly outside of an explicit view wrapper
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Automatically match current file to add high-contrast styling states to active navigation chips
$current_page = basename($_SERVER['PHP_SELF']);
?>
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        body { font-family: 'Manrope', sans-serif; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>

<!-- SideNavBar Structure -->
<aside class="flex flex-col fixed left-0 top-0 h-screen border-r border-stone-200/60 bg-stone-50 dark:bg-stone-900 font-['Manrope'] font-medium text-sm w-72 z-50">
    <div class="px-8 py-10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary-container flex items-center justify-center">
                <span class="material-symbols-outlined text-on-primary-container" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
            </div>
            <div>
                <h1 class="text-lg font-black text-orange-900 dark:text-orange-50 leading-tight">SkinMate</h1>
                <p class="text-[10px] uppercase tracking-widest text-stone-500">Management Portal</p>
            </div>
        </div>
    </div>
    
    <nav class="flex-1 space-y-1">
        <!-- Dashboard Tab -->
        <a class="mx-2 px-4 py-3 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'index.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-600 dark:text-stone-400 hover:bg-orange-50 dark:hover:bg-stone-800'; ?>" href="index.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'index.php') ? '1' : '0'; ?>;">dashboard</span>
            <span>Dashboard</span>
        </a>

        <!-- Inventory Tab -->
        <a class="mx-2 px-4 py-3 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'products.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-600 dark:text-stone-400 hover:bg-orange-50 dark:hover:bg-stone-800'; ?>" href="products.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'products.php') ? '1' : '0'; ?>;">inventory</span>
            <span>Inventory</span>
        </a>

        <!-- Orders Tab -->
        <a class="mx-2 px-4 py-3 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'orders.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-600 dark:text-stone-400 hover:bg-orange-50 dark:hover:bg-stone-800'; ?>" href="orders.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'orders.php') ? '1' : '0'; ?>;">shopping_cart</span>
            <span>Orders</span>
        </a>

        <!-- Support Tab -->
        <a class="mx-2 px-4 py-3 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'support.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-600 dark:text-stone-400 hover:bg-orange-50 dark:hover:bg-stone-800'; ?>" href="support.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'support.php') ? '1' : '0'; ?>;">support_agent</span>
            <span>Support</span>
        </a>
        
        <!-- Users Tab -->
        <!-- <a class="mx-2 px-4 py-3 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'users.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-600 dark:text-stone-400 hover:bg-orange-50 dark:hover:bg-stone-800'; ?>" href="users.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'users.php') ? '1' : '0'; ?>;">group</span>
            <span>Users</span>
        </a> -->
        <div class="space-y-1">
            <!-- Main Users Tab -->
            <a class="mx-2 px-4 py-3 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'users.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-600 dark:text-stone-400 hover:bg-orange-50 dark:hover:bg-stone-800'; ?>" href="users.php">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'users.php') ? '1' : '0'; ?>;">group</span>
                <span>Users</span>
            </a>

            <?php if ($current_page === 'users.php' || $current_page === 'skin_progress.php' || $current_page === 'order_history.php'): ?>
                <?php 
                    $sub_link_param = isset($target_user_id) && $target_user_id > 0 ? '?user_id=' . $target_user_id : '';
                ?>
                <a class="mx-2 ml-8 px-4 py-2.5 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'skin_progress.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-500 dark:text-stone-400 hover:bg-orange-50/60 dark:hover:bg-stone-800/60'; ?>" 
                   href="<?php echo ($sub_link_param) ? 'skin_progress.php' . $sub_link_param : 'javascript:void(0);'; ?>" 
                   <?php if(!$sub_link_param) echo 'style="opacity: 0.35; cursor: not-allowed;" title="Select a user profile first"'; ?>>
                    <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'skin_progress.php') ? '1' : '0'; ?>;">history</span>
                    <span class="text-xs">Skin Progress</span>
                </a>
                <a class="mx-2 ml-8 px-4 py-2.5 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'order_history.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-500 dark:text-stone-400 hover:bg-orange-50/60 dark:hover:bg-stone-800/60'; ?>" 
                   href="<?php echo ($sub_link_param) ? 'order_history.php' . $sub_link_param : 'javascript:void(0);'; ?>" 
                   <?php if(!$sub_link_param) echo 'style="opacity: 0.35; cursor: not-allowed;" title="Select a user profile first"'; ?>>
                    <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'order_history.php') ? '1' : '0'; ?>;">receipt_long</span>
                    <span class="text-xs">Order History</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Refunds Tab -->
        <a class="mx-2 px-4 py-3 flex items-center gap-3 rounded-xl transition-all <?php echo ($current_page === 'refunds.php') ? 'bg-orange-100/50 dark:bg-orange-900/20 text-orange-900 dark:text-orange-100 translate-x-1 font-bold' : 'text-stone-600 dark:text-stone-400 hover:bg-orange-50 dark:hover:bg-stone-800'; ?>" href="refunds.php">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' <?php echo ($current_page === 'refunds.php') ? '1' : '0'; ?>;">assignment_return</span>
            <span>Refunds</span>
        </a>
    </nav>

    <div class="p-4 border-t border-stone-200/60 space-y-1">
        <a class="text-stone-400 dark:text-stone-600 mx-2 px-4 py-2 flex items-center gap-3 text-xs uppercase tracking-widest hover:text-orange-700 transition-colors" href="index.php?action=logout">
            <span class="material-symbols-outlined text-lg">logout</span>
            <span>Logout</span>
        </a>
    </div>
</aside>

<!-- TopNavBar Structure -->
<header class="fixed top-0 right-0 left-72 z-40 bg-white/80 dark:bg-stone-950/80 backdrop-blur-md shadow-sm shadow-orange-900/5 px-6 py-3 flex justify-between items-center font-['Plus_Jakarta_Sans'] text-sm tracking-wide">
    <div class="flex items-center gap-4 bg-stone-100/50 dark:bg-stone-900/50 rounded-full px-4 py-2 w-96">
        <span class="material-symbols-outlined text-stone-400">search</span>
        <input class="bg-transparent border-none focus:ring-0 text-sm w-full" placeholder="Search parameters or logs..." type="text"/>
    </div>
    
    <div class="flex items-center gap-6">
        <div class="flex items-center gap-3 border-l border-stone-100/20 pl-6">
            <div class="text-right">
                <p class="font-bold text-orange-900 dark:text-orange-200 leading-none"><?php echo htmlspecialchars($_SESSION['admin_user']); ?></p>
                <p class="text-[10px] text-stone-400 uppercase tracking-tighter">Administrator</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center font-bold text-orange-900 border-2 border-primary-container">
                <?php echo strtoupper(substr($_SESSION['admin_user'], 0, 2)); ?>
            </div>
        </div>
    </div>
</header>