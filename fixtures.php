<?php
// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter parameters with sanitization
$gender = isset($_GET['gender']) ? $conn->real_escape_string($_GET['gender']) : 'MEN';
$competition = isset($_GET['competition']) ? $conn->real_escape_string($_GET['competition']) : 'ALL';
$season = isset($_GET['season']) ? intval($_GET['season']) : date('Y');
$tab = isset($_GET['tab']) ? $conn->real_escape_string($_GET['tab']) : 'fixtures';

// Get all competitions for dropdown
$competitions = [];
$compResult = $conn->query("SELECT DISTINCT competition FROM fixtures WHERE season = $season ORDER BY competition");
if ($compResult->num_rows > 0) {
    while ($row = $compResult->fetch_assoc()) {
        $competitions[] = stripslashes($row['competition']);
    }
}

// Build SQL query
if ($tab === 'results') {
    $sql = "SELECT * FROM fixtures WHERE status = 'COMPLETED' AND season = ? 
            AND NOT (home_score = 0 AND away_score = 0)";
} else {
    $sql = "SELECT * FROM fixtures WHERE season = ? 
            AND (status != 'COMPLETED' OR (home_score = 0 AND away_score = 0))";
}

$params = [$season];
$types = "i"; // season is integer

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

$sql .= " ORDER BY match_date " . ($tab === 'results' ? "DESC" : "ASC");

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $fixtures = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($fixtures as &$fixture) {
        $fixture['competition'] = stripslashes($fixture['competition']);
    }
    
    $stmt->close();
} else {
    $fixtures = [];
    error_log("SQL Error: " . $conn->error);
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
        .filter-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        @media (max-width: 767px) {
            .mobile-match-row {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                border-bottom: 1px solid #e5e7eb;
            }
            .mobile-team-col {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .mobile-score-col {
                flex: 0 0 auto;
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 8px;
            }
            .mobile-team-name {
                font-size: 14px;
                font-weight: 500;
                margin-top: 4px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 120px;
                text-align: center;
            }
            .mobile-logo {
                width: 32px;
                height: 32px;
            }
            .mobile-score {
                font-size: 16px;
                font-weight: bold;
                color: #1a5632;
            }
            .mobile-vs {
                font-size: 14px;
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
                margin-top: 2px;
            }
            .mobile-card {
                border-radius: 8px;
                margin-bottom: 8px;
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-green-800 to-green-900 shadow-md">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="./" class="flex items-center">
                        <img src="./logos_/logoT.jpg" alt="Club Logo" class="h-12 rounded-full border-2 border-white shadow-md">
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="fixtures.php?tab=fixtures" class="text-white font-medium text-sm uppercase tracking-wider py-4 hover:text-green-200 <?php echo $tab === 'fixtures' ? 'font-bold' : ''; ?>">
                        <i class="fas fa-calendar-alt mr-2"></i>Fixtures
                    </a>
                    <a href="fixtures.php?tab=results" class="text-white font-medium text-sm uppercase tracking-wider py-4 hover:text-green-200 <?php echo $tab === 'results' ? 'font-bold' : ''; ?>">
                        <i class="fas fa-list-ol mr-2"></i>Results
                    </a>
                    <a href="tables.php" class="text-white font-medium text-sm uppercase tracking-wider py-4 hover:text-green-200">
                        <i class="fas fa-table mr-2"></i>Tables
                    </a>
                </nav>
                
                <button id="mobile-menu-button" class="md:hidden text-white focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-gradient-to-r from-green-800 to-green-900 py-2 px-4 shadow-lg">
            <a href="fixtures.php?tab=fixtures" class="block py-3 px-4 text-white rounded-md hover:bg-green-700 <?php echo $tab === 'fixtures' ? 'bg-green-600 font-bold' : ''; ?>">
                <i class="fas fa-calendar-alt mr-3"></i>Fixtures
            </a>
            <a href="fixtures.php?tab=results" class="block py-3 px-4 text-white rounded-md hover:bg-green-700 <?php echo $tab === 'results' ? 'bg-green-600 font-bold' : ''; ?>">
                <i class="fas fa-list-ol mr-3"></i>Results
            </a>
            <a href="tables.php" class="block py-3 px-4 text-white rounded-md hover:bg-green-700">
                <i class="fas fa-table mr-3"></i>Tables
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
        <!-- Page Title -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <?php echo $tab === 'results' ? 'Match Results' : 'Upcoming Fixtures'; ?>
            </h1>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3 border border-gray-100">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select id="genderFilter" class="filter-select w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-600 focus:border-green-600 text-sm">
                    <option value="ALL" <?php echo ($gender == 'ALL') ? 'selected' : ''; ?>>All Genders</option>
                    <option value="MEN" <?php echo ($gender == 'MEN') ? 'selected' : ''; ?>>Men</option>
                    <option value="WOMEN" <?php echo ($gender == 'WOMEN') ? 'selected' : ''; ?>>Women</option>
                </select>
            </div>
            
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Competition</label>
                <select id="competitionFilter" class="filter-select w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-600 focus:border-green-600 text-sm">
                    <option value="ALL" <?php echo ($competition == 'ALL') ? 'selected' : ''; ?>>All Competitions</option>
                    <?php foreach ($competitions as $comp): ?>
                        <option value="<?php echo displayText($comp); ?>" <?php echo ($competition == $comp) ? 'selected' : ''; ?>>
                            <?php echo displayText($comp); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Season</label>
                <select id="seasonFilter" class="filter-select w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-600 focus:border-green-600 text-sm">
                    <?php 
                    $currentYear = date('Y');
                    for ($year = $currentYear; $year >= 2014; $year--) {
                        $selected = ($season == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Matches List -->
        <div class="space-y-3">
            <?php if (empty($fixtures)): ?>
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-calendar-times text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-2">
                        <?php echo $tab === 'results' ? 'No match results found' : 'No upcoming fixtures scheduled'; ?>
                    </h3>
                    <p class="text-gray-500">
                        Try adjusting your filters or check back later for updates.
                    </p>
                </div>
            <?php else: ?>
                <?php 
                $currentMonth = '';
                foreach ($fixtures as $fixture): 
                    $matchDate = new DateTime($fixture['match_date']);
                    $month = $matchDate->format('F Y');
                    $isCompleted = $fixture['status'] == 'COMPLETED';
                    $isZeroZero = ($fixture['home_score'] == 0 && $fixture['away_score'] == 0);
                    
                    if ($month != $currentMonth) {
                        $currentMonth = $month;
                        echo "<h3 class='text-lg font-semibold text-gray-700 mb-2 mt-4'>$currentMonth</h3>";
                    }
                ?>
                    <!-- Mobile View -->
                    <div class="md:hidden mobile-card">
                        <div class="mobile-match-row">
                            <!-- Home Team -->
                            <div class="mobile-team-col">
                                <?php if (!empty($fixture['home_logo'])): ?>
                                    <img src="<?php echo displayText($fixture['home_logo']); ?>" alt="<?php echo displayText($fixture['home_team']); ?>" class="mobile-logo">
                                <?php else: ?>
                                    <div class="mobile-logo bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold">
                                        <?php echo substr($fixture['home_team'], 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="mobile-team-name"><?php echo displayText($fixture['home_team']); ?></span>
                            </div>
                            
                            <!-- Score -->
                            <div class="mobile-score-col">
                                <?php if ($isCompleted && !$isZeroZero): ?>
                                    <span class="mobile-score"><?php echo (int)$fixture['home_score']; ?></span>
                                    <span class="text-gray-400">-</span>
                                    <span class="mobile-score"><?php echo (int)$fixture['away_score']; ?></span>
                                <?php else: ?>
                                    <span class="mobile-vs">vs</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Away Team -->
                            <div class="mobile-team-col">
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
                        
                        <div class="px-4 pb-3 pt-1">
                            <div class="flex justify-between items-center text-xs">
                                <span class="mobile-date"><?php echo $matchDate->format('D, M j - g:i A'); ?></span>
                                <span class="mobile-competition"><?php echo displayText($fixture['competition']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop View -->
                    <div class="hidden md:block fixture-card bg-white rounded-lg shadow-sm overflow-hidden <?php echo $isCompleted ? 'result-card' : ''; ?>">
                        <div class="p-4 border-b border-gray-100">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <i class="far fa-calendar-alt text-gray-400 mr-2"></i>
                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo formatMatchDate($fixture['match_date']); ?>
                                    </span>
                                    <?php if (!empty($fixture['stadium'])): ?>
                                        <span class="text-sm text-gray-500 ml-3">
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
                                    <span class="font-medium text-gray-800"><?php echo displayText($fixture['home_team']); ?></span>
                                </div>
                                
                                <!-- Score -->
                                <div class="text-center mx-2">
                                    <?php if ($isCompleted && !$isZeroZero): ?>
                                        <div class="score-pill bg-green-700 text-white py-1 px-3 rounded-full inline-block">
                                            <span class="font-bold"><?php echo (int)$fixture['home_score']; ?></span>
                                            <span class="mx-1">-</span>
                                            <span class="font-bold"><?php echo (int)$fixture['away_score']; ?></span>
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
                                    <span class="font-medium text-gray-800 mr-3"><?php echo displayText($fixture['away_team']); ?></span>
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
        document.querySelectorAll('#genderFilter, #competitionFilter, #seasonFilter').forEach(select => {
            select.addEventListener('change', function() {
                const gender = document.getElementById('genderFilter').value;
                const competition = document.getElementById('competitionFilter').value;
                const season = document.getElementById('seasonFilter').value;
                const tab = '<?php echo $tab; ?>';
                
                window.location.href = `fixtures.php?tab=${tab}&gender=${gender}&competition=${encodeURIComponent(competition)}&season=${season}`;
            });
        });
    </script>
</body>
</html>