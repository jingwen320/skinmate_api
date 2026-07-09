<?php
// admin/backend/update_order_status.php 
header('Content-Type: application/json');
include '../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'update_order') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid endpoint access protocol rejection.']);
    exit;
}

$order_table_id = intval($_POST['id']);
$status = trim($_POST['status']);
$note = trim($_POST['note'] ?? '');

// Fetch structural parameters necessary for tracking alerts (Added status to selection)
$fetch_stmt = $conn->prepare("SELECT user_id, order_id, status AS current_status FROM orders WHERE id = ?");
$fetch_stmt->bind_param("i", $order_table_id);
$fetch_stmt->execute();
$order_meta = $fetch_stmt->get_result()->fetch_assoc();
$fetch_stmt->close();

if (!$order_meta) {
    echo json_encode(['status' => 'error', 'message' => 'Target database record entry missing.']);
    exit;
}

$user_id = $order_meta['user_id'];
$custom_ref = $order_meta['order_id'];
$current_status = $order_meta['current_status'];

// Check if only the note is changing, while the status remains exactly the same
$is_status_changed = ($status !== $current_status);

// Begin isolated transaction block
$conn->begin_transaction();

try {
    // 1. Update the order table (status and note)
    $update_stmt = $conn->prepare("UPDATE orders SET status = ?, note = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $status, $note, $order_table_id);
    $update_stmt->execute();
    $update_stmt->close();

    // 2. Only generate and send notification if the status actually changed
    if ($is_status_changed) {
        
        $refund_display_amount = "";
        if (in_array($status, ['Refund Processing', 'Refunded'])) {
            $ref_stmt = $conn->prepare("SELECT refund_amount FROM refund_requests WHERE order_id = ? LIMIT 1");
            $ref_stmt->bind_param("s", $custom_ref);
            $ref_stmt->execute();
            $ref_res = $ref_stmt->get_result()->fetch_assoc();
            if ($ref_res) {
                $refund_display_amount = " of RM " . number_format($ref_res['refund_amount'], 2);
            }
            $ref_stmt->close();
        }
        // ---------------------------------------------------------------

        $title = "Order Status Update";
        switch ($status) {
            case 'Processing':
                $message = "Your order #$custom_ref is currently being packed and processed.";
                break;
            case 'In Transit':
                $message = "Great news! Your package for order #$custom_ref has been shipped out and is now in transit.";
                break;
            case 'Delivered':
                $message = "Your package for order #$custom_ref has arrived at your shipping address destination.";
                break;
            case 'Completed':
                $message = "Order #$custom_ref has been marked as fully completed. Thank you for choosing SkinMate!";
                break;
            case 'Cancelled':
                $title = "Order Cancelled";
                $message = "Your order #$custom_ref has been cancelled. The refund is being processed and will reflect in your account within 3 to 5 working days.";
                break;
            case 'Refund Processing':
                $title = "Refund Initiation";
                $message = "We have received your request. The refund{$refund_display_amount} for order #$custom_ref is now processing.";
                break;
            case 'Refunded':
                $title = "Refund Approved";
                $message = "The total sum{$refund_display_amount} for order #$custom_ref has been successfully credited to your payment account.";
                break;
            case 'Refund Rejected':
                $title = "Refund Request Declined";
                $message = "Your refund request for order #$custom_ref could not be approved as it did not meet our refund policy criteria. Please check your email or contact support for details.";
                break;
            default:
                $title = "Order Status Update";
                $message = "The status of your order #$custom_ref has been changed to: $status.";
                break;
        }

        $noti_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, is_read) VALUES (?, ?, ?, 0)");
        $noti_stmt->bind_param("iss", $user_id, $title, $message);
        $noti_stmt->execute();
        $noti_stmt->close();
        
        $response_message = 'Order state and notification synchronized successfully.';
    } else {
        $response_message = 'Administrative remarks updated successfully. Logistics state unchanged.';
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => $response_message]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Transaction processing failure: ' . $e->getMessage()]);
}
exit;