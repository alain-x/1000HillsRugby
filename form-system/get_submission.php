<?php
// get_submission.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Submission ID required']);
    exit;
}

$submission_id = $_GET['id'];

// Get submission details with form and user info
$stmt = $pdo->prepare("SELECT fs.*, u.username, u.email, f.title 
                      FROM form_submissions fs 
                      LEFT JOIN users u ON fs.user_id = u.id 
                      JOIN forms f ON fs.form_id = f.id 
                      WHERE fs.id = ?");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
    exit;
}

// Check if current user has permission to view this submission
if (isLoggedIn()) {
    if (!isAdmin() && $submission['user_id'] != getUserId()) {
        echo json_encode(['success' => false, 'message' => 'You don\'t have permission to view this submission']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'You need to login to view submissions']);
    exit;
}

// Get submission data with field labels
$stmt = $pdo->prepare("SELECT ff.label, sd.field_value 
                      FROM submission_data sd 
                      JOIN form_fields ff ON sd.field_id = ff.id 
                      WHERE sd.submission_id = ?");
$stmt->execute([$submission_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'submission' => $submission,
    'fields' => $fields
]);
?>