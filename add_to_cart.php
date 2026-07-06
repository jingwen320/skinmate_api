<?php
include 'db.php';

$user_id       = $_POST['user_id'] ?? '';
$product_id    = $_POST['product_id'] ?? ''; // 🌟 ADDED
$product_name  = $_POST['product_name'] ?? '';
$product_price = $_POST['product_price'] ?? '';
$product_image = $_POST['product_image'] ?? '';
$quantity      = intval($_POST['quantity'] ?? 1);

if (!$user_id || !$product_id || !$product_name || !$product_price) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required product field entries']);
    exit;
}

// 🌟 UPDATED: Check duplicate entries using product_id for precision instead of string names
$check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$check_stmt->bind_param("ii", $user_id, $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Item exists: sum the quantities together
    $new_quantity = $row['quantity'] + $quantity;
    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_quantity, $row['id']);
    
    if ($update_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Cart quantity updated!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update item quantity']);
    }
} else {
    // 🌟 UPDATED: Insert product_id directly into your write statement loop 
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, product_name, product_price, product_image, quantity) VALUES (?, ?, ?, ?, ?, ?)");
    // Type matching signature: iisssi
    $insert_stmt->bind_param("iisssi", $user_id, $product_id, $product_name, $product_price, $product_image, $quantity);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Product successfully added to cart!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database write failure: ' . $insert_stmt->error]);
    }
}
?>