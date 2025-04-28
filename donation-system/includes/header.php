<?php
/**
 * Header template with proper includes
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header class="main-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="index.php"><?= SITE_NAME ?></a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="campaigns.php">Campaigns</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if (isUserLoggedIn()): ?>
                    <div class="user-dropdown">
                        <button class="user-menu-toggle">
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars($_SESSION['user_name']) ?>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="user-dropdown-menu">
                            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                            <a href="donations.php"><i class="fas fa-donate"></i> My Donations</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>