<?php
// admin/orders.php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'actionable'; 
$sub_status = isset($_GET['sub_status']) ? trim($_GET['sub_status']) : 'All';
$orders_list = [];

$completed_statuses = ['Delivered', 'Completed', 'Cancelled', 'Refunded', 'Refund Rejected'];

// 1. Core base query structure
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];
$types = "";

// 2. Safely apply Tab Restrictions
if ($active_tab === 'actionable') {
    $sql .= " AND status IN ('Processing')";
} elseif ($active_tab === 'active') {
    $sql .= " AND status IN ('In Transit')";
} elseif ($active_tab === 'refund') {
    $sql .= " AND status IN ('Refund Processing')";
} elseif ($active_tab === 'completed') {
    if ($sub_status !== 'All' && in_array($sub_status, $completed_statuses)) {
        $escaped_status = $conn->real_escape_string($sub_status);
        $sql .= " AND status = '$escaped_status'";
    } else {
        $sql .= " AND status IN ('Delivered', 'Completed', 'Cancelled', 'Refunded', 'Refund Rejected')";
    }
}

if ($date_filter !== '') {
    $sql .= " AND DATE(order_date) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

// 3. Safely apply Search Filters
if ($search_query !== '') {
    $sql .= " AND (order_id LIKE ? OR CAST(user_id AS CHAR) LIKE ?)";
    $like_param = '%' . $search_query . '%';
    $params[] = $like_param;
    $params[] = $like_param;
    $types .= "ss";
}

// 4. Force global First-Come, First-Serve sequencing
$sql .= " ORDER BY order_date ASC, id ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders_list[] = $row;
}
$stmt->close();

// Fetch Total Orders across the system
$total_orders_count = 0;
$count_query = "SELECT COUNT(*) as total FROM orders";
if ($count_res = $conn->query($count_query)) {
    $count_row = $count_res->fetch_assoc();
    $total_orders_count = $count_row['total'];
}

// Fetch Pending Shipments count (Matching 'Processing' status)
$pending_shipments_count = 0;
$pending_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'Processing'";
if ($pending_res = $conn->query($pending_query)) {
    $pending_row = $pending_res->fetch_assoc();
    $pending_shipments_count = $pending_row['total'];
}

// Select active focal order item for details panel sidebar matching target context
$selected_order = null;
if (!empty($orders_list)) {
    $selected_order_id = isset($_GET['view']) ? trim($_GET['view']) : $orders_list[0]['order_id'];
    foreach ($orders_list as $o) {
        if ($o['order_id'] === $selected_order_id) {
            $selected_order = $o;
            break;
        }
    }
    // Fallback if parameter invalid
    if (!$selected_order) { $selected_order = $orders_list[0]; }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SkinMate Admin - Orders Management</title>
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
    <script>
        function submitOrderUpdate(orderTableId) {
            const statusSelect = document.getElementById(`status-select-${orderTableId}`);
            const noteTextarea = document.getElementById(`note-textarea-${orderTableId}`);
            
            if (!statusSelect) return;

            const formData = new FormData();
            formData.append('action', 'update_order');
            formData.append('id', orderTableId);
            formData.append('status', statusSelect.value);
            formData.append('note', noteTextarea ? noteTextarea.value : '');

            // Targets the unified system worker endpoint
            fetch('backend/update_order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Order state and user notifications synchronized successfully!');
                    window.location.reload();
                } else {
                    alert('Error updating configuration: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected network processing exception occurred.');
            });
        }
    </script>
</head>
<body class="bg-surface text-on-background min-h-screen">

    <!-- Structural App Sidebar Component -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Workspace Canvas Area -->
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen bg-surface">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Page Header Controls -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">Order Records</h2>
                    <p class="text-on-surface-variant mt-1">Manage Orders</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
                <div class="bg-white rounded-3xl p-6 border border-stone-100 flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">local_mall</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-stone-400">Total System Orders</p>
                        <h3 class="text-2xl font-extrabold text-stone-900 mt-0.5"><?php echo number_format($total_orders_count); ?></h3>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-stone-100 flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl bg-tertiary-container text-on-tertiary-container flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-stone-400">Pending Shipment</p>
                        <h3 class="text-2xl font-extrabold text-stone-900 mt-0.5"><?php echo number_format($pending_shipments_count); ?></h3>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <form method="GET" action="orders.php" class="flex gap-2 w-full md:w-auto">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                    <div class="relative min-w-[320px] flex-1 md:flex-initial">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-xl">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                                placeholder="Search Invoice reference or User ID..." 
                                class="w-full pl-11 pr-4 py-2.5 bg-surface-container-lowest border border-surface-container-high rounded-xl text-xs font-semibold placeholder:text-on-surface-variant/40 focus:outline-none focus:border-primary transition-all">
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-sm hover:bg-primary-dim transition-all">Search</button>
                    
                    <?php if (!empty($search_query)): ?>
                        <a href="orders.php?tab=<?php echo urlencode($active_tab); ?>" class="px-4 py-2.5 bg-surface-container-low border border-surface-container-high text-on-surface-variant text-xs font-bold rounded-xl flex items-center justify-center hover:bg-surface-container-high transition-colors">Clear</a>
                    <?php endif; ?>
                </form>

                <div class="relative min-w-[150px]">
                    <input type="date" 
                        id="date-filter-input"
                        value="<?php echo htmlspecialchars($date_filter); ?>" 
                        onchange="applyDateFilter(this.value)"
                        class="w-full px-3 py-2.5 bg-surface-container-lowest border border-surface-container-high rounded-xl text-xs font-semibold text-stone-600 focus:outline-none focus:border-primary transition-all">
                </div>
            </div>

            <!-- Dashboard Main Grid Container -->
            <div class="space-y-6">
                <?php
                $params_array = [];
                if (!empty($search_query)) $params_array['search'] = $search_query;
                if (!empty($date_filter)) $params_array['date_filter'] = $date_filter;
                $query_string = !empty($params_array) ? '&' . http_build_query($params_array) : '';
                ?>
                <!-- Navigation Filter Tabs Strip -->
                <div class="flex border-b border-surface-container-high gap-2">
                    <a href="orders.php?tab=actionable<?php echo $query_string; ?>" 
                    class="px-5 py-3 text-xs font-black uppercase tracking-wider border-b-2 transition-all <?php echo $active_tab === 'actionable' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                        Pending
                    </a>
                    <a href="orders.php?tab=active<?php echo $query_string; ?>" 
                    class="px-5 py-3 text-xs font-black uppercase tracking-wider border-b-2 transition-all <?php echo $active_tab === 'active' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                        Active
                    </a>
                    <a href="orders.php?tab=refund<?php echo $query_string; ?>" 
                    class="px-5 py-3 text-xs font-black uppercase tracking-wider border-b-2 transition-all <?php echo $active_tab === 'refund' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                        Refunds
                    </a>
                    <a href="orders.php?tab=completed<?php echo $query_string; ?>" 
                    class="px-5 py-3 text-xs font-black uppercase tracking-wider border-b-2 transition-all <?php echo $active_tab === 'completed' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                        Completed
                    </a>
                    <a href="orders.php?tab=all<?php echo $query_string; ?>" 
                    class="px-5 py-3 text-xs font-black uppercase tracking-wider border-b-2 transition-all <?php echo $active_tab === 'all' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                        All Orders
                    </a>
                </div>

                <?php if ($active_tab === 'completed'): ?>
                    <div class="flex items-center gap-1.5 mt-4 mb-2 overflow-x-auto scrollbar-none py-1">
                        
                        <?php $all_url = "skinmate_api/admin/orders.php?tab=completed&sub_status=All" . (!empty($search_query) ? '&search='.urlencode($search_query) : ''); ?>
                        <button type="button" 
                        onclick="viewOrder('<?php echo $all_url; ?>')"
                        class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-full transition-all text-left whitespace-nowrap <?php echo $sub_status === 'All' ? 'bg-primary text-white shadow-sm' : 'bg-stone-100 text-stone-500 hover:text-primary hover:bg-stone-200/70'; ?>">
                            All
                        </button>

                        <?php 
                        foreach ($completed_statuses as $st): 
                            // Define $is_sub_active so the button style updates correctly
                            $is_sub_active = ($sub_status === $st);
                            
                            // Use the $query_string we built earlier (includes search and date_filter)
                            $sub_url = "skinmate_api/admin/orders.php?tab=completed&sub_status=" . urlencode($st) . $query_string;
                        ?>
                            <button type="button" 
                                    onclick="viewOrder('<?php echo $sub_url; ?>')" 
                                    class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-full transition-all text-left whitespace-nowrap <?php echo $is_sub_active ? 'bg-primary text-white shadow-sm' : 'bg-stone-100 text-stone-500 hover:text-primary hover:bg-stone-200/70'; ?>">
                                <?php echo $st; ?>
                            </button>
                        <?php endforeach; ?>
                        
                    </div>
                <?php else: ?>
                    <div class="mb-6"></div>
                <?php endif; ?>

                <div class="grid grid-cols-12 gap-8 items-start">
                    <!-- Master Invoices Log Table Box -->
                    <div class="col-span-12 lg:col-span-8 space-y-6">
                        <div class="bg-surface-container-low rounded-3xl p-2">
                            <div class="overflow-hidden">
                                <table class="w-full text-left border-separate border-spacing-y-2">
                                    <thead class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400">
                                        <tr>
                                            <th class="px-4 py-4">Order ID</th>
                                            <th class="px-4 py-4">Customer Name</th>
                                            <th class="px-4 py-4">Order Date</th>
                                            <th class="px-4 py-4">Order Status</th>
                                            <th class="px-4 py-4 text-right">Inspect</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($orders_list)): ?>
                                            <?php foreach ($orders_list as $row_order): 
                                                $is_active_selection = ($selected_order && $selected_order['order_id'] === $row_order['order_id']);
                                                $status_style = 'bg-stone-100 text-stone-700';
                                                if (in_array($row_order['status'], ['Delivered', 'Refunded'])) { $status_style = 'bg-green-500/10 text-green-600'; }
                                                elseif ($row_order['status'] === 'Completed') { $status_style = 'bg-primary/10 text-primary'; }
                                                elseif (in_array($row_order['status'], ['Cancelled', 'Refund Rejected'])) { $status_style = 'bg-red-500/10 text-red-600'; }
                                            ?>
                                                <!-- <tr class="group hover:translate-y-[-2px] transition-all cursor-pointer" onclick="window.location.href='orders.php?tab=<?php echo $active_tab; ?>&search=<?php echo urlencode($search_query); ?>&view=<?php echo urlencode($row_order['order_id']); ?>'"> -->
                                                <tr class="group hover:translate-y-[-2px] transition-all cursor-pointer" onclick="viewOrder('skinmate_api/admin/orders.php?tab=<?php echo $active_tab; ?>&sub_status=<?php echo urlencode($sub_status); ?>&search=<?php echo urlencode($search_query); ?>&date_filter=<?php echo urlencode($date_filter); ?>&view=<?php echo urlencode($row_order['order_id']); ?>')">
                                                    <td class="w-[30%] px-4 py-4 bg-surface-container-lowest first:rounded-l-2xl last:rounded-r-2xl transition-all <?php echo $is_active_selection ? 'border-y-2 border-l-2 border-primary/40' : ''; ?>">
                                                        <span class="text-xs font-bold text-stone-900">#<?php echo htmlspecialchars($row_order['order_id']); ?></span>
                                                    </td>
                                                    
                                                    <td class="w-[30%] px-4 py-4 bg-surface-container-lowest transition-all <?php echo $is_active_selection ? 'border-y-2 border-primary/40' : ''; ?>">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container">
                                                                <?php echo !empty($row_order['name']) ? strtoupper(substr($row_order['name'], 0, 1)) : 'U'; ?>
                                                            </div>
                                                            <span class="text-xs font-medium text-stone-700"><?php echo htmlspecialchars($row_order['name'] ?: 'Guest Account'); ?></span>
                                                        </div>
                                                    </td>
                                                    
                                                    <td class="w-[20%] px-4 py-4 bg-surface-container-lowest text-xs text-stone-500 transition-all <?php echo $is_active_selection ? 'border-y-2 border-primary/40' : ''; ?>"><?php echo date('M d, Y g:i A', strtotime($row_order['order_date'])); ?></td>
                                                    
                                                    <td class="w-[20%] px-4 py-4 bg-surface-container-lowest transition-all <?php echo $is_active_selection ? 'border-y-2 border-primary/40' : ''; ?>">
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full whitespace-nowrap <?php echo $status_style; ?>">
                                                            <?php echo htmlspecialchars($row_order['status']); ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <td class="px-4 py-4 bg-surface-container-lowest last:rounded-r-2xl text-right transition-all <?php echo $is_active_selection ? 'border-y-2 border-r-2 border-primary/40' : ''; ?>">
                                                        <span class="material-symbols-outlined text-stone-400 group-hover:text-primary transition-colors">arrow_forward_ios</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="p-8 text-center bg-surface-container-lowest rounded-2xl text-xs font-semibold text-stone-400">
                                                    No matching results found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Comprehensive Focus Inspection Panel -->
                    <div class="col-span-12 lg:col-span-4 self-start">
                        <?php if ($selected_order): 
                            $order_table_id = $selected_order['id'];
                            $order_id_raw = $selected_order['order_id'];
                        ?>
                            <div class="bg-surface-container-lowest rounded-[2.5rem] p-8 shadow-xl shadow-orange-900/5 sticky top-24 border border-stone-100/30 space-y-6 h-auto">
                                <div>
                                    <h3 class="text-2xl font-extrabold headline-font text-orange-900">Order Details</h3>
                                    <p class="text-sm font-bold text-stone-400 mt-1">#<?php echo htmlspecialchars($order_id_raw); ?></p>
                                </div>

                                <!-- Purchased Item Breakdown -->
                                <div class="space-y-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400">Items Purchased</p>
                                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                                        <?php
                                        $items_query = "SELECT oi.unit_price, oi.quantity, p.name AS prod_name, p.brand AS prod_brand, p.image AS prod_image, p.price AS current_retail_price 
                                                        FROM order_items oi 
                                                        LEFT JOIN products p ON oi.product_id = p.id 
                                                        WHERE oi.order_id = ?";
                                        $items_stmt = $conn->prepare($items_query);
                                        $items_stmt->bind_param("s", $order_id_raw);
                                        $items_stmt->execute();
                                        $items_result = $items_stmt->get_result();
                                        
                                        if ($items_result && $items_result->num_rows > 0):
                                            while ($item = $items_result->fetch_assoc()):
                                                $prod_brand = !empty($item['prod_brand']) ? $item['prod_brand'] : 'System Archive';
                                                $prod_name = !empty($item['prod_name']) ? $item['prod_name'] : 'Deleted Product Listing';
                                                $product_thumbnail = !empty($item['prod_image']) ? htmlspecialchars($item['prod_image']) : 'default-product.png';
                                            ?>
                                                <div class="flex items-center gap-3 p-2 bg-surface-container-low/50 rounded-xl border border-stone-100">
                                                    <div class="w-12 h-12 rounded-xl bg-surface-container overflow-hidden shrink-0 border border-stone-200/60 relative flex items-center justify-center">
                                                        <img src="../images/<?php echo $product_thumbnail; ?>" 
                                                            alt="" 
                                                            class="absolute inset-0 w-full h-full object-cover object-center" 
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <span class="material-symbols-outlined text-stone-300 text-sm hidden">image</span>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-xs font-bold text-stone-900 truncate"><span class="uppercase mr-1"><?php echo htmlspecialchars($prod_brand); ?></span><?php echo htmlspecialchars($prod_name); ?></p>
                                                        <p class="text-[11px] text-stone-500">Qty: <?php echo $item['quantity']; ?></p>
                                                    </div>
                                                    <div class="flex flex-col items-end justify-center shrink-0">
                                                        <p class="text-xs font-bold text-orange-900">RM <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></p>
                                                        <p class="text-[10px] text-stone-400 font-medium mt-0.5">RM <?php echo number_format($item['unit_price'], 2); ?> ea</p>
                                                    </div>
                                                </div>
                                            <?php 
                                            endwhile;
                                        else: 
                                        ?>
                                            <div class="p-3 bg-error/5 border border-error/10 text-error rounded-xl text-[11px] font-semibold flex items-center gap-2">
                                                <span class="material-symbols-outlined text-sm">warning</span>
                                                <span>No product references found.</span>
                                            </div>
                                        <?php 
                                        endif; 
                                        $items_stmt->close(); 
                                        ?>
                                    </div>
                                </div>

                                <!-- Financial Statement Summary -->
                                <div class="pt-4 border-t border-dashed space-y-1.5 text-xs text-stone-500">
                                    <div class="flex justify-between"><span>Subtotal</span><span class="font-bold text-stone-900">RM <?php echo number_format($selected_order['subtotal'], 2); ?></span></div>
                                    <?php if (floatval($selected_order['discount_amount']) > 0): ?>
                                        <div class="flex justify-between text-error"><span>Discount Applied</span><span class="font-bold">-RM <?php echo number_format($selected_order['discount_amount'], 2); ?></span></div>
                                    <?php endif; ?>
                                    <div class="flex justify-between pb-2"><span>Shipping</span><span class="font-bold text-stone-900"><?php echo is_numeric($selected_order['shipping_fee']) ? 'RM ' . number_format($selected_order['shipping_fee'], 2) : htmlspecialchars($selected_order['shipping_fee']); ?></span></div>
                                    <div class="flex justify-between border-t pt-4 mt-2 text-sm text-orange-900 font-extrabold"><span>Final Total</span><span>RM <?php echo number_format($selected_order['final_total'], 2); ?></span></div>
                                </div>

                                <!-- Shipping Target Card Wrapper -->
                                <div class="pt-4 border-t">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400 mb-2">Shipping Address</p>
                                    <div class="bg-surface-container-low/60 rounded-xl p-3 text-xs text-stone-600 space-y-1">
                                        <div class="text-stone-900 flex items-center">
                                            <span class="font-bold mr-1"><?php echo htmlspecialchars($selected_order['name'] ?: 'Guest'); ?></span>
                                            <span class="text-primary font-mono text-[10px]">
                                                <?php 
                                                if (!empty($selected_order['phone'])) {
                                                    $raw_phone = preg_replace('/[^0-9]/', '', $selected_order['phone']);
                                                    if (substr($raw_phone, 0, 2) === '60') { $raw_phone = substr($raw_phone, 2); }
                                                    elseif (substr($raw_phone, 0, 1) === '0') { $raw_phone = substr($raw_phone, 1); }
                                                    $prefix = substr($raw_phone, 0, 2);
                                                    $rest = substr($raw_phone, 2);
                                                    $mid_chunk = (strlen($rest) === 8) ? substr($rest, 0, 4) : substr($rest, 0, 3);
                                                    $end_chunk = substr($rest, strlen($mid_chunk));
                                                    echo "(+60) " . $prefix . "-" . $mid_chunk . " " . $end_chunk;
                                                } else {
                                                    echo "No Phone Provided";
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <p class="whitespace-pre-line text-stone-500 leading-relaxed mt-1"><?php 
                                            echo htmlspecialchars($selected_order['address_line_1']) . ",\n";
                                            if(!empty($selected_order['address_line_2'])) { echo htmlspecialchars($selected_order['address_line_2']) . ",\n"; }
                                            echo htmlspecialchars($selected_order['postcode']) . " " . htmlspecialchars($selected_order['city']) . ",\n";
                                            echo htmlspecialchars($selected_order['state']) . ", " . htmlspecialchars($selected_order['region']) . ".";
                                        ?></p>
                                    </div>
                                </div>

                                <!-- State & Memo Modifiers Engine -->
                                <div class="pt-4 border-t space-y-3">
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400 block mb-2">Administrative Remark</label>
                                        <textarea id="note-textarea-<?php echo $order_table_id; ?>" rows="2" placeholder="Write notes..." class="w-full px-3 py-2 bg-surface-container-low border-none rounded-xl text-xs text-stone-700 placeholder:text-stone-400 focus:ring-2 focus:ring-primary/20 resize-none transition-all"><?php echo htmlspecialchars($selected_order['note'] ?? ''); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400 block mb-2">Modify Logistics Status</label>
                                        <div class="relative">
                                            <select id="status-select-<?php echo $order_table_id; ?>" class="w-full appearance-none bg-none bg-surface-container-low border-none rounded-xl px-4 py-3 text-xs font-bold text-stone-700 focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                                                <?php 
                                                $statuses = ['Processing', 'In Transit', 'Delivered', 'Completed', 'Cancelled', 'Refund Processing', 'Refunded', 'Refund Rejected'];
                                                foreach ($statuses as $st): 
                                                ?>
                                                    <option value="<?php echo $st; ?>" <?php echo ($selected_order['status'] === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-stone-400">expand_more</span>
                                        </div>
                                    </div>
                                    <button type="button" onclick="submitOrderUpdate('<?php echo $order_table_id; ?>')" class="w-full py-3 bg-orange-900 text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-orange-800 transition-all flex items-center justify-center gap-1.5 shadow-lg shadow-orange-900/15">
                                        <span class="material-symbols-outlined text-sm">save</span> Save Changes
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-surface-container-lowest rounded-[2.5rem] p-8 text-center text-stone-400 text-xs font-medium border border-dashed border-stone-200">
                                Select an order to view details.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Structural Layout System Footer -->
    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const savedScrollPos = sessionStorage.getItem("orders_scroll_pos");
            if (savedScrollPos) {
                window.scrollTo(0, parseInt(savedScrollPos));
                sessionStorage.removeItem("orders_scroll_pos"); 
            }
        });

        // function viewOrder(url) {
        //     sessionStorage.setItem("orders_scroll_pos", window.scrollY);
        //     window.location.href = url;
        // }

        function viewOrder(url) {
            sessionStorage.setItem("orders_scroll_pos", window.scrollY);
            
            // Ensure existing date/search filters are carried over to the 'view' URL
            const targetUrl = new URL(url, window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            
            if (currentParams.has('search') && !targetUrl.searchParams.has('search')) 
                targetUrl.searchParams.set('search', currentParams.get('search'));
            if (currentParams.has('date_filter') && !targetUrl.searchParams.has('date_filter')) 
                targetUrl.searchParams.set('date_filter', currentParams.get('date_filter'));
                
            window.location.href = targetUrl.toString();
        }

        function applyDateFilter(dateValue) {
            const url = new URL(window.location.href);
            
            // Set or clear the date_filter
            if (dateValue) {
                url.searchParams.set('date_filter', dateValue);
            } else {
                url.searchParams.delete('date_filter');
            }
            
            // Redirect to the updated URL (maintaining tab, search, and sub_status)
            window.location.href = url.toString();
        }
    </script>

</body>
</html>