<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$video_id = isset($data['video_id']) ? intval($data['video_id']) : 0;

if (!$video_id) {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = $conn->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
$stmt->bind_param("i", $video_id);
$stmt->execute();

$count_stmt = $conn->prepare("SELECT views FROM videos WHERE id = ?");
$count_stmt->bind_param("i", $video_id);
$count_stmt->execute();
$views = $count_stmt->get_result()->fetch_assoc()['views'];

echo json_encode(['success' => true, 'views' => $views]);
