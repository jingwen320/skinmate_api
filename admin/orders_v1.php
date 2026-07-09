<?php
// admin/orders.php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'actionable'; 
$orders_list = [];

$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];
$types = "";

if ($active_tab === 'actionable') {
    $sql .= " AND status IN ('Processing', 'In Transit', 'Refund Processing')";
} elseif ($active_tab === 'completed') {
    $sql .= " AND status NOT IN ('Processing', 'In Transit', 'Refund Processing')";
}

if ($search_query !== '') {
    $sql .= " AND (order_id LIKE ? OR CAST(user_id AS CHAR) LIKE ?)";
    $like_param = '%' . $search_query . '%';
    $params[] = $like_param;
    $params[] = $like_param;
    $types .= "ss";
}

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
?>
<!DOCTYPE html>
<html lang="en">
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
        function toggleOrderCollapse(orderId) {
            const content = document.getElementById(`collapse-target-${orderId}`);
            const button = document.getElementById(`btn-toggle-${orderId}`);
            if (!content || !button) return;

            const icon = button.querySelector('.material-symbols-outlined');
            if (content.style.maxHeight && content.style.maxHeight !== '0px') {
                content.style.maxHeight = '0px';
                if (icon) icon.style.transform = 'rotate(0deg)';
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                if (icon) icon.style.transform = 'rotate(180deg)';
            }
        }

        function submitOrderUpdate(orderTableId) {
            const statusVal = document.getElementById(`status-select-${orderTableId}`).value;
            const noteVal = document.getElementById(`note-textarea-${orderTableId}`).value;

            const formData = new FormData();
            formData.append('action', 'update_order');
            formData.append('id', orderTableId);
            formData.append('status', statusVal);
            formData.append('note', noteVal);

            // Points precisely to backend directory layer relative to /admin/
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
    <?php include 'sidebar.php'; ?>
</head>
<body class="bg-surface text-on-background min-h-screen">

    <!-- Main Workspace Canvas Area -->
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen bg-surface">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-extrabold text-orange-900 tracking-tight">Order Records</h2>
                    <p class="text-on-surface-variant mt-1">Holistic transactional control dashboard panel and full order items dispatch tracking view.</p>
                </div>

                <form method="GET" action="orders.php" class="flex gap-2 w-full md:w-auto">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                    <div class="relative min-w-[320px]">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-xl">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Search Invoice reference or User ID..." 
                               class="w-full pl-11 pr-4 py-2.5 bg-surface-container-lowest border border-surface-container-high rounded-xl text-xs font-semibold placeholder:text-on-surface-variant/40 focus:outline-none focus:border-primary transition-all">
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-sm hover:bg-primary-dim transition-all">Search</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="orders.php" class="px-4 py-2.5 bg-surface-container-low border border-surface-container-high text-on-surface-variant text-xs font-bold rounded-xl flex items-center justify-center hover:bg-surface-container-high transition-colors">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="flex border-b border-surface-container-high gap-2 mb-6">
                <a href="orders.php?tab=actionable&search=<?php echo urlencode($search_query); ?>" 
                class="px-5 py-3 text-xs font-bold border-b-2 transition-all <?php echo $active_tab === 'actionable' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                    Pending
                </a>
                <a href="orders.php?tab=completed&search=<?php echo urlencode($search_query); ?>" 
                class="px-5 py-3 text-xs font-bold border-b-2 transition-all <?php echo $active_tab === 'completed' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                    Completed & Archival
                </a>
                <a href="orders.php?tab=all&search=<?php echo urlencode($search_query); ?>" 
                class="px-5 py-3 text-xs font-bold border-b-2 transition-all <?php echo $active_tab === 'all' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/70 hover:text-on-surface'; ?>">
                    All Orders
                </a>
            </div>

            <?php if (!empty($orders_list)): ?>
                <div class="space-y-4 w-full">
                    <?php foreach ($orders_list as $order): 
                        $order_table_id = $order['id'];
                        $order_id_raw = $order['order_id'];
                        $normalizedStatus = trim($order['status']);
                        
                        // Map status to designated design tokens
                        $badgeClass = 'bg-surface-container text-on-surface-variant';
                        if (in_array($normalizedStatus, ['Delivered', 'Completed', 'Refunded'])) {
                            $badgeClass = 'bg-green-500/10 text-green-600 font-bold';
                        } else if (in_array($normalizedStatus, ['Cancelled', 'Refund Rejected'])) {
                            $badgeClass = 'bg-error/10 text-error font-bold';
                        } else if (in_array($normalizedStatus, ['Processing', 'In Transit', 'Refund Processing'])) {
                            $badgeClass = 'bg-primary/10 text-primary font-bold';
                        }
                    ?>
                        <div class="bg-surface-container-lowest border border-surface-container-high rounded-xl shadow-xs transition-all duration-300 overflow-hidden">
                            
                            <div onclick="toggleOrderCollapse('<?php echo htmlspecialchars($order_table_id); ?>')" 
                                 class="p-5 flex items-center justify-between gap-4 cursor-pointer hover:bg-surface-container-low/50 select-none transition-colors">
                                
                                <div class="space-y-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10px] font-black text-on-surface-variant/70 tracking-widest uppercase">
                                            <?php echo date('M d, Y · h:i A', strtotime($order['order_date'])); ?>
                                        </span>
                                        <span class="text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                        <span class="text-[10px] font-mono text-outline">UID Ref: #<?php echo htmlspecialchars($order['user_id']); ?></span>
                                    </div>
                                    <h3 class="text-base font-extrabold text-on-surface tracking-tight mt-1">
                                        Invoice Reference: <span class="text-primary">#<?php echo htmlspecialchars($order_id_raw); ?></span>
                                    </h3>
                                </div>

                                <div class="flex items-center gap-5 shrink-0">
                                    <div class="text-right">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-on-surface-variant/60">Final Total</p>
                                        <p class="text-lg font-black text-primary">RM <?php echo number_format($order['final_total'], 2); ?></p>
                                    </div>
                                    <button type="button" id="btn-toggle-<?php echo htmlspecialchars($order_table_id); ?>"
                                            class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-all duration-300">
                                        <span class="material-symbols-outlined transition-transform duration-300 pointer-events-none">keyboard_arrow_down</span>
                                    </button>
                                </div>
                            </div>

                            <div id="collapse-target-<?php echo htmlspecialchars($order_table_id); ?>" style="max-height: 0px;" class="overflow-hidden transition-all duration-300 ease-in-out">
                                <div class="p-6 pt-2 space-y-6 border-t border-surface-container-high">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        
                                        <div class="lg:col-span-2 space-y-4">
                                            <div class="space-y-2.5">
                                                <p class="text-[10px] font-black uppercase tracking-wider text-on-surface-variant/80">Items Purchased</p>
                                                
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
                                                        // Apply safe fallback defaults if the item row contains NULL values due to product deletion
                                                        $prod_brand = !empty($item['prod_brand']) ? $item['prod_brand'] : 'System Archive';
                                                        $prod_name = !empty($item['prod_name']) ? $item['prod_name'] : 'Deleted Product Listing';
                                                        $product_thumbnail = !empty($item['prod_image']) ? htmlspecialchars($item['prod_image']) : 'default-product.png';
                                                        $current_retail_price = isset($item['current_retail_price']) ? $item['current_retail_price'] : 0;
                                                    ?>
                                                        <div class="flex items-center justify-between p-2.5 bg-surface-container-low/40 rounded-xl border border-surface-container/30 gap-3">
                                                            <div class="flex items-center gap-3 min-w-0">
                                                                <div class="w-12 h-12 rounded-lg bg-surface-container-high overflow-hidden shrink-0 border border-surface-container-highest flex items-center justify-center">
                                                                    <img src="../images/<?php echo $product_thumbnail; ?>" alt="" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                                    <span class="material-symbols-outlined text-on-surface-variant/40 text-lg hidden">image</span>
                                                                </div>
                                                                <div class="min-w-0 space-y-0.5">
                                                                    <h4 class="font-bold text-on-surface text-xs whitespace-normal break-words leading-snug">
                                                                        <span class="uppercase font-black text-primary text-[11px] block sm:inline mr-1">
                                                                            <?php echo htmlspecialchars($prod_brand); ?>
                                                                        </span><?php echo htmlspecialchars($prod_name); ?>
                                                                    </h4>
                                                                    <p class="text-[10px] text-on-surface-variant/70 font-medium">Qty: <?php echo $item['quantity']; ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="text-right shrink-0">
                                                                <p class="text-xs font-extrabold text-on-surface">RM <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></p>
                                                                <p class="text-[9px] text-on-surface-variant/50 font-medium tracking-tight mt-0.5">RM <?php echo number_format($current_retail_price, 2); ?> Each</p>
                                                            </div>
                                                        </div>
                                                    <?php 
                                                    endwhile; 
                                                else: 
                                                ?>
                                                    <div class="p-3 bg-error/5 border border-error/10 text-error rounded-xl text-xs font-semibold flex items-center gap-2">
                                                        <span class="material-symbols-outlined text-base">warning</span>
                                                        <span>No itemized products linked to this transaction record.</span>
                                                    </div>
                                                <?php 
                                                endif; // FIXED: Safely closed out the outer validation conditional statement
                                                $items_stmt->close(); 
                                                ?>
                                            </div>

                                            <div class="border-t border-dashed border-surface-container-high my-4"></div>
                                            <div class="space-y-1.5 px-1 text-xs text-on-surface-variant">
                                                <div class="flex justify-between">
                                                    <span>Subtotal</span>
                                                    <span class="font-bold text-on-surface">RM <?php echo number_format($order['subtotal'], 2); ?></span>
                                                </div>
                                                <?php if (floatval($order['discount_amount']) > 0): ?>
                                                    <div class="flex justify-between text-error font-bold">
                                                        <span>Discount Applied</span>
                                                        <span>-RM <?php echo number_format($order['discount_amount'], 2); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex justify-between">
                                                    <span>Shipping Fee</span>
                                                    <span class="font-bold text-on-surface">RM <?php echo number_format($order['shipping_fee'], 2); ?></span>
                                                </div>
                                            </div>

                                            <div class="pt-4 border-t border-surface-container border-dashed text-xs space-y-2">
                                                <div class="flex items-center gap-1.5 font-bold text-on-surface">
                                                    <span class="material-symbols-outlined text-sm text-primary">local_shipping</span>
                                                    <span>Shipping Destination</span>
                                                </div>
                                                <div class="bg-surface-container-low/60 rounded-xl p-3 text-on-surface-variant font-medium leading-relaxed space-y-1">
                                                    <div class="font-bold text-on-surface text-[11px]">
                                                        <span><?php echo htmlspecialchars($order['name']); ?></span>
                                                        <span class="text-primary font-mono ml-1 text-[10px] font-normal">
                                                            <?php 
                                                            if (!empty($order['phone'])) {
                                                                $raw_phone = preg_replace('/[^0-9]/', '', $order['phone']);
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
                                                    <p class="text-[11px] text-on-surface-variant/90 whitespace-pre-line mt-1"><?php 
                                                        echo htmlspecialchars($order['address_line_1']) . ",\n";
                                                        if(!empty($order['address_line_2'])) { echo htmlspecialchars($order['address_line_2']) . ",\n"; }
                                                        echo htmlspecialchars($order['postcode']) . " " . htmlspecialchars($order['city']) . ",\n";
                                                        echo htmlspecialchars($order['state']) . ", " . htmlspecialchars($order['region']) . ".";
                                                    ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-surface-container-low/40 border border-surface-container p-4 rounded-xl flex flex-col justify-between space-y-4">
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-wider text-on-surface-variant mb-2">Modify Logistics Status</label>
                                                    <select id="status-select-<?php echo $order_table_id; ?>" onclick="event.stopPropagation();" class="w-full px-3 py-2 bg-surface-container-lowest border border-surface-container-high rounded-xl text-xs font-bold text-on-surface focus:outline-none focus:border-primary transition-all">
                                                        <?php 
                                                        $statuses = ['Processing', 'In Transit', 'Delivered', 'Completed', 'Cancelled', 'Refund Processing', 'Refunded', 'Refund Rejected'];
                                                        foreach ($statuses as $st): 
                                                            $selected = ($normalizedStatus === $st) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?php echo $st; ?>" <?php echo $selected; ?>><?php echo $st; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-wider text-on-surface-variant mb-2">Administrative Memos & Notes</label>
                                                    <textarea id="note-textarea-<?php echo $order_table_id; ?>" rows="4" onclick="event.stopPropagation();" placeholder="Write internal processing requirements here..." class="w-full px-3 py-2 bg-surface-container-lowest border border-surface-container-high rounded-xl text-xs font-medium text-on-surface placeholder:text-on-surface-variant/40 focus:outline-none focus:border-primary resize-none transition-all"><?php echo htmlspecialchars($order['note'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <button type="button" onclick="event.stopPropagation(); submitOrderUpdate('<?php echo $order_table_id; ?>')" class="w-full py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-xs hover:bg-primary-dim transition-all flex items-center justify-center gap-1">
                                                <span class="material-symbols-outlined text-sm">save</span> Sync Tracking State
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-surface-container-lowest border border-surface-container-high rounded-xl p-16 text-center text-on-surface-variant/60 max-w-2xl mx-auto">
                    <span class="material-symbols-outlined text-4xl block mb-3 text-outline-variant">shopping_bag</span>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-on-surface">No Orders Found</h3>
                    <p class="text-xs text-on-surface-variant mt-1 max-w-sm mx-auto">No orders currently match your system queries or search metrics.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Structural Layout System Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>