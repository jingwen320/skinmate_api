<?php
// apply_voucher.php
date_default_timezone_set('Asia/Kuala_Lumpur');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$voucherCode = isset($input['voucher_code']) ? trim($input['voucher_code']) : '';
$cartSubtotal = isset($input['cart_subtotal']) ? floatval($input['cart_subtotal']) : 0.0;

if (!$userId || !$voucherCode) {
    echo json_encode(['status' => 'error', 'message' => 'User ID and voucher code are required']);
    exit;
}

// 1. Fetch voucher details
$stmt = $conn->prepare("SELECT voucher_id, discount_type, discount_value, status, expires_at FROM user_vouchers WHERE user_id = ? AND voucher_code = ?");
$stmt->bind_param("is", $userId, $voucherCode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid voucher code']);
    $stmt->close();
    $conn->close();
    exit;
}

$voucher = $result->fetch_assoc();
$stmt->close();

// 2. Validate voucher status and expiration
if ($voucher['status'] === 'used') {
    echo json_encode(['status' => 'error', 'message' => 'This voucher has already been used']);
    $conn->close();
    exit;
}

if ($voucher['status'] === 'expired' || strtotime($voucher['expires_at']) < time()) {
    // Force status to expired if not already updated
    if ($voucher['status'] !== 'expired') {
        $upd = $conn->prepare("UPDATE user_vouchers SET status = 'expired' WHERE voucher_id = ?");
        $upd->bind_param("i", $voucher['voucher_id']);
        $upd->execute();
        $upd->close();
    }
    echo json_encode(['status' => 'error', 'message' => 'This voucher has expired']);
    $conn->close();
    exit;
}

// 3. Compute discount (10% percentage calculation)
$discountAmount = 0.0;
$type = $voucher['discount_type'];
$val = floatval($voucher['discount_value']);

if ($type === 'percentage') {
    $discountAmount = $cartSubtotal * ($val / 100.0);
} elseif ($type === 'fixed') {
    $discountAmount = $val;
} elseif ($type === 'free_shipping') {
    $discountAmount = 0.0; // Handled as shipping waiver
}

if ($discountAmount > $cartSubtotal) {
    $discountAmount = $cartSubtotal;
}

$conn->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Voucher applied successfully!',
    'voucher_code' => $voucherCode,
    'discount_type' => $type,
    'discount_value' => $val,
    'calculated_discount' => round($discountAmount, 2)
]);
?>