// Wild Haryanvi - Main JavaScript

// Theme Toggle Functionality
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    // Check localStorage for saved theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    html.classList.toggle('light-theme', savedTheme === 'light');
    html.classList.toggle('dark-theme', savedTheme === 'dark');
    updateThemeIcon(savedTheme);
    
    // Toggle theme on button click
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isLight = html.classList.toggle('light-theme');
            const isDark = html.classList.toggle('dark-theme');
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

// Hamburger Menu Toggle
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

if (hamburger) {
    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        hamburger.classList.toggle('active');
    });

    // Close menu when a link is clicked
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
        });
    });
}

// Search Functionality
const searchInput = document.getElementById('search-input');
if (searchInput) {
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `pages/videos.php?search=${encodeURIComponent(query)}`;
            }
        }
    });
}

// Video Card Click Handler
document.addEventListener('DOMContentLoaded', () => {
    const videoCards = document.querySelectorAll('.video-card');
    videoCards.forEach(card => {
        card.addEventListener('click', function() {
            const videoId = this.getAttribute('data-video-id');
            if (videoId) {
                window.location.href = `pages/watch.php?id=${videoId}`;
            }
        });
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeIn 0.5s ease forwards';
        }
    });
}, observerOptions);

document.querySelectorAll('.video-card, .category-card, .section').forEach(el => {
    observer.observe(el);
});

// Format watch count
function formatViews(views) {
    if (views >= 1000000) {
        return (views / 1000000).toFixed(1) + 'M';
    } else if (views >= 1000) {
        return (views / 1000).toFixed(1) + 'K';
    }
    return views;
}

// Get time difference
function getTimeAgo(date) {
    const now = new Date();
    const secondsAgo = Math.floor((now - new Date(date)) / 1000);
    
    if (secondsAgo < 60) return 'now';
    if (secondsAgo < 3600) return Math.floor(secondsAgo / 60) + 'm ago';
    if (secondsAgo < 86400) return Math.floor(secondsAgo / 3600) + 'h ago';
    if (secondsAgo < 604800) return Math.floor(secondsAgo / 86400) + 'd ago';
    
    return new Date(date).toLocaleDateString();
}

// Notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 2rem;
        border-radius: 10px;
        font-weight: 600;
        z-index: 9999;
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.3s ease;
    }
    
    .notification.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .notification-success {
        background: #139817;
        color: white;
    }
    
    .notification-error {
        background: #e72424;
        color: white;
    }
    
    .notification-info {
        background: #1381dc;
        color: white;
    }
    
    .nav-menu.active {
        display: flex !important;
    }
    
    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(8px, 8px);
    }
    
    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }
    
    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(8px, -8px);
    }
`;
document.head.appendChild(style);

