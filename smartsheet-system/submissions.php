<?php
require_once 'includes/auth.php';
require_once 'includes/form_builder.php';
require_once 'includes/submission_handler.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$formBuilder = new FormBuilder();
$submissionHandler = new SubmissionHandler();
$user = $auth->getCurrentUser();

$formId = $_GET['form'] ?? null;
$submissions = [];

if ($formId) {
    // View submissions for a specific form
    $form = $formBuilder->getForm($formId);
    if (!$form) {
        header('Location: submissions.php');
        exit;
    }
    
    // Check if user has access to these submissions
    if ($auth->isAdmin() || $form['created_by'] == $user['id']) {
        $submissions = $submissionHandler->getSubmissions($formId);
    } else {
        // User can only see their own submissions
        $submissions = $submissionHandler->getSubmissions($formId, $user['id']);
    }
} else {
    // View all submissions user has access to
    if ($auth->isAdmin()) {
        // Admin can see all submissions
        $forms = $formBuilder->getAllForms();
        foreach ($forms as $form) {
            $formSubmissions = $submissionHandler->getSubmissions($form['id']);
            $submissions = array_merge($submissions, $formSubmissions);
        }
    } else {
        // Regular users can see submissions for forms they created or their own submissions
        $forms = $formBuilder->getAllForms($user['id']);
        foreach ($forms as $form) {
            $formSubmissions = $submissionHandler->getSubmissions($form['id']);
            $submissions = array_merge($submissions, $formSubmissions);
        }
        
        // Also get submissions user made to other forms
        $userSubmissions = [];
        $allForms = $formBuilder->getAllForms();
        foreach ($allForms as $form) {
            if ($form['created_by'] != $user['id']) {
                $formSubmissions = $submissionHandler->getSubmissions($form['id'], $user['id']);
                $userSubmissions = array_merge($userSubmissions, $formSubmissions);
            }
        }
        
        $submissions = array_merge($submissions, $userSubmissions);
        
        // Sort by submission date
        usort($submissions, function($a, $b) {
            return strtotime($b['submitted_at']) - strtotime($a['submitted_at']);
        });
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions - SmartSheet</title>
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
            
            <h1 class="h3 mb-0 text-gray-800">
                <?= $formId ? 'Submissions for ' . htmlspecialchars($form['title']) : 'All Submissions' ?>
            </h1>
            <?php if ($formId): ?>
            <div class="ms-auto">
                <a href="form_view.php?id=<?= $formId ?>" class="btn btn-primary">
                    <i class="fas fa-eye me-1"></i> View Form
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Submission List</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No submissions found</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <?php if (!$formId): ?>
                                        <th>Form</th>
                                        <?php endif; ?>
                                        <th>Submitted By</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <?php if (!$formId): ?>
                                        <td>
                                            <?php 
                                                $submissionForm = $formBuilder->getForm($submission['form_id']);
                                                echo htmlspecialchars($submissionForm ? $submissionForm['title'] : 'Unknown Form');
                                            ?>
                                        </td>
                                        <?php endif; ?>
                                        <td><?= htmlspecialchars($submission['username'] ?? 'Anonymous') ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($submission['submitted_at'])) ?></td>
                                        <td>
                                            <a href="view_submission.php?id=<?= $submission['id'] ?>" class="btn btn-sm btn-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($auth->isAdmin() || ($submission['submitted_by'] == $user['id'])): ?>
                                            <a href="delete_submission.php?id=<?= $submission['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this submission?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>