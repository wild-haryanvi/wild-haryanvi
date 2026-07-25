<?php
$page_title = "Sign Up - Wild Haryanvi";
include '../includes/db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL);
    exit();
}

// Handle signup
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Email already registered!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['is_admin'] = 0;
                header("Location: " . BASE_URL);
                exit();
            } else {
                $error = 'Error creating account. Please try again!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" href="<?php echo BASE_URL; ?>logo.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #FF4444;
            --dark-black: #1a1a1a;
            --secondary-black: #2a2a2a;
            --light-black: #3a3a3a;
            --white: #ffffff;
            --light-gray: #f0f0f0;
            --text-gray: #b0b0b0;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--dark-black) 0%, var(--secondary-black) 100%);
            color: var(--white);
        }

        .navbar {
            background: linear-gradient(90deg, var(--dark-black) 0%, var(--secondary-black) 100%);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(255, 68, 68, 0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            gap: 2rem;
        }

        .nav-logo a {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: var(--white);
        }

        .logo-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 0.5rem;
            border: 2px solid var(--primary-red);
        }

        .logo {
            display: flex;
            align-items: center;
            color: var(--primary-red);
        }

        .nav-menu {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-gray);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: var(--transition);
        }

        .nav-link:hover {
            color: var(--primary-red);
        }

        .signup-btn {
            background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
            color: var(--white) !important;
            border-radius: 20px;
            padding: 0.5rem 1.5rem !important;
        }

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
            max-width: 450px;
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
            color: var(--white);
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            background: var(--light-black);
            border: 2px solid var(--secondary-black);
            border-radius: 10px;
            color: var(--white);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .form-group input::placeholder {
            color: var(--text-gray);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 15px rgba(255, 68, 68, 0.3);
        }

        .btn-signup {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .btn-signup:hover {
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

        .alert-error {
            background: rgba(255, 68, 68, 0.2);
            color: #dc4a4a;
            border: 1px solid rgba(255, 68, 68, 0.5);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--light-black);
        }

        .login-link p {
            color: var(--text-gray);
        }

        .login-link a {
            color: var(--primary-red);
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* FOOTER */

        .footer {
            background: var(--dark-black);
            border-top: 2px solid var(--primary-red);
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 0 2rem 2rem;
        }

        .footer-section h3,
        .footer-section h4 {
            color: var(--primary-red);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .footer-section p {
            color: var(--text-gray);
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 0.5rem;
        }

        .footer-section a {
            color: var(--text-gray);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-section a:hover {
            color: var(--primary-red);
            transform: translateX(5px);
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--secondary-black);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--primary-red);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--light-black);
            color: var(--text-gray);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo BASE_URL; ?>">
                    <div class="logo">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.jpeg" alt="Wild Haryanvi" class="logo-img">
                    <span class="logo-text"> Wild Haryanvi</span>
                    </div>
                </a>
            </div>
            <div class="nav-menu">
                <a href="<?php echo BASE_URL; ?>" class="nav-link">Home</a>
                <a href="login.php" class="nav-link">Login</a>
                <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Create Account</h2>
            <p class="subtitle">Join Wild Haryanvi today</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="Your name">
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••" minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" minlength="6">
                </div>

                <button type="submit" class="btn-signup">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    

    <script>
        // Theme Toggle Functionality
        function initThemeToggle() {
            const themeToggle = document.getElementById('themeToggle');
            const html = document.documentElement;
            
            // Check localStorage for saved theme
            const savedTheme = localStorage.getItem('theme') || 'dark';
            html.classList.toggle('dark-theme', savedTheme === 'dark');
            updateThemeIcon(savedTheme);
            
            // Toggle theme on button click
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const isDark = html.classList.toggle('dark-theme');
                    const theme = isDark ? 'dark' : 'light';
                    localStorage.setItem('theme', theme);
                    updateThemeIcon(theme);
                });
            }
        }

        function updateThemeIcon(theme) {
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                if (theme === 'dark') {
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    themeToggle.title = 'Switch to Light Theme';
                } else {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    themeToggle.title = 'Switch to Dark Theme';
                }
            }
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            initThemeToggle();
        });
    </script>
</body>
</html>
