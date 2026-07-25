<?php
$page_title = "Watch Later - Wild Haryanvi";
include '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if video ID is provided (add to watch later)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id'])) {
    $video_id = intval($_POST['video_id']);
    
    // Check if already in watch later
    $exists = $conn->query("SELECT id FROM watch_later WHERE user_id = $user_id AND video_id = $video_id");
    
    if ($exists->num_rows === 0) {
        $conn->query("INSERT INTO watch_later (user_id, video_id, added_at) VALUES ($user_id, $video_id, NOW())");
    }
    
    echo json_encode(['success' => true]);
    exit();
}

// Get watch later videos
$watch_later = $conn->query("SELECT v.* FROM watch_later wl JOIN videos v ON wl.video_id = v.id WHERE wl.user_id = $user_id ORDER BY wl.added_at DESC");
?>

<style>
    .watch-later-container {
        max-width: 1400px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .watch-later-header {
        margin-bottom: 2rem;
    }

    .watch-later-header h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .watch-later-stats {
        color: var(--text-gray);
        font-size: 1rem;
    }
</style>

<div class="watch-later-container">
    <div class="watch-later-header">
        <h1><i class="fas fa-clock"></i> Watch Later</h1>
        <p class="watch-later-stats">
            <?php echo $watch_later ? $watch_later->num_rows : 0; ?> videos saved
        </p>
    </div>

    <div class="video-grid">
        <?php
        if ($watch_later && $watch_later->num_rows > 0) {
            while ($video = $watch_later->fetch_assoc()) {
                $is_premium = $video['type'] == 'premium' ? true : false;
                ?>
                <div class="video-card" data-video-id="<?php echo $video['id']; ?>">
                    <div class="video-thumbnail">
                        <?php if ($video['thumbnail']): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            🎬
                        <?php endif; ?>
                        <div class="video-duration"><?php echo $video['duration']; ?></div>
                        <?php if ($is_premium): ?>
                            <div class="video-badge">PREMIUM</div>
                        <?php endif; ?>
                    </div>
                    <div class="video-info">
                        <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                        <div class="video-category"><?php echo htmlspecialchars($video['category']); ?></div>
                        <div class="video-meta">
                            <span><?php echo formatViews($video['views']); ?> views</span>
                            <span><?php echo getTimeAgo($video['created_at']); ?></span>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p style="grid-column: 1/-1; text-align: center; color: #b0b0b0; font-size: 1.1rem;">Your watch later list is empty. <a href="videos.php" style="color: var(--primary-red);">Add videos to watch later</a></p>';
        }
        ?>
    </div>
</div>

<?php
function formatViews($views) {
    if ($views >= 1000000) {
        return number_format($views / 1000000, 1) . 'M';
    } elseif ($views >= 1000) {
        return number_format($views / 1000, 1) . 'K';
    }
    return $views;
}

function getTimeAgo($date) {
    $now = new DateTime();
    $past = new DateTime($date);
    $diff = $now->diff($past);

    if ($diff->days > 365) {
        return $diff->format('%y year' . ($diff->y > 1 ? 's' : '') . ' ago');
    } elseif ($diff->days > 30) {
        return $diff->format('%m month' . ($diff->m > 1 ? 's' : '') . ' ago');
    } elseif ($diff->days > 0) {
        return $diff->format('%d day' . ($diff->d > 1 ? 's' : '') . ' ago');
    } elseif ($diff->h > 0) {
        return $diff->format('%h hour' . ($diff->h > 1 ? 's' : '') . ' ago');
    } elseif ($diff->i > 0) {
        return $diff->format('%i minute' . ($diff->i > 1 ? 's' : '') . ' ago');
    } else {
        return 'Just now';
    }
}
?>

<?php include '../includes/footer.php'; ?>
