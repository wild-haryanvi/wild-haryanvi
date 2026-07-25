<?php
$page_title = "Contact Messages - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

// Handle delete / mark_read (AJAX or normal POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['msg_id'])) {
    $msg_id = intval($_POST['msg_id']);

    if ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $msg_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'mark_read') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->bind_param("i", $msg_id);
        $stmt->execute();
        exit(); // AJAX call, no need to reload page
    }
}

$total_count = $conn->query("SELECT COUNT(*) as total FROM contact_messages")->fetch_assoc()['total'];
$unread_count = $conn->query("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'unread'")->fetch_assoc()['total'];
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 900px;
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

    .admin-header h1 { font-size: 2rem; }

    .back-link {
        color: var(--primary-red);
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 1.5rem;
    }

    .back-link:hover { text-decoration: underline; }

    .total-badge {
        background: var(--primary-red);
        color: #fff;
        padding: 0.3rem 0.9rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .inbox {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        border-radius: 15px;
        border: 2px solid var(--light-black);
        overflow: hidden;
    }

    .inbox-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--light-black);
        cursor: pointer;
        transition: var(--transition);
    }

    .inbox-row:last-child { border-bottom: none; }

    .inbox-row:hover {
        background: var(--light-black);
    }

    .inbox-row.unread {
        background: rgba(255, 68, 68, 0.06);
    }

    .inbox-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--primary-red);
        flex-shrink: 0;
    }

    .inbox-dot.hidden-dot { visibility: hidden; }

    .inbox-sender {
        width: 160px;
        flex-shrink: 0;
        font-size: 0.95rem;
        color: var(--white);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .inbox-row.unread .inbox-sender,
    .inbox-row.unread .inbox-preview {
        font-weight: 700;
    }

    .inbox-preview {
        flex: 1;
        font-size: 0.9rem;
        color: var(--text-gray);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .inbox-time {
        flex-shrink: 0;
        font-size: 0.8rem;
        color: var(--text-gray);
    }

    .empty-state {
        text-align: center;
        color: var(--text-gray);
        padding: 3rem 0;
    }

    /* MODAL */
    .msg-modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.75);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .msg-modal-overlay.show { display: flex; }

    .msg-modal {
        background: var(--secondary-black);
        border: 2px solid var(--primary-red);
        border-radius: 15px;
        padding: 2rem;
        max-width: 550px;
        width: 100%;
        max-height: 85vh;
        overflow-y: auto;
        position: relative;
    }

    .msg-modal-close {
        position: absolute;
        top: 12px;
        right: 15px;
        background: none;
        border: none;
        color: var(--text-gray);
        font-size: 1.5rem;
        cursor: pointer;
    }

    .msg-modal-sender {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }

    .msg-modal-email {
        color: var(--primary-red);
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }

    .msg-modal-time {
        color: var(--text-gray);
        font-size: 0.8rem;
        margin-bottom: 1.2rem;
        padding-bottom: 1.2rem;
        border-bottom: 1px solid var(--light-black);
    }

    .msg-modal-body {
        color: var(--white);
        line-height: 1.8;
        white-space: pre-wrap;
        margin-bottom: 1.5rem;
    }

    .msg-modal-actions {
        display: flex;
        gap: 0.6rem;
    }

    .action-btn {
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .action-btn.reply {
        background: rgba(76, 175, 80, 0.2);
        color: #38cd38;
    }
    .action-btn.reply:hover { background: rgba(76, 175, 80, 0.4); }

    .action-btn.delete {
        background: rgba(255, 68, 68, 0.2);
        color: #e14949;
    }
    .action-btn.delete:hover { background: rgba(255, 68, 68, 0.4); }
</style>

<div class="admin-container">
    <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="admin-header">
        <h1><i class="fas fa-envelope"></i> Contact Messages</h1>
        <?php if ($total_count > 0): ?>
            <span class="total-badge"><?php echo $total_count; ?> Total<?php echo $unread_count > 0 ? ' • ' . $unread_count . ' Unread' : ''; ?></span>
        <?php endif; ?>
    </div>

    <div class="inbox">
        <?php if ($messages && $messages->num_rows > 0): ?>
            <?php while ($m = $messages->fetch_assoc()):
                $preview = trim(str_replace(["\r", "\n"], ' ', $m['message']));
                $preview = mb_strlen($preview) > 70 ? mb_substr($preview, 0, 70) . '...' : $preview;
            ?>
                <div class="inbox-row <?php echo $m['status'] === 'unread' ? 'unread' : ''; ?>"
                     data-msg-id="<?php echo $m['id']; ?>"
                     onclick='openMessage(<?php echo json_encode([
                        "id" => $m['id'],
                        "name" => $m['name'],
                        "email" => $m['email'],
                        "message" => $m['message'],
                        "time" => date('d M Y, h:i A', strtotime($m['created_at'])),
                        "status" => $m['status']
                     ]); ?>)'>
                    <span class="inbox-dot <?php echo $m['status'] === 'read' ? 'hidden-dot' : ''; ?>"></span>
                    <span class="inbox-sender"><?php echo htmlspecialchars($m['name']); ?></span>
                    <span class="inbox-preview"><?php echo htmlspecialchars($preview); ?></span>
                    <span class="inbox-time"><?php echo date('d M', strtotime($m['created_at'])); ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">No messages yet.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Message Modal -->
<div class="msg-modal-overlay" id="msgModalOverlay">
    <div class="msg-modal">
        <button type="button" class="msg-modal-close" onclick="closeModal()">&times;</button>
        <div class="msg-modal-sender" id="modalSender"></div>
        <div class="msg-modal-email" id="modalEmail"></div>
        <div class="msg-modal-time" id="modalTime"></div>
        <div class="msg-modal-body" id="modalBody"></div>
        <div class="msg-modal-actions">
            <a href="#" id="modalReplyLink" class="action-btn reply"><i class="fas fa-reply"></i> Reply via Email</a>
            <button type="button" class="action-btn delete" id="modalDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<script>
    let currentMsgId = null;

    function openMessage(msg) {
        currentMsgId = msg.id;

        document.getElementById('modalSender').textContent = msg.name;
        document.getElementById('modalEmail').textContent = msg.email;
        document.getElementById('modalTime').textContent = msg.time;
        document.getElementById('modalBody').textContent = msg.message;
        document.getElementById('modalReplyLink').href = 'mailto:' + msg.email;
        document.getElementById('msgModalOverlay').classList.add('show');

        // Mark as read visually + in DB, if it wasn't already
        const row = document.querySelector('.inbox-row[data-msg-id="' + msg.id + '"]');
        if (msg.status === 'unread') {
            row.classList.remove('unread');
            row.querySelector('.inbox-dot').classList.add('hidden-dot');

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_read&msg_id=' + msg.id
            });
        }
    }

    function closeModal() {
        document.getElementById('msgModalOverlay').classList.remove('show');
    }

    document.getElementById('modalDeleteBtn').addEventListener('click', function () {
        if (!confirm('Delete this message?')) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="msg_id" value="' + currentMsgId + '">';
        document.body.appendChild(form);
        form.submit();
    });
</script>

<?php include '../includes/footer.php'; ?>

