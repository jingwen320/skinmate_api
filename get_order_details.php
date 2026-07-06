<?php
include 'db.php';

$order_id = $_GET['order_id'] ?? '';

if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order_id']);
    exit;
}

// 1. Fetch the master order details
// Note: Changed column check from 'order_date' to 'created_at' to prevent structural crashes 
// if your main table uses default timestamp naming conventions.
$query = "SELECT * FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

$order_data = $order_result->fetch_assoc();
$date_column = isset($order_data['order_date']) ? $order_data['order_date'] : ($order_data['created_at'] ?? 'now');

// 2. Fetch items - 🌟 UPGRADED: Added INNER JOIN onto your products table to fetch real image paths and brands!
$items_query = "
    SELECT oi.product_id, oi.product_name, oi.unit_price, oi.quantity, p.brand, p.image 
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("s", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = [
        'product_id' => $row['product_id'], 
        'name'       => $row['product_name'],
        'price'      => (float)$row['unit_price'],
        'qty'        => (int)$row['quantity'], 
        'brand'      => $row['brand'] ?? '',
        'image'      => $row['image'] ?? ''
    ];
}

// 3. Package it all together seamlessly
echo json_encode([
    'status' => 'success',
    'order' => [
        'order_id'        => $order_data['order_id'],
        'date'            => date("M d, Y", strtotime($date_column)),
        'status'          => $order_data['status'],
        'subtotal'        => (float)$order_data['subtotal'],
        'discount'        => (float)$order_data['discount_amount'],
        'shipping_fee'    => $order_data['shipping_fee'],
        'final_total'     => (float)$order_data['final_total'],
        'admin_reply'     => $order_data['admin_reply'] ?? null, 
        'name'            => $order_data['name'] ?? '',
        'phone'           => $order_data['phone'] ?? '',
        'address' => [
            'line1'    => $order_data['address_line_1'],
            'line2'    => $order_data['address_line_2'],
            'postcode' => $order_data['postcode'],
            'city'     => $order_data['city'],
            'state'    => $order_data['state'],
            'region'   => $order_data['region']
        ],
        'items' => $items
    ]
]);
?>