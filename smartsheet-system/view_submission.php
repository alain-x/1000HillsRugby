<?php
require_once 'includes/auth.php';
require_once 'includes/form_builder.php';
require_once 'includes/submission_handler.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$submissionHandler = new SubmissionHandler();
$formBuilder = new FormBuilder();
$user = $auth->getCurrentUser();

$submissionId = $_GET['id'] ?? null;
if (!$submissionId || !is_numeric($submissionId)) {
    header('Location: submissions.php');
    exit;
}

// Get submission data
$submission = $submissionHandler->getSubmission($submissionId);
if (!$submission) {
    header('Location: submissions.php');
    exit;
}

// Get the form with all its fields
$form = $formBuilder->getForm($submission['form_id']);
if (!$form) {
    header('Location: submissions.php');
    exit;
}

// Check if user has access to view this submission
$canView = false;
if ($auth->isAdmin()) {
    $canView = true;
} elseif ($form['created_by'] == $user['id']) {
    $canView = true; // Form owner
} elseif ($submission['submitted_by'] == $user['id']) {
    $canView = true; // Submission owner
}

if (!$canView) {
    header('Location: submissions.php');
    exit;
}

// Organize submission data by field ID for easier access
$submissionData = [];
foreach ($submission['data'] as $data) {
    $submissionData[$data['field_id']] = $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission #<?= htmlspecialchars($submissionId) ?> - SmartSheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar sidebar-dark d-flex flex-column flex-shrink-0 p-3 text-white">
        <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-4">SmartSheet</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link text-white">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="forms.php" class="nav-link text-white">
                    <i class="fas fa-list-alt me-2"></i>
                    Forms
                </a>
            </li>
            <li>
                <a href="submissions.php" class="nav-link active">
                    <i class="fas fa-inbox me-2"></i>
                    Submissions
                </a>
            </li>
            <?php if ($auth->isAdmin()): ?>
            <li>
                <a href="users.php" class="nav-link text-white">
                    <i class="fas fa-users me-2"></i>
                    Users
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle me-2 fs-4"></i>
                <strong><?= htmlspecialchars($user['username']) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
            </ul>
        </div>
    </div>

    <!-- Main content -->
    <div class="flex-grow-1">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow">
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
                <i class="fa fa-bars"></i>
            </button>
            
            <h1 class="h3 mb-0 text-gray-800">Submission #<?= htmlspecialchars($submissionId) ?></h1>
            <div class="ms-auto">
                <a href="submissions.php?form=<?= $form['id'] ?>" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Submissions
                </a>
                <?php if ($auth->isAdmin() || $submission['submitted_by'] == $user['id']): ?>
                <a href="delete_submission.php?id=<?= $submissionId ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this submission?')">
                    <i class="fas fa-trash me-1"></i> Delete
                </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <?= htmlspecialchars($form['title']) ?>
                                <small class="text-muted d-block">
                                    Submitted by: <?= htmlspecialchars($submission['username'] ?? 'Anonymous') ?> on <?= date('M d, Y H:i', strtotime($submission['submitted_at'])) ?>
                                </small>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($form['fields'] as $field): ?>
                                <?php 
                                    $value = $submissionData[$field['id']]['value'] ?? '';
                                    $fieldType = $field['field_type'];
                                ?>
                                <div class="mb-4">
                                    <label class="form-label font-weight-bold"><?= htmlspecialchars($field['label']) ?></label>
                                    
                                    <?php if ($fieldType === 'file' && !empty($value)): ?>
                                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $value)): ?>
                                            <div class="mb-2">
                                                <img src="<?= htmlspecialchars($value) ?>" class="img-thumbnail" style="max-height: 200px;">
                                            </div>
                                        <?php endif; ?>
                                        <a href="<?= htmlspecialchars($value) ?>" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="fas fa-download me-1"></i> Download File
                                        </a>
                                    <?php elseif (in_array($fieldType, ['checkbox', 'radio']) && !empty($value)): ?>
                                        <?php 
                                            $selectedOptions = json_decode($value, true) ?: [];
                                            foreach ($selectedOptions as $option): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="<?= $fieldType ?>" checked disabled>
                                                <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php elseif ($fieldType === 'dropdown' && !empty($value)): ?>
                                        <select class="form-select" disabled>
                                            <option selected><?= htmlspecialchars($value) ?></option>
                                        </select>
                                    <?php else: ?>
                                        <input type="<?= $fieldType === 'date' ? 'date' : 'text' ?>" 
                                               class="form-control" 
                                               value="<?= htmlspecialchars($value) ?>" 
                                               disabled>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($field['description'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($field['description']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>