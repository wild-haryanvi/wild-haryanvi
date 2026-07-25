<?php
$page_title = "Forgot Password - Wild Haryanvi";
require_once '../includes/db.php';
require_once '../includes/PHPMailer/PHPMailer.php';
require_once '../includes/PHPMailer/SMTP.php';
require_once '../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Always show the same success message, even if email doesn't exist
        // (this prevents attackers from checking which emails are registered)
        $success = 'If an account exists with that email, a password reset link has been sent.';

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $token, $expiry, $user['id']);
            $update_stmt->execute();

            $reset_link = BASE_URL . "pages/reset-password.php?token=" . $token;

            // Send the email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'YAHAN_APNA_GMAIL_DAALO@gmail.com';
                $mail->Password   = 'YAHAN_APP_PASSWORD_DAALO';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('YAHAN_APNA_GMAIL_DAALO@gmail.com', 'Wild Haryanvi');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Reset Your Password - Wild Haryanvi';
                $mail->Body    = "
                    <p>Hi,</p>
                    <p>You requested to reset your password. Click the link below to set a new password:</p>
                    <p><a href='{$reset_link}'>{$reset_link}</a></p>
                    <p>This link will expire in 1 hour. If you didn't request this, please ignore this email.</p>
                    <p>- Wild Haryanvi Team</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                // Don't reveal email errors to the user, just log for yourself
                error_log("Mail error: " . $mail->ErrorInfo);
            }
        }
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
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your email and we'll send you a reset link</p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required placeholder="your@email.com">
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
