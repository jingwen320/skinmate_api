<?php
header('Content-Type: application/json');

// 1. Configuration
$pythonPath = "/opt/anaconda3/envs/skinmate_env/bin/python";
$scriptPath = "/Applications/XAMPP/xamppfiles/htdocs/skinmate_api/models/predict.py";

// 2. Get the image from the Flutter request
if (isset($_FILES['image']['tmp_name'])) {
    $uploadDir = "uploads/";
    $targetFile = $uploadDir . basename($_FILES['image']['name']);
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        
        // 3. Execute the Python script
        // We use 'exec' instead of 'shell_exec' to get an array of lines
        $command = "$pythonPath $scriptPath " . escapeshellarg($targetFile) . " 2>&1";
        exec($command, $outputArray, $returnCode);

        // 4. Grab the VERY LAST line (the prediction)
        $prediction = trim(end($outputArray));

        // 5. Return JSON to Flutter
        echo json_encode([
            "status" => "success",
            "prediction" => $prediction
        ]);
        
    } else {
        echo json_encode(["status" => "error", "message" => "Upload failed"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No image provided"]);
}
?>