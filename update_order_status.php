<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$order_id = $_POST['order_id'] ?? '';
$status = $_POST['status'] ?? '';

if (!$order_id || !$status) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Fetch user assignment data metrics
    $lookup_stmt = $conn->prepare("SELECT user_id, order_id FROM orders WHERE id = ?");
    $lookup_stmt->bind_param("i", $order_id);
    $lookup_stmt->execute();
    $order_data = $lookup_stmt->get_result()->fetch_assoc();

    if (!$order_data) {
        throw new Exception("Targeted order record tracking sequence not found.");
    }

    $user_id = $order_data['user_id'];
    $custom_ref = $order_data['order_id'];

    // 2. Execute status update execution block
    $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $status, $order_id);
    $update_stmt->execute();

    // 3. Match exact tracking updates
    $title = "Order Status Update";
    switch (trim($status)) {
        Case 'Processing':
            $message = "Your order #$custom_ref is currently being packed and processed.";
            break;
        Case 'In Transit':
            $message = "Great news! Your package for order #$custom_ref has been shipped out and is now in transit.";
            break;
        Case 'Delivered':
            $message = "Your package for order #$custom_ref has arrived at your shipping address destination.";
            break;
        Case 'Completed':
            $message = "Order #$custom_ref has been marked as fully completed. Thank you for choosing SkinMate!";
            break;
        Case 'Cancelled':
            $title = "Order Cancelled";
            $message = "Your order #$custom_ref has been cancelled. The refund is being processed and will reflect in your account within 3 to 5 working days.";
            break;
        Case 'Refund Processing':
            $title = "Refund Initiation";
            $message = "We have received your request. The refund for order #$custom_ref is now processing.";
            break;
        Case 'Refunded':
            $title = "Refund Approved";
            $message = "The total invoice sum for order #$custom_ref has been successfully refunded to your payment account.";
            break;
        Case 'Refund Rejected':
            $title = "Refund Request Declined";
            $message = "Your refund request for order #$custom_ref could not be approved as it did not meet our return policy criteria. Please check your email or contact support for details.";
            break;
        Default:
            $title = "Order Status Update";
            $message = "The status of your order #$custom_ref has been changed to: $status.";
            break;
    }

    // 4. Log the row inside the new notification table
    $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, is_read) VALUES (?, ?, ?, 0)");
    $noti_stmt->bind_param("iss", $user_id, $title, $message);
    $noti_stmt->execute();

    $conn->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>