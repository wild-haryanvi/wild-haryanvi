<?php
$page_title = "Edit Video - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$video_id) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}

$success = '';
$error = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $duration = trim($_POST['duration']);
    $type = trim($_POST['type']);
    $video_url = trim($_POST['video_url']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title) || empty($category)) {
        $error = 'Title and category are required!';
    } else {
        // Get current video data (in case thumbnail/video file isn't being replaced)
        $current_stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
        $current_stmt->bind_param("i", $video_id);
        $current_stmt->execute();
        $current = $current_stmt->get_result()->fetch_assoc();

        $thumbnail_name = $current['thumbnail'];
        $video_file_name = $current['video_file'];

        // Replace thumbnail if a new one was uploaded
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $thumbnail_name = uniqid('thumb_') . '.' . $ext;
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../uploads/thumbnails/' . $thumbnail_name);
            } else {
                $error = 'Invalid thumbnail format! Use jpg, jpeg, png, or webp.';
            }
        }

        // Replace video file if a new one was uploaded
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
            $allowed_video = ['mp4', 'mov', 'webm'];
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_video)) {
                $video_file_name = uniqid('video_') . '.' . $ext;
                move_uploaded_file($_FILES['video_file']['tmp_name'], '../uploads/videos/' . $video_file_name);
            }
        }

        if (empty($error)) {
            $update_stmt = $conn->prepare("
                UPDATE videos SET
                    title = ?, description = ?, category_id = ?, duration = ?,
                    access_type = ?, thumbnail = ?, video_url = ?, video_file = ?, is_featured = ?
                WHERE id = ?
            ");

            if (!$update_stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $update_stmt->bind_param(
                "ssisssssii",
                $title, $description, $category, $duration, $type,
                $thumbnail_name, $video_url, $video_file_name, $is_featured, $video_id
            );

            if ($update_stmt->execute()) {
                $success = 'Video updated successfully!';
            } else {
                $error = 'Error updating video: ' . $update_stmt->error;
            }
        }
    }
}

// Fetch current video data (fresh, after any update)
$video_stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
$video_stmt->bind_param("i", $video_id);
$video_stmt->execute();
$video = $video_stmt->get_result()->fetch_assoc();

if (!$video) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}

$categories = $conn->query("SELECT id, name FROM categories ORDER BY id");

include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 800px;
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

    .back-link {
        color: var(--primary-red);
        text-decoration: none;
        font-weight: 600;
    }

    .admin-section {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
    }

    .form-group {
        margin-bottom: 1.2rem;
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
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .current-thumb {
        width: 160px;
        aspect-ratio: 16/9;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 0.8rem;
        border: 1px solid var(--light-black);
    }

    .current-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .admin-btn {
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        color: var(--white);
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .checkbox-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .checkbox-row input {
        width: auto;
    }

    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.2);
        color: #8bff8b;
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #ff8888;
    }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-edit"></i> Edit Video</h1>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="admin-section">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($video['title']); ?>" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($video['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Current Thumbnail</label>
                <?php if (!empty($video['thumbnail'])): ?>
                    <div class="current-thumb">
                        <img src="<?php echo BASE_URL; ?>uploads/thumbnails/<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="thumbnail">
                    </div>
                <?php endif; ?>
                <input type="file" name="thumbnail" accept="image/*">
                <small style="color: var(--text-gray);">Leave empty to keep the current thumbnail</small>
            </div>

            <div class="form-group">
                <label>Replace Video File (optional)</label>
                <input type="file" name="video_file" accept="video/*">
                <small style="color: var(--text-gray);">Leave empty to keep the current video file</small>
            </div>

            <div class="form-group">
                <label>Video URL (YouTube/embed link)</label>
                <input type="text" name="video_url" value="<?php echo htmlspecialchars($video['video_url'] ?? ''); ?>" placeholder="https://youtube.com/embed/...">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" required>
                        <?php
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()) {
                            $sel = ($cat['id'] == $video['category_id']) ? 'selected' : '';
                            echo '<option value="' . $cat['id'] . '" ' . $sel . '>' . htmlspecialchars($cat['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Duration (HH:MM:SS)</label>
                    <input type="text" name="duration" value="<?php echo htmlspecialchars($video['duration'] ?? ''); ?>" placeholder="00:05:30">
                </div>

                <div class="form-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="free" <?php echo $video['access_type'] === 'free' ? 'selected' : ''; ?>>Free</option>
                        <option value="premium" <?php echo $video['access_type'] === 'premium' ? 'selected' : ''; ?>>Premium</option>
                    </select>
                </div>
            </div>

            <div class="form-group checkbox-row">
                <input type="checkbox" name="is_featured" id="is_featured" value="1" <?php echo $video['is_featured'] ? 'checked' : ''; ?>>
                <label for="is_featured" style="margin-bottom:0;">Mark as Featured</label>
            </div>

            <button type="submit" class="admin-btn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
