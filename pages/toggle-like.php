<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$video_id = isset($data['video_id']) ? intval($data['video_id']) : 0;

if (!$video_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid video']);
    exit();
}

// Check if already liked
$check_stmt = $conn->prepare("SELECT id FROM video_likes WHERE user_id = ? AND video_id = ?");
$check_stmt->bind_param("ii", $user_id, $video_id);
$check_stmt->execute();
$existing = $check_stmt->get_result()->fetch_assoc();

if ($existing) {
    // Unlike
    $del_stmt = $conn->prepare("DELETE FROM video_likes WHERE user_id = ? AND video_id = ?");
    $del_stmt->bind_param("ii", $user_id, $video_id);
    $del_stmt->execute();
    $liked = false;
} else {
    // Like
    $ins_stmt = $conn->prepare("INSERT INTO video_likes (user_id, video_id) VALUES (?, ?)");
    $ins_stmt->bind_param("ii", $user_id, $video_id);
    $ins_stmt->execute();
    $liked = true;
}

// Get updated count
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM video_likes WHERE video_id = ?");
$count_stmt->bind_param("i", $video_id);
$count_stmt->execute();
$count = $count_stmt->get_result()->fetch_assoc()['total'];

echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
