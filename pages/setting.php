<?php
$page_title = "Settings - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pwd_success = '';
$pwd_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $current_hash = $check_stmt->get_result()->fetch_assoc()['password'];

    if (!password_verify($current_password, $current_hash)) {
        $pwd_error = 'Current password is incorrect.';
    } elseif (strlen($new_password) < 6) {
        $pwd_error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $pwd_error = 'New password and confirmation do not match.';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_hash, $user_id);
        $update_stmt->execute();
        $pwd_success = 'Password updated successfully!';
    }
}

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

include '../includes/header.php';
?>

<style>
    .settings-container {
        max-width: 700px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .settings-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .settings-header h1 {
        font-size: 1.8rem;
    }

    .back-link {
        color: var(--primary-red);
        text-decoration: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 1.5rem;
    }

    .settings-section {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
        margin-bottom: 1.5rem;
    }

    .settings-section h2 {
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 0.8rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 8px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary-red);
    }

    .settings-btn-submit {
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        color: var(--white);
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.8rem 0;
        border-bottom: 1px solid var(--light-black);
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        color: var(--text-gray);
    }

    .info-value {
        font-weight: 600;
    }

    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.2);
        color: #309c30;
        border: 1px solid rgba(76, 175, 80, 0.5);
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #dc4c4c;
        border: 1px solid rgba(255, 68, 68, 0.5);
    }
</style>

<div class="settings-container">
    <a href="<?php echo BASE_URL; ?>pages/profile.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>

    <div class="settings-header">
        <h1><i class="fas fa-cog"></i> Settings</h1>
    </div>

    <div class="settings-section">
        <h2><i class="fas fa-user"></i> Account Info</h2>
        <div class="info-row">
            <span class="info-label">Name</span>
            <span class="info-value"><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Member Since</span>
            <span class="info-value"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
        </div>
    </div>

    <div class="settings-section">
        <h2><i class="fas fa-lock"></i> Change Password</h2>

        <?php if (!empty($pwd_success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($pwd_success); ?></div>
        <?php endif; ?>
        <?php if (!empty($pwd_error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($pwd_error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="change_password" value="1" class="settings-btn-submit">Update Password</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>