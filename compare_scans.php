<?php
// compare_scans.php
include 'db.php';

header('Content-Type: application/json');

$scan_id_1 = $_GET['scan_id_1'] ?? null;
$scan_id_2 = $_GET['scan_id_2'] ?? null;

if (!$scan_id_1 || !$scan_id_2) {
    echo json_encode(['status' => 'error', 'message' => 'Missing scan IDs for comparison.']);
    exit;
}

$sql = "SELECT id, user_id, image, skin_type, health_score, 
               acne_pct, dark_spots_pct, pigmentation_pct, 
               pores_pct, redness_pct, wrinkles_pct, 
               created_at 
        FROM skin_progress 
        WHERE id IN (?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $scan_id_1, $scan_id_2);
$stmt->execute();
$result = $stmt->get_result();

$scans = [];
while ($row = $result->fetch_assoc()) {
    $row['conditions'] = [
        'Acne' => (float)$row['acne_pct'],
        'Dark Spots' => (float)$row['dark_spots_pct'],
        'Pigmentation' => (float)$row['pigmentation_pct'],
        'Pores' => (float)$row['pores_pct'],
        'Redness' => (float)$row['redness_pct'],
        'Wrinkles' => (float)$row['wrinkles_pct'],
    ];

    unset($row['acne_pct'], $row['dark_spots_pct'], $row['pigmentation_pct'], 
          $row['pores_pct'], $row['redness_pct'], $row['wrinkles_pct']);

    $scans[] = $row;
}

if (count($scans) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'One or both scans could not be found.']);
    exit;
}

// Order chronologically (older first, newer second)
usort($scans, function($a, $b) {
    return strtotime($a['created_at']) - strtotime($b['created_at']);
});

$older = $scans[0];
$newer = $scans[1];

// Health score change (higher marks = better)
$health_score_diff = (float)$newer['health_score'] - (float)$older['health_score'];

$condition_diffs = [];
foreach ($older['conditions'] as $key => $old_val) {
    $new_val = $newer['conditions'][$key];
    $condition_diffs[$key] = [
        'older' => $old_val,
        'newer' => $new_val,
        'difference' => round($new_val - $old_val, 2) 
    ];
}

echo json_encode([
    'status' => 'success',
    'older_scan' => $older,
    'newer_scan' => $newer,
    'health_score_change' => round($health_score_diff, 2),
    'condition_changes' => $condition_diffs
]);
?>