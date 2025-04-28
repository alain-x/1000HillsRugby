<?php
require_once 'includes/auth.php';
require_once 'includes/form_builder.php'; // Add this line
require_once 'includes/admin_dashboard.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Rest of your code...
$dashboard = new AdminDashboard();
$stats = $dashboard->getSystemStats();
$user = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SmartSheet</title>
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
                <a href="dashboard.php" class="nav-link active">
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
            
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card primary h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Forms</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['forms'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-list-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card success h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Submissions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['submissions'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-inbox fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card info h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['users'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card warning h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        My Forms</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                            $formBuilder = new FormBuilder();
                                            $userForms = $formBuilder->getAllForms($user['id']);
                                            echo count($userForms);
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-edit fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- Submissions Chart -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Submissions Overview</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="submissionsChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-plus-circle text-success"></i>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">Today, 10:45 AM</div>
                                        <span class="font-weight-bold">New form "Employee Feedback" created</span>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-check-circle text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">Yesterday, 3:22 PM</div>
                                        <span class="font-weight-bold">5 new submissions for "Customer Survey"</span>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-user-plus text-info"></i>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">Yesterday, 1:10 PM</div>
                                        <span class="font-weight-bold">New user "john_doe" registered</span>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-file-upload text-warning"></i>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">Monday, 9:30 AM</div>
                                        <span class="font-weight-bold">File uploaded with submission #42</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>