<?php
// get_recommendation_history.php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$user_id_raw = $_POST['user_id'] ?? $data['user_id'] ?? null;

if ($user_id_raw) {
    $user_id = intval($user_id_raw);

    $stmt = $conn->prepare("
        SELECT h.match_score, h.created_at, 
               p.brand, p.name, p.category, p.price, p.image AS product_image,
               s.image AS scan_image, s.skin_type
        FROM recommendation_history h
        JOIN products p ON h.product_id = p.id
        JOIN skin_progress s ON h.skin_id = s.id
        WHERE h.user_id = ?
        ORDER BY h.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            "match_score"   => intval($row['match_score']), 
            "created_at"    => $row['created_at'],
            "brand"         => $row['brand'],
            "name"          => $row['name'],
            "category"      => $row['category'],
            "price"         => floatval($row['price']),     
            "product_image" => $row['product_image'],
            "scan_image"    => $row['scan_image'],
            "skin_type"     => $row['skin_type']
        ];
    }

    echo json_encode(["status" => "success", "history" => $history]);
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing user_id parameter tracking attribute."]);
}
?>