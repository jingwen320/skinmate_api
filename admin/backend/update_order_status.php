<?php
// admin/backend/update_order_status.php 
header('Content-Type: application/json');
include '../../db.php';

require '../../PHPMailer/Exception.php';
require '../../PHPMailer/PHPMailer.php';
require '../../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize Mailer
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'jingwenng0320@gmail.com';
$mail->Password   = 'ihyq rjjg mmxx odvc';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port       = 465;
$mail->setFrom('jingwenng0320@gmail.com', 'SkinMate Support');

function sendShipmentEmail($to, $subject, $body, $mail) {
    try {
        $mail->clearAddresses();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
    } catch (Exception $e) { /* Log silently */ }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'update_order') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid endpoint access protocol rejection.']);
    exit;
}

$order_table_id = intval($_POST['id']);
$status = trim($_POST['status']);
$note = trim($_POST['note'] ?? '');
$tracking_no = trim($_POST['tracking_no'] ?? '');

if ($status === 'In Transit' && empty($tracking_no)) {
    echo json_encode(['status' => 'error', 'message' => 'Tracking number is required when setting order to In Transit.']);
    exit;
}

// Fetch structural parameters necessary for tracking alerts (Added status to selection)
// $fetch_stmt = $conn->prepare("SELECT user_id, order_id, status AS current_status FROM orders WHERE id = ?");
$fetch_stmt = $conn->prepare("SELECT orders.user_id, orders.order_id, orders.status AS current_status, users.email, users.name FROM orders JOIN users ON orders.user_id = users.id WHERE orders.id = ?");
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
$user_email = $order_meta['email'];
$user_name = $order_meta['name'];

// Check if only the note is changing, while the status remains exactly the same
$is_status_changed = ($status !== $current_status);
$is_tracking_provided = !empty($tracking_no);

// Begin isolated transaction block
$conn->begin_transaction();

try {
    // 1. Update the order table (status and note)
    // $update_stmt = $conn->prepare("UPDATE orders SET status = ?, note = ? WHERE id = ?");
    // $update_stmt->bind_param("ssi", $status, $note, $order_table_id);
    if ($status === 'In Transit') {
        $update_stmt = $conn->prepare("UPDATE orders SET status = ?, note = ?, tracking_no = ?, shipped_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("sssi", $status, $note, $tracking_no, $order_table_id);
    } else {
        $update_stmt = $conn->prepare("UPDATE orders SET status = ?, note = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $status, $note, $order_table_id);
    }
    $update_stmt->execute();
    $update_stmt->close();

    // 2. Only generate and send notification if the status actually changed
    // if ($is_status_changed) {
    if ($is_status_changed || ($status === 'In Transit' && $is_tracking_provided)) {
        
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
            // case 'In Transit':
            //     $message = "Great news! Your package for order #$custom_ref has been shipped out and is now in transit.";
            //     break;
            case 'In Transit':
                $title = "Parcel Shipped";
                $trackingUrl = "https://www.dhl.com/my-en/home/tracking.html?tracking-id=" . urlencode($tracking_no);
                // $message = "Great news! Order #$custom_ref has been shipped via DHL Express. Tracking No: $tracking_no. Track here: $trackingUrl";
                $message = "Great news! Order #$custom_ref has been shipped via DHL Express.";

                if (!empty($user_email)) {
                    $email_subject = "Your SkinMate Order #$custom_ref has been Shipped! (DHL)";
                    $email_body = "<h3>Hi $user_name,</h3>"
                                . "<p>Great news! Your SkinMate parcel for order <b>#$custom_ref</b> is now <b>In Transit</b> with DHL Express.</p>"
                                . "<p><b>DHL Tracking Number:</b> $tracking_no</p>"
                                . "<p><a href='$trackingUrl' style='background: #D40511; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Track Live Parcel</a></p>"
                                . "<p>Thank you for shopping with SkinMate!</p>";
                    
                    sendShipmentEmail($user_email, $email_subject, $email_body, $mail);
                }
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