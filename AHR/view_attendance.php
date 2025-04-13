<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Check if attendance ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_my_attendance.php");
    exit();
}

$attendance_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Check if the user has permission to view this record
$sql = "SELECT a.*, u.username 
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.id = ? AND (a.user_id = ? OR ? IN (SELECT id FROM users WHERE is_admin = 1))";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $attendance_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Record doesn't exist or user doesn't have permission
    header("Location: view_my_attendance.php");
    exit();
}

$attendance = $result->fetch_assoc();
$is_admin = isAdmin();
$is_owner = ($attendance['user_id'] == $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Record - <?= htmlspecialchars($attendance['player_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 6px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: var(--light-color);
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        h1 {
            color: var(--dark-color);
            margin: 0;
            font-size: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 15px;
        }

        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            font-size: 14px;
        }

        .btn-back {
            background-color: #6c757d;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        .btn-edit {
            background-color: var(--warning-color);
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-delete {
            background-color: var(--danger-color);
        }

        .btn-delete:hover {
            background-color: #d9534f;
        }

        .attendance-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 25px;
        }

        .attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .attendance-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .attendance-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .status-present {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-late {
            background-color: rgba(241, 196, 15, 0.1);
            color: var(--warning-color);
        }

        .status-absent {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .status-excused {
            background-color: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .notes-section {
            margin-top: 25px;
        }

        .notes-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .notes-content {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .file-section {
            margin-top: 25px;
        }

        .file-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .file-link:hover {
            background-color: #e9ecef;
        }

        .file-link i {
            font-size: 18px;
        }

        .footer-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .view-only-message {
            margin-top: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            border-left: 4px solid #6c757d;
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> Attendance Record</h1>
            <a href="view_attendance.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Records
            </a>
        </div>
        
        <div class="attendance-card">
            <div class="attendance-header">
                <div class="attendance-title">
                    <?= htmlspecialchars($attendance['player_name']) ?>
                    <span class="attendance-status status-<?= strtolower($attendance['attendance_status']) ?>">
                        <?= htmlspecialchars($attendance['attendance_status']) ?>
                    </span>
                </div>
                <div class="attendance-date">
                    <?= date('F j, Y', strtotime($attendance['date'])) ?>
                </div>
            </div>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Registration Number</div>
                    <div class="detail-value"><?= htmlspecialchars($attendance['reg_number']) ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Category</div>
                    <div class="detail-value"><?= htmlspecialchars($attendance['category']) ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Session Type</div>
                    <div class="detail-value"><?= htmlspecialchars($attendance['session_type']) ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Arrival Time</div>
                    <div class="detail-value">
                        <?= !empty($attendance['arriving_time']) ? htmlspecialchars($attendance['arriving_time']) : 'N/A' ?>
                    </div>
                </div>
                
                <?php if ($is_admin): ?>
                <div class="detail-item">
                    <div class="detail-label">Recorded By</div>
                    <div class="detail-value"><?= htmlspecialchars($attendance['username']) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="detail-item">
                    <div class="detail-label">Recorded On</div>
                    <div class="detail-value">
                        <?= date('F j, Y \a\t g:i A', strtotime($attendance['created_at'])) ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($attendance['notes'])): ?>
            <div class="notes-section">
                <div class="notes-label">Additional Notes</div>
                <div class="notes-content">
                    <?= nl2br(htmlspecialchars($attendance['notes'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($attendance['file_path'])): ?>
            <div class="file-section">
                <div class="file-label">Attached File</div>
                <a href="<?= htmlspecialchars($attendance['file_path']) ?>" class="file-link" target="_blank">
                    <i class="fas fa-paperclip"></i> View Attachment
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($is_owner || $is_admin): ?>
            <div class="footer-actions">
                <a href="edit_attendance.php?id=<?= $attendance['id'] ?>" class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Record
                </a>
                <a href="delete_attendance.php?id=<?= $attendance['id'] ?>" class="btn btn-delete" 
                   onclick="return confirm('Are you sure you want to delete this attendance record?');">
                    <i class="fas fa-trash-alt"></i> Delete Record
                </a>
            </div>
            <?php else: ?>
            <div class="view-only-message">
                <i class="fas fa-info-circle"></i> You are viewing this record in read-only mode.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>