<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("UPDATE users SET notifications_last_seen = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo json_encode(['success' => true]);
