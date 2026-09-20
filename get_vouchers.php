<?php
// get_vouchers.php
date_default_timezone_set('Asia/Kuala_Lumpur');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
    exit;
}

// 1. Automatically update expired active vouchers
$expireUpdate = $conn->prepare("UPDATE user_vouchers SET status = 'expired' WHERE user_id = ? AND status = 'active' AND expires_at < NOW()");
$expireUpdate->bind_param("i", $userId);
$expireUpdate->execute();
$expireUpdate->close();

// 2. Fetch all vouchers for the user
$stmt = $conn->prepare("SELECT voucher_id, voucher_code, title, discount_type, discount_value, status, expires_at FROM user_vouchers WHERE user_id = ? ORDER BY expires_at ASC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$vouchers = [
    'active' => [],
    'used' => [],
    'expired' => []
];

while ($row = $result->fetch_assoc()) {
    $status = $row['status'];
    if (isset($vouchers[$status])) {
        $vouchers[$status][] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'vouchers' => $vouchers
]);
?>