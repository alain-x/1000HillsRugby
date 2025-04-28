<?php
// index.php - Main entry point for the application

require_once 'includes/auth.php';

$auth = new Auth();

// Check if user is logged in
if ($auth->isLoggedIn()) {
    // Redirect to dashboard if logged in
    header('Location: dashboard.php');
} else {
    // Redirect to login page if not logged in
    header('Location: login.php');
}

exit;
?>