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
    // Get submission details
    $stmt = $pdo->prepare("SELECT fs.*, f.title, u.username, u.email 
                         FROM form_submissions fs
                         JOIN forms f ON fs.form_id = f.id
                         LEFT JOIN users u ON fs.user_id = u.id
                         WHERE fs.id = ?");
    $stmt->execute([$submission_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Submission not found']);
        exit;
    }

    // Get all fields and responses
    $stmt = $pdo->prepare("SELECT sd.*, ff.label, ff.field_type 
                         FROM submission_data sd
                         JOIN form_fields ff ON sd.field_id = ff.id
                         WHERE sd.submission_id = ?");
    $stmt->execute([$submission_id]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'submission' => [
            'title' => $submission['title'],
            'username' => $submission['username'] ?? 'Guest',
            'email' => $submission['email'] ?? null,
            'submitted_at' => $submission['submitted_at']
        ],
        'fields' => $fields
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
?>