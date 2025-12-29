<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        session_start();
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