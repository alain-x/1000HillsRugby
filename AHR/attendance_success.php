<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Submitted Successfully</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }
        .success-container {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
        .success-icon {
            color: #4CAF50;
            font-size: 60px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-secondary {
            background-color: #6c757d;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .action-buttons {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1>Attendance Recorded Successfully!</h1>
        <p>Thank you for submitting the attendance information. The record has been saved to the system.</p>
        
        <div class="action-buttons">
            <a href="index.php" class="btn">Submit Another Attendance</a>
            
            <?php if (isAdmin()): ?>
                <a href="admin_dashboard.php" class="btn btn-secondary">View All Records</a>
            <?php else: ?>
                <a href="view_attendance" class="btn btn-secondary">View My Submissions</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>