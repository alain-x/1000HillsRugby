<?php
// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize database tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS players (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    img VARCHAR(255),
    age INT(3),
    role VARCHAR(50),
    player_category VARCHAR(50),  -- Backs or Forwards
    special_role VARCHAR(50),     -- Captain, Vice-Captain, or NULL
    team VARCHAR(20) NOT NULL DEFAULT 'men',
    weight VARCHAR(10),
    height VARCHAR(10),
    games INT(11),
    points INT(11),
    tries INT(11),
    placeOfBirth VARCHAR(100),
    nationality VARCHAR(50),
    honours TEXT,
    joined VARCHAR(20),
    previousClubs TEXT,
    sponsor VARCHAR(100),
    sponsorDesc TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get current team (men, women, academy_u18_boys, academy_u18_girls, academy_u16_boys, academy_u16_girls)
$currentTeam = isset($_GET['team']) ? $_GET['team'] : 'men';
$validTeams = ['men', 'women', 'academy_u18_boys', 'academy_u18_girls', 'academy_u16_boys', 'academy_u16_girls'];
if (!in_array($currentTeam, $validTeams)) {
    $currentTeam = 'men';
}

// Check if we're viewing a specific player
$playerId = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
$selectedPlayer = null;

if ($playerId > 0) {
    $stmt = $conn->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->bind_param("i", $playerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $selectedPlayer = $result->fetch_assoc();
    $stmt->close();
}

// Get filter and search parameters (only if not viewing a single player)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? strtolower($_GET['search']) : '';

// Build SQL query for player list (only if not viewing a single player)
if (!$selectedPlayer) {
    $sql = "SELECT * FROM players WHERE team = '" . $conn->real_escape_string($currentTeam) . "'";

    if ($filter != 'all') {
        if ($filter == 'Backs' || $filter == 'Forwards') {
            $sql .= " AND player_category = '" . $conn->real_escape_string($filter) . "'";
        } elseif ($filter == 'Captain' || $filter == 'Vice-Captain') {
            $sql .= " AND special_role = '" . $conn->real_escape_string($filter) . "'";
        }
    }

    if (!empty($search)) {
        $sql .= " AND (LOWER(name) LIKE '%" . $conn->real_escape_string($search) . "%'";
        $sql .= " OR LOWER(role) LIKE '%" . $conn->real_escape_string($search) . "%')";
    }

    $result = $conn->query($sql);
    $players = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
    }
}

// Calculate team stats
function calculateTeamStats($conn, $team) {
    $stats = [
        'totalPlayers' => 0,
        'totalGames' => 0,
        'totalPoints' => 0,
        'totalTries' => 0,
        'avgAge' => 0,
        'avgHeight' => 0,
        'avgWeight' => 0,
        'backsCount' => 0,
        'forwardsCount' => 0,
        'captain' => 'None',
        'viceCaptain' => 'None',
        'topScorer' => 'None',
        'topScorerPoints' => 0
    ];

    // Get all players for stats calculation
    $result = $conn->query("SELECT * FROM players WHERE team = '" . $conn->real_escape_string($team) . "'");
    $players = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
    }

    $stats['totalPlayers'] = count($players);
    $totalAge = 0;
    $totalHeight = 0;
    $totalWeight = 0;

    foreach ($players as $player) {
        $stats['totalGames'] += intval($player['games'] ?? 0);
        $stats['totalPoints'] += intval($player['points'] ?? 0);
        $stats['totalTries'] += intval($player['tries'] ?? 0);
        $totalAge += intval($player['age'] ?? 0);
        $totalHeight += intval($player['height'] ?? 0);
        $totalWeight += intval($player['weight'] ?? 0);

        if ($player['player_category'] == 'Backs') $stats['backsCount']++;
        if ($player['player_category'] == 'Forwards') $stats['forwardsCount']++;
        
        if ($player['special_role'] == 'Captain') $stats['captain'] = $player['name'];
        if ($player['special_role'] == 'Vice-Captain') $stats['viceCaptain'] = $player['name'];
        
        // Check for top scorer
        if (intval($player['points'] ?? 0) > $stats['topScorerPoints']) {
            $stats['topScorer'] = $player['name'];
            $stats['topScorerPoints'] = intval($player['points'] ?? 0);
        }
    }

    if ($stats['totalPlayers'] > 0) {
        $stats['avgAge'] = round($totalAge / $stats['totalPlayers'], 1);
        $stats['avgHeight'] = round($totalHeight / $stats['totalPlayers'], 1);
        $stats['avgWeight'] = round($totalWeight / $stats['totalPlayers'], 1);
    }

    return $stats;
}

$teamStats = calculateTeamStats($conn, $currentTeam);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selectedPlayer ? htmlspecialchars($selectedPlayer['name']) : '1000 Hills Rugby Club - ' . ucfirst(str_replace('_', ' ', $currentTeam)); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain unchanged */
        :root {
            --primary-color: #0a9113;
            --primary-dark: #077a0e;
            --secondary-color: #1a1a1a;
            --secondary-light: #2e2e2e;
            --accent-color: #f8f8f8;
            --text-color: #333;
            --light-text: #777;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --women-color: #e91e63;
            --women-dark: #c2185b;
            --academy-u18-color: #2196F3;
            --academy-u18-dark: #1976D2;
            --academy-u16-color: #FF9800;
            --academy-u16-dark: #F57C00;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Montserrat", sans-serif;
            color: var(--text-color);
            background: var(--accent-color);
            line-height: 1.6;
        }
        
        h1, h2, h3, h4 {
            font-weight: 700;
            line-height: 1.2;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-women {
            background-color: var(--women-color);
            color: var(--white);
        }

        .btn-women:hover {
            background-color: var(--women-dark);
            transform: translateY(-2px);
        }

        .btn-academy-u18 {
            background-color: var(--academy-u18-color);
            color: var(--white);
        }

        .btn-academy-u18:hover {
            background-color: var(--academy-u18-dark);
            transform: translateY(-2px);
        }

        .btn-academy-u16 {
            background-color: var(--academy-u16-color);
            color: var(--white);
        }

        .btn-academy-u16:hover {
            background-color: var(--academy-u16-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .section-title {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            font-size: 1.75rem;
        }

        .section-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
        }

        /* Header Styles */
        .header {
            background: linear-gradient(
                135deg,
                var(--primary-color) 0%,
                var(--secondary-color) 100%
            );
            color: var(--white);
            padding: 1rem 0;
            box-shadow: var(--shadow);
            z-index: 1000;
        }

        .header.women {
            background: linear-gradient(
                135deg,
                var(--women-color) 0%,
                var(--secondary-color) 100%
            );
        }

        .header.academy-u18 {
            background: linear-gradient(
                135deg,
                var(--academy-u18-color) 0%,
                var(--secondary-color) 100%
            );
        }

        .header.academy-u16 {
            background: linear-gradient(
                135deg,
                var(--academy-u16-color) 0%,
                var(--secondary-color) 100%
            );
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo img {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            border: 2px solid var(--white);
            object-fit: cover;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
        }

        .logo span {
            color: var(--primary-color);
        }

        .header.women .logo span {
            color: var(--women-color);
        }

        .header.academy-u18 .logo span {
            color: var(--academy-u18-color);
        }

        .header.academy-u16 .logo span {
            color: var(--academy-u16-color);
        }

        .club-motto {
            font-size: 0.7rem;
            font-weight: 400;
            opacity: 0.8;
            font-family: "Open Sans", sans-serif;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            font-weight: 600;
            transition: var(--transition);
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .header.women .nav-links a:hover {
            color: var(--women-color);
        }

        .header.academy-u18 .nav-links a:hover {
            color: var(--academy-u18-color);
        }

        .header.academy-u16 .nav-links a:hover {
            color: var(--academy-u16-color);
        }

        .nav-links a::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-color);
            transition: var(--transition);
        }

        .header.women .nav-links a::after {
            background-color: var(--women-color);
        }

        .header.academy-u18 .nav-links a::after {
            background-color: var(--academy-u18-color);
        }

        .header.academy-u16 .nav-links a::after {
            background-color: var(--academy-u16-color);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a.active {
            color: var(--primary-color);
        }

        .header.women .nav-links a.active {
            color: var(--women-color);
        }

        .header.academy-u18 .nav-links a.active {
            color: var(--academy-u18-color);
        }

        .header.academy-u16 .nav-links a.active {
            color: var(--academy-u16-color);
        }

        .search-bar {
            position: relative;
            width: 300px;
        }

        .search-bar input {
            width: 100%;
            padding: 0.75rem 1.25rem;
            border-radius: 2rem;
            border: none;
            font-size: 0.95rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .search-bar input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 145, 19, 0.3);
        }

        .search-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            order: 1;
        }

        .mobile-menu-close {
            display: none;
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1001;
        }

        /* Team Selector */
        .team-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        /* Academy Dropdown - Improved for Mobile */
        .academy-dropdown {
            position: relative;
        }

        .academy-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .academy-dropdown-toggle::after {
            content: "▾";
            font-size: 0.8em;
            transition: transform 0.3s ease;
        }

        .academy-dropdown.active .academy-dropdown-toggle::after {
            transform: rotate(180deg);
        }

        .academy-dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--white);
            min-width: 200px;
            box-shadow: var(--shadow-lg);
            z-index: 1;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .academy-dropdown:hover .academy-dropdown-content {
            display: block;
        }

        .academy-dropdown.active .academy-dropdown-content {
            display: block;
            position: static;
            box-shadow: none;
            background-color: transparent;
        }

        .academy-dropdown-content a {
            color: var(--text-color);
            padding: 0.75rem 1rem;
            text-decoration: none;
            display: block;
            transition: var(--transition);
        }

        .academy-dropdown:hover .academy-dropdown-content a:hover {
            background-color: var(--accent-color);
        }

        .academy-dropdown.active .academy-dropdown-content a {
            color: var(--white);
            padding: 1rem;
            text-align: center;
        }

        /* Filter Bar */
        .filter-bar {
            background-color: var(--secondary-color);
            padding: 1rem 0;
            z-index: 50;
            box-shadow: var(--shadow);
        }

        .filter-tabs {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none;
        }

        .filter-tabs::-webkit-scrollbar {
            display: none;
        }

        .filter-tab {
            padding: 0.5rem 1.25rem;
            border-radius: 2rem;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .filter-tab:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .filter-tab.active {
            background-color: var(--primary-color);
        }

        .women .filter-tab.active {
            background-color: var(--women-color);
        }

        .academy-u18 .filter-tab.active {
            background-color: var(--academy-u18-color);
        }

        .academy-u16 .filter-tab.active {
            background-color: var(--academy-u16-color);
        }

        /* Player Grid */
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 2rem 0;
        }

        .player-card {
            background: var(--white);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .player-image-container {
            height: 300px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .player-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .player-card:hover .player-image {
            transform: scale(1.05);
        }

        .player-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                to top,
                rgba(0, 0, 0, 0.7) 0%,
                rgba(0, 0, 0, 0) 50%
            );
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .player-card:hover .player-image-overlay {
            opacity: 1;
        }

        .player-info {
            padding: 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .player-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 0.25rem 0;
        }

        .player-position {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0;
        }

        .women .player-position {
            color: var(--women-color);
        }

        .academy-u18 .player-position {
            color: var(--academy-u18-color);
        }

        .academy-u16 .player-position {
            color: var(--academy-u16-color);
        }

        .player-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-weight: 700;
            color: var(--primary-color);
        }

        .women .stat-value {
            color: var(--women-color);
        }

        .academy-u18 .stat-value {
            color: var(--academy-u18-color);
        }

        .academy-u16 .stat-value {
            color: var(--academy-u16-color);
        }

        .stat-label {
            font-size: 0.7rem;
            color: var(--light-text);
        }

        /* Player Detail */
        .player-detail {
            background: var(--white);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin: 2rem 0;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .women .back-button {
            color: var(--women-color);
        }

        .academy-u18 .back-button {
            color: var(--academy-u18-color);
        }

        .academy-u16 .back-button {
            color: var(--academy-u16-color);
        }

        .back-button:hover {
            text-decoration: underline;
        }

        .detail-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }

        .detail-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            margin-right: 2rem;
            border: 4px solid var(--primary-color);
            object-fit: cover;
            flex-shrink: 0;
        }

        .women .detail-image {
            border-color: var(--women-color);
        }

        .academy-u18 .detail-image {
            border-color: var(--academy-u18-color);
        }

        .academy-u16 .detail-image {
            border-color: var(--academy-u16-color);
        }

        .detail-name {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .detail-position {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0.5rem 0;
        }

        .women .detail-position {
            color: var(--women-color);
        }

        .academy-u18 .detail-position {
            color: var(--academy-u18-color);
        }

        .academy-u16 .detail-position {
            color: var(--academy-u16-color);
        }

        .detail-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item-lg {
            background: var(--accent-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }

        .stat-item-lg:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .stat-value-lg {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0.25rem 0;
        }

        .women .stat-value-lg {
            color: var(--women-color);
        }

        .academy-u18 .stat-value-lg {
            color: var(--academy-u18-color);
        }

        .academy-u16 .stat-value-lg {
            color: var(--academy-u16-color);
        }

        .stat-label-lg {
            font-size: 0.8rem;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-group {
            margin-bottom: 1.25rem;
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
        }

        .sponsor {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .sponsor-label {
            font-size: 0.8rem;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .sponsor-name {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .sponsor-desc {
            font-size: 0.9rem;
            color: var(--light-text);
            margin-top: 0.25rem;
        }

        /* No Results */
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
        }

        .no-results i {
            font-size: 3rem;
            color: var(--light-text);
            margin-bottom: 1rem;
        }

        /* Team Stats */
        .team-stats {
            background: var(--white);
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .stats-item {
            text-align: center;
            padding: 1rem;
            background: var(--accent-color);
            border-radius: 0.5rem;
            transition: var(--transition);
        }

        .stats-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .women .stats-value {
            color: var(--women-color);
        }

        .academy-u18 .stats-value {
            color: var(--academy-u18-color);
        }

        .academy-u16 .stats-value {
            color: var(--academy-u16-color);
        }

        .stats-label {
            font-size: 0.8rem;
            color: var(--light-text);
            text-transform: uppercase;
        }

        /* Loading State */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .women .spinner {
            border-left-color: var(--women-color);
        }

        .academy-u18 .spinner {
            border-left-color: var(--academy-u18-color);
        }

        .academy-u16 .spinner {
            border-left-color: var(--academy-u16-color);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Fallback for missing images */
        .player-image-container {
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .player-image-placeholder {
            font-size: 3rem;
            color: #ccc;
        }

        /* Footer */
        .footer {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .footer-logo img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            border: 2px solid var(--white);
        }

        .footer-logo-text h3 {
            font-size: 1.25rem;
        }

        .footer-logo-text span {
            color: var(--primary-color);
        }

        .footer-motto {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .footer-links h4 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-links h4::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }

        .footer-contact p {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-contact i {
            width: 20px;
            text-align: center;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .footer-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--secondary-light);
            color: var(--white);
            transition: var(--transition);
        }

        .footer-social a:hover {
            background-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .nav-links {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.9);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                padding: 2rem;
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .mobile-menu-close {
                display: block;
            }

            .search-bar {
                width: 100%;
                margin-top: 1rem;
                order: 3;
            }

            .academy-dropdown-content {
                position: static;
                display: none;
                box-shadow: none;
                background-color: transparent;
            }

            .academy-dropdown:hover .academy-dropdown-content {
                display: none;
            }

            .academy-dropdown.active .academy-dropdown-content {
                display: block;
            }

            .detail-header {
                flex-direction: column;
                text-align: center;
            }

            .detail-image {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }

            .detail-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .detail-info {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .player-grid {
                grid-template-columns: 1fr;
            }

            .player-image-container {
                height: 250px;
            }

            .player-card {
                height: auto;
            }

            .player-info {
                padding: 1rem;
            }

            .player-name {
                font-size: 1.1rem;
            }

            .player-position {
                font-size: 0.85rem;
            }

            .player-stats {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 300px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .player-image-container {
                height: 200px;
            }
        }
    </style>
</head>
<body class="<?php 
    if (strpos($currentTeam, 'academy_u18') !== false) echo 'academy-u18';
    elseif (strpos($currentTeam, 'academy_u16') !== false) echo 'academy-u16';
    else echo $currentTeam;
?>">
    <!-- Header -->
    <header class="header <?php 
        if (strpos($currentTeam, 'academy_u18') !== false) echo 'academy-u18';
        elseif (strpos($currentTeam, 'academy_u16') !== false) echo 'academy-u16';
        else echo $currentTeam;
    ?>">
        <div class="container">
            <div class="logo-container">
                <a href="./" class="logo">
                    <img src="./logos_/logoT.jpg" alt="Club Logo" />
                </a>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="header-content">
                <nav class="nav-links" id="navLinks">
                    <button class="mobile-menu-close" id="mobileMenuClose">
                        <i class="fas fa-times"></i>
                    </button>
                    <a href="./">Home</a>
                    <a href="?team=men" class="<?php echo $currentTeam == 'men' ? 'active' : ''; ?>">Men's Squad</a>
                    <a href="?team=women" class="<?php echo $currentTeam == 'women' ? 'active' : ''; ?>">Women's Squad</a>
                    <div class="academy-dropdown" id="academyDropdown">
                        <div class="academy-dropdown-toggle">
                            <a href="#" class="<?php echo strpos($currentTeam, 'academy_') !== false ? 'active' : ''; ?>">Academy</a>
                        </div>
                        <div class="academy-dropdown-content">
                            <a href="?team=academy_u18_boys">U18 Boys</a>
                            <a href="?team=academy_u18_girls">U18 Girls</a>
                            <a href="?team=academy_u16_boys">U16 Boys</a>
                            <a href="?team=academy_u16_girls">U16 Girls</a>
                        </div>
                    </div>
                    <a href="./fixtures.php">Fixtures</a>
                </nav>

                <div class="search-bar">
                    <form method="get" action="">
                        <input
                            type="text"
                            name="search"
                            placeholder="Search players..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        />
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="hidden" name="team" value="<?php echo $currentTeam; ?>">
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="container">
            <div class="filter-tabs">
                <a href="?filter=all&search=<?php echo urlencode($search); ?>&team=<?php echo $currentTeam; ?>" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All Players</a>
                <a href="?filter=Backs&search=<?php echo urlencode($search); ?>&team=<?php echo $currentTeam; ?>" class="filter-tab <?php echo $filter == 'Backs' ? 'active' : ''; ?>">Backs</a>
                <a href="?filter=Forwards&search=<?php echo urlencode($search); ?>&team=<?php echo $currentTeam; ?>" class="filter-tab <?php echo $filter == 'Forwards' ? 'active' : ''; ?>">Forwards</a>
                <?php if (!strpos($currentTeam, 'academy_')): ?>
                    <a href="?filter=Captain&search=<?php echo urlencode($search); ?>&team=<?php echo $currentTeam; ?>" class="filter-tab <?php echo $filter == 'Captain' ? 'active' : ''; ?>">Captain</a>
                    <a href="?filter=Vice-Captain&search=<?php echo urlencode($search); ?>&team=<?php echo $currentTeam; ?>" class="filter-tab <?php echo $filter == 'Vice-Captain' ? 'active' : ''; ?>">Vice-Captain</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container">
        <!-- Team Stats -->
        <div class="team-stats">
            <h2 class="section-title">Team Statistics</h2>
            <div class="stats-grid">
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['totalPlayers']; ?></div>
                    <div class="stats-label">Players</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['totalGames']; ?></div>
                    <div class="stats-label">Total Games</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['totalPoints']; ?></div>
                    <div class="stats-label">Total Points</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['totalTries']; ?></div>
                    <div class="stats-label">Total Tries</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['avgAge']; ?></div>
                    <div class="stats-label">Avg Year</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['avgHeight']; ?> cm</div>
                    <div class="stats-label">Avg Height</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['avgWeight']; ?> kg</div>
                    <div class="stats-label">Avg Weight</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['backsCount']; ?></div>
                    <div class="stats-label">Backs</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['forwardsCount']; ?></div>
                    <div class="stats-label">Forwards</div>
                </div>
                <?php if (!strpos($currentTeam, 'academy_')): ?>
                    <div class="stats-item">
                        <div class="stats-value"><?php echo $teamStats['captain']; ?></div>
                        <div class="stats-label">Captain</div>
                    </div>
                    <div class="stats-item">
                        <div class="stats-value"><?php echo $teamStats['viceCaptain']; ?></div>
                        <div class="stats-label">Vice-Captain</div>
                    </div>
                <?php endif; ?>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['topScorer']; ?></div>
                    <div class="stats-label">Top Scorer</div>
                </div>
                <div class="stats-item">
                    <div class="stats-value"><?php echo $teamStats['topScorerPoints']; ?></div>
                    <div class="stats-label">Top Scorer Points</div>
                </div>
            </div>
        </div>

        <?php if ($selectedPlayer): ?>
            <!-- Player Detail View -->
            <div class="player-detail">
                <div class="back-button" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i> Back to Players
                </div>
                
                <div class="detail-header">
                    <?php if (!empty($selectedPlayer['img'])): ?>
                        <img src="<?php echo htmlspecialchars($selectedPlayer['img']); ?>" alt="<?php echo htmlspecialchars($selectedPlayer['name']); ?>" class="detail-image">
                    <?php else: ?>
                        <div class="detail-image"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                    <div>
                        <h2 class="detail-name"><?php echo htmlspecialchars($selectedPlayer['name']); ?></h2>
                        <p class="detail-position"><?php echo htmlspecialchars($selectedPlayer['role']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($selectedPlayer['player_category']); 
                            if (!empty($selectedPlayer['special_role'])) {
                                echo ' (' . htmlspecialchars($selectedPlayer['special_role']) . ')';
                            }
                        ?></p>
                        <p><strong>Team:</strong> <?php 
                            $teamName = $selectedPlayer['team'];
                            if ($teamName == 'men') echo "Men's Team";
                            elseif ($teamName == 'women') echo "Women's Team";
                            elseif ($teamName == 'academy_u18_boys') echo "Academy U18 Boys";
                            elseif ($teamName == 'academy_u18_girls') echo "Academy U18 Girls";
                            elseif ($teamName == 'academy_u16_boys') echo "Academy U16 Boys";
                            elseif ($teamName == 'academy_u16_girls') echo "Academy U16 Girls";
                            else echo ucfirst($teamName);
                        ?></p>
                    </div>
                </div>

                <div class="detail-stats">
                    <div class="stat-item-lg">
                        <div class="stat-label-lg">Year</div>
                        <div class="stat-value-lg"><?php echo htmlspecialchars($selectedPlayer['age']); ?></div>
                    </div>
                    <div class="stat-item-lg">
                        <div class="stat-label-lg">Height</div>
                        <div class="stat-value-lg"><?php echo htmlspecialchars($selectedPlayer['height']); ?> cm</div>
                    </div>
                    <div class="stat-item-lg">
                        <div class="stat-label-lg">Weight</div>
                        <div class="stat-value-lg"><?php echo htmlspecialchars($selectedPlayer['weight']); ?> kg</div>
                    </div>
                    <div class="stat-item-lg">
                        <div class="stat-label-lg">Games</div>
                        <div class="stat-value-lg"><?php echo htmlspecialchars($selectedPlayer['games']); ?></div>
                    </div>
                    <div class="stat-item-lg">
                        <div class="stat-label-lg">Points</div>
                        <div class="stat-value-lg"><?php echo htmlspecialchars($selectedPlayer['points']); ?></div>
                    </div>
                    <div class="stat-item-lg">
                        <div class="stat-label-lg">Tries</div>
                        <div class="stat-value-lg"><?php echo htmlspecialchars($selectedPlayer['tries']); ?></div>
                    </div>
                </div>

                <div class="detail-info">
                    <div>
                        <div class="info-group">
                            <div class="info-label">Place of Birth</div>
                            <div class="info-value"><?php echo htmlspecialchars($selectedPlayer['placeOfBirth'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Nationality</div>
                            <div class="info-value"><?php echo htmlspecialchars($selectedPlayer['nationality'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Joined Club</div>
                            <div class="info-value"><?php echo htmlspecialchars($selectedPlayer['joined'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="info-group">
                            <div class="info-label">Honours</div>
                            <div class="info-value"><?php echo htmlspecialchars($selectedPlayer['honours'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Previous Clubs</div>
                            <div class="info-value"><?php echo htmlspecialchars($selectedPlayer['previousClubs'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($selectedPlayer['sponsor'])): ?>
                    <div class="sponsor">
                        <div class="sponsor-label">Sponsored By</div>
                        <div class="sponsor-name"><?php echo htmlspecialchars($selectedPlayer['sponsor']); ?></div>
                        <?php if (!empty($selectedPlayer['sponsorDesc'])): ?>
                            <div class="sponsor-desc"><?php echo htmlspecialchars($selectedPlayer['sponsorDesc']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Player Grid View -->
            <div class="player-grid">
                <?php if (empty($players)): ?>
                    <div class="no-results">
                        <i class="fas fa-user-slash"></i>
                        <h3>No players found</h3>
                        <p>Try adjusting your filters or search term</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($players as $player): ?>
                        <div class="player-card">
                            <div class="player-image-container" onclick="window.location.href='?player_id=<?php echo $player['id']; ?>&team=<?php echo $currentTeam; ?>'">
                                <?php if (!empty($player['img'])): ?>
                                    <img src="<?php echo htmlspecialchars($player['img']); ?>" alt="<?php echo htmlspecialchars($player['name']); ?>" class="player-image">
                                <?php else: ?>
                                    <div class="player-image-placeholder"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                                <div class="player-image-overlay"></div>
                            </div>
                            <div class="player-info">
                                <h3 class="player-name"><?php echo htmlspecialchars($player['name']); ?></h3>
                                <p class="player-position"><?php echo htmlspecialchars($player['role']); 
                                    if (!empty($player['special_role'])) {
                                        echo ' (' . htmlspecialchars($player['special_role']) . ')';
                                    }
                                ?></p>
                                <div class="player-stats">
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo htmlspecialchars($player['age']); ?></span>
                                        <span class="stat-label">Year</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo htmlspecialchars($player['height']); ?> cm</span>
                                        <span class="stat-label">Height</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo htmlspecialchars($player['weight']); ?> kg</span>
                                        <span class="stat-label">Weight</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenuClose = document.getElementById('mobileMenuClose');
            const navLinks = document.getElementById('navLinks');
            const academyDropdown = document.getElementById('academyDropdown');

            // Toggle mobile menu
            mobileMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                navLinks.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            // Close mobile menu
            mobileMenuClose.addEventListener('click', function(e) {
                e.stopPropagation();
                navLinks.classList.remove('active');
                document.body.style.overflow = '';
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!navLinks.contains(e.target) && e.target !== mobileMenuBtn) {
                    navLinks.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            // Academy dropdown toggle for mobile
            academyDropdown.addEventListener('click', function(e) {
                // Only prevent default if we're on mobile and clicking the toggle
                if (window.innerWidth <= 768 && e.target.closest('.academy-dropdown-toggle')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.toggle('active');
                }
            });

            // Close dropdown when clicking a link inside it
            document.querySelectorAll('.academy-dropdown-content a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        academyDropdown.classList.remove('active');
                        navLinks.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });

            // Handle window resize to reset dropdown state
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    academyDropdown.classList.remove('active');
                    navLinks.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>