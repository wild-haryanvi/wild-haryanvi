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
$comment = isset($data['comment']) ? trim($data['comment']) : '';

if (!$video_id || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

if (strlen($comment) > 500) {
    echo json_encode(['success' => false, 'message' => 'Comment too long']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO video_comments (user_id, video_id, comment) VALUES (?, ?, ?)");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iis", $user_id, $video_id, $comment);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not post comment']);
}