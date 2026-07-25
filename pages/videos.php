<?php
    $page_title = "Browse Videos - Wild Haryanvi";
    include '../includes/header.php';

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

    // Get search and category filters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? trim($_GET['category']) : ''; // this is now a category ID (number)

    // Fetch categories for the dropdown (dynamic, always matches DB)
    $all_categories = $conn->query("SELECT id, name FROM categories ORDER BY name");

    // Build query
    $query = "SELECT videos.*, categories.name AS category_name FROM videos LEFT JOIN categories ON videos.category_id = categories.id WHERE videos.status='published'";
    $params = [];
    $types = '';

    if (!empty($search)) {
        $search_term = "%$search%";
        $query .= " AND (videos.title LIKE ? OR videos.description LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }

    if (!empty($category)) {
        $query .= " AND videos.category_id = ?";
        $params[] = intval($category);
        $types .= 'i';
    }

    // Check if user is premium for premium videos
    if (!isset($_SESSION['user_id'])) {
        $query .= " AND videos.access_type = 'free'";
    } else {
        $sub_stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
        $sub_stmt->bind_param("i", $_SESSION['user_id']);
        $sub_stmt->execute();
        $subscription = $sub_stmt->get_result()->fetch_assoc();

        if (!$subscription) {
            $query .= " AND videos.access_type = 'free'";
        }
    }

    $query .= " ORDER BY videos.created_at DESC LIMIT 100";

    if (!empty($params)) {
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            die("Prepare Error: " . $conn->error . "<br><br>SQL: " . $query);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }

    // Get the selected category's name (for the results-info line)
    $selected_category_name = '';
    if (!empty($category)) {
        $cat_name_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $cat_name_stmt->bind_param("i", $category);
        $cat_name_stmt->execute();
        $cat_row = $cat_name_stmt->get_result()->fetch_assoc();
        $selected_category_name = $cat_row ? $cat_row['name'] : '';
    }
?>

<style>
    .filter-bar {
        background: linear-gradient(90deg, var(--dark-black) 0%, var(--secondary-black) 100%);
        padding: 2rem;
        margin-top: 2rem;
        border-radius: 15px;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-bar input,
    .filter-bar select {
        padding: 0.8rem 1rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 8px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
        transition: var(--transition);
    }

    .filter-bar input::placeholder {
        color: var(--text-gray);
    }

    .filter-bar input:focus,
    .filter-bar select:focus {
        outline: none;
        border-color: var(--primary-red);
        box-shadow: 0 0 15px rgba(255, 68, 68, 0.3);
    }

    .filter-btn {
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        color: var(--white);
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
    }

    .filter-btn:hover {
        box-shadow: 0 5px 15px rgba(255, 68, 68, 0.4);
    }

    .results-info {
        margin-top: 2rem;
        color: var(--text-gray);
    }
</style>

<div class="section">
    <div class="filter-bar">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; width: 100%;">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

            <select name="category" style="min-width: 150px;">
                <option value="">All Categories</option>
                <?php
                if ($all_categories && $all_categories->num_rows > 0) {
                    while ($cat = $all_categories->fetch_assoc()) {
                        $selected = ($category == $cat['id']) ? 'selected' : '';
                        echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                    }
                }
                ?>
            </select>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>

    <div class="results-info">
        <?php
        if (!empty($search)) {
            echo "Search results for: <strong>" . htmlspecialchars($search) . "</strong>";
        }
        if (!empty($selected_category_name)) {
            echo " in <strong>" . htmlspecialchars($selected_category_name) . "</strong>";
        }
        echo " (" . ($result ? $result->num_rows : 0) . " videos)";
        ?>
    </div>

    <div class="video-grid" style="margin-top: 2rem;">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $is_premium = $row['access_type'] == 'premium' ? true : false;
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
        } else {
            echo '<p style="grid-column: 1/-1; text-align: center; color: #b0b0b0; font-size: 1.1rem;">No videos found. Try different filters!</p>';
        }
        ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
