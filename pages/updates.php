<?php
$page_title = "Updates - Wild Haryanvi";
include '../includes/header.php';

// Fetch upcoming videos from database (dynamic, not hardcoded)
$upcoming_videos = $conn->query("
    SELECT videos.*, categories.name AS category_name
    FROM videos
    LEFT JOIN categories ON videos.category_id = categories.id
    WHERE videos.status = 'upcoming'
    ORDER BY videos.release_date ASC
");
$upcoming_count = $upcoming_videos ? $upcoming_videos->num_rows : 0;

$announcements = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC");

?>

<style>
    .updates-hero {
        background: linear-gradient(135deg, var(--dark-black) 0%, var(--secondary-black) 50%, var(--primary-red) 150%);
        padding: 5rem 2rem 4rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .updates-hero::before {
        content: '';
        position: absolute;
        top: -30%;
        left: -10%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(255, 68, 68, 0.15) 0%, transparent 70%);
        border-radius: 50%;
    }

    .updates-hero-content {
        position: relative;
        max-width: 700px;
        margin: 0 auto;
    }

    .updates-hero-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 68, 68, 0.15);
        border: 1px solid rgba(255, 68, 68, 0.4);
        color: var(--primary-red);
        padding: 0.5rem 1.2rem;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 1.5rem;
    }

    .updates-hero h1 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 1rem;
        line-height: 1.15;
    }

    .updates-hero p {
        color: var(--text-gray);
        font-size: 1.15rem;
        line-height: 1.7;
    }

    .updates-hero-stats {
        display: flex;
        justify-content: center;
        gap: 3rem;
        margin-top: 2.5rem;
        flex-wrap: wrap;
    }

    .hero-stat {
        text-align: center;
    }

    .hero-stat .num {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary-red);
    }

    .hero-stat .label {
        font-size: 0.85rem;
        color: var(--text-gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .updates-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 4rem 2rem;
    }

    /* Section Title */
    .updates-section-title {
        font-size: 1.7rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .updates-section-title::before {
        content: '';
        width: 5px;
        height: 28px;
        background: linear-gradient(180deg, var(--primary-red) 0%, transparent 100%);
        border-radius: 5px;
    }

    .updates-section-sub {
        color: var(--text-gray);
        font-size: 0.95rem;
        margin-bottom: 2rem;
        padding-left: 1.3rem;
    }

    /* Upcoming Videos Grid */
    .upcoming-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
        gap: 1.8rem;
        margin-bottom: 5rem;
    }

    .upcoming-card {
        background: linear-gradient(180deg, var(--secondary-black) 0%, var(--light-black) 100%);
        border-radius: 18px;
        overflow: hidden;
        transition: var(--transition);
        border: 1px solid var(--light-black);
    }

    .upcoming-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 45px rgba(255, 68, 68, 0.25);
        border-color: var(--primary-red);
    }

    .upcoming-thumb {
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

    .upcoming-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .upcoming-thumb::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 45%);
    }

    .upcoming-tag {
        position: absolute;
        top: 12px;
        left: 12px;
        background: var(--primary-red);
        color: var(--white);
        padding: 0.35rem 0.9rem;
        border-radius: 20px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        z-index: 2;
        box-shadow: 0 4px 12px rgba(255, 68, 68, 0.4);
    }

    .upcoming-info {
        padding: 1.4rem;
    }

    .upcoming-title {
        font-size: 1.08rem;
        font-weight: 700;
        margin-bottom: 0.7rem;
        color: var(--white);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
    }

    .upcoming-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .upcoming-category {
        display: inline-block;
        background: var(--light-black);
        color: var(--primary-red);
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .upcoming-date {
        color: var(--text-gray);
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .no-upcoming {
        grid-column: 1/-1;
        text-align: center;
        color: var(--text-gray);
        padding: 4rem 1rem;
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        border-radius: 18px;
        border: 1px dashed var(--light-black);
    }

    .no-upcoming i {
        font-size: 2.5rem;
        color: var(--primary-red);
        opacity: 0.6;
        margin-bottom: 1rem;
        display: block;
    }

    /* Announcements Timeline */
    .announcements-list {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    .announcement-card {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 1.8rem 2rem;
        border-radius: 16px;
        border-left: 4px solid var(--primary-red);
        transition: var(--transition);
        display: flex;
        gap: 1.5rem;
        align-items: flex-start;
    }

    .announcement-card:hover {
        transform: translateX(8px);
        box-shadow: 0 10px 30px rgba(255, 68, 68, 0.2);
    }

    .announcement-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        background: rgba(255, 68, 68, 0.12);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }

    .announcement-body {
        flex: 1;
    }

    .announcement-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.4rem;
    }

    .announcement-badge {
        display: inline-block;
        background: rgba(255, 68, 68, 0.15);
        color: var(--primary-red);
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .announcement-date {
        color: var(--text-gray);
        font-size: 0.82rem;
    }

    .announcement-card h3 {
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
        color: var(--white);
    }

    .announcement-content {
        color: var(--text-gray);
        line-height: 1.7;
        font-size: 0.95rem;
    }

    @media (max-width: 768px) {
        .updates-hero {
            padding: 3.5rem 1.5rem 3rem;
        }
        .updates-hero h1 {
            font-size: 2rem;
        }
        .updates-hero-stats {
            gap: 2rem;
        }
        .updates-container {
            padding: 3rem 1.2rem;
        }
        .upcoming-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        .announcement-card {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>

<!-- Hero -->
<div class="updates-hero">
    <div class="updates-hero-content">
        <?php if ($upcoming_count > 0): ?>
            <a href="#upcoming-releases" class="updates-hero-tag" style="text-decoration: none;">🔥 What's New</a>
        <?php endif; ?>
        <h1>Latest Updates & Releases</h1>
        <p>Everything coming to Wild Haryanvi — new content and announcements in one place.</p>
        <div class="updates-hero-stats">
            <div class="hero-stat">
                <div class="num"><?php echo $upcoming_count; ?></div>
                <div class="label">Upcoming</div>
            </div>
            <div class="hero-stat">
                <div class="num">Weekly</div>
                <div class="label">New Content</div>
            </div>
        </div>
    </div>
</div>

<div class="updates-container">
    <!-- Upcoming Videos (dynamic from DB) -->
    <h2 class="updates-section-title" id="upcoming-releases">Upcoming Releases</h2>
    <p class="updates-section-sub">Fresh content lined up and ready to drop</p>

    <div class="upcoming-grid">
        <?php
        if ($upcoming_videos && $upcoming_videos->num_rows > 0) {
            while ($video = $upcoming_videos->fetch_assoc()) {
                ?>
                <div class="upcoming-card">
                    <div class="upcoming-thumb">
                        <span class="upcoming-tag">Upcoming</span>
                        <?php if (!empty($video['thumbnail'])): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                        <?php else: ?>
                            🎬
                        <?php endif; ?>
                    </div>
                    <div class="upcoming-info">
                        <div class="upcoming-title"><?php echo htmlspecialchars($video['title']); ?></div>
                        <div class="upcoming-footer">
                            <?php if (!empty($video['category_name'])): ?>
                                <span class="upcoming-category"><?php echo htmlspecialchars($video['category_name']); ?></span>
                            <?php endif; ?>
                            <div class="upcoming-date">
                                <i class="fas fa-calendar"></i>
                                <?php
                                echo !empty($video['release_date'])
                                    ? date('d M Y', strtotime($video['release_date']))
                                    : 'TBA';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="no-upcoming"><i class="fas fa-film"></i>No upcoming releases announced yet. Check back soon!</div>';
        }
        ?>
    </div>

    <!-- Announcements -->
    <h2 class="updates-section-title">Announcements</h2>
    <p class="updates-section-sub">Platform news and important updates</p>

    <div class="announcements-list">
        <?php
        if ($announcements && $announcements->num_rows > 0) {
            while ($a = $announcements->fetch_assoc()) {
                ?>
                <div class="announcement-card">
                    <div class="announcement-icon"><?php echo $a['icon']; ?></div>
                    <div class="announcement-body">
                        <div class="announcement-top">
                            <span class="announcement-badge"><?php echo htmlspecialchars($a['badge']); ?></span>
                            <span class="announcement-date"><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($a['created_at'])); ?></span>
                        </div>
                        <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                        <div class="announcement-content"><?php echo nl2br(htmlspecialchars($a['content'])); ?></div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p style="color: var(--text-gray); text-align:center;">No announcements posted yet.</p>';
        }
        ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
