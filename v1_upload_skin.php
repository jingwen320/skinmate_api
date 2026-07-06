<?php
include 'db.php';

header('Content-Type: application/json');

// Check user_id
$user_id = $_POST['user_id'] ?? '';
if (!$user_id) {
    echo json_encode(['status'=>'error','message'=>'Missing user_id']);
    exit;
}

// Check file
if (!isset($_FILES['image'])) {
    echo json_encode(['status'=>'error','message'=>'No file uploaded']);
    exit;
}

if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status'=>'error','message'=>'File upload error: ' . $_FILES['image']['error']]);
    exit;
}

// Create uploads folder if not exists
$folder = __DIR__ . "/uploads/";
if (!is_dir($folder)) mkdir($folder, 0777, true);

// Save file
$originalName = basename($_FILES['image']['name']);
$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$imagePath = "uploads/skin_" . time() . "_" . rand(1000,9999) . "." . $ext;

if (!move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . "/" . $imagePath)) {
    echo json_encode(['status'=>'error','message'=>'Failed to move uploaded file']);
    exit;
}

// Assign skin type and conditions
$skin_type = 'Oily';
$skin_conditions = json_encode([
    'redness' => false,
    'wrinkles' => false,
    'pores' => true
]);

// Insert into database
$stmt = $conn->prepare("INSERT INTO skin_progress (user_id, image, skin_type, skin_conditions) VALUES (?,?,?,?)");
$stmt->bind_param("isss", $user_id, $imagePath, $skin_type, $skin_conditions);

if ($stmt->execute()) {
    echo json_encode([
        'status'=>'success',
        'skin_id'=>$stmt->insert_id,
        'skin_type'=>$skin_type,
        'skin_conditions'=>$skin_conditions,
        'image_path'=>$imagePath
    ]);
} else {
    echo json_encode(['status'=>'error','message'=>'Failed to insert into database']);
}
?>





<!-- Version 2 -->
 <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

header('Content-Type: application/json');

// Check user_id
$user_id = $_POST['user_id'] ?? '';
if (!$user_id) {
    echo json_encode(['status'=>'error','message'=>'Missing user_id']);
    exit;
}

// Check file
if (!isset($_FILES['image'])) {
    echo json_encode(['status'=>'error','message'=>'No file uploaded']);
    exit;
}

if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status'=>'error','message'=>'File upload error: ' . $_FILES['image']['error']]);
    exit;
}

// Create uploads folder if not exists
$folder = __DIR__ . "/uploads/";
if (!is_dir($folder)) mkdir($folder, 0777, true);

// Save file
$originalName = basename($_FILES['image']['name']);
$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$imagePath = "uploads/skin_" . time() . "_" . rand(1000,9999) . "." . $ext;

$fullImagePath = __DIR__ . "/" . $imagePath;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $fullImagePath)) {
    echo json_encode(['status'=>'error','message'=>'Failed to save image']);
    exit;
}

// --- 4. AI INFERENCE ENGINE ---
$pythonPath = "/opt/anaconda3/envs/skinmate_env/bin/python"; 
$pythonScript = realpath(__DIR__ . "/models/predict.py"); 

exec("$pythonPath $pythonScript " . escapeshellarg($fullImagePath) . " 2>&1", $outputArray);

// 1. Log everything for your own debugging (check XAMPP error_log)
error_log("Raw Python Output: " . implode(" | ", $outputArray));

// 2. Filter out the DEBUG lines and any empty whitespace
$allowed_types = ['Oily', 'Dry', 'Normal', 'Combination'];
$skin_type = "Analysis Failed"; // Default

foreach ($outputArray as $line) {
    $trimmedLine = trim($line);
    if (in_array($trimmedLine, $allowed_types)) {
        $skin_type = $trimmedLine;
        break; // Stop as soon as we find a valid skin type
    }
}

// 5. Hardcoded Logic for conditions (can be AI-driven later)
$skin_conditions_data = [
    'redness' => false,
    'wrinkles' => false,
    'pores' => ($skin_type === 'Oily') 
];
$skin_conditions = json_encode($skin_conditions_data);

// 6. Database Insertion
$stmt = $conn->prepare("INSERT INTO skin_progress (user_id, image, skin_type, skin_conditions) VALUES (?,?,?,?)");
$stmt->bind_param("isss", $user_id, $imagePath, $skin_type, $skin_conditions);

if ($stmt->execute()) {
    echo json_encode([
        'status'=>'success',
        'skin_id'=>$stmt->insert_id,
        'skin_type'=>$skin_type,
        'skin_conditions'=>$skin_conditions_data, 
        'image_path'=>$imagePath,
    ]);
} else {
    echo json_encode(['status'=>'error', 'message'=>'Failed to insert into database']);
}
?>