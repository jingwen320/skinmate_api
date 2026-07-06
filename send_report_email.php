<?php
// send_report_email.php
header("Content-Type: application/json");
include 'db.php';

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// require 'vendor/autoload.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
    exit;
}

$scan_id = $_POST['scan_id'] ?? '';

if (empty($scan_id)) {
    echo json_encode(["status" => "error", "message" => "Missing scan_id"]);
    exit;
}

// 1. Fetch scan history along with user's full name via INNER JOIN
$sql = "SELECT sp.*, u.name as user_name, u.email as user_email 
        FROM skin_progress sp 
        INNER JOIN users u ON sp.user_id = u.id 
        WHERE sp.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $scan_id);
$stmt->execute();
$result = $stmt->get_result();
$scan = $result->fetch_assoc();

if (!$scan) {
    echo json_encode(["status" => "error", "message" => "Scan report details not found"]);
    exit;
}

// Extract variables for ease of use
$userName = htmlspecialchars($scan['user_name']);
$userEmail = $scan['user_email'];
$skinType = htmlspecialchars($scan['skin_type']);
$healthScore = intval($scan['health_score']);
$createdAt = explode(' ', $scan['created_at'])[0];

// Handle percentages
$acne = number_format(floatval($scan['acne_pct']), 1);
$darkSpots = number_format(floatval($scan['dark_spots_pct']), 1);
$pigmentation = number_format(floatval($scan['pigmentation_pct']), 1);
$pores = number_format(floatval($scan['pores_pct']), 1);
$redness = number_format(floatval($scan['redness_pct']), 1);
$wrinkles = number_format(floatval($scan['wrinkles_pct']), 1);

// Determine colors for the health score
$scoreColor = $healthScore < 40 ? '#FF5252' : ($healthScore < 75 ? '#FF9800' : '#4CAF50');

$adviceTitle = "Expert Advice";
$adviceText = "Based on your skin profile, focus on maintaining hydration and using sun protection to improve your skin performance scores.";

// Normalize text for safer conditional pattern matching
switch (strtolower(trim($skinType))) {
    case 'oily':
        $adviceTitle = "💡 Oily Skin Routine Advice";
        $adviceText = "Focus on oil control and maintaining hydration. Incorporate a gentle salicylic acid (BHA) cleanser to clear out pores and choose lightweight, non-comedogenic gel moisturizers.";
        break;
        
    case 'dry':
        $adviceTitle = "💡 Dry Skin Routine Advice";
        $adviceText = "Your skin barrier requires deep moisture reinforcement. Look for products containing ceramides, hyaluronic acid, and glycerin. Avoid harsh foaming cleansers and use a rich cream moisturizer.";
        break;
        
    case 'combination':
        $adviceTitle = "💡 Combination Skin Routine Advice";
        $adviceText = "Balance is key for your skin. Use a gentle, balancing cleanser, apply lightweight hydration to your T-zone, and layer a richer moisturizing cream only on dry cheek patches.";
        break;
        
    case 'normal':
    default:
        $adviceTitle = "💡 Normal Skin Routine Advice";
        $adviceText = "Your skin balance is currently stable. Maintain your healthy barrier function by sticking to a baseline daily regimen of gentle cleansing, daily hydration, and consistent SPF broad-spectrum protection.";
        break;
}

// 2. Draft the Premium HTML Email Layout Template matching SkinMate branding
$htmlTemplate = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Your SkinMate Scan Report</title>
</head>
<body style='margin: 0; padding: 0; background-color: #F7F6F3; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>
    <table role='presentation' cellspacing='0' cellpadding='0' width='100%' style='background-color: #F7F6F3; padding: 20px;'>
        <tr>
            <td align='center'>
                <table role='presentation' cellspacing='0' cellpadding='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);'>
                    
                    <tr>
                        <td style='background-color: #91462E; padding: 32px; text-align: center;'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px;'>SKINMATE REPORT</h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style='padding: 32px;'>
                            <p style='font-size: 16px; color: #555555; margin-top: 0;'>Hi <strong>{$userName}</strong>,</p>
                            <p style='font-size: 15px; color: #777777; line-height: 1.5;'>Here is the digital layout copy of your skin health report generated on <strong>{$createdAt}</strong>.</p>
                            
                            <table role='presentation' width='100%' style='margin: 24px 0; padding: 20px; background-color: #F7F6F3; border-radius: 16px;'>
                                <tr>
                                    <td>
                                        <span style='font-size: 12px; font-weight: bold; color: #888888; text-transform: uppercase;'>Skin Type</span>
                                        <h2 style='margin: 4px 0 0 0; color: #91462E; font-size: 22px;'>{$skinType} Skin</h2>
                                    </td>
                                    <td align='right'>
                                        <div style='background-color: #ffffff; width: 64px; height: 64px; border-radius: 50%; border: 4px solid {$scoreColor}; text-align: center; line-height: 64px;'>
                                            <strong style='font-size: 18px; color: #333333;'>{$healthScore}%</strong>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <h3 style='color: #91462E; font-size: 18px; border-bottom: 1px solid #E8E8E5; padding-bottom: 8px; margin-bottom: 16px;'>Condition Analysis</h3>
                            
                            " . Helper::renderEmailRow("Acne", $acne) . "
                            " . Helper::renderEmailRow("Dark Spots", $darkSpots) . "
                            " . Helper::renderEmailRow("Pigmentation", $pigmentation) . "
                            " . Helper::renderEmailRow("Pores", $pores) . "
                            " . Helper::renderEmailRow("Redness", $redness) . "
                            " . Helper::renderEmailRow("Wrinkles", $wrinkles) . "
                            
                            <div style='margin-top: 32px; padding: 20px; background-color: rgba(145, 70, 46, 0.05); border: 1px solid rgba(145, 70, 46, 0.1); border-radius: 16px;'>
                                <h4 style='margin: 0 0 8px 0; color: #91462E; font-size: 15px;'>{$adviceTitle}</h4>
                                <p style='margin: 0; font-size: 14px; color: #444444; line-height: 1.6;'>{$adviceText}</p>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style='background-color: #F0EEE9; padding: 20px; text-align: center; font-size: 12px; color: #888888;'>
                            This analysis was created automatically by SkinMate. For educational purposes only.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
";

// 3. Configure PHPMailer Engine
$mail = new PHPMailer(true);
try {
    // Server SMTP configuration settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jingwenng0320@gmail.com'; 
    $mail->Password   = 'ihyq rjjg mmxx odvc'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('jingwenng0320@gmail.com', 'SkinMate Support');
    $mail->addAddress($userEmail, $userName);

    $mail->isHTML(true);
    $mail->Subject = "SkinMate Analysis Summary - " . $createdAt;
    $mail->Body    = $htmlTemplate;

    $mail->send();
    echo json_encode(["status" => "success", "message" => "Report copy successfully emailed to $userEmail"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Mailer Exception Error: {$mail->ErrorInfo}"]);
}

// Little helper class inline closure to render HTML progress layout lines
class Helper {
    public static function renderEmailRow($label, $score) {
        $color = $score < 40 ? '#FF5252' : ($score < 75 ? '#FF9800' : '#4CAF50');
        return "
        <div style='margin-bottom: 14px;'>
            <table role='presentation' width='100%' style='font-size: 14px; margin-bottom: 4px;'>
                <tr>
                    <td style='color: #444444;'>{$label}</td>
                    <td align='right' style='font-weight: bold; color: #333333;'>{$score}%</td>
                </tr>
            </table>
            <div style='background-color: #E8E8E5; height: 8px; border-radius: 4px; overflow: hidden;'>
                <div style='background-color: {$color}; width: {$score}%; height: 8px;'></div>
            </div>
        </div>";
    }
}
?>