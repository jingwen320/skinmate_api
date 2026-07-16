<?php
set_time_limit(0);
ignore_user_abort(true);

// Disable output buffering
while (ob_get_level() > 0) {
    ob_end_flush();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo "event: error\n";
    echo "data: " . json_encode([
        "status" => "error",
        "message" => "Invalid user ID"
    ]) . "\n\n";
    flush();
    exit;
}

// Tell EventSource to reconnect after 3 seconds
echo "retry: 3000\n\n";
flush();

$last_checked_count = -1;

while (true) {

    // Stop if client disconnects
    if (connection_aborted()) {
        break;
    }

    // Reconnect to MySQL if necessary
    if (!$conn->ping()) {
        $conn->close();
        include 'db.php';
    }

    try {

        // Get unread count
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS unread_count
            FROM notifications
            WHERE user_id = ?
            AND is_read = 0
        ");

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        $current_unread = (int)$result['unread_count'];

        $stmt->close();

        if ($current_unread != $last_checked_count) {

            $last_checked_count = $current_unread;

            $stmt = $conn->prepare("
                SELECT *
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");

            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $result = $stmt->get_result();

            $notifications = [];

            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }

            $stmt->close();

            echo "event: notification\n";
            echo "data: " . json_encode([
                "status" => "success",
                "unread_count" => $current_unread,
                "notifications" => $notifications
            ]) . "\n\n";

        } else {

            // Heartbeat to keep SSE alive
            echo ": heartbeat\n\n";
        }

    } catch (Exception $e) {

        echo "event: error\n";
        echo "data: " . json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]) . "\n\n";
    }

    // Flush output
    if (ob_get_length()) {
        ob_flush();
    }

    flush();

    // Check every 2 seconds
    sleep(2);
}

$conn->close();
exit;