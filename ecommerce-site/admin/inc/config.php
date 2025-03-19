<?php
// Database Configuration
define('DB_HOST', 'localhost'); // Database host
define('DB_NAME', 'ecommerceweb'); // Database name
define('DB_USER', 'root'); // Database username
define('DB_PASS', '1234'); // Database password

// Base URL Configuration
define('BASE_URL', ''); // Base URL of the site
define('ADMIN_URL', BASE_URL . 'admin/'); // Admin URL

// Error Reporting
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Timezone Configuration
date_default_timezone_set('America/Los_Angeles');

// Establish Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>