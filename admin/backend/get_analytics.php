<?php
// admin/backend/get_analytics.php
session_start();
include '../../db.php'; 

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

function forecastNextMonth($monthly_data) {
    $values = array_values(array_filter($monthly_data, fn($v) => $v > 0));
    $n = count($values);
    if ($n < 2) return 0; 

    $sum_x = 0; $sum_y = 0; $sum_xy = 0; $sum_xx = 0;
    for ($i = 0; $i < $n; $i++) {
        $sum_x += $i;
        $sum_y += $values[$i];
        $sum_xy += ($i * $values[$i]);
        $sum_xx += ($i * $i);
    }

    $denominator = ($n * $sum_xx - ($sum_x * $sum_x));
    if ($denominator == 0) return end($values);

    $m = ($n * $sum_xy - ($sum_x * $sum_y)) / $denominator;
    $b = ($sum_y - $m * $sum_x) / $n;

    return max(0, ($m * $n) + $b);
}

try {
    // $stmt_sales = $conn->query("SELECT IFNULL(SUM(final_total), 0) FROM orders WHERE status IN ('Completed', 'Refund Rejected')");
    // $total_sales = $stmt_sales->fetch_row()[0];
    $stmt_gross = $conn->query("SELECT IFNULL(SUM(final_total), 0) FROM orders WHERE status IN ('Completed', 'Refund Rejected')");
    $gross_sales = (float)$stmt_gross->fetch_row()[0];

    $stmt_refunds = $conn->query("SELECT IFNULL(SUM(refund_amount), 0) FROM refund_requests WHERE status = 'Approved'");
    $approved_refunds = (float)$stmt_refunds->fetch_row()[0];

    $net_sales = $gross_sales - $approved_refunds;

    $stmt_users = $conn->query("SELECT COUNT(*) FROM users");
    $active_users = $stmt_users->fetch_row()[0];
    $stmt_pending = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Processing'");
    $pending_orders = $stmt_pending->fetch_row()[0];

    // $weekly_data = [];

    // $monday = strtotime('monday this week');

    // for ($i = 0; $i < 7; $i++) {
    //     $date = date('Y-m-d', strtotime("+$i day", $monday));
    //     $weekly_data[$date] = 0;
    // }

    // $stmt_weekly = $conn->query("
    //     SELECT o.date, (o.gross_total - IFNULL(r.refunded, 0)) as net_total
    //     FROM (
    //         SELECT DATE(order_date) as date, SUM(final_total) as gross_total 
    //         FROM orders 
    //         WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    //         AND status IN ('Completed', 'Refund Rejected')
    //         GROUP BY DATE(order_date)
    //     ) o
    //     LEFT JOIN (
    //         SELECT DATE(created_at) as date, SUM(refund_amount) as refunded 
    //         FROM refund_requests 
    //         WHERE status = 'Approved' 
    //         AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    //         GROUP BY DATE(created_at)
    //     ) r ON o.date = r.date
    // ");

    // while ($row = $stmt_weekly->fetch_assoc()) {
    //     if (array_key_exists($row['date'], $weekly_data)) {
    //         $weekly_data[$row['date']] = (float)$row['net_total'];
    //     }
    // }

    $week_offset = isset($_GET['week_offset']) ? (int)$_GET['week_offset'] : 0;
    $start_date = date('Y-m-d', strtotime("monday this week -$week_offset week"));
    $end_date = date('Y-m-d', strtotime("$start_date +6 days"));

    // $stmt_weekly = $conn->prepare("
    //     SELECT o.date, (o.gross_total - IFNULL(r.refunded, 0)) as net_total
    //     FROM (
    //         SELECT DATE(order_date) as date, SUM(final_total) as gross_total 
    //         FROM orders 
    //         WHERE order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    //         AND status IN ('Completed', 'Refund Rejected')
    //         GROUP BY DATE(order_date)
    //     ) o
    //     LEFT JOIN (
    //         SELECT DATE(created_at) as date, SUM(refund_amount) as refunded 
    //         FROM refund_requests 
    //         WHERE status = 'Approved' 
    //         AND created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    //         GROUP BY DATE(created_at)
    //     ) r ON o.date = r.date
    // ");
    // $stmt_weekly->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);

    $stmt_weekly = $conn->prepare("
        SELECT d.date, 
            (IFNULL(o.gross, 0) - IFNULL(r.refunded, 0)) as net_total
        FROM (
            SELECT DATE(order_date) as date FROM orders WHERE order_date BETWEEN ? AND ?
            UNION
            SELECT DATE(created_at) as date FROM refund_requests WHERE created_at BETWEEN ? AND ?
        ) d
        LEFT JOIN (
            SELECT DATE(order_date) as date, SUM(final_total) as gross 
            FROM orders WHERE status IN ('Completed', 'Refund Rejected') 
            GROUP BY DATE(order_date)
        ) o ON d.date = o.date
        LEFT JOIN (
            SELECT DATE(created_at) as date, SUM(refund_amount) as refunded 
            FROM refund_requests WHERE status = 'Approved' 
            GROUP BY DATE(created_at)
        ) r ON d.date = r.date
        WHERE d.date BETWEEN ? AND ?
    ");
    $stmt_weekly->bind_param("ssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
    $stmt_weekly->execute();
    $res_weekly = $stmt_weekly->get_result();

    $weekly_data = [];
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i day", strtotime($start_date)));
        $weekly_data[$date] = 0;
    }
    while ($row = $res_weekly->fetch_assoc()) {
        $weekly_data[$row['date']] = (float)$row['net_total'];
    }

    // error_log("Weekly Data for July: " . print_r($weekly_data, true));

    $formatted_weekly = [];
    foreach ($weekly_data as $date => $total) {
        $formatted_weekly[] = ['date' => $date, 'total' => $total];
    }

    $year = date('Y');
    $currentMonth = date('n');

    $monthly_data = [];

    for ($i = 1; $i <= $currentMonth; $i++) {
        $month = sprintf("%s-%02d", $year, $i);
        $monthly_data[$month] = 0;
    }

    $stmt = $conn->query("
        SELECT o.month, (o.gross_total - IFNULL(r.refunded, 0)) as net_total
        FROM (
            SELECT DATE_FORMAT(order_date,'%Y-%m') AS month, SUM(final_total) AS gross_total
            FROM orders
            WHERE status IN ('Completed','Refund Rejected')
            GROUP BY DATE_FORMAT(order_date,'%Y-%m')
        ) o
        LEFT JOIN (
            SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, SUM(refund_amount) AS refunded
            FROM refund_requests
            WHERE status = 'Approved'
            GROUP BY DATE_FORMAT(created_at,'%Y-%m')
        ) r ON o.month = r.month
        ORDER BY o.month ASC
    ");

    while($row = $stmt->fetch_assoc()){
        if(isset($monthly_data[$row['month']])){
            $monthly_data[$row['month']] = (float)$row['net_total'];
        }
    }

    // error_log("Monthly Data for July: " . $monthly_data['2026-07']);

    $formatted_monthly = [];

    foreach($monthly_data as $month=>$total){
        $formatted_monthly[] = [
            'month'=>$month,
            'total'=>$total
        ];
    }

    $leaderboard = [];
    $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';

    // Build the SQL fragment
    $dateFilter = ($period === 'all') 
        ? "" 
        : "AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    // $res_leaderboard = $conn->query("
    //     SELECT p.name as product_name, p.brand, p.image, SUM(oi.quantity) as total_sold 
    //     FROM order_items oi 
    //     JOIN products p ON oi.product_id = p.id 
    //     JOIN orders o ON oi.order_id = o.order_id 
    //     WHERE o.status IN ('Completed', 'Refund Rejected') 
    //     $dateFilter
    //     GROUP BY p.id 
    //     ORDER BY total_sold DESC 
    //     LIMIT 3
    // ");
    // Query to see popularity AND the "trouble" level of the product
    $res_leaderboard = $conn->query("
        SELECT 
            p.name as product_name, 
            p.brand, 
            p.image, 
            SUM(oi.quantity) as total_sold,
            (SELECT COUNT(*) FROM refund_requests r 
            WHERE r.product_id = p.id AND r.status = 'Approved') as refund_count
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        JOIN orders o ON oi.order_id = o.order_id 
        WHERE o.status IN ('Completed', 'Refund Rejected') 
        $dateFilter
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 3
    ");

    while($row = $res_leaderboard->fetch_assoc()) {
        $leaderboard[] = [
            'product_name' => $row['product_name'],
            'total_sold'   => (int)$row['total_sold'],
            'refund_count' => (int)$row['refund_count'],
            'brand' => strtoupper($row['brand']),
            'image' => $row['image']
        ];
    }

    $predicted_revenue = forecastNextMonth($monthly_data);

    echo json_encode([
        'status' => 'success',
        'metrics' => [
            'total_sales'    => number_format($net_sales, 2),
            'active_users'   => (int)$active_users,
            'pending_orders' => (int)$pending_orders,
            'forecast_sales' => number_format($predicted_revenue, 2)
        ],
        'charts' => [
            'weekly' => $formatted_weekly,
            'monthly' => $formatted_monthly
        ],
        'leaderboard' => $leaderboard
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>