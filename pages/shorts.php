<?php
$page_title = "Shorts - Wild Haryanvi";
require_once '../includes/db.php';

// Find the Shorts category ID dynamically (by slug, so it keeps working even if ID changes)
$cat_stmt = $conn->prepare("SELECT id FROM categories WHERE slug = 'shorts' LIMIT 1");
$cat_stmt->execute();
$cat_row = $cat_stmt->get_result()->fetch_assoc();
$shorts_category_id = $cat_row ? $cat_row['id'] : 0;

// Check subscription status (for premium shorts)
$has_premium = false;
if (isset($_SESSION['user_id'])) {
    $sub_stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
    $sub_stmt->bind_param("i", $_SESSION['user_id']);
    $sub_stmt->execute();
    $has_premium = (bool) $sub_stmt->get_result()->fetch_assoc();
}

// Fetch all published shorts
$shorts_stmt = $conn->prepare("
    SELECT videos.*, categories.name AS category_name
    FROM videos
    LEFT JOIN categories ON videos.category_id = categories.id
    WHERE videos.category_id = ? AND videos.status = 'published'
    ORDER BY videos.created_at DESC
");
$shorts_stmt->bind_param("i", $shorts_category_id);
$shorts_stmt->execute();
$shorts_result = $shorts_stmt->get_result();

$shorts = [];
while ($row = $shorts_result->fetch_assoc()) {
    // Skip premium shorts for non-subscribers
    if ($row['access_type'] === 'premium' && !$has_premium) {
        continue;
    }

    // Like count + whether current user has liked it
    $like_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM video_likes WHERE video_id = ?");
    $like_count_stmt->bind_param("i", $row['id']);
    $like_count_stmt->execute();
    $row['like_count'] = $like_count_stmt->get_result()->fetch_assoc()['total'];

    $row['user_liked'] = false;
    if (isset($_SESSION['user_id'])) {
        $liked_stmt = $conn->prepare("SELECT id FROM video_likes WHERE user_id = ? AND video_id = ?");
        $liked_stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
        $liked_stmt->execute();
        $row['user_liked'] = (bool) $liked_stmt->get_result()->fetch_assoc();
    }

    // Comment count
    $comment_count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM video_comments WHERE video_id = ?");
    $comment_count_stmt->bind_param("i", $row['id']);
    $comment_count_stmt->execute();
    $row['comment_count'] = $comment_count_stmt->get_result()->fetch_assoc()['total'];

    $shorts[] = $row;
}

$is_logged_in = isset($_SESSION['user_id']);

function toEmbedUrl($url) {
    if (strpos($url, 'watch?v=') !== false) {
        $id = explode('watch?v=', $url)[1];
        $id = explode('&', $id)[0];
        return 'https://www.youtube.com/embed/' . $id . '?autoplay=1&mute=1&loop=1&playlist=' . $id . '&enablejsapi=1&playsinline=1';
    }
    if (strpos($url, 'youtu.be/') !== false) {
        $id = explode('youtu.be/', $url)[1];
        return 'https://www.youtube.com/embed/' . $id . '?autoplay=1&mute=1&loop=1&playlist=' . $id . '&enablejsapi=1&playsinline=1';
    }
    return $url;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #000;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
            height: 100vh;
        }

        .shorts-close {
            position: fixed;
            top: 18px;
            left: 18px;
            z-index: 100;
            width: 42px;
            height: 42px;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .shorts-feed {
            height: 100vh;
            width: 100%;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            scroll-behavior: smooth;
        }

        .shorts-feed::-webkit-scrollbar { display: none; }

        .short-slide {
            height: 100vh;
            width: 100%;
            scroll-snap-align: start;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }

        .short-media-wrap {
            position: relative;
            height: 100%;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            background: #111;
        }

        .short-media-wrap iframe,
        .short-media-wrap video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: none;
        }

        .short-premium-tag {
            position: absolute;
            top: 18px;
            right: 18px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            z-index: 5;
        }

        .short-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 2.5rem 1.3rem 1.5rem;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 100%);
            color: #fff;
            z-index: 4;
        }

        .short-overlay h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .short-uploader {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.5rem;
        }

        .short-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #FF4444;
            flex-shrink: 0;
        }

        .short-uploader h3 {
            margin-bottom: 0;
        }

        .short-desc {
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
            margin-bottom: 0.6rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }

        .short-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.85);
            flex-wrap: wrap;
        }

        .short-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .short-meta i { color: #FF4444; }

        .sound-btn {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 44px;
            height: 44px;
            background: rgba(0,0,0,0.55);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
            cursor: pointer;
            z-index: 6;
        }

        .short-premium-tag ~ .sound-btn {
            top: 68px;
        }

        .short-nav-hint {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            z-index: 50;
            text-align: center;
        }

        /* Right-side action bar (like Reels/Shorts) */
        .short-actions {
            position: absolute;
            right: 10px;
            bottom: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.4rem;
            z-index: 6;
        }

        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            background: none;
            border: none;
        }

        .action-circle {
            width: 46px;
            height: 46px;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
            transition: transform 0.15s ease;
        }

        .action-item:active .action-circle {
            transform: scale(0.85);
        }

        .action-item.liked .action-circle {
            color: #FF4444;
        }

        .action-label {
            color: #fff;
            font-size: 0.72rem;
            font-weight: 600;
            text-shadow: 0 1px 4px rgba(0,0,0,0.6);
        }

        .subscribe-circle {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
        }

        /* Comment Modal */
        .comment-modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 200;
            display: none;
            align-items: flex-end;
            justify-content: center;
        }

        .comment-modal.open {
            display: flex;
        }

        .comment-sheet {
            background: #1a1a1a;
            width: 100%;
            max-width: 480px;
            max-height: 70vh;
            border-radius: 20px 20px 0 0;
            display: flex;
            flex-direction: column;
        }

        .comment-sheet-header {
            padding: 1rem 1.3rem;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            font-weight: 700;
        }

        .comment-sheet-header i {
            cursor: pointer;
            color: #b0b0b0;
        }

        .comment-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.3rem;
        }

        .comment-row {
            margin-bottom: 1rem;
        }

        .comment-row .name {
            color: #FF4444;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .comment-row .text {
            color: #fff;
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }

        .comment-row .time {
            color: #777;
            font-size: 0.72rem;
            margin-top: 0.2rem;
        }

        .comment-empty {
            color: #777;
            text-align: center;
            padding: 2rem 0;
            font-size: 0.9rem;
        }

        .comment-input-bar {
            padding: 1rem 1.3rem;
            border-top: 1px solid #333;
            display: flex;
            gap: 0.7rem;
        }

        .comment-input-bar input {
            flex: 1;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 20px;
            padding: 0.7rem 1.1rem;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        .comment-input-bar button {
            background: #FF4444;
            border: none;
            color: #fff;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
        }

        .empty-state {
            color: #b0b0b0;
            text-align: center;
            padding: 2rem;
        }

        .empty-state a {
            color: #FF4444;
            text-decoration: none;
            font-weight: 600;
        }

        @media (min-width: 768px) {
            .short-media-wrap {
                max-width: 400px;
                border-radius: 16px;
                overflow: hidden;
                height: 92vh;
            }
        }
    </style>
</head>
<body>

<a href="<?php echo BASE_URL; ?>" class="shorts-close"><i class="fas fa-times"></i></a>

<?php if (empty($shorts)): ?>
    <div style="height:100vh; display:flex; align-items:center; justify-content:center;">
        <div class="empty-state">
            <i class="fas fa-video-slash" style="font-size:2rem; margin-bottom:1rem; display:block;"></i>
            No shorts available yet.<br>
            <a href="<?php echo BASE_URL; ?>">Go back home</a>
        </div>
    </div>
<?php else: ?>
    <div class="shorts-feed" id="shortsFeed">
        <?php foreach ($shorts as $short): ?>
            <div class="short-slide" data-video-id="<?php echo $short['id']; ?>">
                <div class="short-media-wrap">
                    <?php if ($short['access_type'] === 'premium'): ?>
                        <div class="short-premium-tag">👑 PREMIUM</div>
                    <?php endif; ?>

                    <?php if (!empty($short['video_url'])): ?>
                        <iframe class="short-player" data-src="<?php echo htmlspecialchars(toEmbedUrl($short['video_url'])); ?>" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                        <button class="sound-btn" onclick="toggleSound(this)"><i class="fas fa-volume-mute"></i></button>
                    <?php elseif (!empty($short['video_file'])): ?>
                        <video class="short-player" loop playsinline muted poster="<?php echo !empty($short['thumbnail']) ? BASE_URL . 'uploads/thumbnails/' . htmlspecialchars($short['thumbnail']) : ''; ?>">
                            <source data-src="<?php echo BASE_URL; ?>uploads/videos/<?php echo htmlspecialchars($short['video_file']); ?>" type="video/mp4">
                        </video>
                        <button class="sound-btn" onclick="toggleSound(this)"><i class="fas fa-volume-mute"></i></button>
                    <?php else: ?>
                        <img src="<?php echo !empty($short['thumbnail']) ? BASE_URL . 'uploads/thumbnails/' . htmlspecialchars($short['thumbnail']) : ''; ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php endif; ?>

                    <div class="short-overlay">
                        <div class="short-uploader">
                            <img src="<?php echo BASE_URL; ?>assets/images/logo.jpeg" alt="Wild Haryanvi" class="short-avatar">
                            <h3><?php echo htmlspecialchars($short['title']); ?></h3>
                        </div>
                        <?php if (!empty($short['description'])): ?>
                            <p class="short-desc"><?php echo htmlspecialchars($short['description']); ?></p>
                        <?php endif; ?>
                        <div class="short-meta">
                            <span><i class="fas fa-eye"></i> <span class="view-count-<?php echo $short['id']; ?>"><?php echo number_format($short['views']); ?></span> views</span>
                        </div>
                    </div>

                    <div class="short-actions">
                        <button class="action-item <?php echo $short['user_liked'] ? 'liked' : ''; ?>" onclick="toggleLike(this, <?php echo $short['id']; ?>)">
                            <div class="action-circle"><i class="<?php echo $short['user_liked'] ? 'fas' : 'far'; ?> fa-heart"></i></div>
                            <span class="action-label like-count"><?php echo $short['like_count']; ?></span>
                        </button>

                        <button class="action-item" onclick="openComments(<?php echo $short['id']; ?>)">
                            <div class="action-circle"><i class="far fa-comment"></i></div>
                            <span class="action-label comment-count-<?php echo $short['id']; ?>"><?php echo $short['comment_count']; ?></span>
                        </button>

                        <button class="action-item" onclick="shareShort('<?php echo htmlspecialchars(addslashes($short['title'])); ?>', <?php echo $short['id']; ?>)">
                            <div class="action-circle"><i class="fas fa-share"></i></div>
                            <span class="action-label">Share</span>
                        </button>

                        <a href="<?php echo BASE_URL; ?>pages/premium.php" class="action-item">
                            <div class="action-circle subscribe-circle"><i class="fas fa-crown"></i></div>
                            <span class="action-label">Subscribe</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="short-nav-hint">Swipe up for next ↑</div>
<?php endif; ?>

<!-- Comment Modal -->
<div class="comment-modal" id="commentModal">
    <div class="comment-sheet">
        <div class="comment-sheet-header">
            <span>Comments</span>
            <i class="fas fa-times" onclick="closeComments()"></i>
        </div>
        <div class="comment-list" id="commentList">
            <div class="comment-empty">Loading...</div>
        </div>
        <div class="comment-input-bar">
            <input type="text" id="commentInput" placeholder="<?php echo $is_logged_in ? 'Add a comment...' : 'Login to comment'; ?>" <?php echo $is_logged_in ? '' : 'disabled'; ?>>
            <button onclick="postComment()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
    // Lazy-load each slide's video only when it becomes the active one (saves bandwidth, autoplays current)
    const feed = document.getElementById('shortsFeed');
    if (feed) {
        const slides = document.querySelectorAll('.short-slide');

        function loadAndPlay(slide) {
            const iframe = slide.querySelector('iframe.short-player');
            const video = slide.querySelector('video.short-player');

            if (iframe && !iframe.src) {
                iframe.src = iframe.dataset.src;
                iframe.addEventListener('load', () => {
                    if (soundOn) {
                        setTimeout(() => {
                            iframe.contentWindow.postMessage(JSON.stringify({ event: 'command', func: 'unMute', args: [] }), '*');
                        }, 300);
                    }
                });
            }
            if (video) {
                const source = video.querySelector('source');
                if (source && !source.src) {
                    source.src = source.dataset.src;
                    video.load();
                }
                video.muted = !soundOn;
                video.play().catch(() => {});
            }
        }

        function pauseVideo(slide) {
            const video = slide.querySelector('video.short-player');
            if (video) video.pause();
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadAndPlay(entry.target);
                    trackView(entry.target);
                } else {
                    pauseVideo(entry.target);
                }
            });
        }, { threshold: 0.6 });

        slides.forEach(slide => observer.observe(slide));

        // Track views once per video per page load (avoid double-counting on scroll back-and-forth)
        const viewedVideos = new Set();

        function trackView(slide) {
            const videoId = slide.dataset.videoId;
            if (!videoId || viewedVideos.has(videoId)) return;
            viewedVideos.add(videoId);

            fetch('<?php echo BASE_URL; ?>pages/increment-view.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const el = document.querySelector('.view-count-' + videoId);
                    if (el) el.textContent = data.views.toLocaleString();
                }
            })
            .catch(err => console.error('View tracking failed:', err));
        }

        // Hide the swipe hint after first scroll
        feed.addEventListener('scroll', () => {
            const hint = document.querySelector('.short-nav-hint');
            if (hint) hint.style.display = 'none';
        }, { once: true });
    }

    // Remember user's sound preference across slides (if they unmuted once, keep unmuting new ones)
    let soundOn = false;

    function toggleSound(btn) {
        const wrap = btn.closest('.short-media-wrap');
        const iframe = wrap.querySelector('iframe.short-player');
        const video = wrap.querySelector('video.short-player');
        const icon = btn.querySelector('i');

        soundOn = !soundOn;

        if (video) {
            video.muted = !soundOn;
        }

        if (iframe && iframe.contentWindow) {
            const command = soundOn ? 'unMute' : 'mute';
            iframe.contentWindow.postMessage(JSON.stringify({ event: 'command', func: command, args: [] }), '*');
        }

        icon.classList.toggle('fa-volume-mute', !soundOn);
        icon.classList.toggle('fa-volume-up', soundOn);

        // Apply the same preference to whichever slide becomes active next
        document.querySelectorAll('.sound-btn').forEach(b => {
            const i = b.querySelector('i');
            i.classList.toggle('fa-volume-mute', !soundOn);
            i.classList.toggle('fa-volume-up', soundOn);
        });
    }

    // When a new slide becomes active, apply the current sound preference automatically
    document.addEventListener('DOMContentLoaded', () => {
        const observer2 = new MutationObserver(() => {});
    });

    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    let currentCommentVideoId = null;

    function toggleLike(btn, videoId) {
        if (!isLoggedIn) {
            window.location.href = '<?php echo BASE_URL; ?>pages/login.php';
            return;
        }

        fetch('<?php echo BASE_URL; ?>pages/toggle-like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ video_id: videoId })
        })
        .then(res => {
            if (!res.ok) throw new Error('Server returned ' + res.status);
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                console.error('Like failed:', data.message);
                return;
            }
            const icon = btn.querySelector('i');
            const label = btn.querySelector('.like-count');
            if (data.liked) {
                btn.classList.add('liked');
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                btn.classList.remove('liked');
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
            label.textContent = data.count;
        })
        .catch(err => {
            console.error('Like request failed:', err);
            alert('Could not save like — check that toggle-like.php exists in your pages/ folder.');
        });
    }

    function openComments(videoId) {
        currentCommentVideoId = videoId;
        document.getElementById('commentModal').classList.add('open');
        document.getElementById('commentInput').disabled = !isLoggedIn;
        loadComments(videoId);
    }

    function closeComments() {
        document.getElementById('commentModal').classList.remove('open');
    }

    function loadComments(videoId) {
        const list = document.getElementById('commentList');
        list.innerHTML = '<div class="comment-empty">Loading...</div>';

        fetch('<?php echo BASE_URL; ?>pages/get-comments.php?video_id=' + videoId)
            .then(res => {
                if (!res.ok) throw new Error('Server returned ' + res.status);
                return res.json();
            })
            .then(data => {
                if (!data.success || data.comments.length === 0) {
                    list.innerHTML = '<div class="comment-empty">No comments yet. Be the first!</div>';
                    return;
                }
                list.innerHTML = data.comments.map(c => `
                    <div class="comment-row">
                        <div class="name">${escapeHtml(c.user)}</div>
                        <div class="text">${escapeHtml(c.comment)}</div>
                        <div class="time">${c.time}</div>
                    </div>
                `).join('');
            })
            .catch(err => {
                console.error('Load comments failed:', err);
                list.innerHTML = '<div class="comment-empty">Could not load comments. Check console for details.</div>';
            });
    }

    function postComment() {
        if (!isLoggedIn) {
            window.location.href = '<?php echo BASE_URL; ?>pages/login.php';
            return;
        }

        const input = document.getElementById('commentInput');
        const text = input.value.trim();
        if (!text || !currentCommentVideoId) return;

        fetch('<?php echo BASE_URL; ?>pages/add-comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ video_id: currentCommentVideoId, comment: text })
        })
        .then(res => {
            if (!res.ok) throw new Error('Server returned ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.success) {
                input.value = '';
                loadComments(currentCommentVideoId);
                const countEl = document.querySelector('.comment-count-' + currentCommentVideoId);
                if (countEl) countEl.textContent = parseInt(countEl.textContent || '0') + 1;
            } else {
                alert('Could not post comment: ' + (data.message || 'unknown error'));
            }
        })
        .catch(err => {
            console.error('Post comment failed:', err);
            alert('Could not post comment — check console for details.');
        });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function shareShort(title, videoId) {
        const url = '<?php echo BASE_URL; ?>pages/watch.php?id=' + videoId;
        if (navigator.share) {
            navigator.share({ title: title, url: url });
        } else {
            navigator.clipboard.writeText(url);
            alert('Link copied to clipboard!');
        }
    }
</script>

</body>
</html>
