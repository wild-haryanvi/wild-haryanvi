<?php
require_once 'db.php';

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Wild Haryanvi - Haryanvi Entertainment'; ?></title>
    <link rel="icon" href="<?php echo BASE_URL; ?>logo.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="hamburger" id="hamburgerBtn">
                <span></span>
                <span></span>
                <span></span>
            </div>

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
                <a href="<?php echo BASE_URL; ?>pages/videos.php" class="nav-link">Videos</a>
                <a href="<?php echo BASE_URL; ?>pages/updates.php" class="nav-link">Updates</a>

                <?php if($user_id): ?>
                    <?php if($is_admin): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link"><i class="fas fa-cog"></i> Admin</a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>pages/contact.php" class="nav-link">Contact</a>
                    <a href="<?php echo BASE_URL; ?>pages/profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
                    <a href="<?php echo BASE_URL; ?>includes/logout.php" class="nav-link logout-btn">Logout</a>
                <?php else: ?>
                    <!-- <a href="<?php echo BASE_URL; ?>pages/contact.php" class="nav-link">Contact</a> -->
                <?php endif; ?>
            </div>

            <form class="nav-search" id="navSearchForm" action="<?php echo BASE_URL; ?>pages/videos.php" method="GET">
                <input type="text" name="search" placeholder="Search videos..." id="search-input" class="search-box">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>

            <div class="nav-icons">
                <button class="icon-btn" id="searchToggle" title="Search">
                    <i class="fas fa-search"></i>
                </button>

                <?php if ($user_id): ?>
                    <a href="<?php echo BASE_URL; ?>pages/notifications.php" class="icon-btn notif-bell" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadge" class="notif-badge"></span>
                    </a>
                <?php endif; ?>

                <button class="icon-btn" id="themeToggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>

                <?php if (!$user_id): ?>
                    <a href="<?php echo BASE_URL; ?>pages/login.php" class="signin-pill">
                        <i class="fas fa-user-circle"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if ($user_id): ?>
        <script>
            const notifBadge = document.getElementById('notifBadge');

            if (notifBadge) {
                fetch('<?php echo BASE_URL; ?>pages/get-notifications.php')
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) return;

                        if (data.unread > 0) {
                            notifBadge.textContent = data.unread > 9 ? '9+' : data.unread;
                            notifBadge.style.display = 'flex';
                        } else {
                            notifBadge.style.display = 'none';
                        }
                    });
            }
        </script>
    <?php endif; ?>

    <script>
        // Theme Toggle Functionality
        function initThemeToggle() {
            const themeToggle = document.getElementById('themeToggle');
            const html = document.documentElement;

            const savedTheme = localStorage.getItem('theme') || 'dark';
            html.classList.toggle('light-theme', savedTheme === 'light');
            updateThemeIcon(savedTheme);

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

        document.addEventListener('DOMContentLoaded', () => {
            initThemeToggle();
        });

        // Toggle Hamburger Menu Functionality
        const hamburger = document.getElementById('hamburgerBtn');
        const navMenu = document.querySelector('.nav-menu');

        if (hamburger && navMenu) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }

        // Search Toggle Functionality
        const searchToggle = document.getElementById('searchToggle');
        const navSearchForm = document.getElementById('navSearchForm');
        const searchInput = document.getElementById('search-input');

        if (searchToggle && navSearchForm) {
            searchToggle.addEventListener('click', () => {
                navSearchForm.classList.toggle('active');
                if (navSearchForm.classList.contains('active')) {
                    searchInput.focus();
                }
            });
        }
    </script>

</body>
</html>
