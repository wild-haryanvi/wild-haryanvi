# Wild Haryanvi Website - Setup Instructions

## Project Overview
Wild Haryanvi is a modern, animated OTT-style video streaming platform featuring Haryanvi entertainment content. The website includes free and premium video sections, user authentication, admin dashboard, and more.

## Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache/Nginx with PHP support

## Installation Steps

### 1. Setup Database
- Open phpMyAdmin or MySQL command line
- Import the `database.sql` file to create all tables:
  ```sql
  mysql -u root -p wild_haryanvi < database.sql
  ```
- Or manually create the database and run the SQL file content

### 2. Update Database Configuration
Edit `includes/db.php` and update:
```php
define('DB_HOST', 'localhost');  // Your host
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password
define('DB_NAME', 'wild_haryanvi');
```

### 3. Set File Permissions
Make uploads directory writable:
```bash
chmod -R 755 uploads/
chmod -R 755 uploads/videos/
chmod -R 755 uploads/thumbnails/
```

### 4. Access the Website
- Open browser and go to: `http://localhost/wild haryanvi/`
- Or configure virtual host for better URL structure

## Default Login Credentials
- **Admin Email**: admin@wildharyanvi.com
- **Admin Password**: admin123456 (Change this immediately!)

## Project Structure
```
wild haryanvi/
├── assets/
│   ├── css/
│   │   └── style.css          (Main stylesheet)
│   ├── js/
│   │   └── main.js            (Main JavaScript)
│   └── images/                (Image assets)
├── pages/
│   ├── login.php              (Login page)
│   ├── signup.php             (Registration page)
│   ├── videos.php             (Video browsing)
│   ├── watch.php              (Video player)
│   ├── profile.php            (User profile)
│   ├── premium.php            (Subscription)
│   ├── updates.php            (News/updates)
│   └── contact.php            (Contact form)
├── admin/
│   └── dashboard.php          (Admin panel)
├── api/
│   └── add-favorite.php       (API endpoints)
├── includes/
│   ├── db.php                 (Database config)
│   ├── header.php             (Header template)
│   └── footer.php             (Footer template)
├── uploads/
│   ├── videos/                (Video files)
│   └── thumbnails/            (Video thumbnails)
├── index.php                  (Home page)
└── database.sql               (Database schema)
```

## Features Implemented

### ✅ Core Features
- **Home Page**: Attractive banner, latest videos, featured section, trending videos
- **User Authentication**: Sign up, login, session management
- **Video Management**: Browse videos, filter by category, search functionality
- **Video Player**: Watch videos, view details, related videos
- **User Profile**: Watch history, favorite videos, subscription status
- **Admin Dashboard**: Upload videos, manage content, view statistics

### ✅ Premium Features
- Subscription plans (Monthly/Yearly)
- Premium video access
- Watch history tracking
- Favorites management

### ✅ UI/UX Features
- Modern OTT-style design
- Red, Black, White color scheme
- Smooth animations and transitions
- Mobile-responsive design
- Dark theme
- Fast loading

### ✅ Sections
- Home with featured content
- Video categories (Songs, Documentaries, Shorts, News, Entertainment)
- Latest videos
- Featured videos
- Trending videos
- Update announcements
- Contact form

## Future Enhancements

### To Implement
- [ ] Payment gateway integration (Razorpay/PayPal)
- [ ] Comments and likes system
- [ ] Playlists
- [ ] Video quality selection
- [ ] Download option for premium users
- [ ] Referral system
- [ ] Coupon codes
- [ ] Push notifications
- [ ] Android/iOS app
- [ ] Analytics dashboard
- [ ] Recommendation engine

## Admin Features

### Video Management
1. Upload videos with title, description, thumbnail
2. Choose video type (Free/Premium)
3. Categorize videos
4. View upload statistics
5. Edit/Delete videos
6. Publish/Archive videos

### Analytics
- Total videos count
- Total users count
- Total views count
- Premium subscribers count

## Color Scheme
- **Primary Red**: #FF4444
- **Dark Black**: #1a1a1a
- **Secondary Black**: #2a2a2a
- **Light Black**: #3a3a3a
- **White**: #ffffff
- **Text Gray**: #b0b0b0

## Performance Optimization
- Lazy loading for images
- Responsive image sizes
- Efficient database queries with indexes
- Minified CSS and JavaScript
- Browser caching

## Security Features
- Password hashing with bcrypt
- SQL prepared statements
- XSS protection with htmlspecialchars()
- Session management
- CSRF tokens (to be implemented)

## Troubleshooting

### Common Issues

**Issue**: Database connection error
- **Solution**: Check database.sql and ensure MySQL is running. Verify credentials in db.php

**Issue**: 404 errors on pages
- **Solution**: Check if mod_rewrite is enabled. Ensure .htaccess is properly configured

**Issue**: Uploads not working
- **Solution**: Check folder permissions. Run `chmod -R 755 uploads/`

**Issue**: Videos not loading
- **Solution**: Ensure thumbnails are in uploads/thumbnails/ folder

## Customization Guide

### Changing Colors
Edit `assets/css/style.css` - Update CSS variables:
```css
:root {
    --primary-red: #FF4444;  /* Change this */
    --dark-black: #1a1a1a;   /* Change this */
}
```

### Adding New Categories
1. Update `pages/videos.php` select dropdown
2. Update `index.php` categories grid
3. Add category to admin upload form

### Modifying Subscription Plans
Edit `pages/premium.php` - Update pricing cards

## Contact & Support
- **Instagram**: @wild.haryanvi
- **Email**: info@wildharyanvi.com

## License
All rights reserved © 2024 Wild Haryanvi

---
**Last Updated**: 2024
**Version**: 1.0
