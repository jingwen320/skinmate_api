<?php
// submit_refund.php
header("Content-Type: application/json");
include 'db.php';

if (!isset($_POST['order_id']) || !isset($_POST['user_id']) || !isset($_POST['items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data parameters.']);
    exit;
}

$order_id     = $_POST['order_id'];
$user_id      = intval($_POST['user_id']);
$reason       = $_POST['reason'] ?? '';
$desc         = $_POST['description'] ?? '';
$total_amount = floatval($_POST['total_amount'] ?? 0);

$items = json_decode($_POST['items'], true);
if (empty($items) || !is_array($items)) {
    echo json_encode(['status' => 'error', 'message' => 'No items were selected for this return request.']);
    exit;
}

$target_dir = "uploads/refunds/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$uploaded_paths = [];

if (!empty($_FILES['proof']['name'][0])) {
    foreach ($_FILES['proof']['name'] as $index => $name) {
        if ($_FILES['proof']['error'][$index] === UPLOAD_ERR_OK) {
            $file_extension = pathinfo($name, PATHINFO_EXTENSION);
            
            $new_filename = "refund_" . date("Ymd_His") . "_" . uniqid() . "_" . $index . "." . $file_extension;
            $file_path = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES['proof']['tmp_name'][$index], $file_path)) {
                $uploaded_paths[] = $file_path;
            }
        }
    }
}

if (empty($uploaded_paths)) {
    echo json_encode(['status' => 'error', 'message' => 'At least one proof document attachment is required.']);
    exit;
}

$proof_paths_string = implode(",", $uploaded_paths);

$stmt = $conn->prepare("INSERT INTO refund_requests (order_id, product_id, user_id, reason, description, quantity, refund_amount, proof_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$success_count = 0;

foreach ($items as $item) {
    $product_id = intval($item['product_id']);
    $qty        = intval($item['quantity']);
    $amount     = floatval($item['amount']);

    $stmt->bind_param("siissids", $order_id, $product_id, $user_id, $reason, $desc, $qty, $amount, $proof_paths_string);
    
    if ($stmt->execute()) {
        $success_count++;
    }
}

if ($success_count > 0) {
    $update = $conn->prepare("UPDATE orders SET status = 'Refund Processing' WHERE order_id = ?");
    $update->bind_param("s", $order_id);
    $update->execute();

    echo json_encode([
        'status' => 'success', 
        'message' => "$success_count items compiled onto refund request successfully.",
        'files_attached' => count($uploaded_paths)
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database ingestion loops broken entirely.']);
}

$stmt->close();
$conn->close();
?>