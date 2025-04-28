<?php
require_once __DIR__ . '/../config/database.php';

class AdminDashboard {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function getSystemStats() {
        $stats = [];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM forms");
        $stmt->execute();
        $stats['forms'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM form_submissions");
        $stmt->execute();
        $stats['submissions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
    
    public function getUserList() {
        $stmt = $this->db->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateUserRole($userId, $role) {
        $validRoles = ['admin', 'manager', 'user'];
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }
    
    public function deleteUser($userId) {
        // Don't allow deleting yourself
        if ($userId == $_SESSION['user_id']) {
            return false;
        }
        
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}