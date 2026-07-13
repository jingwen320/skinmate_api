<?php
session_start();
include '../../db.php'; 

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $stmt_sales = $conn->query("SELECT IFNULL(SUM(final_total), 0) FROM orders WHERE status IN ('Completed', 'Refund Rejected')");
    $total_sales = $stmt_sales->fetch_row()[0];
    $stmt_users = $conn->query("SELECT COUNT(*) FROM users");
    $active_users = $stmt_users->fetch_row()[0];
    $stmt_pending = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Processing'");
    $pending_orders = $stmt_pending->fetch_row()[0];

    $weekly_data = [];

    $monday = strtotime('monday this week');

    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i day", $monday));
        $weekly_data[$date] = 0;
    }

    $stmt_weekly = $conn->query("SELECT DATE(order_date) as date, SUM(final_total) as total 
                                 FROM orders 
                                 WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                                 AND status IN ('Completed', 'Refund Rejected')
                                 GROUP BY DATE(order_date)");
    
    while ($row = $stmt_weekly->fetch_assoc()) {
        if (array_key_exists($row['date'], $weekly_data)) {
            $weekly_data[$row['date']] = (float)$row['total'];
        }
    }

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
        SELECT DATE_FORMAT(order_date,'%Y-%m') AS month,
            SUM(final_total) AS total
        FROM orders
        WHERE status IN ('Completed','Refund Rejected')
        GROUP BY DATE_FORMAT(order_date,'%Y-%m')
        ORDER BY month ASC
    ");

    while($row = $stmt->fetch_assoc()){
        if(isset($monthly_data[$row['month']])){
            $monthly_data[$row['month']] = (float)$row['total'];
        }
    }

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
    $res_leaderboard = $conn->query("
        SELECT p.name as product_name, p.brand, p.image, SUM(oi.quantity) as total_sold 
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
            'brand' => strtoupper($row['brand']),
            'image' => $row['image']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'metrics' => [
            'total_sales'    => number_format($total_sales, 2),
            'active_users'   => (int)$active_users,
            'pending_orders' => (int)$pending_orders
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