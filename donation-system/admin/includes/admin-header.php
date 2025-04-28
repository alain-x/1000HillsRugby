<?php
/**
 * Admin header template
 */
?>
<header class="admin-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="index.php"><?= SITE_NAME ?> Admin</a>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="campaigns.php">Campaigns</a></li>
                    <li><a href="donations.php">Donations</a></li>
                </ul>
            </nav>
            <div class="admin-user">
                <span class="username"><?= $_SESSION['admin_username'] ?? 'Admin' ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
</header>