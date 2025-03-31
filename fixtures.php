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

// Get all competitions for dropdown
$competitions = [];
$compResult = $conn->query("SELECT DISTINCT competition FROM fixtures ORDER BY competition");
if ($compResult->num_rows > 0) {
    while ($row = $compResult->fetch_assoc()) {
        $competitions[] = stripslashes($row['competition']);
    }
}

// Build SQL query
$sql = "SELECT * FROM fixtures WHERE season = ?";
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

$sql .= " ORDER BY match_date ASC";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Fixtures - 1000 Hills Rugby Club</title>
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
            opacity: 0.8;
            background-color: #f8f8f8;
        }
        .team-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        @media (min-width: 640px) {
            .team-name {
                max-width: 180px;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="fixtures.php" class="flex items-center">
                <img src="./logos_/logoT.jpg" alt="Club Logo" class="h-12">
                <span class="ml-2 text-xl font-bold text-gray-800">1000 Hills Rugby</span>
            </a>
            
            <nav class="hidden md:flex space-x-6">
                <a href="fixtures.php" class="text-green-600 font-bold border-b-2 border-green-600 pb-1">Fixtures</a>
                <a href="upload_fixtures.php" class="text-gray-600 hover:text-green-600 font-bold">Manage Fixtures</a>
            </nav>
            
            <button id="mobile-menu-button" class="md:hidden text-gray-600">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white py-2 px-4 shadow-lg">
            <a href="fixtures.php" class="block py-2 text-green-600 font-bold">Fixtures</a>
            <a href="upload_fixtures.php" class="block py-2 text-gray-600 hover:text-green-600 font-bold">Manage Fixtures</a>
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

        <!-- Fixtures List -->
        <div class="space-y-6">
            <?php if (empty($fixtures)): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">No Fixtures Found</h3>
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
                                        <?php echo $matchDate->format('D, M j - g:i A'); ?>
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
                                <div class="text-center w-1/5">
                                    <?php if ($isCompleted): ?>
                                        <div class="text-2xl font-bold">
                                            <span><?php echo $fixture['home_score']; ?></span>
                                            <span class="mx-2">-</span>
                                            <span><?php echo $fixture['away_score']; ?></span>
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
            
            window.location.href = `fixtures.php?gender=${gender}&competition=${encodeURIComponent(competition)}&season=${season}`;
        });
    </script>
</body>
</html>