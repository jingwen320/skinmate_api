<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'pending'; 
$sub_status = isset($_GET['sub_status']) ? trim($_GET['sub_status']) : 'All';
$refunds_list = [];

// 1. Map tabs to refund statuses
$status_map = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];

// 2. Initialize SQL - Using the dynamic approach
if ($active_tab === 'all') {
    $sql = "SELECT r.* FROM refund_requests r WHERE 1=1 GROUP BY r.order_id, r.created_at";
    $params = [];
    $types = "";
} else {
    $target_status = $status_map[$active_tab] ?? 'Pending';
    $sql = "SELECT r.* FROM refund_requests r WHERE r.status = ? GROUP BY r.order_id, r.created_at";
    $params = [$target_status];
    $types = "s";
}

// 3. Search logic
if ($search_query !== '') {
    $clean_id = preg_replace('/^ref-/i', '', $search_query);
    $sql .= " AND (r.order_id LIKE ? OR r.reason LIKE ? OR r.user_id LIKE ? OR r.id LIKE ?)";
    $like_param = '%' . $search_query . '%';
    $clean_like_param = '%' . $clean_id . '%';
    $params[] = $like_param;
    $params[] = $like_param;
    $params[] = $like_param;
    $params[] = $clean_like_param;
    $types .= "ssss";
}

// 4. Sub-status logic (only for 'all' tab)
if ($active_tab === 'all' && $sub_status !== 'All') {
    $sql .= " AND r.reason = ?";
    $params[] = $sub_status;
    $types .= "s";
}

$sql .= " ORDER BY r.created_at ASC";

// 5. FETCH DATA (This must happen BEFORE calculating $selected_refund)
$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $refunds_list[] = $row;
}
$stmt->close();

// 6. Select active refund
$selected_refund = null;
if (!empty($refunds_list)) {
    $selected_refund_id = isset($_GET['view']) ? (int)$_GET['view'] : $refunds_list[0]['id'];
    foreach ($refunds_list as $r) {
        if ((int)$r['id'] === $selected_refund_id) {
            $selected_refund = $r;
            break;
        }
    }
    if (!$selected_refund) { $selected_refund = $refunds_list[0]; }
}

// 7. Counts
$counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'Cancelled' => 0];
$count_query = "SELECT status, COUNT(*) as cnt 
    FROM (
        SELECT status 
        FROM refund_requests 
        GROUP BY order_id, created_at, status
    ) as unique_requests 
    GROUP BY status";
if ($res = $conn->query($count_query)) {
    while($row = $res->fetch_assoc()) { $counts[$row['status']] = $row['cnt']; }
}

$total_requests_count = array_sum($counts);
$pending_requests_count = $counts['Pending'];
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SkinMate Admin - Refunds Management</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">Refund Requests</h2>
                    <p class="text-on-surface-variant mt-1">Holistic transactional control dashboard panel and full order items dispatch tracking view.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">
                <div class="bg-white rounded-3xl p-6 border border-stone-100 flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">receipt_long</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-stone-400">Total Refund Requests</p>
                        <h3 class="text-2xl font-extrabold text-stone-900 mt-0.5"><?php echo number_format($total_requests_count); ?></h3>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-stone-100 flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-2xl bg-tertiary-container text-on-tertiary-container flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.15em] text-stone-400">Pending Requests</p>
                        <h3 class="text-2xl font-extrabold text-stone-900 mt-0.5"><?php echo number_format($pending_requests_count); ?></h3>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <form method="GET" action="refunds.php" class="flex gap-2 w-full md:w-auto">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                    <div class="relative min-w-[320px] flex-1 md:flex-initial">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-xl">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                            placeholder="Search Request ID or User ID..." 
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-container-lowest border border-surface-container-high rounded-xl text-xs font-semibold placeholder:text-on-surface-variant/40 focus:outline-none focus:border-primary transition-all">
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-sm hover:bg-primary-dim transition-all">Search</button>
                    
                    <?php if (!empty($search_query)): ?>
                        <a href="refunds.php?tab=<?php echo urlencode($active_tab); ?>" class="px-4 py-2.5 bg-surface-container-low border border-surface-container-high text-on-surface-variant text-xs font-bold rounded-xl flex items-center justify-center hover:bg-surface-container-high transition-colors">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Dashboard Main Grid Container -->
            <div class="space-y-6">
                <!-- Navigation Filter Tabs Strip -->
                <div class="flex border-b border-surface-container-high gap-2">
                    <?php 
                    $tabs = [
                        'pending'   => 'Pending',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                        'cancelled' => 'Cancelled',
                        'all'       => 'All Requests'
                    ];
                    
                    foreach ($tabs as $key => $label): 
                        // Logic to calculate total if key is 'all'
                        $display_count = ($key === 'all') ? array_sum($counts) : ($counts[$label] ?? 0);
                    ?>
                        <a href="refunds.php?tab=<?php echo $key; ?>&search=<?php echo urlencode($search_query); ?>" 
                        class="px-5 py-3 text-xs font-black uppercase tracking-wider border-b-2 transition-all 
                        <?php echo $active_tab === $key ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                            <?php echo $label; ?> (<?php echo $display_count; ?>)
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($active_tab === 'all'): ?>
                    <div class="flex items-center gap-1.5 mt-4 mb-2 overflow-x-auto scrollbar-none py-1">
                        
                        <?php 
                        // Define filters specific to refund context
                        $refund_reasons = ['Received broken', 'Did not receive', 'Allergy'];
                        
                        // "All" filter for refunds
                        $all_url = "refunds.php?tab=all" . (!empty($search_query) ? '&search='.urlencode($search_query) : ''); 
                        ?>
                        
                        <a href="<?php echo $all_url; ?>" 
                        class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-full transition-all text-left whitespace-nowrap <?php echo ($sub_status === 'All' || empty($sub_status)) ? 'bg-primary text-white shadow-sm' : 'bg-stone-100 text-stone-500 hover:text-primary hover:bg-stone-200/70'; ?>">
                            All
                        </a>

                        <?php 
                        foreach ($refund_reasons as $reason): 
                            $is_sub_active = ($sub_status === $reason);
                            $sub_url = "refunds.php?tab=all&sub_status=" . urlencode($reason) . (!empty($search_query) ? '&search='.urlencode($search_query) : '');
                        ?>
                            <a href="<?php echo $sub_url; ?>" 
                            class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider rounded-full transition-all text-left whitespace-nowrap <?php echo $is_sub_active ? 'bg-primary text-white shadow-sm' : 'bg-stone-100 text-stone-500 hover:text-primary hover:bg-stone-200/70'; ?>">
                                <?php echo $reason; ?>
                            </a>
                        <?php endforeach; ?>
                        
                    </div>
                <?php else: ?>
                    <div class="mb-6"></div>
                <?php endif; ?>

                <div class="grid grid-cols-12 gap-8 items-start">
                    <!-- Master Refunds Log Table Box -->
                    <div class="col-span-12 lg:col-span-8 space-y-6">
                        <div class="bg-surface-container-low rounded-3xl p-2">
                            <div class="overflow-hidden">
                                <table class="w-full text-left border-separate border-spacing-y-2">
                                    <thead class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400">
                                        <tr>
                                            <th class="px-4 py-4">Request ID</th>
                                            <th class="px-4 py-4">Order ID</th>
                                            <!-- <th class="px-4 py-4">Product</th> -->
                                            <th class="px-4 py-4">Reason</th>
                                            <th class="px-4 py-4">Request Date</th>
                                            <th class="px-4 py-4">Status</th>
                                            <!-- <th class="px-4 py-4 text-right">Refund</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($refunds_list)): ?>
                                            <?php foreach ($refunds_list as $row): 
                                                // 1. Calculate selection state
                                                $is_selected = ($selected_refund && (int)$selected_refund['id'] === (int)$row['id']);
                                                
                                                // 2. Define style based on selection
                                                $row_bg = $is_selected ? 'bg-surface-container-lowest' : 'bg-surface-container-lowest';
                                                $border_class = $is_selected ? 'border-y-2 border-primary/40' : '';

                                                // Status styles
                                                $status_style = 'bg-stone-100 text-stone-700';
                                                if ($row['status'] === 'Approved') { $status_style = 'bg-green-500/10 text-green-600'; }
                                                elseif ($row['status'] === 'Rejected') { $status_style = 'bg-red-500/10 text-red-600'; }
                                                elseif ($row['status'] === 'Cancelled') { $status_style = 'bg-red-500/10 text-red-600'; }
                                            ?>
                                                <!-- 3. Apply classes to the row -->
                                                <tr class="group hover:translate-y-[-2px] transition-all cursor-pointer <?php echo $row_bg . ' ' . $border_class; ?>" 
                                                    onclick="viewRefund('refunds.php?tab=<?php echo urlencode($active_tab); ?>&sub_status=<?php echo urlencode($sub_status); ?>&view=<?php echo urlencode($row['id']); ?>')">
                                                    
                                                    <!-- Use the same $row_bg variable for the columns if needed -->
                                                    <td class="px-4 py-4 <?php echo $row_bg; ?> <?php echo $is_selected ? 'border-l-2 border-y-2 border-primary/40' : ''; ?> first:rounded-l-2xl">
                                                        <span class="text-xs font-bold text-stone-900">REF-<?php echo htmlspecialchars($row['id']); ?></span>
                                                    </td>

                                                    <td class="px-4 py-4 <?php echo $row_bg; ?> <?php echo $is_selected ? 'border-y-2 border-primary/40' : ''; ?>">
                                                        <span class="text-xs font-bold text-stone-900">#<?php echo htmlspecialchars($row['order_id']); ?></span>
                                                    </td>
                                                    
                                                    <!-- <td class="px-4 py-4 <?php echo $row_bg; ?> <?php echo $is_selected ? 'border-y-2 border-primary/40' : ''; ?>">
                                                        <span class="text-xs font-medium text-stone-700 truncate">
                                                            <?php echo htmlspecialchars(strtoupper($row['product_brand']) . ' ' . $row['product_name']); ?>
                                                        </span>
                                                    </td> -->
                                                    
                                                    <td class="px-4 py-4 <?php echo $row_bg; ?> <?php echo $is_selected ? 'border-y-2 border-primary/40' : ''; ?>">
                                                        <span class="text-xs text-stone-500"><?php echo htmlspecialchars($row['reason']); ?></span>
                                                    </td>

                                                    <td class="px-4 py-4 <?php echo $row_bg; ?> <?php echo $is_selected ? 'border-y-2 border-primary/40' : ''; ?>">
                                                        <span class="text-xs text-stone-500">
                                                            <?php echo date('M d, Y g:i A', strtotime($row['created_at'])); ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <td class="px-4 py-4 <?php echo $row_bg; ?> <?php echo $is_selected ? 'border-r-2 border-y-2 border-primary/40' : ''; ?> last:rounded-r-2xl">
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full <?php echo $status_style; ?>">
                                                            <?php echo htmlspecialchars($row['status']); ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <!-- <td class="px-4 py-4 <?php echo $row_bg; ?> <?php echo $is_selected ? 'border-r-2 border-y-2 border-primary/40' : ''; ?> last:rounded-r-2xl text-right text-xs font-bold text-stone-800">
                                                        RM <?php echo number_format($row['refund_amount'], 2); ?>
                                                    </td> -->
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="p-8 text-center bg-surface-container-lowest rounded-2xl text-xs font-semibold text-stone-400">
                                                    No refund requests found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Inspection Panel -->
                    <div class="col-span-12 lg:col-span-4 self-start">
                        <?php if ($selected_refund): 
                            $refund_id = $selected_refund['id'];
                            $proofs = explode(',', $selected_refund['proof_path']);
                        ?>
                            <div class="bg-surface-container-lowest rounded-[2.5rem] p-8 shadow-xl shadow-orange-900/5 sticky top-24 border border-stone-100/30 space-y-6">
                                <div>
                                    <h3 class="text-2xl font-extrabold text-orange-900">Refund Details</h3>
                                    <p class="text-sm font-bold text-stone-400 mt-1">Request #<?php echo $refund_id; ?> (Order ID: <?php echo htmlspecialchars($selected_refund['order_id']); ?>)</p>
                                </div>

                                <!-- Refund Reason & Description -->
                                <div class="space-y-3 bg-surface-container-low/50 p-4 rounded-2xl border border-stone-100">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400">Reason</p>
                                    <p class="text-sm font-bold text-stone-900"><?php echo htmlspecialchars($selected_refund['reason']); ?></p>
                                    <p class="text-xs text-stone-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($selected_refund['description'])); ?></p>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center mb-4">
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400">Refunded Items</p>
                                        <?php
                                        $current_order_id = $selected_refund['order_id'];
                                        $current_user_id = $selected_refund['user_id'];
                                        $current_created_at = $selected_refund['created_at'];

                                        $items_query = "SELECT r.refund_amount, r.quantity, p.name AS prod_name, p.brand AS prod_brand, p.image AS prod_image,
                                                        (SELECT SUM(refund_amount) FROM refund_requests WHERE order_id = ? AND user_id = ? AND created_at = ?) as total_refund 
                                                        FROM refund_requests r 
                                                        LEFT JOIN products p ON r.product_id = p.id 
                                                        WHERE r.order_id = ? AND r.user_id = ? AND r.created_at = ?";
                                        
                                        $items_stmt = $conn->prepare($items_query);
                                        $items_stmt->bind_param("ssssss", $current_order_id, $current_user_id, $current_created_at, $current_order_id, $current_user_id, $current_created_at); 
                                        $items_stmt->execute();
                                        $items_result = $items_stmt->get_result();
                                        
                                        $items = $items_result->fetch_all(MYSQLI_ASSOC);
                                        $total_refund = !empty($items) ? $items[0]['total_refund'] : 0;
                                        ?>
                                        <p class="text-sm font-bold text-orange-900">RM <?php echo number_format($total_refund, 2); ?></p>
                                    </div>

                                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                                        <?php if (!empty($items)): 
                                            foreach ($items as $item): 
                                                $prod_brand = !empty($item['prod_brand']) ? strtoupper($item['prod_brand']) : 'SYSTEM ARCHIVE';
                                                $prod_name = !empty($item['prod_name']) ? $item['prod_name'] : 'Deleted Product Listing';
                                                $product_thumbnail = !empty($item['prod_image']) ? htmlspecialchars($item['prod_image']) : 'default-product.png';
                                        ?>
                                                <div class="flex items-center gap-3 p-2 bg-surface-container-low/50 rounded-xl border border-stone-100">
                                                    <div class="w-12 h-12 rounded-xl bg-surface-container overflow-hidden shrink-0 border border-stone-200/60 relative flex items-center justify-center">
                                                        <img src="../images/<?php echo $product_thumbnail; ?>" class="absolute inset-0 w-full h-full object-cover object-center" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <span class="material-symbols-outlined text-stone-300 text-sm hidden">image</span>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-xs font-bold text-stone-900 truncate"><span class="uppercase mr-1"><?php echo htmlspecialchars($prod_brand); ?></span><?php echo htmlspecialchars($prod_name); ?></p>
                                                        <p class="text-[11px] text-stone-500">Qty: <?php echo $item['quantity']; ?></p>
                                                    </div>
                                                    <div class="flex flex-col items-end justify-center shrink-0">
                                                        <p class="text-xs font-bold text-orange-900">RM <?php echo number_format($item['refund_amount'], 2); ?></p>
                                                    </div>
                                                </div>
                                        <?php endforeach; 
                                        else: ?>
                                            <div class="p-3 text-error rounded-xl text-[11px] font-semibold">No items found.</div>
                                        <?php endif; 
                                        $items_stmt->close(); ?>
                                    </div>
                                </div>

                                <!-- Proof Images -->
                                <div class="space-y-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400">Evidence / Proof</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <?php if (!empty($selected_refund['proof_path'])): ?>
                                            <?php foreach ($proofs as $path): 
                                                $path = trim($path);
                                                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                            ?>
                                                <div class="relative border rounded-lg overflow-hidden">
                                                    <?php if ($ext === 'pdf'): ?>
                                                        <a href="../<?php echo $path; ?>" target="_blank" class="flex-col h-32 w-full flex items-center justify-center bg-stone-100 text-stone-500">
                                                            <i class="fas fa-file-pdf text-4xl text-orange-900"></i>
                                                            <span class="text-xs">View PDF</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="../<?php echo $path; ?>" target="_blank" class="...">
                                                            <img src="../<?php echo $path; ?>" class="h-32 w-full object-cover">
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-xs text-stone-400 italic">No images provided.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Admin Action Section -->
                                <div class="pt-4 border-t space-y-4">
                                    <form id="refundForm-<?php echo $refund_id; ?>">
                                        <input type="hidden" name="refund_id" value="<?php echo $refund_id; ?>">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($selected_refund['order_id']); ?>">
                                        
                                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400 block mb-2">Admin Reply</label>
                                        <textarea name="admin_reply" rows="3" placeholder="Write message..." class="w-full px-3 py-2 bg-surface-container-low border-none rounded-xl text-xs text-stone-700 mb-4 focus:ring-2 focus:ring-primary/20 resize-none"><?php echo htmlspecialchars($selected_refund['admin_reply'] ?? ''); ?></textarea>

                                        <div class="mb-6">
                                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400 block mb-2">Update Status</label>
                                            <select name="status_update" id="status_update" class="w-full px-3 py-2 bg-surface-container-low border-none rounded-xl text-xs text-stone-700 font-bold focus:ring-2 focus:ring-primary/20">
                                                <option value="Pending" <?php echo ($selected_refund['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Approved" <?php echo ($selected_refund['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                                <option value="Rejected" <?php echo ($selected_refund['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                <option value="Cancelled" <?php echo ($selected_refund['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <button type="button" 
                                                id="saveBtn"
                                                onclick="saveRefundChanges(<?php echo $refund_id; ?>)"
                                                <?php echo ($selected_refund['status'] === 'Cancelled') ? 'disabled' : ''; ?>
                                                class="w-full py-3 bg-orange-900 text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-orange-800 transition-all flex items-center justify-center gap-1.5 shadow-lg shadow-orange-900/15 disabled:opacity-30 disabled:cursor-not-allowed">
                                            <span class="material-symbols-outlined text-sm">save</span> Save Changes
                                        </button>
                                        <!-- <div class="grid grid-cols-2 gap-3">
                                            <button type="button" onclick="processRefund(<?php echo $refund_id; ?>, '<?php echo $selected_refund['order_id']; ?>', 'Rejected')" 
                                                    class="py-3 bg-red-50 text-red-700 rounded-xl text-xs font-bold uppercase hover:bg-red-100 transition-all">Reject</button>
                                            <button type="button" onclick="processRefund(<?php echo $refund_id; ?>, '<?php echo $selected_refund['order_id']; ?>', 'Approved')" 
                                                    class="py-3 bg-green-600 text-white rounded-xl text-xs font-bold uppercase hover:bg-green-700 transition-all shadow-lg">Approve</button>
                                        </div> -->
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-surface-container-lowest rounded-[2.5rem] p-8 text-center text-stone-400 text-xs font-medium border border-dashed border-stone-200">
                                Select a refund request to inspect evidence.
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
            // Updated to use refund-specific scroll storage
            const savedScrollPos = sessionStorage.getItem("refunds_scroll_pos");
            if (savedScrollPos) {
                window.scrollTo(0, parseInt(savedScrollPos));
                sessionStorage.removeItem("refunds_scroll_pos"); 
            }

            const statusSelect = document.getElementById('status_update');
            const saveBtn = document.getElementById('saveBtn');
            
            if (statusSelect && saveBtn) {
                if (statusSelect.value === 'Cancelled') {
                    saveBtn.disabled = true;
                }
            }
        });

        function viewRefund(url) {
            sessionStorage.setItem("refunds_scroll_pos", window.scrollY);
            window.location.href = url;
        }

        function processRefund(refundId, orderId, status) {
            // 1. Capture the admin reply dynamically from the textarea
            const replyTextarea = document.querySelector(`textarea[name="admin_reply"]`);
            const adminReply = replyTextarea ? replyTextarea.value : '';

            const formData = new FormData();
            formData.append('refund_id', refundId);
            formData.append('order_id', orderId);
            formData.append('status', status);
            formData.append('admin_reply', adminReply);

            // 2. Adjust fetch path if your script is in /admin/refunds.php 
            // and process_refund.php is in /admin/backend/
            fetch('backend/process_refund.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload(); 
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A network error occurred.');
            });
        }

        document.getElementById('status_update').addEventListener('change', function() {
            const saveBtn = document.getElementById('saveBtn');
            // Disable the button if the selected value is 'Cancelled'
            if (this.value === 'Cancelled') {
                saveBtn.disabled = true;
            } else {
                saveBtn.disabled = false;
            }
        });

        function saveRefundChanges(refundId) {
            const statusSelect = document.getElementById('status_update');
            const replyTextarea = document.querySelector(`textarea[name="admin_reply"]`);
            const saveBtn = document.getElementById('saveBtn');
            const originalText = saveBtn.innerHTML;

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="animate-pulse">SAVING...</span>';

            const formData = new FormData();
            formData.append('refund_id', refundId);
            formData.append('status', statusSelect.value);
            formData.append('admin_reply', replyTextarea ? replyTextarea.value : '');
            formData.append('order_id', '<?php echo htmlspecialchars($selected_refund['order_id']); ?>');

            fetch('backend/process_refund.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Refund status updated successfully!');
                    location.reload(); 
                } else {
                    alert('Error: ' + data.message);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A network error occurred.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            });
        }
    </script>

</body>
</html>