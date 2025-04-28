<?php
// admin/delete_submission.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$submission_id = $_GET['id'] ?? 0;
$form_id = $_GET['form_id'] ?? 0;

if ($submission_id) {
    try {
        $pdo->beginTransaction();
        
        // Delete submission data first
        $stmt = $pdo->prepare("DELETE FROM submission_data WHERE submission_id = ?");
        $stmt->execute([$submission_id]);
        
        // Then delete the submission
        $stmt = $pdo->prepare("DELETE FROM form_submissions WHERE id = ?");
        $stmt->execute([$submission_id]);
        
        $pdo->commit();
        $_SESSION['success'] = 'Submission deleted successfully';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error deleting submission: ' . $e->getMessage();
    }
}

redirect('/admin/view_submissions.php?id=' . $form_id);
?>