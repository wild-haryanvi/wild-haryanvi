<?php
$page_title = "Manage Announcements - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $badge = trim($_POST['badge']);
        $icon = trim($_POST['icon']);

        if (empty($title) || empty($content)) {
            $error = 'Title and content are required!';
        } else {
            if (empty($badge)) $badge = 'General';
            if (empty($icon)) $icon = '📢';

            $stmt = $conn->prepare("INSERT INTO announcements (title, content, badge, icon) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $title, $content, $badge, $icon);

            if ($stmt->execute()) {
                $success = 'Announcement posted successfully!';

                // Also create a notification
                $notif_title = 'New Announcement';
                $notif_message = $title;
                $notif_type = 'announcement';
                $notif_stmt = $conn->prepare("INSERT INTO notifications (title, message, type) VALUES (?, ?, ?)");
                $notif_stmt->bind_param("sss", $notif_title, $notif_message, $notif_type);
                $notif_stmt->execute();
            } else {
                $error = 'Error posting announcement: ' . $stmt->error;
            }
        }
    } 
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'Announcement deleted.';
        } else {
            $error = 'Error deleting announcement.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'toggle') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");

include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1000px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .admin-header h1 {
        font-size: 2rem;
        margin-bottom: 2rem;
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
        font-size: 1.4rem;
        border-bottom: 2px solid var(--primary-red);
        padding-bottom: 1rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
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
    .form-group textarea {
        width: 100%;
        padding: 0.8rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 8px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
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

    .announcement-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid var(--light-black);
        gap: 1rem;
    }

    .announcement-row:last-child {
        border-bottom: none;
    }

    .announcement-row-title {
        font-weight: 600;
    }

    .announcement-row-meta {
        color: var(--text-gray);
        font-size: 0.85rem;
    }

    .row-actions {
        display: flex;
        gap: 0.5rem;
    }

    .action-btn {
        padding: 0.4rem 0.8rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .action-btn.delete {
        background: rgba(255, 68, 68, 0.3);
        color: #db4343;
    }

    .action-btn.toggle {
        background: rgba(76, 175, 80, 0.3);
        color: #2ac02a;
    }

    .status-inactive {
        opacity: 0.5;
    }

    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.2);
        color: #36df36;
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #da4848;
    }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-bullhorn"></i> Manage Announcements</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="admin-section">
        <h2>Post New Announcement</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Content *</label>
                <textarea name="content" rows="3" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Badge (e.g. Platform, Premium, Event)</label>
                    <input type="text" name="badge" placeholder="Platform">
                </div>
                <div class="form-group">
                    <label>Icon (emoji)</label>
                    <input type="text" name="icon" placeholder="📢">
                </div>
            </div>

            <button type="submit" class="admin-btn">Post Announcement</button>
        </form>
    </div>

    <div class="admin-section">
        <h2>Existing Announcements</h2>
        <?php
        if ($announcements && $announcements->num_rows > 0) {
            while ($a = $announcements->fetch_assoc()) {
                $row_class = $a['is_active'] ? '' : 'status-inactive';
                ?>
                <div class="announcement-row <?php echo $row_class; ?>">
                    <div>
                        <div class="announcement-row-title"><?php echo $a['icon']; ?> <?php echo htmlspecialchars($a['title']); ?></div>
                        <div class="announcement-row-meta"><?php echo htmlspecialchars($a['badge']); ?> · <?php echo date('d M Y', strtotime($a['created_at'])); ?> · <?php echo $a['is_active'] ? 'Visible' : 'Hidden'; ?></div>
                    </div>
                    <div class="row-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                            <button type="submit" class="action-btn toggle"><?php echo $a['is_active'] ? 'Hide' : 'Show'; ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                            <button type="submit" class="action-btn delete">Delete</button>
                        </form>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p style="color: var(--text-gray);">No announcements posted yet.</p>';
        }
        ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
