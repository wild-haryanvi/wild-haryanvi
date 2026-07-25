<?php
$page_title = "Manage Subscriptions - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'cancel') {
        $sub_id = intval($_POST['sub_id']);
        $stmt = $conn->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $sub_id);
        $stmt->execute();
    } elseif ($_POST['action'] === 'approve') {
        $sub_id = intval($_POST['sub_id']);
        $sub_stmt = $conn->prepare("SELECT subscriptions.*, plans.duration_days FROM subscriptions LEFT JOIN plans ON subscriptions.plan_id = plans.id WHERE subscriptions.id = ?");
        $sub_stmt->bind_param("i", $sub_id);
        $sub_stmt->execute();
        $sub = $sub_stmt->get_result()->fetch_assoc();

        if ($sub) {
            $update_stmt = $conn->prepare("UPDATE subscriptions SET status = 'active', starts_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?");
            $update_stmt->bind_param("ii", $sub['duration_days'], $sub_id);
            $update_stmt->execute();
        }
    } elseif ($_POST['action'] === 'reject') {
        $sub_id = intval($_POST['sub_id']);
        $stmt = $conn->prepare("UPDATE subscriptions SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $sub_id);
        $stmt->execute();
    }
}

$subs = $conn->query("
    SELECT subscriptions.*, users.email, users.name, users.profile_image, plans.name AS plan_name
    FROM subscriptions
    LEFT JOIN users ON subscriptions.user_id = users.id
    LEFT JOIN plans ON subscriptions.plan_id = plans.id
    ORDER BY subscriptions.created_at DESC
");

$pending = $conn->query("
    SELECT subscriptions.*, users.name, users.email, users.profile_image, plans.name AS plan_name
    FROM subscriptions
    LEFT JOIN users ON subscriptions.user_id = users.id
    LEFT JOIN plans ON subscriptions.plan_id = plans.id
    WHERE subscriptions.status = 'pending'
    ORDER BY subscriptions.created_at ASC
");

// Revenue total (all-time)
$revenue = $conn->query("SELECT SUM(amount) as total FROM subscriptions WHERE status IN ('active', 'cancelled')")->fetch_assoc()['total'] ?? 0;

include '../includes/header.php';
?>

<style>
    .admin-container { max-width: 1200px; margin: 3rem auto; padding: 0 2rem; }
    .admin-header h1 { font-size: 2rem; margin-bottom: 1rem; }
    .revenue-card { background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%); padding: 1.5rem 2rem; border-radius: 15px; margin-bottom: 2rem; display: inline-block; }
    .revenue-card .amt { font-size: 2rem; font-weight: 800; color: #fff; }
    .revenue-card .lbl { color: rgba(255,255,255,0.85); font-size: 0.85rem; }
    .admin-section { background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%); padding: 2rem; border-radius: 15px; border: 2px solid var(--light-black); overflow-x: auto; }
    .subs-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .subs-table th { background: var(--light-black); padding: 1rem; text-align: left; border-bottom: 2px solid var(--primary-red); }
    .subs-table td { padding: 1rem; border-bottom: 1px solid var(--secondary-black); }
    .status-badge { padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; }
    .status-active { background: rgba(76, 175, 80, 0.2); color: #25cb25; }
    .status-cancelled { background: rgba(255, 68, 68, 0.2); color: #cd3c3c; }
    .action-btn { padding: 0.4rem 0.8rem; border: none; border-radius: 5px; cursor: pointer; font-size: 0.85rem; font-weight: 600; background: rgba(255, 68, 68, 0.3); color: #db3030; }
    .payment-card {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 2px solid var(--light-black);
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 1.5rem;
        align-items: center;
    }
    .payment-screenshot {
        width: 100px;
        height: 100px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid var(--light-black);
    }
    .payment-screenshot-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 10px;
        background: var(--dark-black);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-gray);
        font-size: 1.5rem;
    }
    .payment-info .user-name { font-weight: 700; font-size: 1.05rem; }
    .payment-info .user-email { color: var(--text-gray); font-size: 0.85rem; margin-bottom: 0.6rem; }
    .payment-info .detail-row { font-size: 0.88rem; margin-top: 0.3rem; }
    .payment-info .detail-row strong { color: var(--primary-red); }
    .payment-actions { display: flex; flex-direction: column; gap: 0.6rem; }
    .action-btn.approve { background: rgba(76,175,80,0.2); color: #53c853; }
    .action-btn.approve:hover { background: rgba(76,175,80,0.4); }
    .action-btn.reject { background: rgba(255,68,68,0.2); color: #e15757; }

    @media (max-width: 600px) {
        .payment-card { grid-template-columns: 1fr; text-align: center; }
        .payment-actions { flex-direction: row; justify-content: center; }
    }

    .user-name-row {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        margin-bottom: 0.3rem;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-red);
    }

    .user-avatar-placeholder {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--light-black);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-red);
        font-size: 1rem;
        border: 2px solid var(--primary-red);
    }

</style>

<div class="admin-container">
    <div class="admin-header"><h1><i class="fas fa-credit-card"></i> Manage Subscriptions</h1></div>

    <div class="revenue-card">
        <div class="amt">₹<?php echo number_format($revenue, 0); ?></div>
        <div class="lbl">Total Revenue (all-time)</div>
    </div>

    <?php if ($pending->num_rows > 0): ?>
    <div style="margin-bottom: 2rem;">
        <h2 style="margin-bottom: 1rem;">⏳ Pending Payment Requests (<?php echo $pending->num_rows; ?>)</h2>
        <?php while ($p = $pending->fetch_assoc()): ?>
            <div class="payment-card">
                <?php if (!empty($p['payment_screenshot'])): ?>
                    <a href="<?php echo BASE_URL; ?>uploads/payment_proofs/<?php echo htmlspecialchars($p['payment_screenshot']); ?>" target="_blank">
                        <img src="<?php echo BASE_URL; ?>uploads/payment_proofs/<?php echo htmlspecialchars($p['payment_screenshot']); ?>" class="payment-screenshot">
                    </a>
                <?php elseif (!empty($p['profile_image'])): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo htmlspecialchars($p['profile_image']); ?>" class="payment-screenshot">
                
                <?php else: ?>
                    <div class="payment-screenshot-placeholder"><i class="fas fa-image"></i></div>
                <?php endif; ?>

                <div class="payment-info">
                    <div class="user-name">
                        <?php echo htmlspecialchars($p['name'] ?? 'N/A'); ?>
                    </div>
                    <div class="user-email">
                        <?php echo htmlspecialchars($p['email']); ?>
                    </div>
                    <div class="detail-row">
                        <strong>Plan:</strong> 
                        <?php echo htmlspecialchars($p['plan_name']); ?> 
                        — ₹
                        <?php echo number_format($p['amount'], 0); ?>
                    </div>
                    <div class="detail-row">
                        <strong>UTR / Transaction ID:</strong> 
                        <?php echo htmlspecialchars($p['transaction_ref']); ?>
                    </div>
                    <div class="detail-row">
                        <strong>Requested:</strong> 
                        <?php echo date('d M Y, h:i A', strtotime($p['created_at'])); ?>
                    </div>
                </div>

                <div class="payment-actions">
                    <form method="POST">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="sub_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" class="action-btn approve"><i class="fas fa-check"></i> Approve</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Reject this payment request?');">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="sub_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" class="action-btn reject"><i class="fas fa-times"></i> Reject</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <div class="admin-section">
        <table class="subs-table">
            <thead>
                <tr><th>User</th><th>Plan</th><th>Amount</th><th>Status</th><th>Expires</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if ($subs->num_rows > 0): while ($s = $subs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['name'] ?? $s['email']); ?></td>
                    <td><?php echo htmlspecialchars($s['plan_name'] ?? 'N/A'); ?></td>
                    <td>₹<?php echo number_format($s['amount'], 0); ?></td>
                    <td><span class="status-badge status-<?php echo $s['status']; ?>"><?php echo ucfirst($s['status']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($s['expires_at'])); ?></td>
                    <td>
                        <?php if ($s['status'] === 'active'): ?>
                        <form method="POST" onsubmit="return confirm('Cancel this subscription?');">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="sub_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" class="action-btn">Cancel</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" style="text-align:center; color:var(--text-gray);">No subscriptions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
