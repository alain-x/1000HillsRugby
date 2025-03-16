<?php
// Error Reporting Turn On
ini_set('error_reporting', E_ALL);

// Setting up the time zone
date_default_timezone_set('America/Los_Angeles');

// Database Configuration
$dbhost = 'localhost'; // server
 $dbname = 'hillsrug_ecommerceweb'; // database name
 $dbuser = 'hillsrug_gasore'; // database username
 $dbpass = 'M00dle??'; // database password

// Database Configuration
//$dbhost = 'localhost'; // server
//$dbname = 'ecommerceweb'; // database name
//$dbuser = 'root'; // database username
//$dbpass = '1234'; // database password

// Defining base url
if (!defined('BASE_URL')) {
    define("BASE_URL", "");
}

// Getting Admin url
if (!defined('ADMIN_URL')) {
    define("ADMIN_URL", BASE_URL . "admin/");
}

try {
    $pdo = new PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    echo "Connection error: " . $exception->getMessage();
}
?>