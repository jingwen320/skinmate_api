<?php
// admin/backend/process_refund.php
session_start();
include '../../db.php';

// 1. Authorization Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 2. Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['refund_id'], $_POST['status'], $_POST['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$refund_id = (int)$_POST['refund_id'];
$new_status = $_POST['status']; // Expected: 'Approved' or 'Rejected'
$order_id = $conn->real_escape_string($_POST['order_id']);
$admin_reply = isset($_POST['admin_reply']) ? $conn->real_escape_string($_POST['admin_reply']) : '';

$stmt_get_time = $conn->prepare("SELECT created_at FROM refund_requests WHERE id = ?");
$stmt_get_time->bind_param("i", $refund_id);
$stmt_get_time->execute();
$result = $stmt_get_time->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Refund request not found.']);
    exit;
}
$target_created_at = $row['created_at'];

// Map refund status to orders status
$order_status = ($new_status === 'Approved') ? 'Refunded' : 'Refund Rejected';

// 3. Begin Transaction to ensure data integrity
$conn->begin_transaction();

try {
    $order_status = null;
    if ($new_status === 'Approved') {
        $order_status = 'Refunded';
    } elseif ($new_status === 'Rejected') {
        $order_status = 'Refund Rejected';
    }

    // A. Update the Refund Request status and Admin Reply
    $stmt1 = $conn->prepare("UPDATE refund_requests SET status = ?, admin_reply = ? WHERE order_id = ? AND created_at = ?");
    $stmt1->bind_param("ssss", $new_status, $admin_reply, $order_id, $target_created_at);
    if (!$stmt1->execute()) throw new Exception("Failed to update refund request.");

    // B. Update the Order status ONLY if it's a final decision
    if ($order_status !== null) {
        $stmt2 = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt2->bind_param("ss", $order_status, $order_id);
        if (!$stmt2->execute()) throw new Exception("Failed to update order status.");
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);

} catch (Exception $e) {
    // Rollback if anything fails
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>