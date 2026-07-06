<?php
// cancel_refund.php
header("Content-Type: application/json");
include 'db.php';

if (!isset($_POST['order_id']) || !isset($_POST['ticket_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters"]);
    exit;
}

$order_id = $_POST['order_id'];
$ticket_string = $_POST['ticket_id'];
$ticket_id = intval(str_replace("REF-", "", $ticket_string));

// Begin Transaction
$conn->begin_transaction();

try {
    $time_stmt = $conn->prepare("SELECT created_at FROM refund_requests WHERE id = ? AND order_id = ?");
    $time_stmt->bind_param("is", $ticket_id, $order_id);
    $time_stmt->execute();
    $time_result = $time_stmt->get_result()->fetch_assoc();
    $time_stmt->close();

    if (!$time_result) {
        throw new Exception("Target refund ticket record could not be found.");
    }

    $created_at = $time_result['created_at'];

    $updateOrder = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE order_id = ?");
    $updateOrder->bind_param("s", $order_id);
    $updateOrder->execute();
    $updateOrder->close();

    $updateRefund = $conn->prepare("UPDATE refund_requests SET status = 'Cancelled' WHERE order_id = ? AND created_at = ?");
    $updateRefund->bind_param("ss", $order_id, $created_at);
    $updateRefund->execute();
    $updateRefund->close();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Refund bundle cancelled successfully"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Transaction failed: " . $e->getMessage()]);
}

$conn->close();
?>