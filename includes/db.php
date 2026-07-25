<?php

date_default_timezone_set('Asia/Kolkata');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wild_haryanvi');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+05:30'");

// Define base URL
define('BASE_URL', 'http://localhost/wild haryanvi/');

// Session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>