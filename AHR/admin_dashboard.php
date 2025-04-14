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
        :root {
            --primary-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --text-color: #2c3e50;
            --light-text: #7f8c8d;
            --border-color: #ddd;
            --hover-color: #f1f1f1;
            --card-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 15px;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        h1 {
            color: var(--text-color);
            margin-top: 0;
            font-size: 1.8rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            min-width: 600px;
        }
        
        th, td {
            border: 1px solid var(--border-color);
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            position: sticky;
            top: 0;
            font-weight: 500;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: var(--hover-color);
        }
        
        .action-links a {
            margin-right: 10px;
            color: var(--primary-color);
            text-decoration: none;
            white-space: nowrap;
        }
        
        .action-links a:hover {
            text-decoration: underline;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .pagination {
            display: flex;
            flex-wrap: wrap;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 5px;
        }
        
        .pagination a {
            display: block;
            padding: 8px 12px;
            background-color: white;
            border: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--primary-color);
            border-radius: 4px;
        }
        
        .pagination a.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination a:hover:not(.active) {
            background-color: var(--hover-color);
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-links {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--border-color);
            gap: 5px;
        }
        
        .tab-link {
            padding: 8px 15px;
            cursor: pointer;
            background-color: #f1f1f1;
            border: 1px solid var(--border-color);
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            font-size: 0.9rem;
        }
        
        .tab-link.active {
            background-color: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1 1 200px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: var(--light-text);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .stat-card p {
            font-size: 1.5rem;
            margin: 10px 0 0;
            color: var(--text-color);
            font-weight: bold;
        }
        
        .text-danger {
            color: var(--danger-color);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .card {
                padding: 10px;
            }
            
            th, td {
                padding: 8px;
                font-size: 0.85rem;
            }
            
            .btn {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
            
            .tab-link {
                padding: 6px 12px;
                font-size: 0.85rem;
            }
            
            .stat-card {
                flex: 1 1 150px;
                padding: 10px;
            }
            
            .stat-card h3 {
                font-size: 0.8rem;
            }
            
            .stat-card p {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            h1 {
                font-size: 1.5rem;
            }
            
            .action-links a {
                display: block;
                margin: 5px 0;
            }
            
            .tab-links {
                flex-direction: column;
                border-bottom: none;
            }
            
            .tab-link {
                border-radius: 5px;
                border: 1px solid var(--border-color);
                margin-bottom: 5px;
            }
            
            .tab-link.active {
                border-bottom: 1px solid var(--border-color);
                margin-bottom: 5px;
            }
        }
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
                <div style="margin-bottom: 15px;">
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
                <div style="margin-bottom: 15px;">
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
                        <?php 
                        // Reset users_result pointer
                        $users_result->data_seek(0);
                        while ($user = $users_result->fetch_assoc()): ?>
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