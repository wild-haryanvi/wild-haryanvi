<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$video_id = isset($data['video_id']) ? intval($data['video_id']) : 0;
$progress = isset($data['progress']) ? intval($data['progress']) : 0;

if (!$video_id) {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = $conn->prepare("UPDATE watch_history SET progress_seconds = ? WHERE user_id = ? AND video_id = ?");
$stmt->bind_param("iii", $progress, $user_id, $video_id);
$stmt->execute();

echo json_encode(['success' => true]);
