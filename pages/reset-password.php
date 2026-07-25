<?php
$page_title = "Reset Password - Wild Haryanvi";
require_once '../includes/db.php';

$error = '';
$success = '';
$valid_token = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} else {
    $stmt = $conn->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = 'This reset link is invalid or has already been used.';
    } elseif (strtotime($user['reset_token_expiry']) < time()) {
        $error = 'This reset link has expired. Please request a new one.';
    } else {
        $valid_token = true;
    }
}

if ($valid_token && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $update_stmt->bind_param("si", $new_hash, $user['id']);
        $update_stmt->execute();

        $success = 'Your password has been reset successfully! You can now login.';
        $valid_token = false; // hide form after success
    }
}

include '../includes/header.php';
?>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 200px);
        padding: 2rem;
    }

    .auth-box {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 3rem;
        border-radius: 20px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 20px 60px rgba(255, 68, 68, 0.2);
        border: 1px solid var(--light-black);
    }

    .auth-box h2 {
        text-align: center;
        margin-bottom: 0.5rem;
        font-size: 1.8rem;
    }

    .auth-box .subtitle {
        text-align: center;
        color: var(--text-gray);
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 1rem;
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

    .btn-login {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        color: var(--white);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-login:hover {
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
        color: #37d737;
        border: 1px solid rgba(76, 175, 80, 0.5);
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #e85555;
        border: 1px solid rgba(255, 68, 68, 0.5);
    }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 1.5rem;
        color: var(--primary-red);
        text-decoration: none;
    }

    .back-link:hover { text-decoration: underline; }
</style>

<div class="auth-container">
    <div class="auth-box">
        <h2>Reset Password</h2>
        <p class="subtitle">Enter your new password below</p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                    <input type="password" id="new_password" name="new_password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-check"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

