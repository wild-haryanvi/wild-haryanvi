<?php
$page_title = "Login - Wild Haryanvi";
include '../includes/db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL);
    exit();
}

// Handle login
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required!';
    } else {
        // Query user
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['role'] === 'admin' ? 1 : 0;
                header("Location: " . BASE_URL);
                exit();
            } else {
                $error = 'Invalid password!';
            }
        } else {
            $error = 'User not found!';
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

        :root.light-theme {
            --primary-red: #FF4444;
            --dark-black: #ffffff;
            --secondary-black: #f5f5f5;
            --light-black: #eeeeee;
            --white: #1a1a1a;
            --light-gray: #2a2a2a;
            --text-gray: #555555;
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
            transition: var(--transition);
        }

        .navbar {
            background: linear-gradient(90deg, var(--dark-black) 0%, var(--secondary-black) 100%);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(255, 68, 68, 0.3);
            transition: var(--transition);
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

        .theme-toggle {
            background: var(--light-black);
            border: 2px solid var(--light-black);
            color: var(--primary-red);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            border-color: var(--primary-red);
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(255, 68, 68, 0.3);
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
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(255, 68, 68, 0.2);
            border: 1px solid var(--light-black);
            transition: var(--transition);
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

        .auth-box .form-group {
            margin-bottom: 1.5rem;
        }

        .auth-box .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--white);
        }

        .auth-box .form-group input {
            width: 100%;
            padding: 1rem;
            background: var(--light-black);
            border: 2px solid var(--secondary-black);
            border-radius: 10px;
            color: var(--white);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .auth-box .form-group input::placeholder {
            color: var(--text-gray);
        }

        .auth-box .form-group input:focus {
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
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .btn-login:hover {
            box-shadow: 0 10px 30px rgba(255, 68, 68, 0.4);
            transform: translateY(-3px);
        }

        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .auth-links a {
            color: var(--primary-red);
            text-decoration: none;
            transition: var(--transition);
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .signup-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--light-black);
        }

        .signup-link p {
            color: var(--text-gray);
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
            color: #e85555;
            border: 1px solid rgba(255, 68, 68, 0.5);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            color: #37d737;
            border: 1px solid rgba(76, 175, 80, 0.5);
        }
    </style>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
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
                <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Welcome Back</h2>
            <p class="subtitle">Login to your account</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <div class="auth-links">
                    <label>
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="signup-link">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
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
            html.classList.toggle('light-theme', savedTheme === 'light');
            updateThemeIcon(savedTheme);
            
            // Toggle theme on button click
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const isLight = html.classList.toggle('light-theme');
                    const theme = isLight ? 'light' : 'dark';
                    localStorage.setItem('theme', theme);
                    updateThemeIcon(theme);
                });
            }
        }

        function updateThemeIcon(theme) {
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                if (theme === 'light') {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    themeToggle.title = 'Switch to Dark Theme';
                } else {
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    themeToggle.title = 'Switch to Light Theme';
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
