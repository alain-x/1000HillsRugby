<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'hillsrug_hillsrug');
define('DB_PASS', getenv('DB_PASS') ?: 'M00dle!!');
define('DB_NAME', getenv('DB_NAME') ?: 'hillsrug_donation_system');

// Site configuration
define('SITE_NAME', 'Every.org Clone');
define('SITE_URL', 'http://localhost/donation-system');
define('ADMIN_URL', SITE_URL . '/admin');

// Start session
session_start();

// Create database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include functions
require_once 'functions.php';
?>