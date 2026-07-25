<?php
$page_title = "Manage Users - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$success = '';
$error = '';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: " . BASE_URL . "pages/manage-users.php?msg=invalid_request");
        exit();
    }

    if ($_POST['action'] === 'toggle_admin') {
        $target_id = intval($_POST['user_id']);

        // Prevent removing your own admin access
        if ($target_id == $_SESSION['user_id']) {
            header("Location: " . BASE_URL . "pages/manage-users.php?msg=self_demote_error");
            exit();
        }

        // Check current role of target user
        $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $check_stmt->bind_param("i", $target_id);
        $check_stmt->execute();
        $target_role = $check_stmt->get_result()->fetch_assoc()['role'];

        // If demoting an admin, make sure they're not the last one
        if ($target_role === 'admin') {
            $admin_count = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
            if ($admin_count <= 1) {
                header("Location: " . BASE_URL . "pages/manage-users.php?msg=last_admin_error");
                exit();
            }
        }

        $stmt = $conn->prepare("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        header("Location: " . BASE_URL . "pages/manage-users.php?msg=role_updated");
        exit();
    } elseif ($_POST['action'] === 'delete_user') {
        $target_id = intval($_POST['user_id']);
        if ($target_id == $_SESSION['user_id']) {
            header("Location: " . BASE_URL . "pages/manage-users.php?msg=self_delete_error");
            exit();
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            header("Location: " . BASE_URL . "pages/manage-users.php?msg=user_deleted");
            exit();
        }
    }
}

// Show success/error messages based on redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'role_updated') $success = 'User role updated.';
    if ($_GET['msg'] === 'user_deleted') $success = 'User deleted.';
    if ($_GET['msg'] === 'self_delete_error') $error = "You can't delete your own account!";
    if ($_GET['msg'] === 'self_demote_error') $error = "You can't remove your own admin access!";
    if ($_GET['msg'] === 'last_admin_error') $error = "Can't remove the last admin — promote someone else first!";
    if ($_GET['msg'] === 'invalid_request') $error = "Invalid request — please try again.";
}


$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $search_term = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
}

$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_admins = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
$new_this_month = $conn->query("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetch_assoc()['total'];

include '../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1300px;
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

    .back-link {
        color: var(--primary-red);
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 1.8rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
        border-left: 5px solid var(--primary-red);
    }

    .stat-card h3 {
        color: var(--text-gray);
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card .value {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--primary-red);
    }

    .search-bar {
        margin-bottom: 2rem;
    }

    .search-bar form {
        display: flex;
        gap: 0.8rem;
        max-width: 400px;
    }

    .search-bar input {
        flex: 1;
        padding: 0.8rem 1.2rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 30px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
    }

    .search-bar input:focus {
        outline: none;
        border-color: var(--primary-red);
    }

    .search-bar button {
        background: var(--primary-red);
        border: none;
        color: #fff;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        cursor: pointer;
        flex-shrink: 0;
    }

    .admin-section {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        border-radius: 15px;
        border: 2px solid var(--light-black);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 750px;
    }

    .users-table th {
        background: var(--dark-black);
        padding: 1.1rem 1.3rem;
        text-align: left;
        color: var(--text-gray);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--primary-red);
    }

    .users-table td {
        padding: 1.1rem 1.3rem;
        border-bottom: 1px solid var(--light-black);
        vertical-align: middle;
    }

    .users-table tr:last-child td {
        border-bottom: none;
    }

    .users-table tr:hover {
        background: rgba(255, 68, 68, 0.04);
    }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #fff;
        font-size: 1rem;
    }

    .user-name {
        font-weight: 600;
        color: var(--white);
    }

    .user-email {
        color: var(--text-gray);
        font-size: 0.85rem;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.9rem;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .role-admin {
        background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        color: #1a1a1a;
    }

    .role-user {
        background: rgba(76, 175, 80, 0.15);
        color: #1db11d;
    }

    .action-btns {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 0.45rem 0.9rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.82rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .action-btn.toggle {
        background: rgba(255, 193, 7, 0.15);
        color: #c4a02a;
    }

    .action-btn.toggle:hover {
        background: rgba(255, 193, 7, 0.3);
    }

    .action-btn.delete {
        background: rgba(255, 68, 68, 0.15);
        color: #d12121;
    }

    .action-btn.delete:hover {
        background: rgba(255, 68, 68, 0.3);
    }

    .empty-row {
        text-align: center;
        color: var(--text-gray);
        padding: 3rem 1rem;
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
        color: #087808;
        border: 1px solid rgba(76, 175, 80, 0.5);
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #d81d1d;
        border: 1px solid rgba(255, 68, 68, 0.5);
    }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-users"></i> Manage Users</h1>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-user-friends"></i> Total Users</h3>
            <div class="value"><?php echo $total_users; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-user-shield"></i> Admins</h3>
            <div class="value"><?php echo $total_admins; ?></div>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-user-plus"></i> Joined This Month</h3>
            <div class="value"><?php echo $new_this_month; ?></div>
        </div>
    </div>

    <div class="search-bar">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="admin-section">
        <table class="users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users && $users->num_rows > 0): ?>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <?php $isAdmin = $u['role'] === 'admin'; ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"><?php echo strtoupper(substr($u['name'] ?? $u['email'], 0, 1)); ?></div>
                                    <div>
                                        <div class="user-name"><?php echo htmlspecialchars($u['name'] ?? 'N/A'); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($u['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge <?php echo $isAdmin ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo $isAdmin ? '<i class="fas fa-crown"></i> Admin' : '<i class="fas fa-user"></i> User'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo $isAdmin ? 'Remove admin access from this user?' : 'Give this user full admin access? They will be able to upload/delete videos and manage other users.'; ?>');">
                                        
                                        <input type="hidden" name="action" value="toggle_admin">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="action-btn toggle"><?php echo $isAdmin ? 'Remove Admin' : 'Make Admin'; ?></button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="action-btn delete"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty-row">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
