<?php
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

// Get user ID and verify it exists in session
$user_id = $_SESSION['user_id'];
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If video ID provided, check if it's premium and user has access
if ($video_id) {
    $video = $conn->query("SELECT * FROM videos WHERE id = $video_id")->fetch_assoc();
    
    if ($video && $video['type'] == 'premium') {
        // Check subscription
        $subscription = $conn->query("SELECT * FROM subscriptions WHERE user_id = $user_id AND status = 'active' AND expiry_date > NOW()")->fetch_assoc();
        
        if (!$subscription) {
            header("Location: " . BASE_URL . "pages/premium.php");
            exit();
        }
    }
    
    // Increment view count
    $conn->query("UPDATE videos SET views = views + 1 WHERE id = $video_id");
}

$page_title = "Logout";
?>
<?php
// Logout
session_destroy();
header("Location: " . BASE_URL);
exit();
?>
