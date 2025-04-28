<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

logoutAdmin();
header('Location: login.php');
exit;
?>