<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Determine if logging out admin or user based on session flags
if (isAdminLoggedIn()) {
    logoutAdmin();
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

if (isUserLoggedIn()) {
    logoutUser();
}

header('Location: index.php');
exit;
