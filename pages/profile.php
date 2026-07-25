<?php
$page_title = "Profile - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

function formatViews($views) {
    if ($views >= 1000000) return number_format($views / 1000000, 1) . 'M';
    if ($views >= 1000) return number_format($views / 1000, 1) . 'K';
    return $views;
}

// User info
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Active subscription (with plan name)
$sub_stmt = $conn->prepare("
    SELECT subscriptions.*, plans.name AS plan_name
    FROM subscriptions
    LEFT JOIN plans ON subscriptions.plan_id = plans.id
    WHERE subscriptions.user_id = ? AND subscriptions.status = 'active' AND subscriptions.expires_at > NOW()
    ORDER BY subscriptions.expires_at DESC LIMIT 1
");
$sub_stmt->bind_param("i", $user_id);
$sub_stmt->execute();
$subscription = $sub_stmt->get_result()->fetch_assoc();

// Watch History
$history_stmt = $conn->prepare("
    SELECT videos.*, categories.name AS category_name, watch_history.watched_at
    FROM watch_history
    JOIN videos ON watch_history.video_id = videos.id
    LEFT JOIN categories ON videos.category_id = categories.id
    WHERE watch_history.user_id = ?
    ORDER BY watch_history.watched_at DESC
    LIMIT 12
");
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$history = $history_stmt->get_result();

// Continue Watching (only videos with saved progress)
$continue_stmt = $conn->prepare("
    SELECT videos.*, categories.name AS category_name, watch_history.watched_at, watch_history.progress_seconds
    FROM watch_history
    JOIN videos ON watch_history.video_id = videos.id
    LEFT JOIN categories ON videos.category_id = categories.id
    WHERE watch_history.user_id = ? AND watch_history.progress_seconds > 0
    ORDER BY watch_history.watched_at DESC
    LIMIT 6
");
$continue_stmt->bind_param("i", $user_id);
$continue_stmt->execute();
$continue_watching = $continue_stmt->get_result();

// Favorites
$fav_stmt = $conn->prepare("
    SELECT videos.*, categories.name AS category_name, favorites.created_at AS fav_added_at
    FROM favorites
    JOIN videos ON favorites.video_id = videos.id
    LEFT JOIN categories ON videos.category_id = categories.id
    WHERE favorites.user_id = ?
    ORDER BY favorites.created_at DESC
");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$favorites = $fav_stmt->get_result();

// Counts
$watch_count = $conn->prepare("SELECT COUNT(*) as total FROM watch_history WHERE user_id = ?");
$watch_count->bind_param("i", $user_id);
$watch_count->execute();
$total_watched = $watch_count->get_result()->fetch_assoc()['total'];

$fav_count = $conn->prepare("SELECT COUNT(*) as total FROM favorites WHERE user_id = ?");
$fav_count->bind_param("i", $user_id);
$fav_count->execute();
$total_favorites = $fav_count->get_result()->fetch_assoc()['total'];

include '../includes/header.php';
?>

<style>
    .profile-container {
        max-width: 1200px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .profile-header {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        display: flex;
        gap: 2rem;
        align-items: center;
        margin-bottom: 2rem;
        border: 2px solid var(--light-black);
        flex-wrap: wrap;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
    }

    .profile-info h1 {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }

    .profile-info p {
        color: var(--text-gray);
        margin-bottom: 0.3rem;
    }

    .profile-status {
        display: inline-block;
        background: var(--primary-red);
        color: white !important;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-top: 1rem;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .profile-card {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 1.5rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
    }

    .profile-card h3 {
        font-size: 1.1rem;
        margin-bottom: 0.8rem;
        color: var(--primary-red);
    }

    .card-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--white);
    }

    .card-label {
        color: var(--text-gray);
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 3rem 0 1.5rem;
        color: var(--white);
    }

    .profile-video-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }

    .empty-tab {
        text-align: center;
        color: var(--text-gray);
        padding: 3rem 1rem;
        background: var(--secondary-black);
        border-radius: 15px;
    }

    .settings-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--light-black);
        color: var(--white);
        padding: 0.6rem 1.3rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        margin-left: auto;
        transition: var(--transition);
    }

    .settings-btn:hover {
        background: var(--primary-red);
        color: #fff;
    }
</style>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($user['name'] ?? $user['email'], 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['name']); ?></h1>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><i class="fas fa-user"></i> Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
            
            <?php if ($subscription && $subscription['status'] == 'active'): ?>
                <span class="profile-status">
                    <i class="fas fa-star"></i> Premium Member
                </span>
            <?php else: ?>
                <span class="profile-status" style="background: var(--text-gray);">
                    <i class="fas fa-user"></i> Free Member
                </span>
            <?php endif; ?>
        </div>
        <a href="<?php echo BASE_URL; ?>pages/setting.php" class="settings-btn">
            <i class="fas fa-cog"></i> Settings
        </a>
    </div>

    <div class="profile-grid">
        <div class="profile-card">
            <h3><i class="fas fa-film"></i> Videos Watched</h3>
            <div class="card-value"><?php echo $total_watched; ?></div>
            <div class="card-label">Total videos watched</div>
        </div>

        <div class="profile-card">
            <h3><i class="fas fa-heart"></i> Favorites</h3>
            <div class="card-value"><?php echo $total_favorites; ?></div>
            <div class="card-label">Videos saved</div>
        </div>

        <?php if ($subscription): ?>
            <div class="profile-card">
                <h3><i class="fas fa-calendar"></i> Subscription</h3>
                <div class="card-value"><?php echo htmlspecialchars($subscription['plan_name'] ?? 'Premium'); ?></div>
                <div class="card-label">Expires: <?php echo date('M d, Y', strtotime($subscription['expires_at'])); ?></div>
            </div>
        <?php else: ?>
            <div class="profile-card">
                <h3><i class="fas fa-crown"></i> Premium</h3>
                <div class="card-value">Not Active</div>
                <div class="card-label"><a href="<?php echo BASE_URL; ?>pages/premium.php" style="color: var(--primary-red); text-decoration: none;">Upgrade Now</a></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Continue Watching -->
    <h2 class="section-title">Continue Watching</h2>
    <div class="profile-video-grid">
        <?php if ($continue_watching->num_rows > 0): ?>
            <?php while ($v = $continue_watching->fetch_assoc()): ?>
                <a href="<?php echo BASE_URL; ?>pages/watch.php?id=<?php echo $v['id']; ?>" class="video-card">
                    <div class="video-thumbnail">
                        <?php if (!empty($v['thumbnail'])): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($v['thumbnail']); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>🎬<?php endif; ?>
                        <?php if (!empty($v['duration'])): ?>
                            <div class="video-duration"><i class="fas fa-video"></i> <?php echo htmlspecialchars($v['duration']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="video-info">
                        <div class="video-title"><?php echo htmlspecialchars($v['title']); ?></div>
                        <div class="video-category"><?php echo htmlspecialchars($v['category_name'] ?? ''); ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-tab" style="grid-column:1/-1;">Start watching something and it'll show up here!</div>
        <?php endif; ?>
    </div>

    <!-- Watch History -->
    <h2 class="section-title">Watch History</h2>
    <div class="profile-video-grid">
        <?php if ($history->num_rows > 0): ?>
            <?php while ($v = $history->fetch_assoc()): ?>
                <a href="<?php echo BASE_URL; ?>pages/watch.php?id=<?php echo $v['id']; ?>" class="video-card">
                    <div class="video-thumbnail">
                        <?php if (!empty($v['thumbnail'])): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($v['thumbnail']); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>🎬<?php endif; ?>
                        <?php if (!empty($v['duration'])): ?>
                            <div class="video-duration"><i class="fas fa-video"></i> <?php echo htmlspecialchars($v['duration']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="video-info">
                        <div class="video-title"><?php echo htmlspecialchars($v['title']); ?></div>
                        <div class="video-category"><?php echo htmlspecialchars($v['category_name'] ?? ''); ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-tab" style="grid-column:1/-1;">No watch history yet.</div>
        <?php endif; ?>
    </div>

    <!-- Favorites -->
    <h2 class="section-title">Favorite Videos</h2>
    <div class="profile-video-grid">
        <?php if ($favorites->num_rows > 0): ?>
            <?php while ($v = $favorites->fetch_assoc()): ?>
                <a href="<?php echo BASE_URL; ?>pages/watch.php?id=<?php echo $v['id']; ?>" class="video-card">
                    <div class="video-thumbnail">
                        <?php if (!empty($v['thumbnail'])): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($v['thumbnail']); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>🎬<?php endif; ?>
                        <?php if (!empty($v['duration'])): ?>
                            <div class="video-duration"><i class="fas fa-video"></i> <?php echo htmlspecialchars($v['duration']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="video-info">
                        <div class="video-title"><?php echo htmlspecialchars($v['title']); ?></div>
                        <div class="video-category"><?php echo htmlspecialchars($v['category_name'] ?? ''); ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-tab" style="grid-column:1/-1;">No favorites yet — tap the heart icon on any video.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>