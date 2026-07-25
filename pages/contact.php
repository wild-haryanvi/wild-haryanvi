<?php
$page_title = "Contact Us - Wild Haryanvi";
require_once '../includes/db.php';
include '../includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required!';
    } else {
        $full_message = "Subject: " . $subject . "\n\n" . $message;

        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $full_message);

        if ($stmt->execute()) {
            $success = 'Thank you! Your message has been sent. We will get back to you soon.';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>

<style>
    .contact-container {
        max-width: 900px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .contact-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .contact-header h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .contact-info {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
    }

    .contact-info h3 {
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        color: var(--primary-red);
    }

    .contact-item {
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
    }

    .contact-item i {
        color: var(--primary-red);
        font-size: 1.3rem;
        width: 30px;
        text-align: center;
        flex-shrink: 0;
        margin-top: 0.2rem;
    }

    .contact-item div h4 {
        font-weight: 600;
        margin-bottom: 0.3rem;
    }

    .contact-item div p {
        color: var(--text-gray);
    }

    .contact-item a {
        color: var(--primary-red);
        text-decoration: none;
        transition: var(--transition);
    }

    .contact-item a:hover {
        text-decoration: underline;
    }

    .social-icons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--light-black);
    }

    .social-icons a {
        width: 50px;
        height: 50px;
        background: var(--light-black);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-red);
        text-decoration: none;
        transition: var(--transition);
    }

    .social-icons a:hover {
        background: var(--primary-red);
        color: var(--white);
        transform: scale(1.1);
    }

    .contact-form {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
    }

    .contact-form h3 {
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        color: var(--primary-red);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 1rem;
        background: var(--light-black);
        border: 2px solid var(--secondary-black);
        border-radius: 10px;
        color: var(--white);
        font-family: 'Poppins', sans-serif;
        transition: var(--transition);
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: var(--text-gray);
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-red);
        box-shadow: 0 0 15px rgba(255, 68, 68, 0.3);
    }

    .submit-btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary-red) 0%, #f43b3b 100%);
        color: var(--white);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
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

    @media (max-width: 768px) {
        .contact-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="contact-container">
    <div class="contact-header">
        <h1>Get In Touch</h1>
        <p>Have questions or suggestions? We'd love to hear from you!</p>
    </div>

    <div class="contact-grid">
        <!-- Contact Info -->
        <div class="contact-info">
            <h3>Contact Information</h3>

            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>Email</h4>
                    <p><a href="mailto:wildharyanvi@gmail.com">wildharyanvi@gmail.com</a></p>
                </div>
            </div>

            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h4>Phone</h4>
                    <p>+91 XXXXX XXXXX</p>
                </div>
            </div>

            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>Location</h4>
                    <p>Haryana, India</p>
                </div>
            </div>

            <!-- <div class="contact-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h4>Working Hours</h4>
                    <p>Monday - Friday: 10:00 - 18:00</p>
                </div>
            </div> -->

            <div class="social-icons">
                <a href="https://instagram.com/wild.haryanvi" target="_blank" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://www.facebook.com/share/18sarxP1Tt/?mibextid=wwXIfr" target="_blank" title="Facebook">
                    <i class="fab fa-facebook"></i>
                </a>
                <!-- <a href="#" target="_blank" title="Twitter">
                    <i class="fab fa-twitter"></i>
                </a> -->
                <a href="https://youtube.com/@wildharyanvi?si=1H40bsDYpK2t0H0K" target="_blank" title="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form">
            <h3>Send us a Message</h3>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
