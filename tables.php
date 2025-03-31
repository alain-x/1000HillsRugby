<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');
define('LOGO_DIR', __DIR__ . '/logos/');
define('DEFAULT_LOGO', 'default.png');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

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

    // Get available competitions
    $compResult = $conn->query("SELECT id, name FROM competitions WHERE is_active = TRUE ORDER BY name");
    if ($compResult) {
        while ($row = $compResult->fetch_assoc()) {
            $competitions[] = [
                'id' => (int)$row['id'],
                'name' => htmlspecialchars($row['name'])
            ];
        }
        $compResult->free();
    }

    // Get available seasons
    $seasonQuery = "SELECT id, year FROM seasons ORDER BY year DESC";
    $seasonResult = $conn->query($seasonQuery);
    if ($seasonResult) {
        while ($row = $seasonResult->fetch_assoc()) {
            $seasons[] = [
                'id' => (int)$row['id'],
                'year' => htmlspecialchars($row['year'])
            ];
        }
        $seasonResult->free();
        
        // Set default season to current if not specified
        if ($season_id === null && !empty($seasons)) {
            $season_id = $seasons[0]['id'];
        }
    }

    // Get available genders
    $genderResult = $conn->query("SELECT id, name FROM genders ORDER BY id");
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

    // Get standings data with prepared statement
    $query = $conn->prepare("
        SELECT ls.*, t.name as team_name, t.logo as team_logo
        FROM league_standings ls
        JOIN teams t ON ls.team_id = t.id
        WHERE ls.competition_id = ? AND ls.season_id = ? AND ls.gender_id = ?
        ORDER BY ls.league_points DESC, ls.points_difference DESC, ls.tries_for DESC, t.name ASC
    ");
    
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
            'form_score' => (float)$row['form_score'],
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
    $error = "An error occurred while fetching league data. Please try again later.";
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
            width: 30px;
            height: 30px;
            object-fit: contain;
            margin-right: 8px;
        }
        .team-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            margin-right: 8px;
            background-color: #9CA3AF;
            color: white;
            border-radius: 50%;
            font-weight: bold;
        }
        .form-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 2px;
        }
        .form-win { background-color: #10B981; }
        .form-draw { background-color: #F59E0B; }
        .form-loss { background-color: #EF4444; }
        .same-position {
            background-color: #f8fafc;
        }
        /* Navigation styles */
        .nav-container {
            background: linear-gradient(to right, rgb(10, 145, 19) 0%, rgb(1, 20, 2) 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .nav-item {
            position: relative;
            color: white;
            transition: all 0.3s ease;
        }
        .nav-item:hover {
            color: #d1fae5;
        }
        .nav-item.active {
            color: white;
        }
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 3px;
            background-color: #34d399;
            border-radius: 3px;
        }
        .mobile-nav {
            background: linear-gradient(to right, rgb(10, 145, 19) 0%, rgb(1, 20, 2) 100%);
        }
        .mobile-nav-item {
            color: white;
            transition: all 0.2s ease;
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
            background-color: #065f46;
            color: white;
        }
        .promotion-row {
            background-color: rgba(16, 185, 129, 0.1);
        }
        .relegation-row {
            background-color: rgba(239, 68, 68, 0.05);
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
                        <span class="ml-3 text-xl font-bold text-white"></span>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="/" class="nav-item active font-medium text-sm uppercase tracking-wider py-4">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                    <a href="fixtures.php?tab=fixtures" class="nav-item font-medium text-sm uppercase tracking-wider py-4">
                        <i class="fas fa-calendar-alt mr-2"></i>Fixtures
                    </a>
                    <a href="fixtures.php?tab=results" class="nav-item font-medium text-sm uppercase tracking-wider py-4">
                        <i class="fas fa-list-ol mr-2"></i>Results
                    </a>
                    <a href="tables.php" class="nav-item active font-medium text-sm uppercase tracking-wider py-4">
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
            <a href="/" class="block py-3 px-4 mobile-nav-item active rounded-md">
                <i class="fas fa-home mr-3"></i>Home
            </a>
            <a href="fixtures.php?tab=fixtures" class="block py-3 px-4 mobile-nav-item rounded-md">
                <i class="fas fa-calendar-alt mr-3"></i>Fixtures
            </a>
            <a href="fixtures.php?tab=results" class="block py-3 px-4 mobile-nav-item rounded-md">
                <i class="fas fa-list-ol mr-3"></i>Results
            </a>
            <a href="tables.php" class="block py-3 px-4 mobile-nav-item active rounded-md">
                <i class="fas fa-table mr-3"></i>League Tables
            </a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <?php if (isset($error)): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Filters -->
            <div class="p-6 bg-gray-50 border-b">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label for="competitionFilter" class="block text-sm font-medium text-gray-700 mb-1">Competition</label>
                        <select id="competitionFilter" class="w-full p-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?= $comp['id'] ?>" <?= ($competition_id == $comp['id']) ? 'selected' : '' ?>>
                                    <?= $comp['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="seasonFilter" class="block text-sm font-medium text-gray-700 mb-1">Season</label>
                        <select id="seasonFilter" class="w-full p-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                            <?php foreach ($seasons as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($season_id == $s['id']) ? 'selected' : '' ?>>
                                    <?= $s['year'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="genderFilter" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select id="genderFilter" class="w-full p-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                            <?php foreach ($genders as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= ($gender_id == $g['id']) ? 'selected' : '' ?>>
                                    <?= $g['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button id="applyFilters" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-md transition duration-300 shadow-md">
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">#</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Team</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">Pld</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">W</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">D</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">L</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">T+</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">T-</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">PF</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">PA</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">PD</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">Pts</th> 
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($standings)): ?>
                            <tr>
                                <td colspan="13" class="px-6 py-4 text-center text-gray-500">
                                    No standings data available for the selected filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $previous_position = null;
                            foreach ($standings as $team): 
                                $is_same_position = ($previous_position !== null && $team['position'] == $previous_position);
                                $is_promotion = $team['position'] <= 4;
                                $is_relegation = $team['position'] >= (count($standings) - 2);
                            ?>
                                <tr class="<?= $is_promotion ? 'promotion-row' : ($is_relegation ? 'relegation-row' : ($is_same_position ? 'same-position' : '')) ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $team['position'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (!empty($team['team_logo']) && file_exists(LOGO_DIR . $team['team_logo'])): ?>
                                                <img src="<?= LOGO_DIR . $team['team_logo'] ?>" alt="<?= $team['team_name'] ?>" class="team-logo">
                                            <?php else: ?>
                                                <div class="team-initial"><?= $team['first_letter'] ?></div>
                                            <?php endif; ?>
                                            <span class="text-sm font-medium text-gray-900"><?= $team['team_name'] ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['matches_played'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['matches_won'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['matches_drawn'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['matches_lost'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['tries_for'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['tries_against'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['points_for'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['points_against'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500"><?= $team['points_difference'] > 0 ? '+' : '' ?><?= $team['points_difference'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900"><?= $team['league_points'] ?></td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                        <?php 
                                            // Simulate form indicators (would normally come from database)
                                            $form = ['W', 'W', 'D', 'L', 'W'];
                                            foreach ($form as $result): 
                                        ?>
                                            <span class="form-indicator form-<?= strtolower($result) ?>"></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                                <?php $previous_position = $team['position']; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Filter handling
        document.getElementById('applyFilters').addEventListener('click', function() {
            const competition_id = document.getElementById('competitionFilter').value;
            const season_id = document.getElementById('seasonFilter').value;
            const gender_id = document.getElementById('genderFilter').value;
            
            // Add loading state
            document.body.classList.add('opacity-75', 'pointer-events-none');
            document.body.insertAdjacentHTML('beforeend', 
                '<div class="fixed inset-0 flex items-center justify-center z-50">' +
                '<div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-green-500"></div>' +
                '</div>');
            
            // Navigate to new URL with filters
            window.location.href = `tables.php?competition_id=${competition_id}&season_id=${season_id}&gender_id=${gender_id}`;
        });
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>