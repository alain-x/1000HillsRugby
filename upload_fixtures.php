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
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Fixtures & Results</h1>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Filter Fixtures</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Season</label>
                    <select name="season" class="w-full p-2 border border-gray-300 rounded-md">
                        <?php for ($year = $current_season; $year >= 2000; $year--): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_season ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Competition</label>
                    <select name="competition" class="w-full p-2 border border-gray-300 rounded-md">
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
                    <select name="gender" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="">All Genders</option>
                        <option value="MEN" <?php echo $selected_gender == 'MEN' ? 'selected' : ''; ?>>Men</option>
                        <option value="WOMEN" <?php echo $selected_gender == 'WOMEN' ? 'selected' : ''; ?>>Women</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Upcoming Fixtures -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <h2 class="text-xl font-semibold p-4 border-b">Upcoming Fixtures</h2>
            
            <?php if (empty($upcoming_fixtures)): ?>
                <div class="p-6 text-center text-gray-500">
                    No upcoming fixtures found for the selected filters.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    <?php foreach ($upcoming_fixtures as $fixture): ?>
                        <div class="fixture-card bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="p-4 bg-gray-50 border-b">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600">
                                        <?php echo htmlspecialchars($fixture['competition']); ?>
                                    </span>
                                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                        <?php echo htmlspecialchars($fixture['gender']); ?>
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-gray-500">
                                    <?php echo date('l, F j, Y', strtotime($fixture['match_date'])); ?>
                                    <br>
                                    <?php echo date('H:i', strtotime($fixture['match_date'])); ?> at <?php echo htmlspecialchars($fixture['stadium']); ?>
                                </div>
                            </div>
                            
                            <div class="p-4">
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
                                
                                <div class="text-center my-2 text-sm text-gray-500 font-medium">VS</div>
                                
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
        
        <!-- Completed Fixtures (Results) -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <h2 class="text-xl font-semibold p-4 border-b">Match Results</h2>
            
            <?php if (empty($completed_fixtures)): ?>
                <div class="p-6 text-center text-gray-500">
                    No results found for the selected filters.
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($completed_fixtures as $fixture): ?>
                        <div class="fixture-card hover:bg-gray-50">
                            <div class="p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-600">
                                        <?php echo htmlspecialchars($fixture['competition']); ?>
                                    </span>
                                    <div class="flex space-x-2">
                                        <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                            <?php echo htmlspecialchars($fixture['gender']); ?>
                                        </span>
                                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-800 rounded-full">
                                            <?php echo date('M j, Y', strtotime($fixture['match_date'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 flex-1">
                                        <?php if (!empty($fixture['home_logo'])): ?>
                                            <img src="logos_/<?php echo htmlspecialchars($fixture['home_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['home_team']); ?>" class="team-logo">
                                        <?php else: ?>
                                            <div class="default-logo">
                                                <?php echo substr($fixture['home_team'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="font-medium"><?php echo htmlspecialchars($fixture['home_team']); ?></span>
                                    </div>
                                    
                                    <div class="px-4">
                                        <span class="font-bold text-lg">
                                            <?php echo $fixture['home_score']; ?> - <?php echo $fixture['away_score']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3 flex-1 justify-end">
                                        <span class="font-medium"><?php echo htmlspecialchars($fixture['away_team']); ?></span>
                                        <?php if (!empty($fixture['away_logo'])): ?>
                                            <img src="logos_/<?php echo htmlspecialchars($fixture['away_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['away_team']); ?>" class="team-logo">
                                        <?php else: ?>
                                            <div class="default-logo">
                                                <?php echo substr($fixture['away_team'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($fixture['stadium'])): ?>
                                    <div class="mt-2 text-sm text-gray-500 text-right">
                                        Played at <?php echo htmlspecialchars($fixture['stadium']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>