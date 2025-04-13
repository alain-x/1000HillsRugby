<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

// Handle delete requests
if (isset($_GET['delete'])) {
    if ($_GET['delete'] === 'user' && isset($_GET['id']) && is_numeric($_GET['id'])){
        // Delete user (prevent deleting self)
        $id = $conn->real_escape_string($_GET['id']);
        if ($id != $_SESSION['user_id']) {
            $sql = "DELETE FROM users WHERE id = $id";
            $conn->query($sql);
        }
    } elseif (is_numeric($_GET['delete'])) {
        // Delete attendance record
        $id = $conn->real_escape_string($_GET['delete']);
        $sql = "DELETE FROM attendance WHERE id = $id";
        $conn->query($sql);
    }
}

// Get all attendance records with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT a.*, u.username FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.date DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM attendance";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get all users for management
$users_sql = "SELECT id, username, is_admin, created_at FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        h1 { color: #2c3e50; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #3498db; color: white; position: sticky; top: 0; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .action-links a { margin-right: 10px; color: #2980b9; text-decoration: none; }
        .action-links a:hover { text-decoration: underline; }
        .btn { display: inline-block; background-color: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .btn:hover { background-color: #2980b9; }
        .btn-danger { background-color: #e74c3c; }
        .btn-danger:hover { background-color: #c0392b; }
        .btn-success { background-color: #2ecc71; }
        .btn-success:hover { background-color: #27ae60; }
        .pagination { display: flex; list-style: none; padding: 0; }
        .pagination li { margin-right: 5px; }
        .pagination a { display: block; padding: 8px 12px; background-color: white; border: 1px solid #ddd; text-decoration: none; color: #3498db; }
        .pagination a.active { background-color: #3498db; color: white; border-color: #3498db; }
        .pagination a:hover:not(.active) { background-color: #f1f1f1; }
        .tab-container { margin-bottom: 20px; }
        .tab-links { display: flex; border-bottom: 1px solid #ddd; }
        .tab-link { padding: 10px 20px; cursor: pointer; background-color: #f1f1f1; border: 1px solid #ddd; border-bottom: none; margin-right: 5px; border-radius: 5px 5px 0 0; }
        .tab-link.active { background-color: white; border-bottom: 1px solid white; margin-bottom: -1px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .stats-container { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .stat-card { flex: 1; min-width: 200px; background-color: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 15px; }
        .stat-card h3 { margin-top: 0; color: #7f8c8d; font-size: 14px; }
        .stat-card p { font-size: 24px; margin: 10px 0 0; color: #2c3e50; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Attendance Records</h3>
                <p><?= $total_records ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?= $users_result->num_rows ?></p>
            </div>
        </div>
        
        <div class="tab-container">
            <div class="tab-links">
                <div class="tab-link active" onclick="openTab('attendance')">Attendance Records</div>
                <div class="tab-link" onclick="openTab('users')">User Management</div>
            </div>
        </div>
        
        <div class="card">
            <div class="tab-content active" id="attendance">
                <div style="margin-bottom: 20px;">
                    <a href="index.php" class="btn btn-success">Add New Attendance</a>
                    <a href="register.php" class="btn">Register New User</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Player Name</th>
                            <th>Reg Number</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['player_name']) ?></td>
                            <td><?= htmlspecialchars($row['reg_number']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['attendance_status']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td class="action-links">
                                <a href="view_my_attendance.php?id=<?= $row['id'] ?>">View</a>
                                <a href="edit_attendance.php?id=<?= $row['id'] ?>">Edit</a>
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this record?')" class="text-danger">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" <?= $i == $page ? 'class="active"' : '' ?>><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="users">
                <div style="margin-bottom: 20px;">
                    <a href="register.php" class="btn btn-success">Register New User</a>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= $user['is_admin'] ? 'Admin' : 'User' ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td class="action-links">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="edit_user.php?id=<?= $user['id'] ?>">Edit</a>
                                    <a href="?delete=user&id=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')" class="text-danger">Delete</a>
                                <?php else: ?>
                                    <span>Current user</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function openTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab links
            document.querySelectorAll('.tab-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to the clicked tab link
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>