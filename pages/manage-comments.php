<?php
$page_title = "Manage Comments - Wild Haryanvi";
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$success = '';
$error = '';

// Handle actions: delete / hide / show
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['comment_id'])) {
    $comment_id = intval($_POST['comment_id']);

    if ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM video_comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $success = $stmt->execute() ? 'Comment deleted.' : 'Error deleting comment.';
    } elseif ($_POST['action'] === 'hide') {
        $stmt = $conn->prepare("UPDATE video_comments SET status = 'hidden' WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $success = $stmt->execute() ? 'Comment hidden from public view.' : 'Error updating comment.';
    } elseif ($_POST['action'] === 'show') {
        $stmt = $conn->prepare("UPDATE video_comments SET status = 'visible' WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $success = $stmt->execute() ? 'Comment made visible again.' : 'Error updating comment.';
    }
}

// Optional filter by video
$filter_video_id = isset($_GET['video_id']) ? intval($_GET['video_id']) : 0;

$sql = "
    SELECT video_comments.*, users.name AS username, videos.title AS video_title
    FROM video_comments
    LEFT JOIN users ON video_comments.user_id = users.id
    LEFT JOIN videos ON video_comments.video_id = videos.id
";
if ($filter_video_id) {
    $sql .= " WHERE video_comments.video_id = " . $filter_video_id;
}
$sql .= " ORDER BY video_comments.created_at DESC";

$comments = $conn->query($sql);

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
        flex-wrap: wrap;
        gap: 1rem;
    }

    .admin-header h1 {
        font-size: 2rem;
    }

    .admin-section {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
        width: 100%;
        overflow-x: auto;
    }

    .comments-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    .comments-table th {
        background: var(--light-black);
        padding: 1rem;
        text-align: left;
        border-bottom: 2px solid var(--primary-red);
    }

    .comments-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--secondary-black);
        vertical-align: top;
    }

    .comments-table tr:hover {
        background: var(--light-black);
    }

    .comment-status {
        display: inline-block;
        padding: 0.3rem 0.7rem;
        border-radius: 5px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-visible {
        background: rgba(76, 175, 80, 0.2);
        color: #38cd38;
    }

    .status-hidden {
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
        font-size: 0.8rem;
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

    .action-btn.hide {
        background: rgba(255, 193, 7, 0.3);
        color: #c9a11d;
    }

    .action-btn.hide:hover {
        background: rgba(255, 193, 7, 0.5);
    }

    .action-btn.show {
        background: rgba(76, 175, 80, 0.3);
        color: #17c317;
    }

    .action-btn.show:hover {
        background: rgba(76, 175, 80, 0.5);
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

    .comment-text-cell {
        max-width: 320px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .back-link {
        color: var(--primary-red);
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 1.5rem;
    }

    .back-link:hover {
        text-decoration: underline;
    }
</style>

<div class="admin-container">
    <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="admin-header">
        <h1><i class="fas fa-comments"></i> Manage Comments</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="admin-section">
        <table class="comments-table">
            <thead>
                <tr>
                    <th>Video</th>
                    <th>User</th>
                    <th>Comment</th>
                    <th>Posted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($comments && $comments->num_rows > 0): ?>
                    <?php while ($c = $comments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['video_title'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($c['username'] ?? 'User'); ?></td>
                            <td class="comment-text-cell"><?php echo htmlspecialchars($c['comment']); ?></td>
                            <td><?php echo date('M d, Y g:i A', strtotime($c['created_at'])); ?></td>
                            <td>
                                <span class="comment-status status-<?php echo $c['status']; ?>">
                                    <?php echo ucfirst($c['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($c['status'] === 'visible'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="hide">
                                            <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="action-btn hide"><i class="fas fa-eye-slash"></i> Hide</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="show">
                                            <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="action-btn show"><i class="fas fa-eye"></i> Show</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this comment permanently?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color: var(--text-gray);">No comments yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
