<?php
$page_title = "Notifications - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all as read
$stmt = $conn->prepare("UPDATE users SET notifications_last_seen = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$notifications = $conn->query("
    SELECT notifications.*, videos.thumbnail 
    FROM notifications 
    LEFT JOIN videos ON notifications.video_id = videos.id
    ORDER BY notifications.created_at DESC 
    LIMIT 50");

include '../includes/header.php';
?>

<style>
    .notif-page-container {
        max-width: 700px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .notif-page-header {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 2rem;
    }

    .notif-page-item {
        display: flex;
        gap: 1rem;
        padding: 1.3rem;
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        border-radius: 15px;
        margin-bottom: 1rem;
        border: 1px solid var(--light-black);
        text-decoration: none;
        color: inherit;
    }

    .notif-page-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        background: var(--text-black);
        overflow: hidden;
        position: relative;
        color: var(--text-gray);
    }

    .notif-page-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .notif-crown {
        position: absolute; 
        bottom: -4px;
        right: -4px;
        width: 22px;
        height: 22px;
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        color: #1a1a1a;
        border: 2px solid var(--secondary-black);
        box-shadow: 0 2px 6px rgba(255, 165, 0, 0.5);
    }

    .notif-page-body {
        flex: 1;
    }

    .notif-page-title {
        font-weight: 700;
        color: var(--white);
        margin-bottom: 0.3rem;
    }

    .notif-page-message {
        color: var(--text-gray);
        font-size: 0.9rem;
    }

    .notif-page-time {
        color: var(--text-gray);
        font-size: 0.78rem;
        margin-top: 0.5rem;
        opacity: 0.7;
    }

    .notif-page-empty {
        text-align: center;
        color: var(--text-gray);
        padding: 4rem 1rem;
        background: var(--secondary-black);
        border-radius: 15px;
    }

    .notif-page-empty i {
        font-size: 2.5rem;
        color: var(--light-black);
        margin-bottom: 1rem;
        display: block;
    }
</style>

<div class="notif-page-container">
    <div class="notif-page-header"><i class="fas fa-bell"></i> Notifications</div>

    <?php if ($notifications && $notifications->num_rows > 0): ?>
        <?php while ($n = $notifications->fetch_assoc()): ?>
            <?php $isPremium = $n['type'] === 'premium_release'; ?>
            <?php $isAnnouncement = $n['type'] === 'announcement'; ?>
            <?php $link = !empty($n['video_id']) ? BASE_URL . 'pages/watch.php?id=' . $n['video_id'] : (BASE_URL . 'pages/updates.php'); ?>
            <a href="<?php echo $link; ?>" class="notif-page-item">
                <div class="notif-page-icon">
                    <?php if (!empty($n['thumbnail'])): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($n['thumbnail']); ?>" alt="">
                    <?php elseif ($isAnnouncement): ?>
                        <i class="fas fa-bullhorn"></i>
                    <?php else: ?>
                        <i class="fas fa-film"></i>
                    <?php endif; ?>
                    <?php if ($isPremium): ?>
                        <span class="notif-crown"><i class="fas fa-crown"></i></span>
                    <?php endif; ?>
                </div>
                <div class="notif-page-body">
                    <div class="notif-page-title"><?php echo htmlspecialchars($n['title']); ?></div>
                    <div class="notif-page-message"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div class="notif-page-time"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></div>
                </div>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="notif-page-empty">
            <i class="fas fa-bell-slash"></i>
            No notifications yet.
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
