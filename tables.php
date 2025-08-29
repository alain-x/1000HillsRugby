<?php
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: same-origin');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');
define('UPLOAD_DIR', __DIR__ . '/uploads/team_logos/');
define('DEFAULT_LOGO', 'default_team.png');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error_' . date('Y-m-d') . '.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Ensure upload directory exists and is writable
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        error_log('Failed to create upload directory: ' . UPLOAD_DIR);
        die('System configuration error. Please contact administrator.');
    }
}

// Security functions
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateInt($value, $min = null, $max = null) {
    $options = [
        'options' => [
            'default' => 0,
            'min_range' => $min,
            'max_range' => $max
        ]
    ];
    return filter_var($value, FILTER_VALIDATE_INT, $options);
}

// Handle AJAX requests
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Initialize response array
$response = [
    'status' => 'success',
    'message' => '',
    'data' => []
];

// Initialize variables
$gender_id = isset($_GET['gender_id']) && is_numeric($_GET['gender_id']) ? (int)$_GET['gender_id'] : 1;
$competition_id = isset($_GET['competition_id']) && is_numeric($_GET['competition_id']) ? (int)$_GET['competition_id'] : 1;
$season_id = isset($_GET['season_id']) && is_numeric($_GET['season_id']) ? (int)$_GET['season_id'] : null;
$standings = [];
$competitions = [];
$seasons = [];
$genders = [];
$error = null;

try {
    // Create database connection with error handling
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper encoding
    if (!$conn->set_charset('utf8mb4')) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }

    // Enable exceptions for mysqli errors
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Check if required tables exist using prepared statement
    $required_tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
    $table_check = $conn->prepare("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    
    foreach ($required_tables as $table) {
        $table_check->bind_param('ss', DB_NAME, $table);
        $table_check->execute();
        $result = $table_check->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            throw new Exception("Required table '$table' doesn't exist. Please run setup_database.php first.");
        }
    }
    $table_check->close();
    
    // Start transaction for data consistency
    $conn->begin_transaction();

    // Get available competitions with prepared statement
    $compQuery = $conn->prepare("SELECT id, name, short_name FROM competitions WHERE is_active = TRUE ORDER BY name");
    if ($compQuery === false) {
        throw new Exception("Failed to prepare competitions query: " . $conn->error);
    }
    
    if (!$compQuery->execute()) {
        throw new Exception("Failed to fetch competitions: " . $compQuery->error);
    }
    
    $compResult = $compQuery->get_result();
    while ($row = $compResult->fetch_assoc()) {
        $competitions[] = [
            'id' => (int)$row['id'],
            'name' => htmlspecialchars($row['name']),
            'short_name' => !empty($row['short_name']) ? htmlspecialchars($row['short_name']) : ''
        ];
    }
    $compQuery->close();

    // Get available seasons with prepared statement
    $seasonQuery = $conn->prepare("SELECT id, year, is_current FROM seasons ORDER BY year DESC");
    if ($seasonQuery === false) {
        throw new Exception("Failed to prepare seasons query: " . $conn->error);
    }
    
    if (!$seasonQuery->execute()) {
        throw new Exception("Failed to fetch seasons: " . $seasonQuery->error);
    }
    
    $seasonResult = $seasonQuery->get_result();
    $current_season_id = null;
    
    while ($row = $seasonResult->fetch_assoc()) {
        $season = [
            'id' => (int)$row['id'],
            'year' => (int)$row['year'],
            'is_current' => (bool)$row['is_current']
        ];
        
        $seasons[] = $season;
        
        // Track current season
        if ($season['is_current'] && $current_season_id === null) {
            $current_season_id = $season['id'];
        }
    }
    
    // Set default season to current if not specified
    if ($season_id === null) {
        $season_id = $current_season_id ?? ($seasons[0]['id'] ?? null);
    }
    
    $seasonQuery->close();

    // Get available genders with prepared statement
    $genderQuery = $conn->prepare("SELECT id, name, code FROM genders WHERE is_active = TRUE ORDER BY sort_order, name");
    if ($genderQuery === false) {
        throw new Exception("Failed to prepare genders query: " . $conn->error);
    }
    
    if (!$genderQuery->execute()) {
        throw new Exception("Failed to fetch genders: " . $genderQuery->error);
    }
    
    $genderResult = $genderQuery->get_result();
    $default_gender_id = null;
    
    while ($row = $genderResult->fetch_assoc()) {
        $gender = [
            'id' => (int)$row['id'],
            'name' => htmlspecialchars($row['name']),
            'code' => !empty($row['code']) ? strtoupper(trim($row['code'])) : ''
        ];
        
        $genders[] = $gender;
        
        // Track first gender as default
        if ($default_gender_id === null) {
            $default_gender_id = $gender['id'];
        }
    }
    
    $genderQuery->close();
    
    // Validate gender_id
    $valid_gender_ids = array_column($genders, 'id');
    if (!in_array($gender_id, $valid_gender_ids)) {
        $gender_id = $default_gender_id ?? 1; // Fallback to 1 if no default set
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 20; // Teams per page
    $offset = ($page - 1) * $per_page;
    
    // Get total teams for pagination
    $countQuery = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM league_standings ls
        JOIN teams t ON ls.team_id = t.id
        WHERE ls.competition_id = ? AND ls.season_id = ? AND ls.gender_id = ?
    ");
    
    if ($countQuery === false) {
        throw new Exception("Count query prepare failed: " . $conn->error);
    }
    
    $countQuery->bind_param("iii", $competition_id, $season_id, $gender_id);
    if (!$countQuery->execute()) {
        throw new Exception("Count query execute failed: " . $countQuery->error);
    }
    
    $total_teams = $countQuery->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_teams / $per_page);
    $countQuery->close();
    
    // Get standings data with prepared statement and pagination
    $query = $conn->prepare("
        SELECT 
            ls.*, 
            t.id as team_id,
            t.name as team_name, 
            t.short_name as team_short_name,
            t.logo as team_logo,
            t.founded_year,
            t.home_ground,
            t.website
        FROM league_standings ls
        JOIN teams t ON ls.team_id = t.id
        WHERE ls.competition_id = ? 
          AND ls.season_id = ? 
          AND ls.gender_id = ?
        ORDER BY 
            ls.league_points DESC, 
            ls.points_difference DESC, 
            ls.tries_for DESC, 
            t.name ASC
        LIMIT ? OFFSET ?
    ");
    
    if ($query === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $query->bind_param("iiiii", $competition_id, $season_id, $gender_id, $per_page, $offset);
    if (!$query->execute()) {
        throw new Exception("Execute failed: " . $query->error);
    }

    $result = $query->get_result();
    $position = $offset + 1;
    $previous_points = null;
    $previous_pd = null;
    $previous_tries = null;
    $position_to_display = $position;
    
    // Get team form data (last 5 matches)
    $formQuery = $conn->prepare("
        SELECT 
            m.id,
            m.home_team_id,
            m.away_team_id,
            m.home_score,
            m.away_score,
            m.match_date,
            ht.name as home_team_name,
            at.name as away_team_name
        FROM matches m
        JOIN teams ht ON m.home_team_id = ht.id
        JOIN teams at ON m.away_team_id = at.id
        WHERE (m.home_team_id IN (SELECT team_id FROM league_standings WHERE competition_id = ? AND season_id = ? AND gender_id = ?) 
               OR m.away_team_id IN (SELECT team_id FROM league_standings WHERE competition_id = ? AND season_id = ? AND gender_id = ?))
          AND m.match_date <= NOW()
          AND m.status = 'completed'
        ORDER BY m.match_date DESC
        LIMIT 100
    ");
    
    $formData = [];
    if ($formQuery) {
        $formQuery->bind_param("iiiiii", $competition_id, $season_id, $gender_id, $competition_id, $season_id, $gender_id);
        if ($formQuery->execute()) {
            $formResult = $formQuery->get_result();
            while ($match = $formResult->fetch_assoc()) {
                $formData[$match['home_team_id']][] = $match;
                $formData[$match['away_team_id']][] = $match;
            }
        }
        $formQuery->close();
    }

    // Process standings data
    while ($row = $result->fetch_assoc()) {
        $team_id = (int)$row['team_id'];
        $team_name = htmlspecialchars($row['team_name']);
        $team_short_name = !empty($row['team_short_name']) ? htmlspecialchars($row['team_short_name']) : '';
        $first_letter = strtoupper(substr($team_name, 0, 1));
        
        $current_points = (int)$row['league_points'];
        $current_pd = (int)$row['points_difference'];
        $current_tries = (int)$row['tries_for'];
        
        // Calculate position
        if ($previous_points !== null && 
            ($current_points != $previous_points || 
             $current_pd != $previous_pd || 
             $current_tries != $previous_tries)) {
            $position_to_display = $position;
        }
        
        // Process team form (last 5 matches)
        $team_form = [];
        $form_string = '';
        
        if (isset($formData[$team_id])) {
            $team_matches = array_slice($formData[$team_id], 0, 5);
            
            foreach ($team_matches as $match) {
                if ($match['home_team_id'] == $team_id) {
                    // Home match
                    if ($match['home_score'] > $match['away_score']) {
                        $team_form[] = 'W';
                        $form_string .= '<span class="form-indicator form-win" title="' . 
                                      htmlspecialchars($match['away_team_name']) . ' (H) ' . $match['home_score'] . '-' . $match['away_score'] . '">W</span>';
                    } elseif ($match['home_score'] < $match['away_score']) {
                        $team_form[] = 'L';
                        $form_string .= '<span class="form-indicator form-loss" title="' . 
                                      htmlspecialchars($match['away_team_name']) . ' (H) ' . $match['home_score'] . '-' . $match['away_score'] . '">L</span>';
                    } else {
                        $team_form[] = 'D';
                        $form_string .= '<span class="form-indicator form-draw" title="' . 
                                      htmlspecialchars($match['away_team_name']) . ' (H) ' . $match['home_score'] . '-' . $match['away_score'] . '">D</span>';
                    }
                } else {
                    // Away match
                    if ($match['away_score'] > $match['home_score']) {
                        $team_form[] = 'W';
                        $form_string .= '<span class="form-indicator form-win" title="' . 
                                      htmlspecialchars($match['home_team_name']) . ' (A) ' . $match['away_score'] . '-' . $match['home_score'] . '">W</span>';
                    } elseif ($match['away_score'] < $match['home_score']) {
                        $team_form[] = 'L';
                        $form_string .= '<span class="form-indicator form-loss" title="' . 
                                      htmlspecialchars($match['home_team_name']) . ' (A) ' . $match['away_score'] . '-' . $match['home_score'] . '">L</span>';
                    } else {
                        $team_form[] = 'D';
                        $form_string .= '<span class="form-indicator form-draw" title="' . 
                                      htmlspecialchars($match['home_team_name']) . ' (A) ' . $match['away_score'] . '-' . $match['home_score'] . '">D</span>';
                    }
                }
            }
        }
        
        // Calculate form points (last 5 matches: W=3, D=1, L=0)
        $form_points = 0;
        foreach ($team_form as $result) {
            if ($result === 'W') $form_points += 3;
            elseif ($result === 'D') $form_points += 1;
        }
        
        // Add team to standings
        $standings[] = [
            'position' => $position_to_display,
            'team_id' => $team_id,
            'team_name' => $team_name,
            'team_short_name' => $team_short_name,
            'first_letter' => $first_letter,
            'matches_played' => (int)$row['matches_played'],
            'matches_won' => (int)$row['matches_won'],
            'matches_drawn' => (int)$row['matches_drawn'],
            'matches_lost' => (int)$row['matches_lost'],
            'tries_for' => (int)$row['tries_for'],
            'tries_against' => (int)$row['tries_against'],
            'points_for' => (int)$row['points_for'],
            'points_against' => (int)$row['points_against'],
            'points_difference' => (int)$row['points_difference'],
            'league_points' => $current_points,
            'form_score' => (float)$row['form_score'],
            'form_points' => $form_points,
            'form_string' => $form_string,
            'team_logo' => $row['team_logo'],
            'founded_year' => !empty($row['founded_year']) ? (int)$row['founded_year'] : null,
            'home_ground' => !empty($row['home_ground']) ? htmlspecialchars($row['home_ground']) : '',
            'website' => !empty($row['website']) ? filter_var($row['website'], FILTER_SANITIZE_URL) : ''
        ];
        
        $previous_points = $current_points;
        $previous_pd = $current_pd;
        $previous_tries = $current_tries;
        $position++;
    }
    
    // Commit transaction if we got this far
    $conn->commit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred while processing your request.',
            'debug' => DEBUG_MODE ? $e->getMessage() : null
        ]);
        exit;
    } else {
        $error = "An error occurred while fetching league data. " . 
                (DEBUG_MODE ? $e->getMessage() : 'Please try again later.');
    }
} finally {
    // Close database connection if it exists
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View the latest league standings, team statistics, and match results for 1000 Hills Rugby.">
    <title>League Standings | 1000 Hills Rugby</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    
    <!-- CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/images/1000-hills-logo.png">
    <style>
        :root {
            --primary-color: #1a5f7a;
            --secondary-color: #1597bb;
            --accent-color: #ffd700;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --dark-bg: #1e293b;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #1f2937;
        }
        
        .team-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 12px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .team-logo:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .team-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            margin-right: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .team-initial:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .form-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            margin-right: 4px;
            color: white;
            font-size: 10px;
            font-weight: bold;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        
        .form-indicator:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .form-win { 
            background: linear-gradient(135deg, var(--success-color), #059669);
        }
        
        .form-draw { 
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }
        
        .form-loss { 
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .position-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .position-1 { background: linear-gradient(135deg, #fbbf24, #d97706); }
        .position-2 { background: linear-gradient(135deg, #9ca3af, #6b7280); }
        .position-3 { background: linear-gradient(135deg, #cd7f32, #b45309); }
        .position-4-6 { background: linear-gradient(135deg, #10b981, #059669); }
        .position-other { background: linear-gradient(135deg, #e5e7eb, #d1d5db); color: #4b5563; }
        
        .same-position {
            background-color: var(--light-bg);
        }
        
        .promotion-row {
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.03));
            border-left: 4px solid var(--success-color);
        }
        
        .relegation-row {
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.08), rgba(239, 68, 68, 0.03));
            border-left: 4px solid var(--danger-color);
        }
        
        .championship-row {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.03));
            border-left: 4px solid var(--warning-color);
        }
        /* Navigation styles */
        .nav-container {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #065f46 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .nav-item {
            position: relative;
            color: white;
            transition: all 0.3s ease;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .nav-item:hover {
            color: #d1fae5;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }
        .nav-item.active {
            color: white;
            background-color: rgba(52, 211, 153, 0.2);
        }
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 3px;
            background: linear-gradient(90deg, #34d399, #10b981);
            border-radius: 3px;
        }
        .mobile-nav {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #065f46 100%);
        }
        .mobile-nav-item {
            color: white;
            transition: all 0.2s ease;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .mobile-nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .mobile-nav-item.active {
            background-color: rgba(52, 211, 153, 0.2);
            color: white;
        }
        /* Table styles */
        .table-header {
            background: linear-gradient(135deg, #065f46 0%, #047857 100%);
            color: white;
        }
        .table-header th {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .filter-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .filter-select {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .filter-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .update-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }
        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }
        .position-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 12px;
        }
        .position-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; }
        .position-2 { background: linear-gradient(135deg, #9ca3af, #6b7280); color: white; }
        .position-3 { background: linear-gradient(135deg, #cd7f32, #b45309); color: white; }
        .position-4 { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .position-other { background: linear-gradient(135deg, #e5e7eb, #d1d5db); color: #374151; }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 12px;
            }
            .team-logo, .team-initial {
                width: 25px;
                height: 25px;
                margin-right: 6px;
            }
            .form-indicator {
                width: 8px;
                height: 8px;
                margin-right: 1px;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Professional Header -->
    <header class="nav-container">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="./" class="flex items-center">
                        <img src="./logos_/logoT.jpg" alt="Club Logo" class="h-12 rounded-full border-2 border-white shadow-md">
                        <span class="ml-3 text-xl font-bold text-white">1000 Hills Rugby</span>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-2">
                    <a href="/" class="nav-item font-medium text-sm uppercase tracking-wider">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                    <a href="fixtures?tab=fixtures" class="nav-item font-medium text-sm uppercase tracking-wider">
                        <i class="fas fa-calendar-alt mr-2"></i>Fixtures
                    </a>
                    <a href="fixtures?tab=results" class="nav-item font-medium text-sm uppercase tracking-wider">
                        <i class="fas fa-list-ol mr-2"></i>Results
                    </a>
                    <a href="tables.php" class="nav-item active font-medium text-sm uppercase tracking-wider">
                        <i class="fas fa-table mr-2"></i>League Tables
                    </a> 
                </nav>
                
                <button id="mobile-menu-button" class="md:hidden text-white focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden mobile-nav py-2 px-4 shadow-lg">
            <a href="/" class="block mobile-nav-item rounded-md">
                <i class="fas fa-home mr-3"></i>Home
            </a>
            <a href="fixtures?tab=fixtures" class="block mobile-nav-item rounded-md">
                <i class="fas fa-calendar-alt mr-3"></i>Fixtures
            </a>
            <a href="fixtures?tab=results" class="block mobile-nav-item rounded-md">
                <i class="fas fa-list-ol mr-3"></i>Results
            </a>
            <a href="tables.php" class="block mobile-nav-item active rounded-md">
                <i class="fas fa-table mr-3"></i>League Tables
            </a> 
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Filters -->
            <div class="p-6 filter-card">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-filter mr-3 text-green-600"></i>
                    League Table Filters
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label for="competitionFilter" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-trophy mr-1 text-green-600"></i>Competition
                        </label>
                        <select id="competitionFilter" class="w-full p-3 text-sm filter-select focus:outline-none">
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?= $comp['id'] ?>" <?= ($competition_id == $comp['id']) ? 'selected' : '' ?>>
                                    <?= $comp['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="seasonFilter" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1 text-green-600"></i>Season
                        </label>
                        <select id="seasonFilter" class="w-full p-3 text-sm filter-select focus:outline-none">
                            <?php foreach ($seasons as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($season_id == $s['id']) ? 'selected' : '' ?>>
                                    <?= $s['year'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="genderFilter" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-users mr-1 text-green-600"></i>Gender
                        </label>
                        <select id="genderFilter" class="w-full p-3 text-sm filter-select focus:outline-none">
                            <?php foreach ($genders as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= ($gender_id == $g['id']) ? 'selected' : '' ?>>
                                    <?= $g['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button id="applyFilters" class="w-full update-btn text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                            <i class="fas fa-sync-alt mr-2"></i>Update Table
                        </button>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden mobile-nav py-2 px-4 shadow-lg">
                <a href="/" class="block mobile-nav-item rounded-md">
                    <i class="fas fa-home mr-3"></i>Home
                </a>
                <a href="fixtures?tab=fixtures" class="block mobile-nav-item rounded-md">
                    <i class="fas fa-calendar-alt mr-3"></i>Fixtures
                </a>
                <a href="fixtures?tab=results" class="block mobile-nav-item rounded-md">
                    <i class="fas fa-list-ol mr-3"></i>Results
                </a>
                <a href="tables.php" class="block mobile-nav-item active rounded-md">
                    <i class="fas fa-table mr-3"></i>League Tables
                </a> 
            </div>
        </header>

        <main class="container mx-auto px-4 py-6">
            <?php if (isset($error)): ?>
                <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Filters -->
                <div class="p-6 filter-card">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-filter mr-3 text-green-600"></i>
                        League Table Filters
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label for="competitionFilter" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-trophy mr-1 text-green-600"></i>Competition
                            </label>
                            <select id="competitionFilter" class="w-full p-3 text-sm filter-select focus:outline-none">
                                <?php foreach ($competitions as $comp): ?>
                                    <option value="<?= $comp['id'] ?>" <?= ($competition_id == $comp['id']) ? 'selected' : '' ?>>
                                        <?= $comp['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="seasonFilter" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-1 text-green-600"></i>Season
                            </label>
                            <select id="seasonFilter" class="w-full p-3 text-sm filter-select focus:outline-none">
                                <?php foreach ($seasons as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($season_id == $s['id']) ? 'selected' : '' ?>>
                                        <?= $s['year'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="genderFilter" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-users mr-1 text-green-600"></i>Gender
                            </label>
                            <select id="genderFilter" class="w-full p-3 text-sm filter-select focus:outline-none">
                                <?php foreach ($genders as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= ($gender_id == $g['id']) ? 'selected' : '' ?>>
                                        <?= $g['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button id="applyFilters" class="w-full update-btn text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                                <i class="fas fa-sync-alt mr-2"></i>Update Table
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Standings Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-green-800 to-green-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">#</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Team</th>
                                <th scope="col" class="px-2 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider" title="Matches Played">P</th>
                                <th scope="col" class="px-2 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider" title="Won">W</th>
                                <th scope="col" class="px-2 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider" title="Drawn">D</th>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Standings Available</h3>
                                    <p class="text-gray-600 mb-6">There are currently no standings available for the selected filters. Please try adjusting your search criteria.</p>
                                    
                                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                        <button onclick="window.location.href='tables.php'" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center">
                                            <i class="fas fa-redo mr-2"></i> Reset Filters
                                        </button>
                                        <a href="uploadtables.php" class="px-5 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-colors flex items-center justify-center">
                                            <i class="fas fa-upload mr-2"></i> Upload Standings
                                        </a>
                                    </div>
                                    
                                    <div class="mt-8 pt-6 border-t border-gray-200">
                                        <h4 class="text-sm font-medium text-gray-500 mb-3">NEED HELP?</h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <a href="#" class="group flex items-center text-sm text-gray-600 hover:text-green-600 transition-colors">
                                                <i class="fas fa-question-circle mr-2 text-gray-400 group-hover:text-green-500"></i>
                                                Help Center
                                            </a>
                                            <a href="#" class="group flex items-center text-sm text-gray-600 hover:text-green-600 transition-colors">
                                                <i class="fas fa-envelope mr-2 text-gray-400 group-hover:text-green-500"></i>
                                                Contact Support
                                            </a>
                                            <a href="#" class="group flex items-center text-sm text-gray-600 hover:text-green-600 transition-colors">
                                                <i class="fas fa-bug mr-2 text-gray-400 group-hover:text-green-500"></i>
                                                Report an Issue
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $previous_position = null;
                        foreach ($standings as $team): 
                            $is_same_position = ($previous_position !== null && $team['position'] == $previous_position);
                            $is_promotion = $team['position'] <= 4;
                            $is_relegation = $team['position'] >= (count($standings) - 2);
                            $is_championship = $team['position'] == 1;
                            
                            // Determine position badge class
                            $position_class = 'position-other';
                            if ($team['position'] == 1) $position_class = 'position-1';
                            elseif ($team['position'] == 2) $position_class = 'position-2';
                            elseif ($team['position'] == 3) $position_class = 'position-3';
                            elseif ($team['position'] == 4) $position_class = 'position-4';
                        ?>
                            <tr class="group <?= $is_championship ? 'championship-row' : ($is_promotion ? 'promotion-row' : ($is_relegation ? 'relegation-row' : ($is_same_position ? 'same-position' : 'hover:bg-gray-50'))) ?> transition-all duration-200 border-b border-gray-100 last:border-0">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="position-badge <?= $position_class ?> transform group-hover:scale-110 transition-transform duration-200 shadow-sm">
                                        <?= $team['position'] ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center group-hover:pl-1 transition-all duration-200">
                                        <?php if (!empty($team['team_logo']) && file_exists(UPLOAD_DIR . 'team_logos/' . $team['team_logo'])): ?>
                                            <div class="relative">
                                                <img src="<?= UPLOAD_DIR . 'team_logos/' . $team['team_logo'] ?>" 
                                                     alt="<?= htmlspecialchars($team['team_name']) ?>" 
                                                     class="team-logo group-hover:ring-2 group-hover:ring-green-200 group-hover:scale-110 transition-all duration-200"
                                                     onerror="this.onerror=null; this.src='images/default-team-logo.png'"
                                                >
                                                <?php if ($is_championship): ?>
                                                    <div class="absolute -top-1 -right-1 bg-yellow-400 text-yellow-800 text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                                        <i class="fas fa-trophy"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="team-initial group-hover:shadow-md group-hover:scale-110 transition-all duration-200 flex-shrink-0">
                                                <?= mb_substr(trim($team['team_name']), 0, 1) ?: 'T' ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ml-3">
                                            <div class="text-sm font-semibold text-gray-900 group-hover:text-green-700 transition-colors">
                                                <?= htmlspecialchars($team['team_name']) ?>
                                                <?php if ($is_championship): ?>
                                                    <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                                                        <i class="fas fa-crown mr-1"></i>Champions
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($team['team_short_name'])): ?>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($team['team_short_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-gray-700 bg-gray-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all">
                                        <?= (int)$team['matches_played'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-semibold text-green-700 bg-green-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all">
                                        <?= (int)$team['matches_won'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-semibold text-yellow-600 bg-yellow-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all">
                                        <?= (int)$team['matches_drawn'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-semibold text-red-600 bg-red-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all">
                                        <?= (int)$team['matches_lost'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-blue-700 bg-blue-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all" title="Tries For">
                                        <?= (int)$team['tries_for'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-blue-700 bg-blue-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all" title="Tries Against">
                                        <?= (int)$team['tries_against'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-gray-700 bg-gray-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all" title="Points For">
                                        <?= (int)$team['points_for'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-gray-700 bg-gray-50 rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all" title="Points Against">
                                        <?= (int)$team['points_against'] ?: '-' ?>
                                    </div>
                                </td>
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-semibold <?= $team['points_difference'] > 0 ? 'text-green-600 bg-green-50' : ($team['points_difference'] < 0 ? 'text-red-600 bg-red-50' : 'text-gray-600 bg-gray-50') ?> rounded-md py-1 px-2 group-hover:bg-white group-hover:shadow-sm transition-all" title="Points Difference">
                                        <?= $team['points_difference'] > 0 ? '+' : '' ?><?= (int)$team['points_difference'] ?: '0' ?>
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    <div class="text-sm font-bold text-gray-900 bg-gray-100 rounded-md py-1 px-3 group-hover:bg-white group-hover:shadow-md transition-all transform group-hover:-translate-y-0.5" title="League Points">
                                        <?= (int)$team['league_points'] ?: '0' ?>
                                    </div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                    <?php 
                                        // Simulate form indicators (would normally come from database)
                                        $form = ['W', 'W', 'D', 'L', 'W'];
                                        foreach ($form as $result): 
                                    ?>
                                        <span class="form-indicator form-<?= strtolower($result) ?>" title="<?= $result ?>"></span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <?php $previous_position = $team['position']; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
                            foreach ($standings as $team): 
                                $is_same_position = ($previous_position !== null && $team['position'] == $previous_position);
                                $is_promotion = $team['position'] <= 4;
                                $is_relegation = $team['position'] >= (count($standings) - 2);
                                $is_championship = $team['position'] == 1;
                                
                                // Determine position badge class
                                $position_class = 'position-other';
                                if ($team['position'] == 1) $position_class = 'position-1';
                                elseif ($team['position'] == 2) $position_class = 'position-2';
                                elseif ($team['position'] == 3) $position_class = 'position-3';
                                elseif ($team['position'] == 4) $position_class = 'position-4';
                            ?>
                                <tr class="<?= $is_championship ? 'championship-row' : ($is_promotion ? 'promotion-row' : ($is_relegation ? 'relegation-row' : ($is_same_position ? 'same-position' : ''))) ?> hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="position-badge <?= $position_class ?>">
                                            <?= $team['position'] ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($team['team_logo']) && file_exists(LOGO_DIR . $team['team_logo'])): ?>
                                                <img src="<?= LOGO_DIR . $team['team_logo'] ?>" alt="<?= $team['team_name'] ?>" class="team-logo">
                                            <?php else: ?>
                                                <div class="team-initial"><?= $team['first_letter'] ?></div>
                                            <?php endif; ?>
                                            <span class="text-sm font-semibold text-gray-900"><?= $team['team_name'] ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-600 font-medium"><?= $team['matches_played'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-green-600 font-semibold"><?= $team['matches_won'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-yellow-600 font-semibold"><?= $team['matches_drawn'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-red-600 font-semibold"><?= $team['matches_lost'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-600"><?= $team['tries_for'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-600"><?= $team['tries_against'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-600"><?= $team['points_for'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-600"><?= $team['points_against'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-semibold <?= $team['points_difference'] > 0 ? 'text-green-600' : ($team['points_difference'] < 0 ? 'text-red-600' : 'text-gray-600') ?>">
                                        <?= $team['points_difference'] > 0 ? '+' : '' ?><?= $team['points_difference'] ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900"><?= $team['league_points'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                        <?php 
                                            // Simulate form indicators (would normally come from database)
                                            $form = ['W', 'W', 'D', 'L', 'W'];
                                            foreach ($form as $result): 
                                        ?>
                                            <span class="form-indicator form-<?= strtolower($result) ?>" title="<?= $result ?>"></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <?php $previous_position = $team['position']; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Table Legend -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-gradient-to-r from-yellow-400 to-yellow-500 rounded mr-2"></div>
                        <span class="text-gray-700">Championship Position</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-gradient-to-r from-green-400 to-green-500 rounded mr-2"></div>
                        <span class="text-gray-700">Promotion Zone</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-gradient-to-r from-red-400 to-red-500 rounded mr-2"></div>
                        <span class="text-gray-700">Relegation Zone</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Filter handling with enhanced UX
        document.getElementById('applyFilters').addEventListener('click', function() {
            const competition_id = document.getElementById('competitionFilter').value;
            const season_id = document.getElementById('seasonFilter').value;
            const gender_id = document.getElementById('genderFilter').value;
            
            // Add loading state
            const button = this;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            button.disabled = true;
            
            // Add page overlay
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            overlay.innerHTML = '<div class="bg-white p-6 rounded-lg shadow-xl"><div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-green-500 mx-auto"></div><p class="mt-4 text-center text-gray-700">Updating league table...</p></div>';
            document.body.appendChild(overlay);
            
            // Navigate to new URL with filters
            setTimeout(() => {
                window.location.href = `tables.php?competition_id=${competition_id}&season_id=${season_id}&gender_id=${gender_id}`;
            }, 500);
        });
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
        
        // Add smooth scrolling and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate table rows on load
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>