<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

// Load PHPMailer files manually
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? '';
$subtotal = $data['subtotal'] ?? 0.00;
$discount = $data['discount'] ?? 0.00;
$shipping_fee = $data['shipping_fee'] ?? '0.00'; 
$final_total = $data['final_total'] ?? 0.00;

$name = $data['name'] ?? '';

$incoming_phone = $data['phone'] ?? '';

// 🏠 Extracting Address Fields
$address_1 = $data['address_line_1'] ?? '';
$address_2 = $data['address_line_2'] ?? null;
$postcode = $data['postcode'] ?? '';
$city = $data['city'] ?? '';
$state = $data['state'] ?? '';
$region = $data['region'] ?? '';

if (!$user_id || !$name || !$incoming_phone || !$address_1 || !$postcode || !$city || !$state || !$region) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields or address incomplete']);
    exit;
}

// This securely turns "+60123456789" into raw numeric "60123456789"
$clean_db_phone = preg_replace('/[^0-9]/', '', $incoming_phone);

// Safety guard check: A 60 prefix + 9 or 10 digits means the final database string length must be exactly 11 or 12 characters long.
if (strlen($clean_db_phone) < 11 || strlen($clean_db_phone) > 12) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone digit format sequence encountered.']);
    exit;
}

// Generate the custom editorial invoice tracking reference string
$order_id = 'SK' . $user_id . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));

$conn->begin_transaction();

try {
    // 0. Fetch User Email from DB for the receipt dispatch system 
    $user_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result()->fetch_assoc();
    $user_email = $user_res['email'] ?? null;

    if (!$user_email) {
        throw new Exception("Targeted user identity or email registration record not found.");
    }

    // 1. Insert into main orders table
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_id, status, name, phone, address_line_1, address_line_2, postcode, city, state, region, subtotal, discount_amount, shipping_fee, final_total) VALUES (?, ?, 'Processing', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssssdsd", 
        $user_id, $order_id, $name, $clean_db_phone, $address_1, $address_2, 
        $postcode, $city, $state, $region, 
        $subtotal, $discount, $shipping_fee, $final_total
    );
    $stmt->execute();

    // 2. Fetch the cart items along with their real product_id, stock, and brand
    $cart_query = "
        SELECT c.product_id, c.product_name, c.product_price, c.quantity, p.stock_quantity, p.brand 
        FROM cart c
        INNER JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_items = $cart_stmt->get_result();

    if ($cart_items->num_rows === 0) {
        throw new Exception("Your shopping cart is currently empty.");
    }

    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)");
    $stock_stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ?, sold_quantity = sold_quantity + ? WHERE id = ?");

    $receipt_items_html = ""; 

    // 3. Insert each item into the order_items table and process catalog deductions
    while ($row = $cart_items->fetch_assoc()) {
        $pid = $row['product_id'];
        $qty = (int)$row['quantity'];
        $product_name = $row['product_name'];
        $price = (float)$row['product_price'];
        $current_stock = (int)$row['stock_quantity'];
        
        $brand = !empty($row['brand']) ? strtoupper(trim($row['brand'])) : '';

        // 🛑 SAFETY GUARD: Prevent overselling out of range parameters
        if ($current_stock < $qty) {
            throw new Exception("Inadequate stock available for item: " . $product_name . " (Only " . $current_stock . " left).");
        }

        $item_stmt->bind_param("sisdi", $order_id, $pid, $product_name, $price, $qty);
        $item_stmt->execute();

        $stock_stmt->bind_param("iii", $qty, $qty, $pid);
        $stock_stmt->execute();

        // Format row price figures cleanly
        $formatted_row_total = number_format($price * $qty, 2);

        // Build product table row without image tags
        $receipt_items_html .= "
            <tr>
                <td style='padding: 12px 10px; border-bottom: 1px solid #EAEAEA; vertical-align: middle;'>
                    " . (!empty($brand) ? "<span style='color: #91462E; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; display: block; margin-bottom: 2px;'>{$brand}</span>" : "") . "
                    <span style='font-size: 14px; color: #2E2F2D; font-weight: bold;'>{$product_name}</span>
                    <span style='font-size: 12px; color: #777777; display: block; margin-top: 2px;'>Quantity: {$qty}</span>
                </td>
                <td style='padding: 12px 10px; border-bottom: 1px solid #EAEAEA; text-align: right; vertical-align: middle; font-weight: bold; color: #2E2F2D; font-size: 14px;'>
                    RM {$formatted_row_total}
                </td>
            </tr>";
    }

    // 4. Wipe the cart clean
    $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_stmt->bind_param("i", $user_id);
    $clear_stmt->execute();

    // Commit all operations to the database atomically
    $conn->commit();

    // 5. SEND EMAIL VIA PHPMAILER (Passing address fields directly)
    sendPHPMailerReceipt(
        $user_email, $order_id, $receipt_items_html, $subtotal, $discount, $shipping_fee, $final_total,
        $address_1, $address_2, $postcode, $city, $state, $region, $clean_db_phone, $name
    );

    echo json_encode(['status' => 'success', 'order_id' => $order_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// =========================================================================
// 📬 PHPMAILER ENCRYPTED DISPATCH ENGINE
// =========================================================================
function sendPHPMailerReceipt($to_email, $order_id, $items_html, $subtotal, $discount, $shipping, $total, $addr1, $addr2, $postcode, $city, $state, $region, $phone, $name) {
    
    $f_subtotal = number_format($subtotal, 2);
    $f_discount = number_format($discount, 2);
    $f_total    = number_format($total, 2);
    $f_shipping = (is_numeric($shipping)) ? "RM " . number_format((float)$shipping, 2) : $shipping;

    $recipient_name = !empty($name) ? htmlspecialchars(trim($name)) : 'Valued Customer';

    $formatted_phone = formatMalaysianPhone($phone);

    // 🌟 REFINED: Dynamic punctuation rendering loop for addresses
    $address_lines = [];
    
    if (!empty(trim($addr1))) {
        $address_lines[] = htmlspecialchars(trim($addr1));
    }
    if (!empty(trim($addr2))) {
        $address_lines[] = htmlspecialchars(trim($addr2));
    }
    if (!empty(trim($postcode)) || !empty(trim($city))) {
        $address_lines[] = htmlspecialchars(trim("$postcode $city"));
    }
    if (!empty(trim($state)) || !empty(trim($region))) {
        $address_lines[] = htmlspecialchars(trim("$state, $region"));
    }

    // Compile delivery address block nicely
    // $shipping_address_html = htmlspecialchars($addr1) . "<br>";
    // if (!empty($addr2)) {
    //     $shipping_address_html .= htmlspecialchars($addr2) . "<br>";
    // }
    // $shipping_address_html .= htmlspecialchars($postcode) . " " . htmlspecialchars($city) . "<br>";
    // $shipping_address_html .= htmlspecialchars($state) . ", " . htmlspecialchars($region);

    $shipping_address_html = "";
    $total_lines = count($address_lines);

    for ($i = 0; $i < $total_lines; $i++) {
        if ($i === $total_lines - 1) {
            // Last line receives a fullstop
            $shipping_address_html .= $address_lines[$i] . ".";
        } else {
            // Intermediary lines receive a comma and a line break
            $shipping_address_html .= $address_lines[$i] . ",<br>";
        }
    }

    $email_body = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #2E2F2D; background-color: #F7F6F3; padding: 30px; margin: 0;'>
        <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
            <h2 style='color: #91462E; margin-bottom: 5px; font-weight: bold;'>Thank You For Your Purchase!</h2>
            <p style='color: #777777; font-size: 14px;'>Order Reference: <strong>#{$order_id}</strong></p>
            <hr style='border: none; border-top: 1px solid #EAEAEA; margin: 20px 0;'>
            
            <h3 style='color: #2E2F2D; margin-bottom: 10px;'>Shipping Details</h3>
            <div style='background-color: #F9F9F8; padding: 15px; border-radius: 8px; font-size: 14px; color: #555555; line-height: 1.5; border-left: 4px solid #91462E; margin-bottom: 25px;'>
                <span style='color: #777777; font-size: 13px;'>Recipient Name:</span> <style='color: #2E2F2D;'>{$recipient_name}<br>
                <span style='color: #777777; font-size: 13px;'>Phone Number:</span> <style='color: #2E2F2D;'>{$formatted_phone}<br>
                {$shipping_address_html}
            </div>

            <h3 style='color: #2E2F2D; margin-bottom: 15px;'>Items Ordered</h3>
            <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                <thead>
                    <tr style='background: #F9F9F8;'>
                        <th style='padding: 10px; text-align: left; border-bottom: 2px solid #91462E; color: #91462E;'>Product Details</th>
                        <th style='padding: 10px; text-align: right; border-bottom: 2px solid #91462E; color: #91462E;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$items_html}
                </tbody>
            </table>
            
            <div style='margin-top: 20px; float: right; width: 260px; font-size: 14px; line-height: 1.8;'>
                <table style='width: 100%; font-size: 14px;'>
                    <tr><td>Subtotal:</td><td style='text-align: right;'><strong>RM {$f_subtotal}</strong></td></tr>
                    " . ($discount > 0 ? "<tr><td style='color: #D9534F;'>Discount Applied:</td><td style='text-align: right; color: #D9534F;'><strong>-RM {$f_discount}</strong></td></tr>" : "") . "
                    <tr><td>Shipping:</td><td style='text-align: right;'><strong>{$f_shipping}</strong></td></tr>
                    <tr><td colspan='2'><hr style='border: none; border-top: 1px solid #EAEAEA;'></td></tr>
                    <tr style='color: #91462E; font-size: 16px;'><td><strong>Grand Total:</strong></td><td style='text-align: right;'><strong>RM {$f_total}</strong></td></tr>
                </table>
            </div>
            <div style='clear: both;'></div>
            
            <hr style='border: none; border-top: 1px solid #EAEAEA; margin: 30px 0;'>
            <p style='font-size: 12px; color: #999999; text-align: center;'>If you have any questions regarding your delivery details, please contact customer assistance inside the SkinMate application framework.</p>
        </div>
    </body>
    </html>";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'jingwenng0320@gmail.com';      
        $mail->Password   = 'ihyq rjjg mmxx odvc';      
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;                                    

        $mail->setFrom('jingwenng0320@gmail.com', 'SkinMate Support');
        $mail->addAddress($to_email); 

        $mail->isHTML(true);                                  
        $mail->Subject = "Your SkinMate Order Confirmation - Invoice #{$order_id}";
        $mail->Body    = $email_body;
        $mail->AltBody = "Thank you for your order! Your confirmation invoice reference is #{$order_id}. Total Paid: RM {$f_total}.";

        $mail->send();
    } catch (Exception $e) {
        // Keeps user checkout clean if mail server times out
    }
}

// =========================================================================
// 🛠️ MALAYSIAN SMART PHONE STRING FORMATTER
// =========================================================================
// 🌟 NEW FUNCTION: Converts raw 60123456789 -> (+60) 12-345 6789
function formatMalaysianPhone($phone) {
    // Basic fallback safety catch
    if (empty($phone)) return 'N/A';
    
    // Ensure we are working with just digits
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Strip leading "60" temporarily to format the inner body cleanly
    if (substr($phone, 0, 2) === '60') {
        $body = substr($phone, 2);
    } else {
        $body = $phone;
    }
    
    // Check length of mobile core prefix body
    if (strlen($body) == 9) {
        // Handles 9-digit input: e.g., 12-345 6789
        $prefix = substr($body, 0, 2); // 12
        $mid    = substr($body, 2, 3); // 345
        $end    = substr($body, 5, 4); // 6789
        return "(+60) " . $prefix . "-" . $mid . " " . $end;
    } elseif (strlen($body) == 10) {
        // Handles 10-digit input: e.g., 12-3456 7890
        $prefix = substr($body, 0, 2); // 12
        $mid    = substr($body, 2, 4); // 3456
        $end    = substr($body, 6, 4); // 7890
        return "(+60) " . $prefix . "-" . $mid . " " . $end;
    }
    
    // Return original string with simple country notation if pattern structure is unexpected
    return "(+60) " . $body;
}
?>