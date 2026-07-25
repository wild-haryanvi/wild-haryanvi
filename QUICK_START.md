# Wild Haryanvi - Quick Start Guide

## 🚀 Get Started in 5 Minutes!

### Step 1: Database Setup (Important!)
1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Click **New** to create a new database
3. Name it: `wild_haryanvi`
4. Go to **Import** tab
5. Choose `database.sql` file from the project folder
6. Click **Import**

### Step 2: Configure Database Connection
1. Open `includes/db.php`
2. Update these lines with your database credentials:
```php
define('DB_HOST', 'localhost');    // Your host
define('DB_USER', 'root');         // MySQL username
define('DB_PASS', '');             // MySQL password (usually blank for local)
define('DB_NAME', 'wild_haryanvi');
```

### Step 3: Access the Website
- Open browser and visit: `http://localhost/wild haryanvi/`
- Or if using XAMPP, check your virtual host configuration

### Step 4: Admin Login
- **Email**: admin@wildharyanvi.com
- **Password**: admin123456
- Navigate to: `http://localhost/wild haryanvi/admin/dashboard.php`

## 📋 Project Files Overview

### Core Pages
| Page | Path | Purpose |
|------|------|---------|
| Home | `index.php` | Homepage with featured videos |
| Videos | `pages/videos.php` | Browse & search videos |
| Watch | `pages/watch.php` | Video player |
| Login | `pages/login.php` | User login |
| Signup | `pages/signup.php` | User registration |
| Profile | `pages/profile.php` | User profile & history |
| Premium | `pages/premium.php` | Subscription plans |
| Updates | `pages/updates.php` | News & announcements |
| Contact | `pages/contact.php` | Contact form |
| FAQ | `pages/faq.php` | FAQs |

### Admin Pages
| Page | Path | Purpose |
|------|------|---------|
| Dashboard | `admin/dashboard.php` | Admin panel with stats |

## 🎨 Customization Quick Tips

### Change Primary Color
Edit `assets/css/style.css`:
```css
--primary-red: #FF4444;  /* Change this to your color */
```

### Add New Category
1. Edit `index.php` - Add to categories grid
2. Edit `pages/videos.php` - Add to filter select
3. Edit `admin/dashboard.php` - Add to upload form

### Update Instagram Link
Search for `@wild.haryanvi` and replace with your Instagram handle

## 🔒 Security Reminders

1. **Change Admin Password** immediately!
2. Don't share database credentials
3. Set proper file permissions: `chmod 755 uploads/`
4. Enable HTTPS in production
5. Keep PHP and MySQL updated

## 🎥 First Time Setup

### Add Your First Video
1. Login as admin
2. Go to Admin Dashboard
3. Fill in video details:
   - Title
   - Description
   - Category
   - Type (Free/Premium)
4. Upload and publish

### Test User Account
1. Create new user via Signup
2. Login with that account
3. Browse free videos
4. Try adding to favorites

## 📱 Features to Test

- [ ] Homepage loads properly
- [ ] Video browsing and filtering
- [ ] User registration
- [ ] User login
- [ ] Admin dashboard
- [ ] Video upload
- [ ] Add to favorites
- [ ] Profile page

## 🐛 Troubleshooting

### Error: "Connection failed"
- Check database is running
- Verify credentials in `db.php`
- Ensure database `wild_haryanvi` exists

### Error: "File not found" 
- Check file paths are correct
- Ensure uploads folder exists
- Verify permissions

### Videos not showing
- Check thumbnails exist
- Verify video status is "published"
- Ensure category is correct

### Upload not working
- Check uploads folder permissions
- Ensure PHP upload limits are high enough
- Verify disk space

## 🚀 Deployment Checklist

Before going live:
- [ ] Update database credentials
- [ ] Change admin password
- [ ] Enable HTTPS
- [ ] Set up payment gateway (if needed)
- [ ] Configure email for notifications
- [ ] Test all features
- [ ] Optimize database
- [ ] Set up backups
- [ ] Update Instagram links
- [ ] Test on mobile devices

## 📞 Need Help?

Check the full `README.md` for:
- Complete feature list
- Technology details
- File structure
- Future enhancements

---

**Your Wild Haryanvi website is ready to launch!** 🎉
