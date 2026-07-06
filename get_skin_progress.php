<?php
include 'db.php';

header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
    exit;
}

// 1. Fetch the latest progress including the new percentage columns
$sql = "SELECT id, image, skin_type, health_score, 
               acne_pct, dark_spots_pct, pigmentation_pct, 
               pores_pct, redness_pct, wrinkles_pct, 
               created_at 
        FROM skin_progress 
        WHERE user_id = ? 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$progress = [];
while ($row = $result->fetch_assoc()) {
    // 2. Reconstruct the conditions map so the Flutter UI remains compatible
    $row['conditions'] = [
        'Acne' => (float)$row['acne_pct'],
        'Dark Spots' => (float)$row['dark_spots_pct'],
        'Pigmentation' => (float)$row['pigmentation_pct'],
        'Pores' => (float)$row['pores_pct'],
        'Redness' => (float)$row['redness_pct'],
        'Wrinkles' => (float)$row['wrinkles_pct'],
    ];

    // Remove the flat pct columns to keep the JSON clean if preferred
    unset($row['acne_pct'], $row['dark_spots_pct'], $row['pigmentation_pct'], 
          $row['pores_pct'], $row['redness_pct'], $row['wrinkles_pct']);

    $progress[] = $row;
}

echo json_encode($progress);
?>