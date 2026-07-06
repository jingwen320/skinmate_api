<?php
// get_active_refunds.php
header("Content-Type: application/json");
include 'db.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing user_id"]);
    exit;
}

$user_id = intval($_GET['user_id']);

$sql = "SELECT rr.id, rr.order_id, rr.refund_amount, o.status as order_status, rr.created_at, rr.updated_at,
               rr.reason, rr.description, rr.quantity, rr.proof_path, rr.admin_reply,
               p.name as product_name, p.image as product_image, p.brand as product_brand
        FROM refund_requests rr
        INNER JOIN orders o ON rr.order_id = o.order_id
        LEFT JOIN products p ON rr.product_id = p.id
        WHERE rr.user_id = ? AND o.status = 'Refund Processing' AND rr.status = 'Pending'
        ORDER BY rr.created_at DESC, rr.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$grouped_tickets = [];

while ($row = $result->fetch_assoc()) {
    // Create a unique composite reference key based on order_id and time signature
    $group_key = $row['order_id'] . "_" . strtotime($row['created_at']);

    // Parse item values cleanly
    $item_amount = floatval($row['refund_amount']);
    $item_qty = intval($row['quantity']);

    // Build item map entry dictionary
    $item_details = [
        "name" => $row['product_name'] ?? "SkinMate Item",
        "brand" => $row['product_brand'] ?? "SkinMate",
        "image" => $row['product_image'],
        "quantity" => $item_qty,
        "amount" => $item_amount
      ];

    if (!isset($grouped_tickets[$group_key])) {
        $progress = 0.5; 
        $details = "Request received. Awaiting admin verification check.";

        $grouped_tickets[$group_key] = [
            "ticket_id" => "REF-" . $row['id'], // Falls back to first parsed entry index
            "order_id" => $row['order_id'],
            "amount" => $item_amount, // Starts sum loop counter
            "status" => "Pending",
            "progress" => $progress,
            "details" => $details,
            "date" => date("d M Y, h:i A", strtotime($row['created_at'])),
            "updated_date" => date("d M Y, h:i A", strtotime($row['updated_at'])),
            "reason" => $row['reason'],
            "description" => $row['description'],
            "proof_path" => $row['proof_path'],
            "admin_reply" => $row['admin_reply'] ?? "No reply yet.",
            "items" => [$item_details] // 🌟 Sets up array array key to feed Flutter's item list builder
        ];
    } else {
        $grouped_tickets[$group_key]['amount'] += $item_amount;
        $grouped_tickets[$group_key]['items'][] = $item_details;
    }
}

echo json_encode([
    "status" => "success",
    "data" => array_values($grouped_tickets)
]);

$stmt->close();
$conn->close();
?>