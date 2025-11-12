<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if required parameters are provided
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['table']) || !in_array($input['table'], ['teams', 'competitions', 'seasons', 'genders', 'league_standings'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid table specified.']);
    exit;
}

if (!isset($input['id']) || !is_numeric($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID specified.']);
    exit;
}

$table = $input['table'];
$id = (int)$input['id'];
$hard_delete = isset($input['hard_delete']) && $input['hard_delete'] === true;

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Check if record exists
    $check_sql = "SELECT id FROM `$table` WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
        exit;
    }
    
    $check_stmt->close();
    
    // Handle hard delete (admin only)
    if ($hard_delete) {
        // Verify admin privileges here (implement your own admin check)
        $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
        
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin privileges required for hard delete.']);
            exit;
        }
        
        // Perform hard delete
        $delete_sql = "DELETE FROM `$table` WHERE id = ?";
    } else {
        // Perform soft delete
        $delete_sql = "UPDATE `$table` SET is_deleted = 1, deleted_at = NOW() WHERE id = ?";
    }
    
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $message = $hard_delete ? 'Record permanently deleted.' : 'Record marked as deleted.';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception("Failed to delete record: " . $conn->error);
    }
    
    $delete_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    error_log("Delete error: " . $e->getMessage());
}
?>
