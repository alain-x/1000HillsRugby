<?php
require_once 'includes/auth.php';
require_once 'includes/admin_dashboard.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

$dashboard = new AdminDashboard();
$users = $dashboard->getUserList();
$user = $auth->getCurrentUser();

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'] ?? null;
    $role = $_POST['role'] ?? null;
    
    if ($_POST['action'] === 'update_role' && $userId && $role) {
        $success = $dashboard->updateUserRole($userId, $role);
    } elseif ($_POST['action'] === 'delete_user' && $userId) {
        $success = $dashboard->deleteUser($userId);
    }
    
    // Refresh user list
    header("Location: users.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - SmartSheet</title>
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
                <a href="submissions.php" class="nav-link text-white">
                    <i class="fas fa-inbox me-2"></i>
                    Submissions
                </a>
            </li>
            <li>
                <a href="users.php" class="nav-link active">
                    <i class="fas fa-users me-2"></i>
                    Users
                </a>
            </li>
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
            
            <h1 class="h3 mb-0 text-gray-800">User Management</h1>
        </nav>

        <!-- Content -->
        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">All Users</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $userItem): ?>
                                <tr>
                                    <td><?= htmlspecialchars($userItem['username']) ?></td>
                                    <td><?= htmlspecialchars($userItem['email']) ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?= $userItem['id'] ?>">
                                            <select name="role" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                <option value="admin" <?= $userItem['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="manager" <?= $userItem['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                                <option value="user" <?= $userItem['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($userItem['created_at'])) ?></td>
                                    <td>
                                        <?php if ($userItem['id'] != $user['id']): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $userItem['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>