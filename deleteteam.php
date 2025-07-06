<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');
define('LOGO_DIR', __DIR__ . '/logos_/');
define('DEFAULT_LOGO', 'default.png');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Check if team ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: uploadtables.php?error=Invalid team ID');
    exit;
}

$team_id = (int)$_GET['id'];

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get team information before deletion
        $stmt = $conn->prepare("SELECT name, logo FROM teams WHERE id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $team = $result->fetch_assoc();
        $stmt->close();
        
        if (!$team) {
            throw new Exception("Team not found");
        }
        
        // Delete from league_standings first (foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM league_standings WHERE team_id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete from teams table
        $stmt = $conn->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete logo file if it exists and is not the default
        if ($team['logo'] && $team['logo'] !== DEFAULT_LOGO && file_exists(LOGO_DIR . $team['logo'])) {
            unlink(LOGO_DIR . $team['logo']);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect with success message
        header('Location: uploadtables.php?message=Team "' . urlencode($team['name']) . '" deleted successfully&type=success');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: uploadtables.php?error=Failed to delete team: ' . urlencode($e->getMessage()) . '&type=error');
    exit;
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 