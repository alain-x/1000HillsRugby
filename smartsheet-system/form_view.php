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

$formId = $_GET['id'] ?? null;
if (!$formId || !is_numeric($formId)) {
    header('Location: forms.php');
    exit;
}

$form = $formBuilder->getForm($formId);
if (!$form) {
    header('Location: forms.php');
    exit;
}

// Check if user has access to this form (either creator, admin, or has submissions)
$canView = $auth->isAdmin() || $form['created_by'] == $user['id'];

if (!$canView) {
    // Check if user has submissions for this form
    $userSubmissions = $submissionHandler->getSubmissions($formId, $user['id']);
    $canView = count($userSubmissions) > 0;
}

if (!$canView) {
    header('Location: forms.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_id']) && $_POST['form_id'] == $formId) {
    try {
        $submissionId = $submissionHandler->handleSubmission($formId, $_POST, $_FILES, $user['id']);
        $success = "Form submitted successfully!";
    } catch (Exception $e) {
        $error = "Error submitting form: " . $e->getMessage();
    }
}

$submissionCount = $submissionHandler->getSubmissionCount($formId);
$canEdit = $auth->isAdmin() || $form['created_by'] == $user['id'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['title']) ?> - SmartSheet</title>
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
                <a href="forms.php" class="nav-link active">
                    <i class="fas fa-list-alt me-2"></i>
                    Forms
                </a>
            </li>
            <li>
                <a href="submissions.php" class="nav-link text-white">
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
            
            <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($form['title']) ?></h1>
            <div class="ms-auto">
                <?php if ($canEdit): ?>
                <a href="form_builder.php?edit=<?= $formId ?>" class="btn btn-secondary me-2">
                    <i class="fas fa-edit me-1"></i> Edit Form
                </a>
                <?php endif; ?>
                <a href="submissions.php?form=<?= $formId ?>" class="btn btn-primary">
                    <i class="fas fa-inbox me-1"></i> View Submissions (<?= $submissionCount ?>)
                </a>
            </div>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <div id="alertsContainer">
                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card shadow mb-4 animated-form">
                        <?= $formBuilder->renderForm($formId) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>