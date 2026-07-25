<?php
// Add to Favorites API
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$video_id = intval($data['video_id']);

// Check if already in favorites
$exists = $conn->query("SELECT id FROM favorites WHERE user_id = $user_id AND video_id = $video_id");

if ($exists->num_rows > 0) {
    // Remove from favorites
    $conn->query("DELETE FROM favorites WHERE user_id = $user_id AND video_id = $video_id");
    echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
} else {
    // Add to favorites
    $conn->query("INSERT INTO favorites (user_id, video_id, created_at) VALUES ($user_id, $video_id, NOW())");
    echo json_encode(['success' => true, 'message' => 'Added to favorites']);
}
?>
