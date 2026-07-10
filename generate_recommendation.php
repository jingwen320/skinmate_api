<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
include 'db.php'; 

$data = json_decode(file_get_contents("php://input"), true);
$user_id = intval($_POST['user_id'] ?? $data['user_id'] ?? 0);
$target_budget = floatval($_POST['target_budget'] ?? $data['target_budget'] ?? 0);

if ($user_id > 0 && $target_budget > 0) {
    try {
        // Fetch latest scan
        $stmt = $conn->prepare("SELECT * FROM skin_progress WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $scan = $stmt->get_result()->fetch_assoc();
        
        if (!$scan) throw new Exception("No scan found.");

        $metrics = [
            'acne'         => floatval($scan['acne_pct']),
            'dark spots'   => floatval($scan['dark_spots_pct']),
            'pigmentation' => floatval($scan['pigmentation_pct']),
            'pores'        => floatval($scan['pores_pct']),
            'redness'      => floatval($scan['redness_pct']),
            'wrinkles'     => floatval($scan['wrinkles_pct'])
        ];

        // 1. Identify all concerns below 90%, or top 3 lowest
        $target_concerns = [];
        foreach ($metrics as $condition => $score) {
            if ($score < 90.0) $target_concerns[] = $condition;
        }
        if (empty($target_concerns)) {
            asort($metrics);
            $target_concerns = array_slice(array_keys($metrics), 0, 3);
        }

        // 2. Build Query
        $case_sql = "";
        foreach ($target_concerns as $concern) {
            $case_sql .= "WHEN LOWER(description) LIKE '%" . $conn->real_escape_string($concern) . "%' THEN 12 ";
        }

        $query = "SELECT *, (10 + (CASE " . (!empty($case_sql) ? $case_sql : "WHEN 1=0 THEN 0") . " ELSE 0 END)) AS match_score
                  FROM products WHERE price <= ? AND stock_quantity > 0
                  AND (LOWER(skin_suitability) = 'all' OR LOWER(skin_suitability) = LOWER(?))
                  ORDER BY match_score DESC, sold_quantity DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ds", $target_budget, $scan['skin_type']);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                "id" => intval($row['id']),
                "brand" => $row['brand'],
                "name" => $row['name'],
                "category" => $row['category'],
                "price" => floatval($row['price']),
                "image" => $row['image'],
                "match_score" => intval(min(100, 60 + ($row['match_score'] * 2.5)))
            ];
        }

        // 3. Save History (No deletion, just appending)
        $insert_stmt = $conn->prepare("INSERT INTO recommendation_history (user_id, skin_id, product_id, match_score) VALUES (?, ?, ?, ?)");
        foreach ($products as $prod) {
            $insert_stmt->bind_param("iiii", $user_id, $scan['id'], $prod['id'], $prod['match_score']);
            $insert_stmt->execute();
        }

        echo json_encode([
            "status" => "success",
            "detected_skin_type" => $scan['skin_type'],
            "detected_concerns" => $target_concerns,
            "recommendations" => $products
        ]);

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>