<?php
// delete_wishlist.php
header("Content-Type: application/json");
include 'db.php';

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($user_id > 0 && $product_id > 0) {
    // Prepare the script statement to remove the matched record completely
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Product removed from your wishlist.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to delete row from database table.'
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Missing or invalid parameters.'
    ]);
}
$conn->close();
?>