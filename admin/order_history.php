<?php
// admin/order_history.php
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
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $target_user_id);
$user_stmt->execute();
$user_profile = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if (!$user_profile) {
    die("Error: Target platform profile metadata does not exist.");
}

// 3. Aggregate Total Spending Metrics & Orders Count
$metrics_query = "SELECT COUNT(id) as total_orders_count, SUM(final_total) as lifetime_spend 
                  FROM orders 
                  WHERE user_id = ?";
$metrics_stmt = $conn->prepare($metrics_query);
$metrics_stmt->bind_param("i", $target_user_id);
$metrics_stmt->execute();
$metrics_res = $metrics_stmt->get_result()->fetch_assoc();
$metrics_stmt->close();

$total_orders_count = intval($metrics_res['total_orders_count']);
$lifetime_spend = floatval($metrics_res['lifetime_spend']);

// 4. Fetch All Orders with Nested Item Bundles
$orders_query = "SELECT 
                    id, order_id, order_date, status, name, phone,
                    address_line_1, address_line_2, postcode, city, state, region,
                    subtotal, discount_amount, shipping_fee, final_total
                 FROM orders 
                 WHERE user_id = ? 
                 ORDER BY order_date DESC, id DESC";

$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $target_user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SkinMate Admin - Order History</title>
    <!-- Tailwind CSS Engine CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "background": "#f7f6f3",
                    "surface-bright": "#f7f6f3",
                    "outline": "#767775",
                    "primary": "#91462e",
                    "on-surface-variant": "#5b5c5a",
                    "surface-container-high": "#e3e3df",
                    "surface": "#f7f6f3",
                    "primary-container": "#fe9d7f",
                    "on-primary-container": "#5f200b",
                    "on-background": "#2e2f2d",
                    "surface-container-lowest": "#ffffff",
                    "error": "#b31b25",
                    "tertiary-container": "#fed07f",
                    "on-tertiary-container": "#634500",
                    "outline-variant": "#adadab",
                    "surface-container-low": "#f1f1ee",
                    "on-surface": "#2e2f2d",
                    "surface-container": "#e8e8e5"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
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

    <!-- Left Margin Sidebar offset Spacer Frame -->
    <main class="ml-72 pt-24 px-10 pb-20 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-8">
            
            <!-- Breadcrumb Navigation Routing Bar -->
            <div class="flex items-center gap-2 text-xs font-bold text-on-surface-variant/60 uppercase tracking-wider select-none">
                <a href="users.php?view_user=<?php echo $target_user_id; ?>" class="hover:text-primary transition-colors">User Directory</a>
                <span class="material-symbols-outlined text-sm text-outline-variant">chevron_right</span>
                <span class="text-on-surface-variant">Purchase Order History</span>
            </div>

            <!-- Page Meta Headers & Total Performance Aggregates Row -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end border-b border-surface-container-high pb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-orange-900 tracking-tight">
                        Order History: <?php echo htmlspecialchars($user_profile['name']); ?>
                    </h1>
                    <p class="text-on-surface-variant mt-1">
                        Management transactional dashboard view for #SM-<?php echo $target_user_id; ?>-UA
                    </p>
                </div>
                
                <!-- KPI Total Counters Widgets Summary Grid -->
                <div class="flex gap-4 w-full md:w-auto">
                    <div class="bg-surface-container-lowest border border-surface-container-high rounded-xl px-5 py-3 text-left shadow-xs min-w-[130px]">
                        <p class="text-[9px] text-on-surface-variant uppercase font-black tracking-widest">Total Orders</p>
                        <p class="text-xl font-black text-on-background mt-0.5"><?php echo $total_orders_count; ?></p>
                    </div>
                    <div class="bg-primary-container/20 border border-primary-container/40 rounded-xl px-5 py-3 text-left shadow-xs min-w-[160px]">
                        <p class="text-[9px] text-primary uppercase font-black tracking-widest">Lifetime Spending</p>
                        <p class="text-xl font-black text-primary mt-0.5">RM <?php echo number_format($lifetime_spend, 2); ?></p>
                    </div>
                    <a href="users.php?view_user=<?php echo $target_user_id; ?>" 
                       class="self-end px-5 py-2.5 bg-surface-container-lowest border border-surface-container-high rounded-full text-xs font-bold text-on-surface-variant hover:bg-surface-container-low transition-all flex items-center gap-1.5 shadow-xs">
                        <span class="material-symbols-outlined text-sm">arrow_back</span> Return to Profile
                    </a>
                </div>
            </div>

            <!-- Main Dynamic Grid Layout System -->
            <?php if ($total_orders_count > 0): ?>
                <!-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"> -->
                <div class="space-y-4 max-w-full mx-auto">
                    
                    <?php while ($order = $orders_result->fetch_assoc()): 
                        $order_id_raw = $order['order_id'];
                        $normalizedStatus = strtolower(trim($order['status']));
                        
                        // Dynamically build badge status colors
                        $badgeClass = 'bg-stone-100 text-stone-700';
                        if ($normalizedStatus === 'delivered' || $normalizedStatus === 'refunded') {
                            $badgeClass = 'bg-green-500/10 text-green-600';
                        } else if ($normalizedStatus === 'completed') {
                            $badgeClass = 'bg-primary/10 text-primary';
                        } else if (in_array($normalizedStatus, ['cancelled', 'refund rejected'])) {
                            $badgeClass = 'bg-error/10 text-error';
                        }
                    ?>
                        <!-- Order Node Card Base Container -->
                        <div class="bg-surface-container-lowest border border-surface-container-high rounded-xl shadow-xs transition-all duration-300 overflow-hidden">

                            <div onclick="toggleOrderCollapse('<?php echo htmlspecialchars($order['order_id']); ?>')" 
                                class="p-5 flex items-center justify-between gap-4 cursor-pointer hover:bg-surface-container-low/30 select-none transition-colors">
                                
                                <div class="space-y-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10px] font-black text-on-surface-variant/70 tracking-widest uppercase">
                                            <?php echo date('M d, Y · h:i A', strtotime($order['order_date'])); ?>
                                        </span>
                                        <span class="text-[9px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-base font-extrabold text-on-background tracking-tight mt-1">
                                        Invoice Reference: <span class="text-primary">#<?php echo htmlspecialchars($order['order_id']); ?></span>
                                    </h3>
                                </div>

                                <div class="flex items-center gap-5 shrink-0">
                                    <div class="text-right">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-on-surface-variant/60">Final Total</p>
                                        <p class="text-lg font-black text-primary">RM <?php echo number_format($order['final_total'], 2); ?></p>
                                    </div>
                                    
                                    <button type="button" 
                                            id="btn-toggle-<?php echo htmlspecialchars($order['order_id']); ?>"
                                            class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-all duration-300">
                                        <span class="material-symbols-outlined transition-transform duration-300 pointer-events-none">keyboard_arrow_down</span>
                                    </button>
                                </div>
                            </div>

                            <div id="collapse-target-<?php echo htmlspecialchars($order['order_id']); ?>" 
                                class="max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
                                
                                <div class="p-6 pt-5 space-y-6 border-t border-surface-container-low">
                                    
                                    <div class="space-y-2.5 mb-4">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-on-surface-variant/80">Items Purchased</p>
                                        
                                        <?php
                                        $items_query = "SELECT oi.unit_price, oi.quantity, p.name AS prod_name, p.brand AS prod_brand, p.image AS prod_image, p.price AS current_retail_price 
                                                        FROM order_items oi 
                                                        JOIN products p ON oi.product_id = p.id 
                                                        WHERE oi.order_id = ?";
                                        $items_stmt = $conn->prepare($items_query);
                                        $items_stmt->bind_param("s", $order_id_raw);
                                        $items_stmt->execute();
                                        $items_result = $items_stmt->get_result();
                                        
                                        while ($item = $items_result->fetch_assoc()):
                                            $product_thumbnail = !empty($item['prod_image']) ? htmlspecialchars($item['prod_image']) : '../images/default-product.png';
                                        ?>
                                            <div class="flex items-center justify-between p-2.5 bg-surface-container-low/40 rounded-xl border border-surface-container/30 gap-3">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-12 h-12 rounded-lg bg-surface-container-high overflow-hidden shrink-0 border border-surface-container-highest/50 flex items-center justify-center">
                                                        <img src="../images/<?php echo $product_thumbnail; ?>"
                                                            alt="<?php echo htmlspecialchars($item['prod_brand'] . ' ' . $item['prod_name']); ?>" 
                                                            class="w-full h-full object-cover onerror-fallback"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <span class="material-symbols-outlined text-on-surface-variant/40 text-lg hidden">image</span>
                                                    </div>

                                                    <div class="min-w-0 space-y-0.5">
                                                        <h4 class="font-semibold text-on-background text-xs whitespace-normal break-words leading-snug">
                                                            <span class="uppercase tracking-wide block sm:inline mr-1">
                                                                <?php echo htmlspecialchars($item['prod_brand']); ?>
                                                            </span><?php echo htmlspecialchars($item['prod_name']); ?>
                                                        </h4>
                                                        <p class="text-[10px] text-on-surface-variant/70 font-medium tracking-tight">
                                                            Qty: <span class="text-on-surface-variant/70"><?php echo $item['quantity']; ?></span>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="text-right shrink-0 flex flex-col justify-center">
                                                    <p class="text-xs font-extrabold text-on-background tracking-tight">
                                                        RM <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                                                    </p>
                                                    <p class="text-[9px] text-on-surface-variant/50 font-medium tracking-tight mt-0.5">
                                                        RM <?php echo number_format($item['current_retail_price'], 2); ?> ea
                                                    </p>
                                                </div>
                                            </div>
                                        <?php 
                                        endwhile; 
                                        $items_stmt->close();
                                        ?>
                                    </div>

                                    <div class="border-t border-dashed border-surface-container-high my-4"></div>

                                    <div class="space-y-1.5 px-1 text-xs text-on-surface-variant">
                                        <div class="flex justify-between">
                                            <span>Subtotal</span>
                                            <span class="font-medium text-on-background">RM <?php echo number_format($order['subtotal'], 2); ?></span>
                                        </div>
                                        <?php if (floatval($order['discount_amount']) > 0): ?>
                                            <div class="flex justify-between text-red-600 font-medium">
                                                <span>Discount Applied</span>
                                                <span>-RM <?php echo number_format($order['discount_amount'], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex justify-between">
                                            <span>Shipping</span>
                                            <span class="font-medium text-on-background">RM <?php echo number_format($order['shipping_fee'], 2); ?></span>
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t border-surface-container border-dashed text-xs space-y-2">
                                        <div class="flex items-center gap-1.5 font-bold text-on-background">
                                            <span class="material-symbols-outlined text-sm text-primary">local_shipping</span>
                                            <span>Shipping Details</span>
                                        </div>
                                        <div class="bg-background/80 rounded-xl p-3 text-on-surface-variant font-medium leading-relaxed space-y-1">
                                            <div class="font-bold text-on-surface text-[11px]">
                                                <span><?php echo htmlspecialchars($order['name']); ?></span>
                                                <span class="text-primary font-mono ml-1 text-[10px] font-normal">
                                                    <?php 
                                                    $raw_phone = preg_replace('/[^0-9]/', '', $order['phone']);
                                                    
                                                    if (substr($raw_phone, 0, 2) === '60') {
                                                        $raw_phone = substr($raw_phone, 2);
                                                    } elseif (substr($raw_phone, 0, 1) === '0') {
                                                        $raw_phone = substr($raw_phone, 1);
                                                    }
                                                    
                                                    $prefix = substr($raw_phone, 0, 2);
                                                    $rest = substr($raw_phone, 2);

                                                    $total_remainder_len = strlen($rest);
                                                    
                                                    if ($total_remainder_len === 8) {
                                                        $mid_chunk = substr($rest, 0, 4);
                                                        $end_chunk = substr($rest, 4);
                                                    } else {
                                                        $mid_chunk = substr($rest, 0, 3);
                                                        $end_chunk = substr($rest, 3);
                                                    }
                                                    
                                                    echo "(+60) " . $prefix . "-" . $mid_chunk . " " . $end_chunk;
                                                ?>
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-on-surface-variant/90 whitespace-pre-line mt-1"><?php 
                                                echo htmlspecialchars($order['address_line_1']) . ",\n";
                                                if(!empty($order['address_line_2'])) {
                                                    echo htmlspecialchars($order['address_line_2']) . ",\n";
                                                }
                                                echo htmlspecialchars($order['postcode']) . " " . htmlspecialchars($order['city']) . ",\n";
                                                echo htmlspecialchars($order['state']) . ", " . htmlspecialchars($order['region']) . ".";
                                            ?></p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    <?php endwhile; ?>

                </div>
            <?php else: ?>
                <!-- Empty State Layout Canvas -->
                <div class="bg-surface-container-lowest border border-surface-container-high rounded-xl p-16 text-center text-on-surface-variant/60 max-w-2xl mx-auto">
                    <span class="material-symbols-outlined text-4xl block mb-3 text-outline-variant">shopping_bag</span>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-on-surface">No Orders Found</h3>
                    <p class="text-xs text-on-surface-variant mt-1 max-w-sm mx-auto">This customer has not placed any orders yet.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

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
    </script>

</body>
</html>
<?php
$orders_stmt->close();
$conn->close();
?>