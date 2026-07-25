<?php
$page_title = "Account Settings - Wild Haryanvi";
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get current user data
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Step 1: verify current password FIRST, before allowing any change
    if (empty($current_password) || !password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect.';
    } else {
        $updates_made = false;

        // --- Handle email change ---
        if (!empty($new_email) && $new_email !== $user['email']) {
            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check email isn't already taken by someone else
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->bind_param("si", $new_email, $user_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->fetch_assoc()) {
                    $error = 'This email is already in use by another account.';
                } else {
                    $update_email_stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $update_email_stmt->bind_param("si", $new_email, $user_id);
                    $update_email_stmt->execute();
                    $updates_made = true;
                }
            }
        }

        // --- Handle password change (only if no error so far) ---
        if (empty($error) && !empty($new_password)) {
            if (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New password and confirm password do not match.';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $update_pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pass_stmt->bind_param("si", $new_hash, $user_id);
                $update_pass_stmt->execute();
                $updates_made = true;
            }
        }

        if (empty($error) && $updates_made) {
            $success = 'Your account settings have been updated successfully.';
            // Refresh user data after update
            $user_stmt->execute();
            $user = $user_stmt->get_result()->fetch_assoc();
        } elseif (empty($error) && !$updates_made) {
            $error = 'No changes were made.';
        }
    }
}

include '../includes/header.php';
?>

<style>
    .settings-container {
        max-width: 600px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .settings-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .settings-header h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .settings-header p {
        color: var(--text-gray);
    }

    .settings-card {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
    }

    .settings-card h3 {
        font-size: 1.1rem;
        color: var(--primary-red);
        margin-bottom: 1.2rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px solid var(--light-black);
    }

    .form-group {
        margin-bottom: 1.3rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 0.9rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 10px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary-red);
        box-shadow: 0 0 15px rgba(255, 68, 68, 0.3);
    }

    .form-hint {
        font-size: 0.8rem;
        color: var(--text-gray);
        margin-top: 0.3rem;
    }

    .submit-btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        color: var(--white);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 0.5rem;
    }

    .submit-btn:hover {
        box-shadow: 0 10px 30px rgba(255, 68, 68, 0.4);
        transform: translateY(-3px);
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
        color: #46bb46;
        border: 1px solid rgba(76, 175, 80, 0.5);
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #d73e3e;
        border: 1px solid rgba(255, 68, 68, 0.5);
    }

    .divider-note {
        font-size: 0.8rem;
        color: var(--text-gray);
        text-align: center;
        margin: 1.5rem 0;
    }
</style>

<div class="settings-container">
    <div class="settings-header">
        <h1><i class="fas fa-user-cog"></i> Account Settings</h1>
        <p>Update admin email or password securely</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="settings-card">
        <form method="POST" action="">
            <h3><i class="fas fa-envelope"></i> Email Address</h3>
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <h3><i class="fas fa-lock"></i> Change Password</h3>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="New password">
                <div class="form-hint">Minimum 6 characters</div>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Re-enter new password">
            </div>

            <!-- <div class="divider-note">To confirm any change, enter your current password below</div> -->

            <div class="form-group">
                <label>Current Password *</label>
                <input type="password" name="current_password" required placeholder="Required to save changes">
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

