<?php
include 'db.php';

$user_id = $_GET['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
    exit;
}

// 🎯 IMPROVED QUERY: Separated the joins to ensure reminders always load 
// even if the user has NO skin_progress images yet.
$query = "
    SELECT 
        u.id, u.name, u.email, 
        sp.image as latest_image,
        sp.skin_type,
        sp.health_score,
        sp.acne_pct, sp.dark_spots_pct, sp.pigmentation_pct, 
        sp.pores_pct, sp.redness_pct, sp.wrinkles_pct,
        sp.created_at,
        r.morning_enabled, r.morning_time, 
        r.evening_enabled, r.evening_time
    FROM users u
    LEFT JOIN user_reminders r ON u.id = r.user_id
    LEFT JOIN (
        SELECT * 
        FROM skin_progress 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ) sp ON u.id = sp.user_id
    WHERE u.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $profile_pic = null;
    if (!empty($row['latest_image'])) {
        // $profile_pic = "http://192.168.101.170/skinmate_api/" . $row['latest_image'];
        // $profile_pic = "http://192.168.0.22/skinmate_api/" . $row['latest_image'];
        // $profile_pic = "http://10.0.2.2/skinmate_api/" . $row['latest_image'];
        // $profile_pic = "https://carwash-manhandle-sprinkler.ngrok-free.dev/skinmate_api/" . $row['latest_image'];
        $profile_pic = "https://library-valium-riverboat.ngrok-free.dev/skinmate_api/" . $row['latest_image'];
    }

    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'profile_pic' => $profile_pic,
            'skin_type' => $row['skin_type'] ?? 'Not Analyzed',
            'health_score' => (int)($row['health_score'] ?? 0),
            'acne_pct' => (float)($row['acne_pct'] ?? 0),
            'dark_spots_pct' => (float)($row['dark_spots_pct'] ?? 0),
            'pigmentation_pct' => (float)($row['pigmentation_pct'] ?? 0),
            'pores_pct' => (float)($row['pores_pct'] ?? 0),
            'redness_pct' => (float)($row['redness_pct'] ?? 0),
            'wrinkles_pct' => (float)($row['wrinkles_pct'] ?? 0),
            'created_at' => $row['created_at'] ?? null,
            // 🕒 Cast 1/0 to true/false for Flutter ease, or send as is
            'morning_enabled' => (int)($row['morning_enabled'] ?? 0),
            'morning_time' => $row['morning_time'] ?? "08:00:00",
            'evening_enabled' => (int)($row['evening_enabled'] ?? 0),
            'evening_time' => $row['evening_time'] ?? "20:00:00"
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}
?>