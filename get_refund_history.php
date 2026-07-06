<?php
// get_refund_history.php
header("Content-Type: application/json");
include 'db.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing user_id"]);
    exit;
}

$user_id = intval($_GET['user_id']);

$sql = "SELECT rr.id, rr.order_id, rr.refund_amount, o.status as order_status, rr.status as refund_status,
               rr.created_at, rr.updated_at, rr.reason, rr.description, rr.admin_reply, rr.proof_path, rr.quantity,
               p.name as product_name, p.image as product_image, p.brand as product_brand
        FROM refund_requests rr
        LEFT JOIN orders o ON rr.order_id = o.order_id
        LEFT JOIN products p ON rr.product_id = p.id
        WHERE rr.user_id = ? 
          AND (o.status IN ('Refunded', 'Refund Rejected') 
               OR rr.status = 'Cancelled')
        ORDER BY rr.created_at DESC, rr.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$grouped_history = [];

while ($row = $result->fetch_assoc()) {
    $group_key = $row['order_id'] . "_" . strtotime($row['created_at']);

    $item_amount = floatval($row['refund_amount']);
    $item_qty = intval($row['quantity'] ?? 1);

    $item_details = [
        "name" => $row['product_name'] ?? "SkinMate Item",
        "brand" => $row['product_brand'] ?? "SkinMate",
        "image" => $row['product_image'],
        "quantity" => $item_qty,
        "amount" => $item_amount
    ];

    if (!isset($grouped_history[$group_key])) {
        $displayStatus = "Cancelled";
        $details = "You cancelled this refund request.";

        if ($row['refund_status'] === 'Cancel' || $row['refund_status'] === 'Cancelled') {
            $displayStatus = "Cancelled";
            $details = "You cancelled this refund request.";
        } elseif ($row['order_status'] === 'Refunded') {
            $displayStatus = "Approved";
            $details = "Funds have been successfully credited back to your account.";
        } elseif ($row['order_status'] === 'Refund Rejected') {
            $displayStatus = "Rejected";
            $details = "Your refund request was declined by the administrator.";
        }

        $grouped_history[$group_key] = [
            "ticket_id" => "REF-" . $row['id'],
            "order_id" => $row['order_id'],
            "amount" => $item_amount, // Starts sum loop counter
            "status" => $displayStatus,
            "progress" => 1.0, 
            "details" => $details,
            "date" => date("d M Y, h:i A", strtotime($row['created_at'])),
            "updated_date" => date("d M Y, h:i A", strtotime($row['updated_at'])),
            "reason" => $row['reason'],
            "description" => $row['description'],
            "proof_path" => $row['proof_path'],
            "admin_reply" => $row['admin_reply'] ?? "No reply yet.",
            "items" => [$item_details] // 🌟 Sets up items array map collection
        ];
    } else {
        $grouped_history[$group_key]['amount'] += $item_amount;
        $grouped_history[$group_key]['items'][] = $item_details;
    }
}

echo json_encode([
    "status" => "success",
    "data" => array_values($grouped_history) 
]);

$stmt->close();
$conn->close();
?>