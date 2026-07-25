<?php
$page_title = "Admin Dashboard - Wild Haryanvi";
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get statistics
$total_videos = $conn->query("SELECT COUNT(*) as total FROM videos")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_views = $conn->query("SELECT SUM(views) as total FROM videos")->fetch_assoc()['total'];
$total_likes = $conn->query("SELECT COUNT(*) as total FROM video_likes")->fetch_assoc()['total'];
$premium_subscribers = $conn->query("SELECT COUNT(*) as total FROM subscriptions WHERE status='active'")->fetch_assoc()['total'];
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM subscriptions WHERE status IN ('active', 'cancelled')")->fetch_assoc()['total'] ?? 0;

// Handle video actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'upload') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = trim($_POST['category']);
            $duration = trim($_POST['duration']);
            $type = trim($_POST['type']);
            $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : date('Y-m-d H:i:s');
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $video_url = trim($_POST['video_url']);

            if (empty($title) || empty($category)) {
                $error = 'Title and category are required!';
            } else {
                $thumbnail_name = '';

                // Handle thumbnail upload
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $thumbnail_name = uniqid('thumb_') . '.' . $ext;
                        $upload_path = '../uploads/thumbnails/' . $thumbnail_name;
                        move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path);
                    } else {
                        $error = 'Invalid thumbnail format! Use jpg, jpeg, png, or webp.';
                    }
                }

                // Handle video file upload
                $video_file_name = '';
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
                    $allowed_video = ['mp4', 'mov', 'webm'];
                    $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed_video)) {
                        $video_file_name = uniqid('video_') . '.' . $ext;
                        $upload_path = '../uploads/videos/' . $video_file_name;
                        move_uploaded_file($_FILES['video_file']['tmp_name'], $upload_path);
                    }
                }

                if (empty($error)) {
                    // category_id, duration, access_type match your ACTUAL table columns
                    $stmt = $conn->prepare("INSERT INTO videos (title, description, category_id, duration, access_type, status, uploaded_by, thumbnail, video_url, video_file, release_date, is_featured) VALUES (?, ?, ?, ?, ?, 'upcoming', ?, ?, ?, ?, ?, ?)");

                    if (!$stmt) {
                        die("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("ssississssi", $title, $description, $category, $duration, $type, $admin_id, $thumbnail_name, $video_url, $video_file_name, $release_date, $is_featured);
                    
                    if ($stmt->execute()) {
                        $success = 'Video uploaded successfully!';
                    } else {
                        $error = 'Error uploading video! DB says: ' . $stmt->error;
                    }
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $video_id = intval($_POST['video_id']);
            $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
            $stmt->bind_param("i", $video_id);

            if ($stmt->execute()) {
                $success = 'Video deleted successfully!';
            } else {
                $error = 'Error deleting video!';
            }
        } elseif ($_POST['action'] === 'publish') {
            $video_id = intval($_POST['video_id']);
            $stmt = $conn->prepare("UPDATE videos SET status = 'published' WHERE id = ?");
            $stmt->bind_param("i", $video_id);

            if ($stmt->execute()) {
                $success = 'Video published successfully!';

                // Auto-create a notification

                $v_stmt = $conn->prepare("SELECT title, access_type FROM videos WHERE id = ?");
                $v_stmt->bind_param("i", $video_id);
                $v_stmt->execute();
                $v_data = $v_stmt->get_result()->fetch_assoc();

                if ($v_data) {
                    $is_premium = $v_data['access_type'] === 'premium';
                    $notif_title = $is_premium ? "New Premium Release!" : 'New Video Uploaded!';
                    $notif_message = $v_data['title'];
                    $notif_type = $is_premium ? 'premium_release' : 'new_upload';

                    $notif_stmt = $conn->prepare("INSERT INTO notifications (title, message, video_id, type ) VALUES (?, ?, ?, ?)");
                    $notif_stmt->bind_param("ssis", $notif_title, $notif_message, $video_id, $notif_type);
                    $notif_stmt->execute();
                }

            } else {
                $error = 'Error publishing video!';
            }
        }
    }
}

// Get recent videos WITH category name (joined from categories table)
$recent_videos = $conn->query("
    SELECT videos.*, categories.name AS category_name,
           (SELECT COUNT(*) FROM video_likes WHERE video_likes.video_id = videos.id) AS like_count
    FROM videos
    LEFT JOIN categories ON videos.category_id = categories.id
    WHERE videos.uploaded_by = $admin_id
    ORDER BY videos.created_at DESC LIMIT 20
");

include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1400px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .admin-header h1 {
        font-size: 2rem;
    }

    .quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 3rem;
}

.quick-action-card {
    background: linear-gradient(145deg, var(--secondary-black) 0%, var(--light-black) 100%);
    border: 2px solid var(--light-black);
    border-radius: 15px;
    padding: 1.5rem 1rem;
    text-decoration: none;
    color: var(--white);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.8rem;
    transition: var(--transition);
}

.quick-action-card:hover {
    border-color: var(--primary-red);
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(255, 68, 68, 0.25);
}

.qa-icon {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255,68,68,0.15) 0%, rgba(255,68,68,0.05) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--primary-red);
    transition: var(--transition);
}

.quick-action-card:hover .qa-icon {
    background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
    color: var(--white);
    transform: scale(1.1);
}

.quick-action-card span {
    font-weight: 600;
    font-size: 0.9rem;
}

@media (max-width: 480px) {
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
    }

    .quick-action-card {
        padding: 1.2rem 0.6rem;
    }

    .qa-icon {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }

    .quick-action-card span {
        font-size: 0.8rem;
    }
}

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
        border-left: 5px solid var(--primary-red);
    }

    .stat-card h3 {
        color: var(--text-gray);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
    }

    .stat-card .value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-red);
    }

    .admin-section {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
        margin-bottom: 2rem;
    }

    .admin-section h2 {
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        border-bottom: 2px solid var(--primary-red);
        padding-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.8rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 8px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
        transition: var(--transition);
        display: inline-block;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-red);
        box-shadow: 0 0 15px rgba(255, 68, 68, 0.3);
    }

    .admin-btn {
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        color: var(--white);
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .admin-btn:hover {
        box-shadow: 0 10px 30px rgba(255, 68, 68, 0.4);
        transform: translateY(-3px);
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .videos-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .videos-table th {
        background: var(--light-black);
        padding: 1rem;
        text-align: left;
        border-bottom: 2px solid var(--primary-red);
    }

    .videos-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--secondary-black);
    }

    .videos-table tr:hover {
        background: var(--light-black);
    }

    .admin-section {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    @media(max-width: 768px) {
        .videos-table {
            min-width: 700px;
        }
    }

    .status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 5px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-published {
        background: rgba(76, 175, 80, 0.2);
        color: #38cd38;
    }

    .status-upcoming {
        background: rgba(255, 68, 68, 0.2);
        color: #e06262;
    }

    .action-btns {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 0.4rem 0.8rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .action-btn.delete {
        background: rgba(255, 68, 68, 0.3);
        color: #e14949;
    }

    .action-btn.delete:hover {
        background: rgba(255, 68, 68, 0.5);
    }

    .action-btn.publish {
        background: rgba(76, 175, 80, 0.3);
        color: #17c317;
    }

    .action-btn.publish:hover {
        background: rgba(76, 175, 80, 0.5);
    }

    .action-btn.edit {
        background: rgba(255, 193, 7, 0.3);
        color: #c9a11d;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .action-btn.edit:hover {
        background: rgba(255, 193, 7, 0.5);
    }

    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.2);
        color: #0e920e;
        border: 1px solid rgba(76, 175, 80, 0.5);
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #d62929;
        border: 1px solid rgba(255, 68, 68, 0.5);
    }

    /* Responsive Admin Dashboard */

    /* Tablet */
    @media (max-width: 992px){

        .admin-container{
            padding:0 15px;
        }

        /* .admin-header{
            flex-direction:column;
            align-items:center;
            text-align:center;
            gap:20px;
        } */

        .admin-header h1{
            font-size:2rem;
            margin:0;
        }

        .admin-header > div{
            width:100%;
            display:flex !important;
            flex-direction:column;
            gap:12px;
        }

        /* .admin-header .admin-btn{
            width:100%;
            text-align:center;
            padding:14px;
        } */

        .stats-grid{
            grid-template-columns:repeat(2,1fr);
        }
    }

    /* Mobile */
    @media (max-width:768px){

        .admin-container{
            margin:20px auto;
            padding:0 12px;
        }

        .admin-header h1{
            font-size:32px;
            line-height:1.3;
        }

        .stats-grid{
            grid-template-columns:1fr;
        }

        .stat-card{
            padding:20px;
        }

        .stat-card .value{
            font-size:34px;
        }

        .form-row{
            grid-template-columns:1fr;
        }

        .admin-btn{
            width:100%;
            display:block;
        }

        .videos-table{
            min-width:800px;
        }
    }

    /* Small Mobile */
    @media (max-width:480px){

        .admin-header h1{
            font-size:28px;
        }

        .admin-section{
            padding:18px;
        }

        .admin-btn{
            font-size:15px;
            padding:13px;
        }

    }

</style>

<div class="admin-container">
    <!-- <div class="admin-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="<?php echo BASE_URL; ?>pages/settings.php" class="admin-btn" style="text-decoration: none;">
                <i class="fas fa-user-cog"></i> Admin Account Settings
            </a>
            <a href="<?php echo BASE_URL; ?>pages/manage-users.php" class="admin-btn" style="text-decoration: none;">
                <i class="fas fa-users"></i>  Manage Users
            </a>
            <a href="<?php echo BASE_URL; ?>pages/manage-subscriptions.php" class="admin-btn" style="text-decoration: none;">
                <i class="fas fa-credit-card"></i> Manage Subscriptions
            </a>
            <!-- <a href="<?php echo BASE_URL; ?>pages/manage-payments.php" class="admin-btn" style="text-decoration: none;">
                <i class="fas fa-money-check-alt"></i> Pending Payments
            </a> -->
            <!-- <a href="<?php echo BASE_URL; ?>pages/announcements.php" class="admin-btn" style="text-decoration: none;">
                <i class="fas fa-bullhorn"></i> Manage Announcements
            </a>
            <a href="<?php echo BASE_URL; ?>pages/manage-comments.php" class="admin-btn" style="text-decoration: none;">
                <i class="fas fa-comments"></i> Manage Comments
            </a>
            <a href="<?php echo BASE_URL; ?>pages/manage-contact.php" class="admin-btn" style="text-decoration: none;">
                <i class="fas fa-envelope"></i> Contact Messages
            </a>
        </div>
    </div> -->

    <!-- exam -->
    <div class="admin-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
    </div>

    <div class="quick-actions-grid">
        <a href="<?php echo BASE_URL; ?>pages/settings.php" class="quick-action-card">
            <div class="qa-icon"><i class="fas fa-user-cog"></i></div>
            <span>Account Settings</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/manage-users.php" class="quick-action-card">
            <div class="qa-icon"><i class="fas fa-users"></i></div>
            <span>Manage Users</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/manage-subscriptions.php" class="quick-action-card">
            <div class="qa-icon"><i class="fas fa-credit-card"></i></div>
            <span>Subscriptions</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/announcements.php" class="quick-action-card">
            <div class="qa-icon"><i class="fas fa-bullhorn"></i></div>
            <span>Announcements</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/manage-comments.php" class="quick-action-card">
            <div class="qa-icon"><i class="fas fa-comments"></i></div>
            <span>Comments</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/manage-contact.php" class="quick-action-card">
            <div class="qa-icon"><i class="fas fa-envelope"></i></div>
            <span>Contact Messages</span>
        </a>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-film"></i> Total Videos</h3>
            <div class="value"><?php echo $total_videos; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-users"></i> Total Users</h3>
            <div class="value"><?php echo $total_users; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-eye"></i> Total Views</h3>
            <div class="value"><?php echo formatNumber($total_views); ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-crown"></i> Premium Members</h3>
            <div class="value"><?php echo $premium_subscribers; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-rupee-sign"></i> Total Revenue</h3>
            <div class="value">₹<?php echo number_format($total_revenue, 0); ?></div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Upload Video Form -->
    <div class="admin-section">
        <h2><i class="fas fa-upload"></i> Upload Video</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">

            <div class="form-row">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Title *</label>
                    <input type="text" name="title" required>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Thumbnail Image *</label>
                    <input type="file" name="thumbnail" accept="image/*" required>
                </div>

                <div class="form-group">
                    <label>Video File</label>
                    <input type="file" name="video_file" accept="video/*">
                </div>
            </div>

            <div class="form-group">
                <label>OR Video URL (YouTube/embed link)</label>
                <input type="text" name="video_url" placeholder="https://youtube.com/embed/...">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        $cat_result = $conn->query("SELECT id, name FROM categories ORDER BY id");
                        while ($cat = $cat_result->fetch_assoc()) {
                            echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="free">Free</option>
                        <option value="premium">Premium</option>
                    </select>
                </div>


                <div class="form-group">
                    <label>Release Date & Time</label>
                    <input type="datetime-local" name="release_date">
                </div>

                <div class="form-group">
                    <input type="hidden" name="duration" id="duration">
                </div>

            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.6rem;">
                <input type="checkbox" name="is_featured" id="is_featured" value="1" style="width: auto;">
                <label for="is_featured" style="margin-bottom: 0;">Mark as Featured</label>
            </div>

            <button type="submit" class="admin-btn">
                <i class="fas fa-cloud-upload-alt"></i> Upload Video
            </button>
        </form>

        <script>
            document.querySelector('input[name="video_file"]').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const tempVideo = document.createElement('video');
                tempVideo.preload = 'metadata';

                tempVideo.onloadedmetadata = function() {
                    window.URL.revokeObjectURL(tempVideo.src);
                    const totalSeconds = Math.floor(tempVideo.duration);
                    const hrs = Math.floor(totalSeconds / 3600);
                    const mins = Math.floor((totalSeconds % 3600) / 60);
                    const secs = totalSeconds % 60;

                    const formatted =
                        String(hrs).padStart(2, '0') + ':' +
                        String(mins).padStart(2, '0') + ':' +
                        String(secs).padStart(2, '0');

                    document.getElementById('duration').value = formatted;
                };

                tempVideo.src = URL.createObjectURL(file);
            });
        </script>
    </div>

    <!-- Videos List -->
    <div class="admin-section">
        <h2><i class="fas fa-list"></i> Your Videos</h2>
        <table class="videos-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Likes</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($recent_videos && $recent_videos->num_rows > 0) {
                    while ($video = $recent_videos->fetch_assoc()) {
                        $status_class = 'status-' . $video['status'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($video['title']); ?></td>
                            <td><?php echo htmlspecialchars($video['category_name']); ?></td>
                            <td><?php echo ucfirst($video['access_type']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($video['status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($video['views']); ?></td>
                            <td><i class="fas fa-thumbs-up" style="color: var(--primary-red); margin-right: 0.3rem;"></i><?php echo number_format($video['like_count']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($video['created_at'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($video['status'] === 'upcoming'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="publish">
                                            <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                            <button type="submit" class="action-btn publish" title="Publish">
                                                <i class="fas fa-check"></i> Publish
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?php echo BASE_URL; ?>pages/edit-video.php?id=<?php echo $video['id']; ?>" class="action-btn edit" title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this video?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                        <button type="submit" class="action-btn delete" title="Delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>

                                    </form>
                                        
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="7" style="text-align: center; color: var(--text-gray);">No videos uploaded yet.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
function formatNumber($num) {
    if ($num >= 1000000) {
        return number_format($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return number_format($num / 1000, 1) . 'K';
    }
    return number_format($num);
}
?>

<?php include '../includes/footer.php'; ?>
