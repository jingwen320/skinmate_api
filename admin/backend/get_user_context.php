<?php
// admin/backend/get_user_context.php
header('Content-Type: application/json');
include '../../db.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing User Parameter ID"]);
    exit;
}

$userId = (int)$_GET['user_id'];

// 1. Fetch User Meta info and registration details
$userQuery = "SELECT created_at FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userResult) {
    echo json_encode(["status" => "error", "message" => "Target profile user not found"]);
    exit;
}

// Format Join Date gracefully (e.g., "Joined on May 12, 2026")
$joinedDate = "Joined on " . date("M j, Y", strtotime($userResult['created_at']));

// 2. Fetch Order Statistics (Count & Lifetime Total)
$orderQuery = "SELECT COUNT(id) AS total_orders, COALESCE(SUM(final_total), 0) AS lifetime_value 
               FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$orderResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3. Fetch Latest Entry from Skin Progress Analytics
$skinQuery = "SELECT skin_type, health_score, acne_pct, dark_spots_pct, pigmentation_pct, 
                     pores_pct, redness_pct, wrinkles_pct 
              FROM skin_progress WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($skinQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$skinResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fallback defaults if the user has never completed an analytical scan cycle yet
$skinData = $skinResult ? [
    "skin_type" => $skinResult['skin_type'] ?? 'UNDETERMINED',
    "health_score" => (int)$skinResult['health_score'],
    "metrics" => [
        "Acne" => (int)$skinResult['acne_pct'],
        "Dark Spots" => (int)$skinResult['dark_spots_pct'],
        "Pigmentation" => (int)$skinResult['pigmentation_pct'],
        "Pores" => (int)$skinResult['pores_pct'],
        "Redness" => (int)$skinResult['redness_pct'],
        "Wrinkles" => (int)$skinResult['wrinkles_pct']
    ]
] : null;

echo json_encode([
    "status" => "success",
    "joined" => $joinedDate,
    "total_orders" => (int)$orderResult['total_orders'],
    "lifetime_value" => "RM " . number_format((float)$orderResult['lifetime_value'], 2),
    "skin_profile" => $skinData
]);

$conn->close();
?>