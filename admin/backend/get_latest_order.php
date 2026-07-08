<?php
// admin/backend/get_latest_order.php
header('Content-Type: application/json');
session_start();
include '../../db.php';

// Auth Guard Barrier Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid user identity parameter']);
    exit;
}

try {
    // 1. Fetch the absolute latest order summary details for this customer
    $order_query = "SELECT id, order_id, order_date, final_total, status 
                    FROM orders 
                    WHERE user_id = ? 
                    ORDER BY order_date DESC, id DESC LIMIT 1";
                    
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order = $order_result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        echo json_encode(['status' => 'success', 'has_order' => false]);
        exit;
    }

    // 2. Fetch all linked item records along with their base product descriptions
    $items_query = "SELECT 
                        oi.id AS item_id, 
                        oi.product_id, 
                        oi.product_name, 
                        oi.unit_price, 
                        oi.quantity,
                        p.brand,
                        p.name AS base_product_name,
                        p.image AS product_image
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?"; // Matches string layout token parameter references

    $items_stmt = $conn->prepare($items_query);
    // Bind depending on whether your structural 'order_id' field is an integer or string tracking token
    $items_stmt->bind_param("s", $order['order_id']); 
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items = [];
    while ($row = $items_result->fetch_assoc()) {
        // Formulate presentation requirements: "BRAND Name"
        $brand_prefix = !empty($row['brand']) ? strtoupper(trim($row['brand'])) : '';
        $fallback_name = !empty($row['base_product_name']) ? $row['base_product_name'] : $row['product_name'];
        $full_display_name = ($brand_prefix . ' ' . $fallback_name);

        $items[] = [
            "item_id"       => (int)$row['item_id'],
            "product_id"    => (int)$row['product_id'],
            "product_name"  => $full_display_name,
            "unit_price"    => (float)$row['unit_price'],
            "quantity"      => (int)$row['quantity'],
            "image"         => $row['product_image'] ?? 'placeholder.png'
        ];
    }
    $items_stmt->close();

    echo json_encode([
        'status'    => 'success',
        'has_order' => true,
        'order'     => [
            'id'          => (int)$order['id'],
            'order_id'    => $order['order_id'],
            'order_date'  => date('M d, Y', strtotime($order['order_date'])),
            'final_total' => (float)$order['final_total'],
            'status'      => $order['status'] ?? 'Pending'
        ],
        'items'     => $items
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database failure context execution: ' . $e->getMessage()]);
}

$conn->close();

// Safe capitalization formatting helper execution tool
function uppercase_words_check($string) {
    return ucwords(strtolower($string));
}
?>