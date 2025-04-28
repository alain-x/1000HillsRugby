<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Submission ID required']);
    exit;
}

$submission_id = (int)$_GET['id'];

try {
    // Get submission details with form info
    $stmt = $pdo->prepare("SELECT fs.*, u.username, u.email, f.title 
                          FROM form_submissions fs 
                          LEFT JOIN users u ON fs.user_id = u.id 
                          JOIN forms f ON fs.form_id = f.id 
                          WHERE fs.id = ?");
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Submission not found']);
        exit;
    }

    // Check if current user has permission to view this submission
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You don\'t have permission to view this submission']);
        exit;
    }

    // Get submission data with field labels and file info
    $stmt = $pdo->prepare("SELECT ff.label, ff.field_type, sd.field_value as value, 
                          sd.file_path, sd.file_size
                          FROM submission_data sd 
                          JOIN form_fields ff ON sd.field_id = ff.id 
                          WHERE sd.submission_id = ?");
    $stmt->execute([$submission_id]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response
    $response = [
        'success' => true,
        'submission' => [
            'title' => $submission['title'],
            'username' => $submission['username'] ?? 'Guest',
            'email' => $submission['email'] ?? null,
            'submitted_at' => $submission['submitted_at']
        ],
        'fields' => $fields
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
?>