<?php
/**
 * Database Connection Test
 * Visit this file in browser to test database connection
 * Delete after confirming connection works
 */

require_once 'includes/db.php';

// Test connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get database info
$result = $conn->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
$info = $result->fetch_assoc();

// Count tables
$tables_result = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'wild_haryanvi'");
$tables = $tables_result->fetch_assoc();

// Count users
$users_result = $conn->query("SELECT COUNT(*) as user_count FROM users");
$users = $users_result->fetch_assoc();

// Count videos
$videos_result = $conn->query("SELECT COUNT(*) as video_count FROM videos");
$videos = $videos_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wild Haryanvi - Connection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            background: linear-gradient(135deg, #2a2a2a 0%, #3a3a3a 100%);
            padding: 2rem;
            border-radius: 15px;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(255, 68, 68, 0.3);
            border: 2px solid #FF4444;
        }
        h1 {
            color: #FF4444;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status {
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            border-left: 5px solid #FF4444;
        }
        .success {
            background: rgba(76, 175, 80, 0.2);
            color: #8bff8b;
        }
        .info {
            background: rgba(33, 150, 243, 0.2);
            color: #64b5f6;
            margin-bottom: 1rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        .info-label {
            font-weight: 600;
            color: #b0b0b0;
        }
        .info-value {
            color: #64b5f6;
        }
        .warning {
            background: rgba(255, 193, 7, 0.2);
            color: #ffd54f;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        .next-steps {
            background: rgba(255, 68, 68, 0.1);
            padding: 1rem;
            border-radius: 10px;
            border-left: 5px solid #FF4444;
            margin-top: 1rem;
        }
        .next-steps h3 {
            color: #FF4444;
            margin-bottom: 0.5rem;
        }
        .next-steps ol {
            margin-left: 1.5rem;
            color: #b0b0b0;
        }
        .next-steps li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Wild Haryanvi Connection Test</h1>
        
        <div class="status success">
            <strong>✓ Database Connection Successful!</strong>
        </div>

        <div class="info">
            <div class="info-row">
                <span class="info-label">Current Database:</span>
                <span class="info-value"><?php echo $info['current_db']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">MySQL Version:</span>
                <span class="info-value"><?php echo $info['mysql_version']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tables Created:</span>
                <span class="info-value"><?php echo $tables['table_count']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Users in Database:</span>
                <span class="info-value"><?php echo $users['user_count']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Videos in Database:</span>
                <span class="info-value"><?php echo $videos['video_count']; ?></span>
            </div>
        </div>

        <div class="next-steps">
            <h3>🚀 Next Steps:</h3>
            <ol>
                <li>Delete this test file (db-test.php)</li>
                <li>Visit <a href="index.php" style="color: #FF4444; text-decoration: none;"><strong>index.php</strong></a> to see the homepage</li>
                <li>Create a new user account or login with: admin@wildharyanvi.com / admin123456</li>
                <li>Visit the <a href="admin/dashboard.php" style="color: #FF4444; text-decoration: none;"><strong>admin dashboard</strong></a> to upload videos</li>
                <li>Start exploring and customizing!</li>
            </ol>
        </div>

        <div class="warning">
            ⚠️ <strong>Security Reminder:</strong> Delete this test file immediately after confirming the connection. It's a security risk to leave it on a production server.
        </div>
    </div>
</body>
</html>
