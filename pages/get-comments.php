<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$video_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : 0;

if (!$video_id) {
    echo json_encode(['success' => false, 'comments' => []]);
    exit();
}

$stmt = $conn->prepare("
    SELECT video_comments.*, users.name AS user_name
    FROM video_comments
    LEFT JOIN users ON video_comments.user_id = users.id
    WHERE video_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");

if (!$stmt) {
    echo json_encode(['success' => false, 'comments' => [], 'sql_error' => $conn->error]);
    exit();
}

$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $display_name = !empty($row['user_name']) ? $row['user_name'] : 'User';
    $comments[] = [
        'comment' => $row['comment'],
        'user' => $display_name,
        'time' => date('d M, h:i A', strtotime($row['created_at']))
    ];
}

echo json_encode(['success' => true, 'comments' => $comments]);
