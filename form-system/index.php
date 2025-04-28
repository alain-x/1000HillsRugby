<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
} else {
    redirect('/login.php');
}
?>