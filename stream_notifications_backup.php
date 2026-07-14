<?php
if (ob_get_level()) ob_end_clean();
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no'); 

include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id === 0) {
    echo "data: " . json_encode(['status' => 'error', 'message' => 'Invalid ID']) . "\n\n";
    exit;
}

$last_checked_count = -1;

while (true) {
    // Break if Flutter has disconnected
    if (connection_aborted()) break;

    if (!$conn->ping()) {
        $conn->close();
        include 'db.php'; 
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $current_unread = (int)$result['unread_count'];
    $stmt->close();

    if ($current_unread !== $last_checked_count) {
        $last_checked_count = $current_unread;

        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $notifications = [];
        while ($row = $res->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();

        echo "data: " . json_encode([
            'status' => 'success',
            'unread_count' => $current_unread,
            'notifications' => $notifications
        ]) . "\n\n";

        flush();
    }

    sleep(10);
}
?>