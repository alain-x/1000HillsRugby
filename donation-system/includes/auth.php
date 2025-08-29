<?php
/**
 * Authentication functions for both users and admins
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Protection Helpers
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * USER AUTHENTICATION FUNCTIONS
 */

function isUserLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        return true;
    }
    
    return false;
}

function registerUser($name, $email, $password) {
    global $pdo;
    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address';
    }
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters';
    }
    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return 'Email already registered';
        }
        // Create user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([htmlspecialchars($name), $email, $hash]);
        // Auto-login
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        return true;
    } catch (PDOException $e) {
        error_log('registerUser error: ' . $e->getMessage());
        return 'Registration failed. Please try again later.';
    }
}

/**
 * ADMIN AUTHENTICATION FUNCTIONS
 */

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function adminAuthGuard() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function loginAdmin($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        return true;
    }
    
    return false;
}

function logoutAdmin() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
}

// Common logout function
function logoutUser() {
    unset($_SESSION['user_logged_in']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
}
?>