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
if ($tab === 'results') {
    // Query for completed games only
    $sql = "SELECT * FROM fixtures WHERE status = 'COMPLETED' AND season = ?";
} else {
    // Query for upcoming fixtures only (not completed)
    $sql = "SELECT * FROM fixtures WHERE status != 'COMPLETED' AND season = ?";
}

$params = [$season];
$types = "s";

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
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $fixtures = $result->fetch_all(MYSQLI_ASSOC);
    
    // Remove slashes from competition names in fixtures
    foreach ($fixtures as &$fixture) {
        $fixture['competition'] = stripslashes($fixture['competition']);
    }
    
    $stmt->close();
} else {
    $fixtures = [];
}

$conn->close();

// Helper function to safely display text
function displayText($text) {
    return htmlspecialchars(stripslashes($text));
}

// Function to format match date
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
    <title><?php echo $tab === 'results' ? 'Match Results' : 'Match Fixtures'; ?> - 1000 Hills Rugby Club</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gray-icon {
            width: 40px;
            height: 40px;
            background-color: #ccc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
        }
        .fixture-card {
            transition: all 0.3s ease;
        }
        .fixture-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .completed {
            background-color: #f8f8f8;
        }
        .team-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        .tab-active {
            border-bottom: 2px solid #059669;
            color: #059669;
            font-weight: bold;
        }
        .score {
            min-width: 80px;
        }
        @media (min-width: 640px) {
            .team-name {
                max-width: 180px;
            }
        }
        /* New navigation styles */
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
        /* Adjusted score size for mobile */
        @media (max-width: 639px) {
            .score {
                font-size: 1.25rem; /* Reduced from 2xl to xl */
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- New Professional Header -->
    <header class="nav-container">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="./" class="flex items-center">
                        <img src="./logos_/logoT.jpg" alt="Club Logo" class="h-12 rounded-full border-2 border-white shadow-md">
                        <span class="ml-3 text-xl font-bold text-white">1000 Hills Rugby</span>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="fixtures.php?tab=fixtures" class="nav-item <?php echo $tab === 'fixtures' ? 'active' : ''; ?> font-medium text-sm uppercase tracking-wider py-4">
                        <i class="fas fa-calendar-alt mr-2"></i>Fixtures
                    </a>
                    <a href="fixtures.php?tab=results" class="nav-item <?php echo $tab === 'results' ? 'active' : ''; ?> font-medium text-sm uppercase tracking-wider py-4">
                        <i class="fas fa-list-ol mr-2"></i>Results
                    </a>
                    <a href="tables.php" class="nav-item font-medium text-sm uppercase tracking-wider py-4">
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
            <a href="fixtures.php?tab=fixtures" class="block py-3 px-4 mobile-nav-item <?php echo $tab === 'fixtures' ? 'active' : ''; ?> rounded-md">
                <i class="fas fa-calendar-alt mr-3"></i>Fixtures
            </a>
            <a href="fixtures.php?tab=results" class="block py-3 px-4 mobile-nav-item <?php echo $tab === 'results' ? 'active' : ''; ?> rounded-md">
                <i class="fas fa-list-ol mr-3"></i>Results
            </a>
            <a href="tables.php" class="block py-3 px-4 mobile-nav-item rounded-md">
                <i class="fas fa-table mr-3"></i>League Tables
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select id="genderFilter" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                    <option value="ALL" <?php echo ($gender == 'ALL') ? 'selected' : ''; ?>>All Genders</option>
                    <option value="MEN" <?php echo ($gender == 'MEN') ? 'selected' : ''; ?>>Men</option>
                    <option value="WOMEN" <?php echo ($gender == 'WOMEN') ? 'selected' : ''; ?>>Women</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Competition</label>
                <select id="competitionFilter" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
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
                <select id="seasonFilter" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                    <?php 
                    $currentYear = date('Y');
                    for ($year = $currentYear; $year >= 2020; $year--) {
                        $selected = ($season == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button id="applyFilters" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Results/Fixtures List -->
        <div class="space-y-6">
            <?php if (empty($fixtures)): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">
                        <?php echo $tab === 'results' ? 'No Results Found' : 'No Fixtures Found'; ?>
                    </h3>
                    <p class="text-gray-500">Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <?php 
                $currentMonth = '';
                foreach ($fixtures as $fixture): 
                    $matchDate = new DateTime($fixture['match_date']);
                    $month = $matchDate->format('F Y');
                    
                    if ($month != $currentMonth) {
                        $currentMonth = $month;
                        echo "<h2 class='text-2xl font-bold text-gray-800 mb-4'>$currentMonth</h2>";
                    }
                    
                    $isCompleted = $fixture['status'] == 'COMPLETED';
                ?>
                    <div class="fixture-card bg-white rounded-lg shadow-md overflow-hidden <?php echo $isCompleted ? 'completed' : ''; ?>">
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-sm font-medium text-gray-600">
                                        <?php echo formatMatchDate($fixture['match_date']); ?>
                                    </span>
                                    <?php if (!empty($fixture['stadium'])): ?>
                                        <span class="text-sm text-gray-500 ml-2">• <?php echo displayText($fixture['stadium']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xs font-medium px-2 py-1 rounded-full 
                                        <?php echo $fixture['gender'] == 'MEN' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                        <?php echo $fixture['gender']; ?>
                                    </span>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-800 ml-2">
                                        <?php echo displayText($fixture['competition']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <!-- Home Team -->
                                <div class="flex items-center w-2/5">
                                    <?php if (!empty($fixture['home_logo'])): ?>
                                        <img src="<?php echo displayText($fixture['home_logo']); ?>" alt="<?php echo displayText($fixture['home_team']); ?>" class="h-12 w-12 object-contain">
                                    <?php else: ?>
                                        <div class="gray-icon"><?php echo substr($fixture['home_team'], 0, 1); ?></div>
                                    <?php endif; ?>
                                    <span class="ml-3 font-medium team-name" title="<?php echo displayText($fixture['home_team']); ?>">
                                        <?php echo displayText($fixture['home_team']); ?>
                                    </span>
                                </div>
                                
                                <!-- Score -->
                                <div class="text-center score">
                                    <?php if ($isCompleted): ?>
                                        <div class="text-2xl md:text-3xl font-bold">
                                            <span><?php echo $fixture['home_score'] !== null ? $fixture['home_score'] : '0'; ?></span>
                                            <span class="mx-2">-</span>
                                            <span><?php echo $fixture['away_score'] !== null ? $fixture['away_score'] : '0'; ?></span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">FINAL</div>
                                    <?php else: ?>
                                        <div class="text-xl font-bold text-gray-500">VS</div>
                                        <div class="text-xs text-gray-500 mt-1">UPCOMING</div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Away Team -->
                                <div class="flex items-center justify-end w-2/5">
                                    <span class="mr-3 font-medium team-name" title="<?php echo displayText($fixture['away_team']); ?>">
                                        <?php echo displayText($fixture['away_team']); ?>
                                    </span>
                                    <?php if (!empty($fixture['away_logo'])): ?>
                                        <img src="<?php echo displayText($fixture['away_logo']); ?>" alt="<?php echo displayText($fixture['away_team']); ?>" class="h-12 w-12 object-contain">
                                    <?php else: ?>
                                        <div class="gray-icon"><?php echo substr($fixture['away_team'], 0, 1); ?></div>
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
        document.getElementById('applyFilters').addEventListener('click', function() {
            const gender = document.getElementById('genderFilter').value;
            const competition = document.getElementById('competitionFilter').value;
            const season = document.getElementById('seasonFilter').value;
            const tab = '<?php echo $tab; ?>';
            
            window.location.href = `fixtures.php?tab=${tab}&gender=${gender}&competition=${encodeURIComponent(competition)}&season=${season}`;
        });
    </script>
</body>
</html>