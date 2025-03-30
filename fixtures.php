<?php
// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter parameters
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'MEN';
$competition = isset($_GET['competition']) ? $_GET['competition'] : 'ALL';
$season = isset($_GET['season']) ? $_GET['season'] : date('Y');
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'fixtures';

// Get all competitions for dropdown
$competitions = [];
$compResult = $conn->query("SELECT DISTINCT competition FROM fixtures ORDER BY competition");
if ($compResult->num_rows > 0) {
    while ($row = $compResult->fetch_assoc()) {
        $competitions[] = stripslashes($row['competition']);
    }
}

// Build SQL query based on selected tab
$sql = "SELECT * FROM fixtures WHERE season = ?";
$params = [$season];
$types = "s";

if ($tab === 'results') {
    $sql .= " AND status = 'COMPLETED'";
} else {
    $sql .= " AND (status != 'COMPLETED' OR status IS NULL)";
}

if ($gender != 'ALL') {
    $sql .= " AND gender = ?";
    $params[] = $gender;
    $types .= "s";
}

if ($competition != 'ALL') {
    $sql .= " AND competition = ?";
    $params[] = $competition;
    $types .= "s";
}

// Order by date (newest first for results, upcoming first for fixtures)
$sql .= ($tab === 'results') ? " ORDER BY match_date DESC" : " ORDER BY match_date ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $fixtures = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($fixtures as &$fixture) {
        $fixture['competition'] = stripslashes($fixture['competition']);
    }
    
    $stmt->close();
} else {
    $fixtures = [];
}

$conn->close();

function displayText($text) {
    return htmlspecialchars(stripslashes($text));
}

function formatMatchDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('D, M j - g:i A');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tab === 'results' ? 'Match Results' : 'Match Fixtures'; ?> - 1000 Hills Rugby</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        rugby: {
                            green: '#1a5632',
                            dark: '#0d2e1a',
                            light: '#e8f5e9',
                            gold: '#d4af37'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
        }
        .fixture-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .fixture-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left-color: #1a5632;
        }
        .result-card {
            background: linear-gradient(to right, #f8f8f8 0%, white 20%);
        }
        .team-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        .score-pill {
            min-width: 80px;
        }
        .tab-indicator {
            height: 3px;
            background: #1a5632;
            bottom: -1px;
            transition: all 0.3s ease;
        }
        .filter-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        /* Mobile-specific styles */
        @media (max-width: 767px) {
            .match-row {
                display: flex;
                flex-direction: row;
                align-items: center;
                padding: 12px;
                border-bottom: 1px solid #e5e7eb;
            }
            .team-col {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .score-col {
                flex: 0 0 80px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .mobile-team-name {
                font-size: 14px;
                font-weight: 500;
                margin-top: 4px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100px;
            }
            .mobile-logo {
                width: 32px;
                height: 32px;
            }
            .mobile-score {
                font-size: 18px;
                font-weight: bold;
            }
            .mobile-vs {
                font-size: 16px;
                color: #6b7280;
            }
            .mobile-date {
                font-size: 12px;
                color: #6b7280;
                margin-top: 4px;
            }
            .mobile-competition {
                font-size: 11px;
                color: #6b7280;
                margin-top: 4px;
            }
            .mobile-card {
                border-radius: 8px;
                margin-bottom: 8px;
                background: white;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-rugby-green text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <img src="./logos_/logoT.jpg" alt="Club Logo" class="h-12 rounded-full border-2 border-white shadow-md">
                     
                </div>
                
                <nav class="hidden md:flex space-x-8">
                    <a href="fixtures.php?tab=fixtures" class="relative py-2 px-1 font-medium text-sm uppercase tracking-wider hover:text-rugby-gold transition duration-200">
                        Fixtures
                        <?php if ($tab === 'fixtures'): ?>
                        <span class="tab-indicator absolute left-0 right-0"></span>
                        <?php endif; ?>
                    </a>
                    <a href="fixtures.php?tab=results" class="relative py-2 px-1 font-medium text-sm uppercase tracking-wider hover:text-rugby-gold transition duration-200">
                        Results
                        <?php if ($tab === 'results'): ?>
                        <span class="tab-indicator absolute left-0 right-0"></span>
                        <?php endif; ?>
                    </a>
                    <a href="tables.php" class="relative py-2 px-1 font-medium text-sm uppercase tracking-wider hover:text-rugby-gold transition duration-200">
                        League Tables
                    </a>
                    <a href="upload_fixtures.php" class="relative py-2 px-1 font-medium text-sm uppercase tracking-wider hover:text-rugby-gold transition duration-200">
                        Admin
                    </a>
                </nav>
                
                <button id="mobile-menu-button" class="md:hidden text-white focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-rugby-dark text-white py-2 px-4 shadow-lg">
        <a href="fixtures.php?tab=fixtures" class="block py-3 px-4 border-b border-rugby-green <?php echo $tab === 'fixtures' ? 'bg-rugby-green bg-opacity-30' : ''; ?>">
            <i class="fas fa-calendar-alt mr-3"></i>Fixtures
        </a>
        <a href="fixtures.php?tab=results" class="block py-3 px-4 border-b border-rugby-green <?php echo $tab === 'results' ? 'bg-rugby-green bg-opacity-30' : ''; ?>">
            <i class="fas fa-list-ol mr-3"></i>Results
        </a>
        <a href="tables.php" class="block py-3 px-4 border-b border-rugby-green">
            <i class="fas fa-table mr-3"></i>League Tables
        </a>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Page Title -->
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-rugby-dark">
                <?php echo $tab === 'results' ? 'Match Results' : 'Upcoming Fixtures'; ?>
            </h2>
            <div class="text-sm text-gray-500">
                <?php echo date('F j, Y'); ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8 grid grid-cols-1 md:grid-cols-5 gap-4 border border-gray-100">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                <select id="genderFilter" class="filter-select w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rugby-green focus:border-rugby-green text-sm appearance-none">
                    <option value="ALL" <?php echo ($gender == 'ALL') ? 'selected' : ''; ?>>All Teams</option>
                    <option value="MEN" <?php echo ($gender == 'MEN') ? 'selected' : ''; ?>>Men's Teams</option>
                    <option value="WOMEN" <?php echo ($gender == 'WOMEN') ? 'selected' : ''; ?>>Women's Teams</option>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Competition</label>
                <select id="competitionFilter" class="filter-select w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rugby-green focus:border-rugby-green text-sm appearance-none">
                    <option value="ALL" <?php echo ($competition == 'ALL') ? 'selected' : ''; ?>>All Competitions</option>
                    <?php foreach ($competitions as $comp): ?>
                        <option value="<?php echo displayText($comp); ?>" <?php echo ($competition == $comp) ? 'selected' : ''; ?>>
                            <?php echo displayText($comp); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Season</label>
                <select id="seasonFilter" class="filter-select w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rugby-green focus:border-rugby-green text-sm appearance-none">
                    <?php 
                    $currentYear = date('Y');
                    for ($year = $currentYear; $year >= 2020; $year--) {
                        $selected = ($season == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year Season</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button id="applyFilters" class="w-full bg-rugby-green hover:bg-rugby-dark text-white font-medium py-2.5 px-4 rounded-lg transition duration-200 shadow-sm">
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Matches List -->
        <div class="space-y-4">
            <?php if (empty($fixtures)): ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center border border-gray-100">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-calendar-times text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-2">
                        <?php echo $tab === 'results' ? 'No match results found' : 'No upcoming fixtures scheduled'; ?>
                    </h3>
                    <p class="text-gray-500 max-w-md mx-auto">
                        Try adjusting your filters or check back later for updates.
                    </p>
                </div>
            <?php else: ?>
                <?php 
                $currentMonth = '';
                $processedFixtures = [];
                
                foreach ($fixtures as $fixture): 
                    $fixtureKey = $fixture['match_date'].$fixture['home_team'].$fixture['away_team'];
                    if (in_array($fixtureKey, $processedFixtures)) continue;
                    $processedFixtures[] = $fixtureKey;
                    
                    $matchDate = new DateTime($fixture['match_date']);
                    $month = $matchDate->format('F Y');
                    $isCompleted = $fixture['status'] == 'COMPLETED';
                    
                    if ($month != $currentMonth) {
                        $currentMonth = $month;
                        echo "<h3 class='text-lg font-semibold text-gray-700 mb-3 mt-6 pl-2 border-l-4 border-rugby-green'>$currentMonth</h3>";
                    }
                ?>
                    <!-- Desktop View -->
                    <div class="hidden md:block fixture-card bg-white rounded-lg shadow-sm overflow-hidden <?php echo $isCompleted ? 'result-card' : ''; ?>">
                        <div class="p-4 border-b border-gray-100">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                                <div class="flex items-center mb-2 sm:mb-0">
                                    <i class="far fa-calendar-alt text-gray-400 mr-2"></i>
                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo formatMatchDate($fixture['match_date']); ?>
                                    </span>
                                    <?php if (!empty($fixture['stadium'])): ?>
                                        <span class="hidden sm:inline-block text-sm text-gray-500 ml-3">
                                            <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                                            <?php echo displayText($fixture['stadium']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex space-x-2">
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full 
                                        <?php echo $fixture['gender'] == 'MEN' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                        <?php echo $fixture['gender']; ?>
                                    </span>
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-800">
                                        <?php echo displayText($fixture['competition']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (!empty($fixture['stadium']) && !$isCompleted): ?>
                                <div class="sm:hidden text-xs text-gray-500 mt-1">
                                    <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                                    <?php echo displayText($fixture['stadium']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <!-- Home Team -->
                                <div class="flex items-center w-2/5">
                                    <?php if (!empty($fixture['home_logo'])): ?>
                                        <img src="<?php echo displayText($fixture['home_logo']); ?>" alt="<?php echo displayText($fixture['home_team']); ?>" class="team-logo mr-3">
                                    <?php else: ?>
                                        <div class="team-logo bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold mr-3">
                                            <?php echo substr($fixture['home_team'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="font-medium text-gray-800 team-name" title="<?php echo displayText($fixture['home_team']); ?>">
                                        <?php echo displayText($fixture['home_team']); ?>
                                    </span>
                                </div>
                                
                                <!-- Score -->
                                <div class="text-center mx-2">
                                    <?php if ($isCompleted): ?>
                                        <div class="score-pill bg-rugby-green text-white py-1 px-3 rounded-full inline-block">
                                            <span class="font-bold"><?php echo isset($fixture['home_score']) ? (int)$fixture['home_score'] : '0'; ?></span>
                                            <span class="mx-1">-</span>
                                            <span class="font-bold"><?php echo isset($fixture['away_score']) ? (int)$fixture['away_score'] : '0'; ?></span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">FINAL</div>
                                    <?php else: ?>
                                        <div class="score-pill bg-gray-100 text-gray-700 py-1 px-3 rounded-full inline-block font-medium">
                                            VS
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo $matchDate->format('g:i A'); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Away Team -->
                                <div class="flex items-center justify-end w-2/5">
                                    <span class="font-medium text-gray-800 team-name text-right mr-3" title="<?php echo displayText($fixture['away_team']); ?>">
                                        <?php echo displayText($fixture['away_team']); ?>
                                    </span>
                                    <?php if (!empty($fixture['away_logo'])): ?>
                                        <img src="<?php echo displayText($fixture['away_logo']); ?>" alt="<?php echo displayText($fixture['away_team']); ?>" class="team-logo">
                                    <?php else: ?>
                                        <div class="team-logo bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold">
                                            <?php echo substr($fixture['away_team'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($isCompleted): ?>
                        <div class="px-5 pb-4">
                            <div class="text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Match completed on <?php echo (new DateTime($fixture['match_date']))->format('M j, Y'); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mobile View - Horizontal Layout -->
                    <div class="md:hidden mobile-card">
                        <div class="match-row">
                            <div class="team-col">
                                <?php if (!empty($fixture['home_logo'])): ?>
                                    <img src="<?php echo displayText($fixture['home_logo']); ?>" alt="<?php echo displayText($fixture['home_team']); ?>" class="mobile-logo">
                                <?php else: ?>
                                    <div class="mobile-logo bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold">
                                        <?php echo substr($fixture['home_team'], 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="mobile-team-name"><?php echo displayText($fixture['home_team']); ?></span>
                            </div>
                            
                            <div class="score-col">
                                <?php if ($isCompleted): ?>
                                    <span class="mobile-score text-rugby-green">
                                        <?php echo isset($fixture['home_score']) ? (int)$fixture['home_score'] : '0'; ?>-<?php echo isset($fixture['away_score']) ? (int)$fixture['away_score'] : '0'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="mobile-vs">vs</span>
                                <?php endif; ?>
                                <span class="mobile-date"><?php echo $matchDate->format('g:i A'); ?></span>
                                <span class="mobile-competition"><?php echo displayText($fixture['competition']); ?></span>
                            </div>
                            
                            <div class="team-col">
                                <?php if (!empty($fixture['away_logo'])): ?>
                                    <img src="<?php echo displayText($fixture['away_logo']); ?>" alt="<?php echo displayText($fixture['away_team']); ?>" class="mobile-logo">
                                <?php else: ?>
                                    <div class="mobile-logo bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold">
                                        <?php echo substr($fixture['away_team'], 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="mobile-team-name"><?php echo displayText($fixture['away_team']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Filter functionality
        document.getElementById('applyFilters').addEventListener('click', function() {
            const gender = document.getElementById('genderFilter').value;
            const competition = document.getElementById('competitionFilter').value;
            const season = document.getElementById('seasonFilter').value;
            const tab = '<?php echo $tab; ?>';
            
            window.location.href = `fixtures.php?tab=${tab}&gender=${gender}&competition=${encodeURIComponent(competition)}&season=${season}`;
        });

        // Auto-apply filters when they change
        document.querySelectorAll('#genderFilter, #competitionFilter, #seasonFilter').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('applyFilters').click();
            });
        });
    </script>
</body>
</html>