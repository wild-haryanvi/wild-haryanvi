<?php
$page_title = "Wild Haryanvi - Haryanvi Entertainment";
include 'includes/header.php';

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

// Reusable function to render a video card
function renderVideoCard($row) {
    $is_premium = $row['access_type'] === 'premium';
    ?>
    <a href="<?php echo BASE_URL; ?>pages/watch.php?id=<?php echo $row['id']; ?>" class="video-card" data-video-id="<?php echo $row['id']; ?>">
        <div class="video-thumbnail">
            <?php if (!empty($row['thumbnail'])): ?>
                <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($row['thumbnail']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                🎬
            <?php endif; ?>
            <?php if (!empty($row['duration'])): ?>
                <div class="video-duration"><?php echo htmlspecialchars($row['duration']); ?></div>
            <?php endif; ?>
            <?php if ($is_premium): ?>
                <div class="video-badge">PREMIUM</div>
            <?php endif; ?>
            <div class="video-play-btn">
                <i class="fas fa-play"></i>
            </div>
        </div>
        <div class="video-info">
            <div class="video-title"><?php echo htmlspecialchars($row['title']); ?></div>
            <div class="video-category"><?php echo htmlspecialchars($row['category_name'] ?? ''); ?></div>
            <div class="video-meta">
                <span><?php echo formatViews($row['views']); ?> views</span>
                <span><?php echo getTimeAgo($row['created_at']); ?></span>
            </div>
        </div>
    </a>
    <?php
}
$categories_list = $conn->query("SELECT id, name, slug FROM categories ORDER BY id");
?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Welcome to <span class="highlight">Wild Haryanvi</span></h1>
                <p>Your ultimate destination for authentic Haryanvi entertainment. Watch exclusive songs, documentaries, shorts, and news.</p>
                <div class="hero-buttons">
                    <a href="pages/videos.php" class="btn btn-primary">Explore Videos</a>
                    <a href="pages/premium.php" class="btn btn-secondary">Subscribe Premium</a>
                </div>
            </div>
            <div class="hero-image">
                <?php
                $hero_result = $conn->query("
                    SELECT videos.*, categories.name AS category_name
                    FROM videos
                    LEFT JOIN categories ON videos.category_id = categories.id
                    WHERE videos.status='published'
                    ORDER BY videos.created_at DESC LIMIT 1
                ");

                $hero_video = $hero_result->fetch_assoc();

                if ($hero_video && !empty($hero_video['thumbnail'])) {
                    $is_premium = $hero_video['access_type'] === 'premium';
                    echo '<a href="' . BASE_URL . 'pages/watch.php?id=' . $hero_video['id'] . '" class="hero-video-card">';
                    echo '<img src="' . BASE_URL . 'uploads/thumbnails/' . htmlspecialchars($hero_video['thumbnail']) . '" alt="' . htmlspecialchars($hero_video['title']) . '">';
                    if ($is_premium) {
                        echo '<div class="hero-premium-tag">👑 PREMIUM</div>';
                    }
                    echo '<div class="hero-play-btn"><i class="fas fa-play"></i></div>';
                    echo '<div class="hero-video-overlay">';
                    echo '<span class="hero-video-tag">🔥 Latest Upload</span>';
                    echo '<h3>' . htmlspecialchars($hero_video['title']) . '</h3>';
                    echo '<div class="hero-video-meta">';
                    if (!empty($hero_video['category_name'])) {
                        echo '<span><i class="fas fa-tag"></i> ' . htmlspecialchars($hero_video['category_name']) . '</span>';
                    }
                    if (!empty($hero_video['duration'])) {
                        echo '<span><i class="fas fa-clock"></i> ' . htmlspecialchars($hero_video['duration']) . '</span>';
                    }
                    echo '<span><i class="fas fa-eye"></i> ' . number_format($hero_video['views']) . ' views</span>';
                    echo '<span><i class="fas fa-calendar"></i> ' . getTimeAgo($hero_video['created_at']) . '</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '</a>';
                } else {
                    echo '<div class="hero-image-placeholder">🎬</div>';
                }
                ?>
            </div>
        </div>
    </section>
    
    <!-- Categories Chips -->
    <section class="chips-section">
        <div class="chips-row">
            <a href="<?php echo BASE_URL; ?>pages/videos.php" class="chip chip-active">All</a>
            <?php
            if ($categories_list && $categories_list->num_rows > 0) {
                while ($cat = $categories_list->fetch_assoc()) {
                    $chip_link = ($cat['slug'] === 'shorts')
                        ? BASE_URL . 'pages/shorts.php'
                        : BASE_URL . 'pages/videos.php?category=' . $cat['id'];
                    ?>
                    <a href="<?php echo $chip_link; ?>" class="chip"><?php echo htmlspecialchars($cat['name']); ?></a>
                    <?php
                }
            }
            ?>
        </div>
    </section>

    <!-- Latest Videos -->
    <section class="section">
        <h2 class="section-title">Latest Videos</h2>
        <div class="video-grid">
            <?php
            $result = $conn->query("
                SELECT videos.*, categories.name AS category_name
                FROM videos
                LEFT JOIN categories ON videos.category_id = categories.id
                WHERE videos.status='published'
                ORDER BY videos.created_at DESC LIMIT 8
            ");

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    renderVideoCard($row);
                }
            } else {
                echo '<p style="grid-column: 1/-1; text-align: center; color: #b0b0b0;">No videos available yet. Check back soon!</p>';
            }
            ?>
        </div>
    </section>

    <!-- Featured Videos -->
    <section class="section">
        <h2 class="section-title">Featured</h2>
        <div class="video-grid">
            <?php
            $result = $conn->query("
                SELECT videos.*, categories.name AS category_name
                FROM videos
                LEFT JOIN categories ON videos.category_id = categories.id
                WHERE videos.is_featured=1 AND videos.status='published'
                LIMIT 8
            ");

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    renderVideoCard($row);
                }
            } else {
                echo '<p style="grid-column: 1/-1; text-align: center; color: #b0b0b0;">No featured videos available yet.</p>';
            }
            ?>
        </div>
    </section>

    <!-- Trending Videos -->
    <section class="section">
        <h2 class="section-title">Trending Now</h2>
        <div class="video-grid">
            <?php
            $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
            $stmt = $conn->prepare("
                SELECT videos.*, categories.name AS category_name
                FROM videos
                LEFT JOIN categories ON videos.category_id = categories.id
                WHERE videos.status='published' AND videos.created_at > ?
                ORDER BY videos.views DESC LIMIT 8
            ");
            $stmt->bind_param("s", $week_ago);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    renderVideoCard($row);
                }
            } else {
                echo '<p style="grid-column: 1/-1; text-align: center; color: #b0b0b0;">No trending videos available yet.</p>';
            }
            ?>
        </div>
    </section>


<?php include 'includes/footer.php'; ?>
