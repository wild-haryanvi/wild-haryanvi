<?php
    $page_title = "Watch Video - Wild Haryanvi";
    require_once '../includes/db.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "pages/login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if (!$video_id) {
        header("Location: " . BASE_URL . "pages/videos.php");
        exit();
    }

    // Get video details (with category name via join)
    $stmt = $conn->prepare("
        SELECT videos.*, categories.name AS category_name
        FROM videos
        LEFT JOIN categories ON videos.category_id = categories.id
        WHERE videos.id = ?
    ");
    $stmt->bind_param("i", $video_id);
    $stmt->execute();
    $video = $stmt->get_result()->fetch_assoc();

    if (!$video) {
        header("Location: " . BASE_URL . "pages/videos.php");
        exit();
    }

    // Only published videos can be watched
    if ($video['status'] !== 'published') {
        header("Location: " . BASE_URL . "pages/videos.php");
        exit();
    }

    // Check if it's premium and user has access
    $show_preview_lock = false;
    if ($video['access_type'] === 'premium') {
        $sub_stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
        $sub_stmt->bind_param("i", $user_id);
        $sub_stmt->execute();
        $subscription = $sub_stmt->get_result()->fetch_assoc();

        if (!$subscription) {
            $show_preview_lock = true;
            // header("Location: " . BASE_URL . "pages/premium.php");
            // exit();
        }
    }

    // Record watch history
    $hist_stmt = $conn->prepare("INSERT INTO watch_history (user_id, video_id, watched_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE watched_at = NOW()");
    $progress_stmt = $conn->prepare("SELECT progress_seconds FROM watch_history WHERE user_id = ? AND video_id = ?");
    $progress_stmt->bind_param("ii", $user_id, $video_id);
    $progress_stmt->execute();
    $existing_progress = $progress_stmt->get_result()->fetch_assoc()['progress_seconds'] ?? 0;
    $hist_stmt->bind_param("ii", $user_id, $video_id);
    $hist_stmt->execute();

    // Increment view count
    $view_stmt = $conn->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $view_stmt->bind_param("i", $video_id);
    $view_stmt->execute();

    // Handle new comment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
        $comment_text = trim($_POST['comment_text']);
        if (!empty($comment_text)) {
            $c_stmt = $conn->prepare("INSERT INTO video_comments (video_id, user_id, comment) VALUES (?, ?, ?)");
        
            $c_stmt->bind_param("iis", $video_id, $user_id, $comment_text);
            $c_stmt->execute();
        }
        header("Location: " . BASE_URL . "pages/watch.php?id=" . $video_id . "#comments");
        exit();
    }

    // Fetch comments for this video
    $comments_stmt = $conn->prepare("
        SELECT video_comments.*, users.name AS username
        FROM video_comments
        LEFT JOIN users ON video_comments.user_id = users.id
        WHERE video_comments.video_id = ? AND video_comments.status = 'visible'
        ORDER BY video_comments.created_at DESC
    ");
    $comments_stmt->bind_param("i", $video_id);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();

    // Like count + whether current user liked it
    $like_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM video_likes WHERE video_id = ?");
    $like_count_stmt->bind_param("i", $video_id);
    $like_count_stmt->execute();
    $like_count = $like_count_stmt->get_result()->fetch_assoc()['total'];

    $liked_stmt = $conn->prepare("SELECT id FROM video_likes WHERE user_id = ? AND video_id = ?");
    $liked_stmt->bind_param("ii", $user_id, $video_id);
    $liked_stmt->execute();
    $user_liked = (bool) $liked_stmt->get_result()->fetch_assoc();

    // Get related videos (same category, correct column name)
    $rel_stmt = $conn->prepare("
        SELECT videos.*, categories.name AS category_name
        FROM videos
        LEFT JOIN categories ON videos.category_id = categories.id
        WHERE videos.category_id = ? AND videos.id != ? AND videos.status = 'published'
        LIMIT 6
    ");
    $rel_stmt->bind_param("ii", $video['category_id'], $video_id);
    $rel_stmt->execute();
    $related = $rel_stmt->get_result();

    // Helper: convert a normal YouTube URL to an embeddable one
    function toEmbedUrl($url) {
        if (strpos($url, 'watch?v=') !== false) {
            $id = explode('watch?v=', $url)[1];
            $id = explode('&', $id)[0];
            return 'https://www.youtube.com/embed/' . $id;
        }
        if (strpos($url, 'youtu.be/') !== false) {
            $id = explode('youtu.be/', $url)[1];
            return 'https://www.youtube.com/embed/' . $id;
        }
        return $url; // already an embed link or another platform
    }

    function formatViews($views) {
        if ($views >= 1000000) {
            return number_format($views / 1000000, 1) . 'M';
        } elseif ($views >= 1000) {
            return number_format($views / 1000, 1) . 'K';
        }
        return $views;
    }

    include '../includes/header.php';
?>

<style>
    .watch-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 2rem;
    }

    .video-player-section {
        background: var(--dark-black);
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 2rem;
        box-shadow: 0 10px 40px rgba(255, 68, 68, 0.2);
    }

    .video-player {
        width: 100%;
        aspect-ratio: 16/9;
        background: #000;
        position: relative;
    }

    .video-player iframe,
    .video-player video {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    .no-source {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-gray);
        font-size: 1.2rem;
        flex-direction: column;
        gap: 1rem;
    }

    .premium-lock-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(10, 10, 10, 0.92);
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--white);
        z-index: 20;
        padding: 1.5rem;
        overflow: hidden;
        box-sizing: border-box;
    }

    .premium-lock-overlay.show {
        display: flex;
    }

    .premium-lock-overlay i.fa-crown {
        font-size: 2.5rem;
        color: #FFD700;
        margin-bottom: 1rem;
    }

    .premium-lock-overlay h3 {
        font-size: 1.4rem;
        margin-bottom: 0.5rem;
    }

    .premium-lock-overlay p {
        color: var(--text-gray);
        margin-bottom: 1.5rem;
        max-width: 320px;
    }

    .premium-lock-overlay .btn {
        padding: 0.8rem 2rem;
    }

    @media (max-width: 480px) {
        .premium-lock-overlay {
            padding: 0.8rem;
            gap: 0.3rem;
        }

        .premium-lock-overlay i.fa-crown {
            font-size: 1.6rem;
            margin-bottom: 0.4rem;
        }

        .premium-lock-overlay h3 {
            font-size: 1rem;
            margin-bottom: 0.3rem;
            line-height: 1.3;
        }

        .premium-lock-overlay p {
            font-size: 0.78rem;
            margin-bottom: 0.8rem;
            line-height: 1.4;
        }

        .premium-lock-overlay .btn {
            padding: 0.5rem 1.2rem;
            font-size: 0.85rem;
        }
    }

    .iframe-block-layer {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 15;
        display: none;
    }

    .iframe-block-layer.show {
        display: block;
    }

    .video-info-section {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        border: 2px solid var(--light-black);
    }

    .video-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .video-meta-info {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--light-black);
        align-items: center;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .meta-item i {
        color: var(--primary-red);
        font-size: 1.1rem;
    }

    .video-description {
        color: var(--text-gray);
        line-height: 1.8;
        margin-bottom: 1.5rem;
    }

    .video-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .action-btn {
        background: var(--light-black);
        border: 2px solid var(--light-black);
        color: var(--white);
        padding: 0.8rem 1.5rem;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .action-btn:hover {
        border-color: var(--primary-red);
        color: var(--primary-red);
    }

    .action-btn.primary {
        background: var(--primary-red);
        border-color: var(--primary-red);
        color: var(--white);
    }

    .action-btn.primary:hover {
        background: #ff6666;
        border-color: #ff6666;
    }

    .related-videos {
        margin-top: 3rem;
    }

    .related-videos h2 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }

    .comments-section {
        margin-top: 3rem;
    }

    .comments-section h2 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .comment-form {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .comment-form textarea {
        flex: 1;
        padding: 1rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 10px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
        resize: vertical;
        min-height: 60px;
    }

    .comment-form textarea:focus {
        outline: none;
        border-color: var(--primary-red);
    }

    .comment-item {
        background: var(--secondary-black);
        padding: 1.2rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        border-left: 3px solid var(--primary-red);
    }

    .comment-author {
        font-weight: 700;
        margin-bottom: 0.3rem;
    }

    .comment-time {
        font-size: 0.75rem;
        color: var(--text-gray);
        font-weight: 400;
        margin-left: 0.5rem;
    }

    .comment-text {
        color: var(--text-gray);
        line-height: 1.6;
    }

    .video-card {
        background: var(--secondary-black);
        border-radius: 15px;
        overflow: hidden;
        transition: var(--transition);
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .video-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(255, 68, 68, 0.3);
    }

    .video-thumbnail {
        width: 100%;
        aspect-ratio: 16/9;
        background: var(--light-black);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
    }

    .video-duration {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: var(--white);
        padding: 0.3rem 0.8rem;
        border-radius: 5px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .video-info {
        padding: 1.2rem;
    }

    .video-info .video-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .video-category {
        display: inline-block;
        background: var(--light-black);
        color: var(--primary-red);
        padding: 0.25rem 0.7rem;
        border-radius: 15px;
        font-size: 0.72rem;
        margin-bottom: 0.6rem;
    }

    .video-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        color: var(--text-gray);
    }

    .premium-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--primary-red);
        color: var(--white);
        padding: 0.5rem 1rem;
        border-radius: 5px;
        font-weight: 700;
        z-index: 10;
    }

    .video-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--primary-red);
        color: var(--white);
        padding: 0.2rem 0.8rem;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .category-badge {
        display: inline-block;
        background: var(--light-black);
        color: var(--primary-red);
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }
</style>

<div class="watch-container">
    <!-- Video Player -->
    <div class="video-player-section">
        <div class="video-player">
            <?php if (!empty($video['video_url'])): ?>
                <!-- YouTube / external embed -->
                <iframe id="ytFrame" src="<?php echo htmlspecialchars(toEmbedUrl($video['video_url'])); ?>" allowfullscreen allow="autoplay; encrypted-media"></iframe>
                <?php if ($show_preview_lock): ?>
                    <div class="iframe-block-layer" id="iframeBlockLayer"></div>
                <?php endif; ?>
            <?php elseif (!empty($video['video_file'])): ?>
                <!-- Direct uploaded file -->
                <video id="mainVideo" controls poster="<?php echo !empty($video['thumbnail']) ? BASE_URL . 'uploads/thumbnails/' . htmlspecialchars($video['thumbnail']) : ''; ?>">
                    <source src="<?php echo BASE_URL; ?>uploads/videos/<?php echo htmlspecialchars($video['video_file']); ?>" type="video/mp4">
                    Your browser does not support video playback.
                </video>
            <?php else: ?>
                <div class="no-source">
                    <i class="fas fa-video-slash" style="font-size: 2rem;"></i>
                    <span>Video source not available yet</span>
                </div>
            <?php endif; ?>

            <?php if ($show_preview_lock): ?>
                <div class="premium-lock-overlay" id="premiumOverlay">
                    <i class="fas fa-crown"></i>
                    <h3>This is a Premium Video</h3>
                    <p>Subscribe to Wild Haryanvi Premium to keep watching this video and unlock all premium content.</p>
                    <a href="<?php echo BASE_URL; ?>pages/premium.php" class="btn btn-primary">Get Premium</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Video Info -->
    <div class="video-info-section">
        <h1 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h1>

        <div class="video-meta-info">
            <div class="meta-item">
                <i class="fas fa-eye"></i>
                <span><?php echo number_format($video['views']); ?> views</span>
            </div>
            <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span><?php echo date('M d, Y', strtotime($video['created_at'])); ?></span>
            </div>
            <?php if (!empty($video['duration'])): ?>
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span><?php echo htmlspecialchars($video['duration']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($video['category_name'])): ?>
            <div class="category-badge">
                <?php echo htmlspecialchars($video['category_name']); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="video-description">
            <strong>Description:</strong><br>
            <?php echo nl2br(htmlspecialchars($video['description'])); ?>
        </div>

        <div class="video-actions">
            <button class="action-btn <?php echo $user_liked ? 'primary' : ''; ?>" id="likeBtn" onclick="toggleVideoLike(<?php echo $video_id; ?>)">
                <i class="<?php echo $user_liked ? 'fas' : 'far'; ?> fa-thumbs-up"></i> <span id="likeCount"><?php echo $like_count; ?></span>
            </button>
            <button class="action-btn primary" onclick="addToFavorites(<?php echo $video_id; ?>)">
                <i class="fas fa-heart"></i> Add to Favorites
            </button>
            <button class="action-btn" onclick="shareVideo()">
                <i class="fas fa-share-alt"></i> Share
            </button>
            <button class="action-btn" onclick="reportVideo()">
                <i class="fas fa-flag"></i> Report
            </button>
        </div>
    </div>

    <!-- Comments -->
    <div class="comments-section" id="comments">
        <h2><i class="fas fa-comments"></i> Comments (<?php echo $comments_result->num_rows; ?>)</h2>

        <form method="POST" action="" class="comment-form">
            <textarea name="comment_text" placeholder="Write a comment..." required></textarea>
            <button type="submit" class="action-btn primary" style="align-self: flex-end;">Post</button>
        </form>

        <?php if ($comments_result->num_rows > 0): ?>
            <?php while ($comment = $comments_result->fetch_assoc()): ?>
                <div class="comment-item">
                    <div class="comment-author">
                        <?php echo htmlspecialchars($comment['username'] ?? 'User'); ?>
                        <span class="comment-time"><?php echo date('M d, Y g:i A', strtotime($comment['created_at'])); ?></span>
                    </div>
                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: var(--text-gray);">No comments yet. Be the first to comment!</p>
        <?php endif; ?>
    </div>

    <!-- Related Videos -->
    <?php if ($related && $related->num_rows > 0): ?>
        <div class="related-videos">
            <h2>More from <?php echo htmlspecialchars($video['category_name'] ?? 'this category'); ?></h2>
            <div class="related-grid">
                <?php
                while ($rel_video = $related->fetch_assoc()) {
                    ?>
                    <a href="<?php echo BASE_URL; ?>pages/watch.php?id=<?php echo $rel_video['id']; ?>" class="video-card">
                        <div class="video-thumbnail">
                            <?php if (!empty($rel_video['thumbnail'])): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($rel_video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($rel_video['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                🎬
                            <?php endif; ?>
                            <?php if (!empty($rel_video['duration'])): ?>
                                <div class="video-duration"><?php echo htmlspecialchars($rel_video['duration']); ?></div>
                            <?php endif; ?>
                            <?php if ($rel_video['access_type'] === 'premium'): ?>
                                <div class="video-badge">PREMIUM</div>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <div class="video-title"><?php echo htmlspecialchars($rel_video['title']); ?></div>
                            <div class="video-category"><?php echo htmlspecialchars($rel_video['category_name'] ?? ''); ?></div>
                            <div class="video-meta">
                                <span><?php echo formatViews($rel_video['views']); ?> views</span>
                            </div>
                        </div>
                    </a>
                    <?php
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function addToFavorites(videoId) {
        fetch('<?php echo BASE_URL; ?>api/add-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ video_id: videoId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Added to favorites!');
            }
        })
        .catch(() => {
            alert('This feature is coming soon!');
        });
    }

    function shareVideo() {
        const url = window.location.href;
        if (navigator.share) {
            navigator.share({
                title: document.querySelector('.video-title').textContent,
                url: url
            });
        } else {
            alert('Share URL: ' + url);
        }
    }

    function reportVideo() {
        alert('Thank you for reporting. Our team will review this content shortly.');
    }

    // LIKE button toggle
    function toggleVideoLike(videoId) {
        fetch('<?php echo BASE_URL; ?>pages/toggle-like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ video_id: videoId })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (data.message === 'Login required') {
                    window.location.href = '<?php echo BASE_URL; ?>pages/login.php';
                }
                return;
            }
            const btn = document.getElementById('likeBtn');
            const icon = btn.querySelector('i');
            const count = document.getElementById('likeCount');

            if (data.liked) {
                btn.classList.add('primary');
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                btn.classList.remove('primary');
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
            count.textContent = data.count;
        });
    }

    // Track and resume video progress (only for uploaded video files, not YouTube embeds)
    const videoPlayer = document.querySelector('video.video-player') || document.querySelector('.video-player video');
    if (videoPlayer) {
        const savedProgress = <?php echo isset($existing_progress) ? intval($existing_progress) : 0; ?>;

        videoPlayer.addEventListener('loadedmetadata', () => {
            if (savedProgress > 0 && savedProgress < videoPlayer.duration - 10) {
                videoPlayer.currentTime = savedProgress;
            }
        });

        setInterval(() => {
            if (!videoPlayer.paused) {
                fetch('<?php echo BASE_URL; ?>pages/save-progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ video_id: <?php echo $video_id; ?>, progress: Math.floor(videoPlayer.currentTime) })
                });
            }
        }, 10000); // every 10 seconds
    }

    <?php if ($show_preview_lock): ?>
        const previewLimit = 10; // seconds before lock appears
        const overlay = document.getElementById('premiumOverlay');

        // Case 1: uploaded video file
        const previewVideo = document.getElementById('mainVideo');
        if (previewVideo) {
            previewVideo.addEventListener('timeupdate', function () {
                if (previewVideo.currentTime >= previewLimit) {
                    previewVideo.pause();
                    previewVideo.currentTime = previewLimit;
                    overlay.classList.add('show');
                }
            });
        }

        // Case 2: YouTube / iframe embed - block interaction after preview time
        const blockLayer = document.getElementById('iframeBlockLayer');
        if (blockLayer) {
            setTimeout(function () {
                blockLayer.classList.add('show');
                overlay.classList.add('show');
            }, previewLimit * 1000);
        }
    <?php endif; ?>

</script>

<?php include '../includes/footer.php'; ?>
