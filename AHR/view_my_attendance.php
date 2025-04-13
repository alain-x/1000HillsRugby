<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Initialize filter variables
$filter_conditions = [];
$params = [];
$types = '';

// Base query differs for admin vs regular users
if ($is_admin) {
    $sql = "SELECT a.*, u.username FROM attendance a JOIN users u ON a.user_id = u.id";
} else {
    $sql = "SELECT * FROM attendance WHERE user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

// Handle filters
if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
    $filter_conditions[] = "date >= ?";
    $params[] = $_GET['from_date'];
    $types .= 's';
}

if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
    $filter_conditions[] = "date <= ?";
    $params[] = $_GET['to_date'];
    $types .= 's';
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filter_conditions[] = "attendance_status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

if ($is_admin && isset($_GET['player_name']) && !empty($_GET['player_name'])) {
    $filter_conditions[] = "player_name LIKE ?";
    $params[] = '%' . $_GET['player_name'] . '%';
    $types .= 's';
}

// Combine conditions
if (!empty($filter_conditions)) {
    $sql .= $is_admin ? " WHERE " : " AND ";
    $sql .= implode(" AND ", $filter_conditions);
}

$sql .= " ORDER BY date DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
 

// Calculate performance metrics - only for admins
$performance_stats = [];
if ($is_admin) {
    function calculatePerformance($conn, $period) {
        $end_date = date('Y-m-d');
        $start_date = '';
        
        switch ($period) {
            case 'month':
                $start_date = date('Y-m-d', strtotime('-1 month'));
                break;
            case '3months':
                $start_date = date('Y-m-d', strtotime('-3 months'));
                break;
            case '6months':
                $start_date = date('Y-m-d', strtotime('-6 months'));
                break;
            case 'year':
                $start_date = date('Y-m-d', strtotime('-1 year'));
                break;
            default:
                $start_date = date('Y-m-d', strtotime('-1 month'));
        }
        
        // Get total sessions
        $total_sql = "SELECT COUNT(*) as total FROM attendance 
                     WHERE date BETWEEN ? AND ?";
        $stmt = $conn->prepare($total_sql);
        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $total_result = $stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $total_sessions = $total_row['total'];
        
        if ($total_sessions == 0) {
            return [
                'percentage' => 0,
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'excused' => 0,
                'total' => 0
            ];
        }
        
        // Get all status counts
        $status_sql = "SELECT 
                        SUM(CASE WHEN attendance_status = 'Present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN attendance_status = 'Late' THEN 1 ELSE 0 END) as late,
                        SUM(CASE WHEN attendance_status = 'Absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN attendance_status = 'Excused' THEN 1 ELSE 0 END) as excused
                      FROM attendance 
                      WHERE date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($status_sql);
        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $status_result = $stmt->get_result();
        $status_row = $status_result->fetch_assoc();
        
        $attended_sessions = $status_row['present'] + $status_row['late'];
        $percentage = round(($attended_sessions / $total_sessions) * 100, 2);
        
        return [
            'percentage' => $percentage,
            'present' => $status_row['present'],
            'late' => $status_row['late'],
            'absent' => $status_row['absent'],
            'excused' => $status_row['excused'],
            'total' => $total_sessions
        ];
    }

    // Calculate all performance metrics with detailed data
    $performance_stats = [
        'month' => calculatePerformance($conn, 'month'),
        'three_months' => calculatePerformance($conn, '3months'),
        'six_months' => calculatePerformance($conn, '6months'),
        'year' => calculatePerformance($conn, 'year')
    ];
}

// Function to determine performance color class
function getPerformanceClass($percentage) {
    if ($percentage >= 90) return 'excellent';
    if ($percentage >= 75) return 'good';
    if ($percentage >= 50) return 'average';
    return 'poor';
}

// Get player name for the header
$player_name = '';
$player_sql = "SELECT player_name FROM attendance WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($player_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$player_result = $stmt->get_result();
if ($player_result->num_rows > 0) {
    $player_row = $player_result->fetch_assoc();
    $player_name = $player_row['player_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_admin ? 'All' : htmlspecialchars($player_name) ?>'s Attendance Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS styles remain the same as in your original code */
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
            max-width: 1200px;
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

        .player-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #555;
        }

        .player-info strong {
            color: var(--dark-color);
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

        .filter-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 25px;
        }

        .performance-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 25px;
        }

        .performance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .performance-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .performance-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .performance-stat {
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .performance-stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .performance-stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .performance-stat-description {
            font-size: 12px;
            color: #777;
        }

        .performance-excellent {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
        }

        .performance-good {
            background-color: rgba(52, 152, 219, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .performance-average {
            background-color: rgba(241, 196, 15, 0.1);
            border-left: 4px solid var(--warning-color);
        }

        .performance-poor {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--danger-color);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
            flex-grow: 1;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .table-container {
            overflow-x: auto;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            position: sticky;
            top: 0;
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
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

        .action-links {
            display: flex;
            gap: 12px;
        }

        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 14px;
        }

        .action-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .action-link i {
            font-size: 13px;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 13px;
        }

        .file-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .empty-state p {
            color: #777;
            margin-bottom: 20px;
        }

        .footer-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .footer-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .notes-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .performance-stats {
                grid-template-columns: 1fr;
            }
        }
        .performance-stat-details {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 8px 0;
            font-size: 12px;
        }

        .performance-stat-details span {
            padding: 2px 6px;
            border-radius: 10px;
        }

        .detail-present {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
        }

        .detail-late {
            background-color: rgba(241, 196, 15, 0.15);
            color: var(--warning-color);
        }

        .detail-absent {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
        }

        .detail-excused {
            background-color: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
        }

        .performance-period {
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-clipboard-list"></i> <?= $is_admin ? 'All' : 'My' ?> Attendance Records</h1>
                <?php if (!$is_admin && !empty($player_name)): ?>
                    <div class="player-info">
                        <i class="fas fa-user"></i> <strong><?= htmlspecialchars($player_name) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
            <a href="index.php" class="btn">
                <i class="fas fa-plus"></i> New Attendance
            </a>
        </div>
        
        <?php if ($is_admin): ?>
        <!-- Performance Metrics Card - Only shown for admins -->
        <div class="performance-card">
            <div class="performance-header">
                <div class="performance-title">
                    <i class="fas fa-chart-line"></i> Overall Attendance Performance
                </div>
                <div class="performance-period">
                    Showing data from <?= date('M j, Y', strtotime('-1 year')) ?> to <?= date('M j, Y') ?>
                </div>
            </div>
            
            <div class="performance-stats">
                <div class="performance-stat performance-<?= getPerformanceClass($performance_stats['month']['percentage']) ?>">
                    <div class="performance-stat-title">Last Month</div>
                    <div class="performance-stat-value"><?= $performance_stats['month']['percentage'] ?>%</div>
                    <div class="performance-stat-details">
                        <span class="detail-present"><?= $performance_stats['month']['present'] ?> Present</span>
                        <span class="detail-late"><?= $performance_stats['month']['late'] ?> Late</span>
                        <span class="detail-absent"><?= $performance_stats['month']['absent'] ?> Absent</span>
                        <span class="detail-excused"><?= $performance_stats['month']['excused'] ?> Excused</span>
                    </div>
                    <div class="performance-stat-description">of <?= $performance_stats['month']['total'] ?> sessions</div>
                </div>
                
                <div class="performance-stat performance-<?= getPerformanceClass($performance_stats['three_months']['percentage']) ?>">
                    <div class="performance-stat-title">Last 3 Months</div>
                    <div class="performance-stat-value"><?= $performance_stats['three_months']['percentage'] ?>%</div>
                    <div class="performance-stat-details">
                        <span class="detail-present"><?= $performance_stats['three_months']['present'] ?> Present</span>
                        <span class="detail-late"><?= $performance_stats['three_months']['late'] ?> Late</span>
                        <span class="detail-absent"><?= $performance_stats['three_months']['absent'] ?> Absent</span>
                        <span class="detail-excused"><?= $performance_stats['three_months']['excused'] ?> Excused</span>
                    </div>
                    <div class="performance-stat-description">of <?= $performance_stats['three_months']['total'] ?> sessions</div>
                </div>
                
                <div class="performance-stat performance-<?= getPerformanceClass($performance_stats['six_months']['percentage']) ?>">
                    <div class="performance-stat-title">Last 6 Months</div>
                    <div class="performance-stat-value"><?= $performance_stats['six_months']['percentage'] ?>%</div>
                    <div class="performance-stat-details">
                        <span class="detail-present"><?= $performance_stats['six_months']['present'] ?> Present</span>
                        <span class="detail-late"><?= $performance_stats['six_months']['late'] ?> Late</span>
                        <span class="detail-absent"><?= $performance_stats['six_months']['absent'] ?> Absent</span>
                        <span class="detail-excused"><?= $performance_stats['six_months']['excused'] ?> Excused</span>
                    </div>
                    <div class="performance-stat-description">of <?= $performance_stats['six_months']['total'] ?> sessions</div>
                </div>
                
                <div class="performance-stat performance-<?= getPerformanceClass($performance_stats['year']['percentage']) ?>">
                    <div class="performance-stat-title">Last Year</div>
                    <div class="performance-stat-value"><?= $performance_stats['year']['percentage'] ?>%</div>
                    <div class="performance-stat-details">
                        <span class="detail-present"><?= $performance_stats['year']['present'] ?> Present</span>
                        <span class="detail-late"><?= $performance_stats['year']['late'] ?> Late</span>
                        <span class="detail-absent"><?= $performance_stats['year']['absent'] ?> Absent</span>
                        <span class="detail-excused"><?= $performance_stats['year']['excused'] ?> Excused</span>
                    </div>
                    <div class="performance-stat-description">of <?= $performance_stats['year']['total'] ?> sessions</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="filter-card">
            <form method="get" class="filter-form">
                <?php if ($is_admin): ?>
                <div class="form-group">
                    <label for="player_name">Player Name</label>
                    <input type="text" id="player_name" name="player_name" class="form-control" 
                           value="<?= htmlspecialchars($_GET['player_name'] ?? '') ?>" placeholder="Search by player name">
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="from_date">From Date</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" 
                           value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="to_date">To Date</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" 
                           value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="Present" <?= (isset($_GET['status']) && $_GET['status'] === 'Present') ? 'selected' : '' ?>>Present</option>
                        <option value="Late" <?= (isset($_GET['status']) && $_GET['status'] === 'Late') ? 'selected' : '' ?>>Late</option>
                        <option value="Absent" <?= (isset($_GET['status']) && $_GET['status'] === 'Absent') ? 'selected' : '' ?>>Absent</option>
                        <option value="Excused" <?= (isset($_GET['status']) && $_GET['status'] === 'Excused') ? 'selected' : '' ?>>Excused</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <a href="view_attendance.php" class="btn" style="background-color: #6c757d;">
                    <i class="fas fa-sync-alt"></i> Reset
                </a>
            </form>
        </div>
        
        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <?php if ($is_admin): ?>
                            <th>Recorded By</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Player</th>
                            <th>Reg No.</th>
                            <th>Category</th>
                            <th>Sessions</th>
                            <th>Status</th>
                            <th>Arrival Time</th>
                            <th>Notes</th>
                            <th>File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <?php if ($is_admin): ?>
                            <td><?= htmlspecialchars($row['username'] ?? 'N/A') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['player_name']) ?></td>
                            <td><?= htmlspecialchars($row['reg_number']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['session_type']) ?></td>
                            <td>
                                <span class="status status-<?= strtolower($row['attendance_status']) ?>">
                                    <?= htmlspecialchars($row['attendance_status']) ?>
                                </span>
                            </td>
                            <td><?= !empty($row['arriving_time']) ? htmlspecialchars($row['arriving_time']) : 'N/A' ?></td>
                            <td class="notes-preview" title="<?= htmlspecialchars($row['notes']) ?>">
                                <?= htmlspecialchars(substr($row['notes'], 0, 30)) ?>
                                <?= strlen($row['notes']) > 30 ? '...' : '' ?>
                            </td>
                            <td>
                                <?php if (!empty($row['file_path'])): ?>
                                    <a href="<?= htmlspecialchars($row['file_path']) ?>" class="file-link" target="_blank">
                                        <i class="fas fa-paperclip"></i> View
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-links">
                                    <a href="view_attendance.php?id=<?= $row['id'] ?>" class="action-link">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit_attendance.php?id=<?= $row['id'] ?>" class="action-link">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <h3>No Attendance Records Found</h3>
                    <p><?= $is_admin ? 'No records match your filters' : 'You haven\'t submitted any attendance records yet.' ?></p>
                    <a href="index.php" class="btn">
                        <i class="fas fa-plus"></i> Submit New Record
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer-links">
            <a href="index.php" class="footer-link">
                <i class="fas fa-arrow-left"></i> Back to Attendance Form
            </a>
            <a href="logout.php" class="footer-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>