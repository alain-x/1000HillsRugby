<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');
// Paths for team logos
define('LOGO_DIR', 'logos_/'); // Web path for use in <img src>
define('LOGO_FS_DIR', __DIR__ . '/logos_/'); // Filesystem path for existence checks
define('DEFAULT_LOGO', 'default.png');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr in $errfile on line $errline");
    if (ini_get('display_errors')) {
        echo "<div class='p-4 mb-4 text-red-800 bg-red-100 rounded-lg'>
                <strong>Error:</strong> [$errno] $errstr in $errfile on line $errline
              </div>";
    }
    return true;
});

// Enable exception handling
set_exception_handler(function($e) {
    error_log("Uncaught exception: " . $e->getMessage());
    if (ini_get('display_errors')) {
        echo "<div class='p-4 mb-4 text-red-800 bg-red-100 rounded-lg'>
                <strong>Uncaught exception:</strong> " . htmlspecialchars($e->getMessage()) . "
                <pre class='mt-2 text-xs overflow-auto'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
              </div>";
    }
});

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
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Check if required tables exist
    $required_tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            throw new Exception("Required table '$table' doesn't exist. Please run setup_database.php first.");
        }
    }

    // Get available competitions (only non-deleted ones)
    $compResult = $conn->query("SELECT id, name FROM competitions WHERE is_active = TRUE AND is_deleted = 0 ORDER BY name");
    if ($compResult) {
        while ($row = $compResult->fetch_assoc()) {
            $competitions[] = [
                'id' => (int)$row['id'],
                'name' => htmlspecialchars($row['name'])
            ];
        }
        $compResult->free();
        // Validate competition_id and set a sensible default if invalid
        if (!empty($competitions)) {
            $valid_comp_ids = array_column($competitions, 'id');
            if (!in_array($competition_id, $valid_comp_ids, true)) {
                $competition_id = $competitions[0]['id'];
            }
        }
    }
    // Fallback: if no active competitions found, derive from existing league standings
    if (empty($competitions)) {
        $compResult = $conn->query("SELECT DISTINCT c.id, c.name FROM league_standings ls JOIN competitions c ON ls.competition_id = c.id ORDER BY c.name");
        if ($compResult) {
            while ($row = $compResult->fetch_assoc()) {
                $competitions[] = [
                    'id' => (int)$row['id'],
                    'name' => htmlspecialchars($row['name'])
                ];
            }
            $compResult->free();
            if (!empty($competitions)) {
                $competition_id = $competitions[0]['id'];
            }
        }
    }

    // Get available seasons (only non-deleted ones)
    $seasonQuery = "SELECT id, year FROM seasons WHERE is_deleted = 0 ORDER BY year DESC";
    $seasonResult = $conn->query($seasonQuery);
    if ($seasonResult) {
        while ($row = $seasonResult->fetch_assoc()) {
            $seasons[] = [
                'id' => (int)$row['id'],
                'year' => htmlspecialchars($row['year'])
            ];
        }
        $seasonResult->free();
        
        // Set default season to current if not specified or invalid
        if (!empty($seasons)) {
            $valid_season_ids = array_column($seasons, 'id');
            if ($season_id === null || !in_array($season_id, $valid_season_ids, true)) {
                $season_id = $seasons[0]['id'];
            }
        }
    }
    // Fallback: if no seasons found, derive from existing league standings
    if (empty($seasons)) {
        $seasonResult = $conn->query("SELECT DISTINCT s.id, s.year FROM league_standings ls JOIN seasons s ON ls.season_id = s.id ORDER BY s.year DESC");
        if ($seasonResult) {
            while ($row = $seasonResult->fetch_assoc()) {
                $seasons[] = [
                    'id' => (int)$row['id'],
                    'year' => htmlspecialchars($row['year'])
                ];
            }
            $seasonResult->free();
            if (!empty($seasons)) {
                $season_id = $seasons[0]['id'];
            }
        }
    }

    // Get available genders (only non-deleted ones)
    $genderResult = $conn->query("SELECT id, name FROM genders WHERE is_deleted = 0 ORDER BY id");
    if ($genderResult && $genderResult->num_rows > 0) {
        $genders = [];
        while ($row = $genderResult->fetch_assoc()) {
            $genders[] = [
                'id' => (int)$row['id'],
                'name' => htmlspecialchars($row['name'])
            ];
        }
        
        // Validate gender_id
        $valid_gender_ids = array_column($genders, 'id');
        if (!in_array($gender_id, $valid_gender_ids)) {
            $gender_id = $valid_gender_ids[0];
        }
    }
    // Fallback: if genders table is empty, derive from existing league standings
    if (empty($genders)) {
        $genderResult = $conn->query("SELECT DISTINCT g.id, g.name FROM league_standings ls JOIN genders g ON ls.gender_id = g.id ORDER BY g.id");
        if ($genderResult) {
            while ($row = $genderResult->fetch_assoc()) {
                $genders[] = [
                    'id' => (int)$row['id'],
                    'name' => htmlspecialchars($row['name'])
                ];
            }
            if (!empty($genders)) {
                $gender_id = $genders[0]['id'];
            }
        }
    }

    // First, check if is_deleted columns exist in the tables
    $checkColumnsQuery = "
        SELECT 
            (SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teams' AND COLUMN_NAME = 'is_deleted') as has_teams_deleted,
            (SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'competitions' AND COLUMN_NAME = 'is_deleted') as has_competitions_deleted,
            (SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'seasons' AND COLUMN_NAME = 'is_deleted') as has_seasons_deleted,
            (SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'genders' AND COLUMN_NAME = 'is_deleted') as has_genders_deleted
    ";
    
    $result = $conn->query($checkColumnsQuery);
    $columnsExist = $result->fetch_assoc();
    
    // Build the query based on which columns exist
    $sql = "
        SELECT 
            ls.*, 
            t.name as team_name, 
            t.logo as team_logo,
            c.name as competition_name,
            s.year as season_year,
            g.name as gender_name
        FROM league_standings ls
        JOIN teams t ON ls.team_id = t.id " . 
        ($columnsExist['has_teams_deleted'] ? "AND (t.is_deleted = 0 OR t.is_deleted IS NULL) " : "") . 
        " JOIN competitions c ON ls.competition_id = c.id " . 
        ($columnsExist['has_competitions_deleted'] ? "AND (c.is_deleted = 0 OR c.is_deleted IS NULL) " : "") . 
        " JOIN seasons s ON ls.season_id = s.id " . 
        ($columnsExist['has_seasons_deleted'] ? "AND (s.is_deleted = 0 OR s.is_deleted IS NULL) " : "") . 
        " JOIN genders g ON ls.gender_id = g.id " . 
        ($columnsExist['has_genders_deleted'] ? "AND (g.is_deleted = 0 OR g.is_deleted IS NULL) " : "") . 
        " WHERE ls.competition_id = ? 
          AND ls.season_id = ? 
          AND ls.gender_id = ?
        ORDER BY ls.league_points DESC, ls.points_difference DESC, ls.tries_for DESC, t.name ASC
    ";
    
    $query = $conn->prepare($sql);
    
    if ($query === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $query->bind_param("iii", $competition_id, $season_id, $gender_id);
    if (!$query->execute()) {
        throw new Exception("Execute failed: " . $query->error);
    }

    $result = $query->get_result();
    $position = 1;
    $previous_points = null;
    $previous_pd = null;
    $previous_tries = null;
    $position_to_display = 1;
    
    while ($row = $result->fetch_assoc()) {
        $team_name = htmlspecialchars($row['team_name']);
        $first_letter = strtoupper(substr($team_name, 0, 1));
        
        $current_points = (int)$row['league_points'];
        $current_pd = (int)$row['points_difference'];
        $current_tries = (int)$row['tries_for'];
        
        // Only increment position number if the current team's stats are different from previous
        if ($previous_points !== null && 
            ($current_points != $previous_points || 
             $current_pd != $previous_pd || 
             $current_tries != $previous_tries)) {
            $position_to_display = $position;
        }
        
        $standings[] = [
            'position' => $position_to_display,
            'team_name' => $team_name,
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
            'bonus_points' => (int)$row['bonus_points'],
            'team_logo' => $row['team_logo']
        ];
        
        $previous_points = $current_points;
        $previous_pd = $current_pd;
        $previous_tries = $current_tries;
        $position++;
    }
    
    $query->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log($e->getMessage());
    $error = "An error occurred while fetching league data: " . $e->getMessage();
    // Log the full stack trace for debugging
    error_log("Stack trace: " . $e->getTraceAsString());
    // Also log the query parameters for debugging
    error_log("Query params - competition_id: $competition_id, season_id: $season_id, gender_id: $gender_id");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>League Standings | 1000 Hills Rugby</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .team-logo {
            width: 35px;
            height: 35px;
            object-fit: contain;
            margin-right: 10px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .team-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            margin-right: 10px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        .form-win { background: linear-gradient(135deg, #10B981, #059669); }
        .form-draw { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .form-loss { background: linear-gradient(135deg, #EF4444, #DC2626); }
        .same-position {
            background-color: #f8fafc;
        }
        .promotion-row {
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border-left: 4px solid #10B981;
        }
        .relegation-row {
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            border-left: 4px solid #EF4444;
        }
        .championship-row {
            background: linear-gradient(90deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
            border-left: 4px solid #F59E0B;
        }
        /* Navigation styles - align with index.html (no gradients) */
        .nav-container {
            background: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .nav-item {
            position: relative;
            color: #1f2937; /* text-gray-800 */
            transition: all 0.3s ease;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .nav-item:hover {
            color: #16a34a; /* text-green-600 */
            background-color: transparent;
            transform: none;
        }
        .nav-item.active {
            color: #16a34a; /* green text for active */
            background-color: transparent;
        }
        .mobile-nav {
            background: #ffffff;
        }
        .mobile-nav-item {
            color: #1f2937; /* text-gray-800 */
            transition: all 0.2s ease;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .mobile-nav-item:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
        }
        .mobile-nav-item.active {
            background-color: #f3f4f6;
            color: #1f2937;
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
                        <img src="./images/1000-hills-logo.png" alt="1000 Hills Rugby" class="w-[60px]">
                        <span class="ml-3 text-xl font-bold text-gray-800">1000 Hills Rugby</span>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8 font-600 text-gray-800 text-sm tracking-wider">
                    <a href="/" class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 py-4">Home</a>
                    <a href="fixtures?tab=fixtures" class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 py-4">Fixtures</a>
                    <a href="fixtures" class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 py-4">Results</a>
                    <a href="tables.php" class="text-green-600 border-b-2 border-green-600 py-4">League Tables</a> 
                </nav>
                
                <button id="mobile-menu-button" class="md:hidden text-black focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden mobile-nav py-2 px-4 shadow-lg">
            <a href="/" class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300 rounded-md">Home</a>
            <a href="fixtures?tab=fixtures" class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300 rounded-md">Fixtures</a>
            <a href="fixtures" class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300 rounded-md">Results</a>
            <a href="tables.php" class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300 rounded-md">League Tables</a> 
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
                    
                    League Table Filters
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label for="competitionFilter" class="block text-sm font-semibold text-gray-700 mb-2">
                            Competition
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
                            Season
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
                            Gender
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
                            Update Table
                        </button>
                    </div>
                </div>
            </div>

            <!-- Standings Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">#</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">Team</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">Pld</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">W</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">D</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">L</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">T+</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">T-</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">PF</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">PA</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">PD</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">Pts</th>
                            <th scope="col" class="px-3 py-4 text-center text-xs font-medium uppercase tracking-wider">BP</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($standings)): ?>
                            <tr>
                                <td colspan="13" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-table text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-lg font-medium">No standings data available</p>
                                         
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
                                <tr class="<?= $is_championship ? 'championship-row' : ($is_promotion ? 'promotion-row' : ($is_relegation ? 'relegation-row' : ($is_same_position ? 'same-position' : ''))) ?> hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="position-badge <?= $position_class ?>">
                                            <?= $team['position'] ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($team['team_logo']) && file_exists(LOGO_FS_DIR . $team['team_logo'])): ?>
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
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900"><?= (int)$team['bonus_points'] ?></td>
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