<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        session_start();

        // Ensure the users table and at least one admin user exist
        $this->initializeSchema();
    }

    private function initializeSchema() {
        try {
            // Create users table if it does not exist yet
            $this->db->exec("CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(191) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Check if there is already any admin user
            $stmt = $this->db->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $adminCount = isset($row['cnt']) ? (int)$row['cnt'] : 0;

            if ($adminCount === 0) {
                // Create a default admin account only once
                $defaultUsername = 'admin';
                $defaultEmail = 'gasore@1000hillsrugby.rw';
                $defaultPasswordHash = password_hash('Back123!!', PASSWORD_DEFAULT);

                $insert = $this->db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
                $insert->execute([$defaultUsername, $defaultEmail, $defaultPasswordHash]);
            }
        } catch (Exception $e) {
            // In production you might log this instead of echoing
            // For safety, do not break the whole app if this fails
        }
    }
    
    public function register($username, $email, $password, $role = 'user') {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }

        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into database
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$username, $email, $passwordHash, $role]);
        
        if ($success) {
            $this->login($username, $password);
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    public function login($identifier, $password) {
        // Allow login using either username or email
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }
}