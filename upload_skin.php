<?php
header('Content-Type: application/json');
include 'db.php'; // Ensure your database connection is correct

// 1. Validation
$user_id = $_POST['user_id'] ?? null;
if (!$user_id || !isset($_FILES['image'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data or image']);
    exit;
}

// 2. File Handling
$folder = __DIR__ . "/uploads/";
if (!is_dir($folder)) mkdir($folder, 0777, true);

$imagePath = "uploads/skin_" . time() . "_" . rand(1000, 9999) . ".jpg";
$fullImagePath = __DIR__ . "/" . $imagePath;

if (move_uploaded_file($_FILES['image']['tmp_name'], $fullImagePath)) {
    
    // 3. AI Inference Engine
    $pythonPath = "/opt/anaconda3/envs/skinmate_env/bin/python"; 
    $pythonScript = realpath(__DIR__ . "/models/predict.py"); 
    
    // Execute Python script and capture JSON output
    $command = "$pythonPath $pythonScript " . escapeshellarg($fullImagePath) . " 2>&1";
    exec($command, $outputArray);
    
    // Find the JSON line in the output (in case of logs/warnings)
    $jsonResult = null;
    foreach ($outputArray as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded) && isset($decoded['status'])) {
            $jsonResult = $decoded;
            break;
        }
    }

    // if (!$jsonResult || $jsonResult['status'] === 'error') {
    //     echo json_encode(['status' => 'error', 'message' => 'AI Analysis failed', 'debug' => $outputArray]);
    //     exit;
    // }

    if (!$jsonResult || $jsonResult['status'] === 'error') {
        $errorMessage = $jsonResult['message'] ?? 'AI Analysis failed';
        echo json_encode([
            'status' => 'error', 
            'message' => $errorMessage, 
            'debug' => $outputArray
        ]);
        exit;
    }

    // 4. Extract Data
    $skin_type = $jsonResult['skin_type'];
    $c = $jsonResult['conditions']; // The 6 condition percentages

    // 5. Calculate Health Score
    // Logic: Average of all negative conditions subtracted from 1.0
    // 
    
    $health_score = $jsonResult['health_score'];

    // 6. Database Insertion
    // Note: Ensure your 'skin_progress' table has these columns!
    $sql = "INSERT INTO skin_progress (
                user_id, image, skin_type, health_score, 
                acne_pct, dark_spots_pct, pigmentation_pct, 
                pores_pct, redness_pct, wrinkles_pct
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issidddddd", 
        $user_id, $imagePath, $skin_type, $health_score,
        $c['Acne'], $c['Dark Spots'], $c['Pigmentation'], 
        $c['Pores'], $c['Redness'], $c['Wrinkles']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'skin_id' => $stmt->insert_id,
            'skin_type' => $skin_type,
            'health_score' => $health_score,
            'conditions' => $c, 
            'image_path' => $imagePath,
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $stmt->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save image']);
}
?>