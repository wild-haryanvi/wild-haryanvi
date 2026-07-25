<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'notifications' => [], 'unread' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

$user_stmt = $conn->prepare("SELECT notifications_last_seen FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$last_seen = $user_stmt->get_result()->fetch_assoc()['notifications_last_seen'];

$notif_result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 15");

$notifications = [];
$unread = 0;
while ($n = $notif_result->fetch_assoc()) {
    $is_unread = !$last_seen || strtotime($n['created_at']) > strtotime($last_seen);
    if ($is_unread) $unread++;
    $notifications[] = [
        'title' => $n['title'],
        'message' => $n['message'],
        'video_id' => $n['video_id'],
        'type' => $n['type'],
        'time' => date('d M, h:i A', strtotime($n['created_at'])),
        'unread' => $is_unread
    ];
}

echo json_encode(['success' => true, 'notifications' => $notifications, 'unread' => $unread]);
