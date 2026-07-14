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

$stmt_user = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ?");
$stmt_user->bind_param("s", $order_id);
$stmt_user->execute();
$user_res = $stmt_user->get_result()->fetch_assoc();
$user_id = $user_res['user_id'] ?? null;

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

    // C. Notification Logic
    if ($user_id) {
        $total_refund = 0;
        $stmt_refund = $conn->prepare("SELECT SUM(refund_amount) AS total_refund FROM refund_requests WHERE order_id = ? AND created_at = ?");
        $stmt_refund->bind_param("ss", $order_id, $target_created_at);
        $stmt_refund->execute();
        $refund_res = $stmt_refund->get_result()->fetch_assoc();
        $total_refund = $refund_res['total_refund'] ?? 0;
        $stmt_refund->close();

        if ($new_status === 'Approved') {
            $title = "Refund Approved";
            $formatted_amount = number_format($total_refund, 2);
            $message = "The total sum of RM $formatted_amount for order #$order_id has been successfully credited to your payment account.";
        } else {
            $title = "Refund Request Declined";
            $message = "Your refund request for order #$order_id could not be approved as it did not meet our refund policy criteria. Please contact support for details.";
        }
        
        $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, is_read) VALUES (?, ?, ?, 0)");
        $noti_stmt->bind_param("iss", $user_id, $title, $message);
        $noti_stmt->execute();
        $noti_stmt->close();
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);

} catch (Exception $e) {
    // Rollback if anything fails
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>