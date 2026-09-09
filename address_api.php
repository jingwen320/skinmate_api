<?php
// address_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? $_GET['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing user_id']);
    exit;
}

switch ($action) {
    case 'get':
        $stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $addresses = [];
        while ($row = $res->fetch_assoc()) {
            $addresses[] = $row;
        }
        echo json_encode(['status' => 'success', 'addresses' => $addresses]);
        break;

    case 'save':
        $id = $data['id'] ?? null;
        $recipient_name = $data['recipient_name'] ?? '';
        $phone = $data['phone'] ?? '';
        $address_line_1 = $data['address_line_1'] ?? '';
        $address_line_2 = $data['address_line_2'] ?? null;
        $postcode = $data['postcode'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $region = $data['region'] ?? 'Malaysia';
        $is_default = !empty($data['is_default']) ? 1 : 0;

        if (!$recipient_name || !$phone || !$address_line_1 || !$postcode || !$city || !$state) {
            echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
            exit;
        }

        if ($is_default) {
            $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = " . (int)$user_id);
        }

        if ($id) {
            $stmt = $conn->prepare("UPDATE user_addresses SET recipient_name = ?, phone = ?, address_line_1 = ?, address_line_2 = ?, postcode = ?, city = ?, state = ?, region = ?, is_default = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssssssssiii", $recipient_name, $phone, $address_line_1, $address_line_2, $postcode, $city, $state, $region, $is_default, $id, $user_id);
            $stmt->execute();
        } else {
            $chk = $conn->query("SELECT COUNT(*) as cnt FROM user_addresses WHERE user_id = " . (int)$user_id);
            if ($chk->fetch_assoc()['cnt'] == 0) {
                $is_default = 1;
            }

            $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, recipient_name, phone, address_line_1, address_line_2, postcode, city, state, region, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssi", $user_id, $recipient_name, $phone, $address_line_1, $address_line_2, $postcode, $city, $state, $region, $is_default);
            $stmt->execute();
        }
        echo json_encode(['status' => 'success']);
        break;

    case 'delete':
        $id = $data['id'] ?? null;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        }
        break;

    case 'set_default':
        $id = $data['id'] ?? null;
        if ($id) {
            $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = " . (int)$user_id);
            $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            $stmt->execute();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>