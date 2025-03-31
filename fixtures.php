<?php
// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current season (assuming current year is the season)
$current_season = date('Y');

// Get all competitions for filter
$competitions = [];
$compResult = $conn->query("SELECT DISTINCT competition FROM fixtures ORDER BY competition");
if ($compResult->num_rows > 0) {
    while ($row = $compResult->fetch_assoc()) {
        $competitions[] = $row['competition'];
    }
}

// Get filter parameters
$selected_season = isset($_GET['season']) ? intval($_GET['season']) : $current_season;
$selected_competition = isset($_GET['competition']) ? $conn->real_escape_string($_GET['competition']) : '';
$selected_gender = isset($_GET['gender']) ? $conn->real_escape_string($_GET['gender']) : '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'fixtures';

// Build query for fixtures
$where = [];
$params = [];
$types = '';

$where[] = "season = ?";
$params[] = $selected_season;
$types .= 'i';

if (!empty($selected_competition)) {
    $where[] = "competition = ?";
    $params[] = $selected_competition;
    $types .= 's';
}

if (!empty($selected_gender)) {
    $where[] = "gender = ?";
    $params[] = $selected_gender;
    $types .= 's';
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get upcoming fixtures (status = SCHEDULED)
$upcoming_fixtures = [];
$upcoming_query = "SELECT * FROM fixtures $where_clause AND status = 'SCHEDULED' ORDER BY match_date ASC";
$stmt = $conn->prepare($upcoming_query);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $upcoming_fixtures[] = $row;
    }
}
$stmt->close();

// Get completed fixtures (status = COMPLETED)
$completed_fixtures = [];
$completed_query = "SELECT * FROM fixtures $where_clause AND status = 'COMPLETED' ORDER BY match_date DESC";
$stmt = $conn->prepare($completed_query);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $completed_fixtures[] = $row;
    }
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixtures & Results - 1000 Hills Rugby</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fixture-card {
            transition: all 0.3s ease;
        }
        .fixture-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .team-logo {
            height: 60px;
            width: 60px;
            object-fit: contain;
        }
        .default-logo {
            height: 60px;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f4f6;
            border-radius: 50%;
            font-weight: bold;
            color: #6b7280;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-button {
            transition: all 0.3s ease;
            position: relative;
        }
        .tab-button.active {
            color: #3b82f6;
        }
        .tab-button.active:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Fixtures & Results</h1>
            
            <!-- Season Selector -->
            <div class="mt-4 md:mt-0">
                <div class="flex items-center space-x-2 bg-white rounded-lg shadow-sm p-2 border border-gray-200">
                    <span class="text-sm font-medium text-gray-600">Season:</span>
                    <select id="season-selector" class="bg-transparent border-none focus:ring-0 text-sm">
                        <?php for ($year = $current_season; $year >= 2000; $year--): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_season ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <form method="GET" class="space-y-4">
                <input type="hidden" name="season" value="<?php echo $selected_season; ?>">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Competition</label>
                        <select name="competition" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Competitions</option>
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?php echo htmlspecialchars($comp); ?>" <?php echo $comp == $selected_competition ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($comp); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select name="gender" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Genders</option>
                            <option value="MEN" <?php echo $selected_gender == 'MEN' ? 'selected' : ''; ?>>Men</option>
                            <option value="WOMEN" <?php echo $selected_gender == 'WOMEN' ? 'selected' : ''; ?>>Women</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabs Navigation -->
        <div class="flex border-b border-gray-200 mb-6">
            <button class="tab-button px-4 py-3 font-medium text-gray-600 hover:text-blue-600 mr-2 <?php echo $active_tab === 'fixtures' ? 'active' : ''; ?>" data-tab="fixtures">
                <i class="far fa-calendar-alt mr-2"></i>Upcoming Fixtures
            </button>
            <button class="tab-button px-4 py-3 font-medium text-gray-600 hover:text-blue-600 <?php echo $active_tab === 'results' ? 'active' : ''; ?>" data-tab="results">
                <i class="far fa-list-alt mr-2"></i>Match Results
            </button>
        </div>
        
        <!-- Tab Contents -->
        <div class="tab-content <?php echo $active_tab === 'fixtures' ? 'active' : ''; ?>" id="fixtures-tab">
            <?php if (empty($upcoming_fixtures)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <div class="mx-auto w-24 h-24 text-gray-400 mb-4">
                        <i class="far fa-calendar-times text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No Upcoming Fixtures</h3>
                    <p class="text-gray-500">There are no scheduled matches for the selected filters.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($upcoming_fixtures as $fixture): ?>
                        <div class="fixture-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md">
                            <div class="p-5 bg-gray-50 border-b">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600">
                                        <?php echo htmlspecialchars($fixture['competition']); ?>
                                    </span>
                                    <span class="text-xs px-2.5 py-1 bg-blue-100 text-blue-800 rounded-full font-medium">
                                        <?php echo htmlspecialchars($fixture['gender']); ?>
                                    </span>
                                </div>
                                <div class="mt-2 text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <i class="far fa-calendar mr-2"></i>
                                        <?php echo date('l, F j, Y', strtotime($fixture['match_date'])); ?>
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <i class="far fa-clock mr-2"></i>
                                        <?php echo date('H:i', strtotime($fixture['match_date'])); ?>
                                        <?php if (!empty($fixture['stadium'])): ?>
                                            <span class="ml-3"><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($fixture['stadium']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <?php if (!empty($fixture['home_logo'])): ?>
                                            <img src="logos_/<?php echo htmlspecialchars($fixture['home_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['home_team']); ?>" class="team-logo">
                                        <?php else: ?>
                                            <div class="default-logo">
                                                <?php echo substr($fixture['home_team'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="font-medium"><?php echo htmlspecialchars($fixture['home_team']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="text-center my-3">
                                    <span class="inline-block px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">VS</span>
                                </div>
                                
                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex items-center space-x-3">
                                        <?php if (!empty($fixture['away_logo'])): ?>
                                            <img src="logos_/<?php echo htmlspecialchars($fixture['away_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['away_team']); ?>" class="team-logo">
                                        <?php else: ?>
                                            <div class="default-logo">
                                                <?php echo substr($fixture['away_team'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="font-medium"><?php echo htmlspecialchars($fixture['away_team']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content <?php echo $active_tab === 'results' ? 'active' : ''; ?>" id="results-tab">
            <?php if (empty($completed_fixtures)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <div class="mx-auto w-24 h-24 text-gray-400 mb-4">
                        <i class="far fa-frown text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No Results Found</h3>
                    <p class="text-gray-500">There are no completed matches for the selected filters.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Competition</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($completed_fixtures as $fixture): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($fixture['match_date'])); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($fixture['match_date'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="text-xs px-2.5 py-1 bg-blue-100 text-blue-800 rounded-full font-medium mr-2">
                                                    <?php echo htmlspecialchars($fixture['gender']); ?>
                                                </span>
                                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($fixture['competition']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                    <?php if (!empty($fixture['home_logo'])): ?>
                                                        <img src="logos_/<?php echo htmlspecialchars($fixture['home_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['home_team']); ?>" class="h-10 w-10 rounded-full">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center font-medium">
                                                            <?php echo substr($fixture['home_team'], 0, 1); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fixture['home_team']); ?></div>
                                            </div>
                                            <div class="flex items-center mt-2">
                                                <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                    <?php if (!empty($fixture['away_logo'])): ?>
                                                        <img src="logos_/<?php echo htmlspecialchars($fixture['away_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['away_team']); ?>" class="h-10 w-10 rounded-full">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center font-medium">
                                                            <?php echo substr($fixture['away_team'], 0, 1); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fixture['away_team']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-2xl font-bold text-gray-900 text-center">
                                                <?php echo $fixture['home_score']; ?> - <?php echo $fixture['away_score']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($fixture['stadium']); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                
                // Update active tab button
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                button.classList.add('active');
                
                // Update active tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(`${tabId}-tab`).classList.add('active');
                
                // Update URL parameter
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            });
        });
        
        // Season selector functionality
        document.getElementById('season-selector').addEventListener('change', function() {
            const season = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('season', season);
            window.location.href = url.toString();
        });
    </script>
</body>
</html>