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
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixtures & Results | 1000 Hills Rugby Club</title>
    <meta name="description" content="View upcoming matches and results for 1000 Hills Rugby Club teams">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Montserrat', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            DEFAULT: '#1D4ED8',
                            50: '#EFF6FF',
                            100: '#DBEAFE',
                            200: '#BFDBFE',
                            300: '#93C5FD',
                            400: '#60A5FA',
                            500: '#3B82F6',
                            600: '#2563EB',
                            700: '#1D4ED8',
                            800: '#1E40AF',
                            900: '#1E3A8A',
                        },
                        secondary: {
                            DEFAULT: '#047857',
                            50: '#ECFDF5',
                            100: '#D1FAE5',
                            200: '#A7F3D0',
                            300: '#6EE7B7',
                            400: '#34D399',
                            500: '#10B981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065F46',
                            900: '#064E3B',
                        },
                        dark: {
                            DEFAULT: '#111827',
                            50: '#F9FAFB',
                            100: '#F3F4F6',
                            200: '#E5E7EB',
                            300: '#D1D5DB',
                            400: '#9CA3AF',
                            500: '#6B7280',
                            600: '#4B5563',
                            700: '#374151',
                            800: '#1F2937',
                            900: '#111827',
                        }
                    },
                    boxShadow: {
                        card: '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02)',
                        'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --header-height: 80px;
        }
        
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .fixture-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .fixture-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--tw-shadow-card-hover);
        }
        
        .team-logo {
            height: 72px;
            width: 72px;
            object-fit: contain;
        }
        
        .default-logo {
            height: 72px;
            width: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: theme('colors.dark.100');
            border-radius: theme('borderRadius.lg');
            font-weight: theme('fontWeight.bold');
            color: theme('colors.dark.400');
            font-family: theme('fontFamily.heading');
            font-size: theme('fontSize.2xl');
        }
        
        .result-row {
            transition: background-color 0.2s ease;
        }
        
        .result-row:hover {
            background-color: theme('colors.primary.50');
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: theme('spacing.1') theme('spacing.2');
            border-radius: theme('borderRadius.full');
            font-size: theme('fontSize.xs');
            font-weight: theme('fontWeight.medium');
            line-height: 1;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-dark-800">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <img src="/assets/logo.svg" alt="1000 Hills Rugby Club Logo" class="h-10 w-auto">
                <h1 class="text-xl font-heading font-bold text-dark-900">1000 Hills Rugby</h1>
            </div>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="#" class="text-primary-700 font-medium">Fixtures</a>
                <a href="#" class="text-dark-500 hover:text-primary-600 transition-colors">Teams</a>
                <a href="#" class="text-dark-500 hover:text-primary-600 transition-colors">News</a>
                <a href="#" class="text-dark-500 hover:text-primary-600 transition-colors">Gallery</a>
                <a href="#" class="text-dark-500 hover:text-primary-600 transition-colors">About</a>
            </nav>
            <button class="md:hidden text-dark-500">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-[calc(100vh-var(--header-height))]">
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-heading font-bold text-dark-900 mb-2">Fixtures & Results</h1>
                    <p class="text-dark-500">Stay updated with all upcoming matches and recent results</p>
                </div>
                
                <!-- Season Selector -->
                <div class="mt-4 md:mt-0">
                    <form method="GET" class="flex items-center space-x-2 bg-white rounded-lg shadow-sm p-2 border border-gray-200">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                        <input type="hidden" name="competition" value="<?= htmlspecialchars($selected_competition) ?>">
                        <input type="hidden" name="gender" value="<?= htmlspecialchars($selected_gender) ?>">
                        <span class="text-sm font-medium text-dark-600">Season:</span>
                        <select name="season" onchange="this.form.submit()" class="bg-transparent border-none focus:ring-0 text-sm font-medium text-primary-700">
                            <?php for ($year = $current_season; $year >= 2000; $year--): ?>
                                <option value="<?= $year ?>" <?= $year == $selected_season ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <!-- Error Message -->
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500 mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">We encountered an error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p><?= htmlspecialchars($error_message) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <form method="GET" class="space-y-4">
                    <input type="hidden" name="season" value="<?= htmlspecialchars($selected_season) ?>">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-dark-700 mb-2">Competition</label>
                            <div class="relative">
                                <select name="competition" class="w-full pl-3 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 appearance-none bg-white bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjdHJpbWJsZS1kYXJrLTYwMCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGNsYXNzPSJsdWNpZGUgbHVjaWRlLWNoZXZyb24tZG93biI+PHBhdGggZD0ibTYgOSA2IDYgNi02Ii8+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem]">
                                    <option value="">All Competitions</option>
                                    <?php foreach ($competitions as $comp): ?>
                                        <option value="<?= htmlspecialchars($comp) ?>" <?= $comp == $selected_competition ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($comp) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-dark-700 mb-2">Gender</label>
                            <div class="relative">
                                <select name="gender" class="w-full pl-3 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 appearance-none bg-white bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjdHJpbWJsZS1kYXJrLTYwMCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGNsYXNzPSJsdWNpZGUgbHVjaWRlLWNoZXZyb24tZG93biI+PHBhdGggZD0ibTYgOSA2IDYgNi02Ii8+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem]">
                                    <option value="">All Genders</option>
                                    <option value="MEN" <?= $selected_gender == 'MEN' ? 'selected' : '' ?>>Men's</option>
                                    <option value="WOMEN" <?= $selected_gender == 'WOMEN' ? 'selected' : '' ?>>Women's</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-dark-700 mb-2">Status</label>
                            <div class="relative">
                                <select class="w-full pl-3 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 appearance-none bg-white bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjdHJpbWJsZS1kYXJrLTYwMCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGNsYXNzPSJsdWNpZGUgbHVjaWRlLWNoZXZyb24tZG93biI+PHBhdGggZD0ibTYgOSA2IDYgNi02Ii8+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem]">
                                    <option>All Statuses</option>
                                    <option>Upcoming</option>
                                    <option>Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="w-full px-4 py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 flex items-center justify-center">
                                <i class="fas fa-filter mr-2"></i>
                                Apply Filters
                            </button>
                            <button type="reset" class="px-4 py-3 border border-gray-300 text-dark-600 font-medium rounded-lg hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 mb-6">
                <button class="tab-button px-6 py-3 font-medium text-dark-500 hover:text-primary-600 transition-colors mr-1 <?= $active_tab === 'fixtures' ? 'active text-primary-700 border-b-2 border-primary-600' : '' ?>" data-tab="fixtures">
                    <i class="far fa-calendar-alt mr-2"></i>Upcoming Fixtures
                </button>
                <button class="tab-button px-6 py-3 font-medium text-dark-500 hover:text-primary-600 transition-colors <?= $active_tab === 'results' ? 'active text-primary-700 border-b-2 border-primary-600' : '' ?>" data-tab="results">
                    <i class="far fa-list-alt mr-2"></i>Match Results
                </button>
            </div>
            
            <!-- Tab Contents -->
            <div class="tab-content <?= $active_tab === 'fixtures' ? 'active' : '' ?>" id="fixtures-tab">
                <?php if (empty($upcoming_fixtures)): ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="mx-auto w-24 h-24 text-gray-300 mb-4">
                            <i class="far fa-calendar-times text-7xl"></i>
                        </div>
                        <h3 class="text-xl font-heading font-medium text-dark-900 mb-2">No Upcoming Fixtures</h3>
                        <p class="text-dark-500 max-w-md mx-auto mb-6">There are currently no scheduled matches for the selected filters. Please check back later or try different filters.</p>
                        <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            View Full Schedule
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Fixtures Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($upcoming_fixtures as $fixture): ?>
                            <div class="fixture-card bg-white rounded-xl shadow-card border border-gray-200 overflow-hidden hover:shadow-card-hover">
                                <!-- Match Header -->
                                <div class="p-5 bg-gradient-to-r from-primary-50 to-primary-100 border-b border-primary-200">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-primary-800">
                                            <?= htmlspecialchars($fixture['competition']) ?>
                                        </span>
                                        <span class="badge bg-primary-100 text-primary-800">
                                            <?= htmlspecialchars($fixture['gender']) ?>
                                        </span>
                                    </div>
                                    <div class="mt-3 text-sm text-primary-700">
                                        <div class="flex items-center">
                                            <i class="far fa-calendar mr-2"></i>
                                            <?= date('l, F j, Y', strtotime($fixture['match_date'])) ?>
                                        </div>
                                        <div class="flex items-center mt-2">
                                            <i class="far fa-clock mr-2"></i>
                                            <?= date('H:i', strtotime($fixture['match_date'])) ?>
                                            <?php if (!empty($fixture['stadium'])): ?>
                                                <span class="ml-4">
                                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                                    <?= htmlspecialchars($fixture['stadium']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Teams -->
                                <div class="p-5">
                                    <!-- Home Team -->
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="flex items-center space-x-4">
                                            <?php if (!empty($fixture['home_logo'])): ?>
                                                <img src="/logos/<?= htmlspecialchars($fixture['home_logo']) ?>" alt="<?= htmlspecialchars($fixture['home_team']) ?>" class="team-logo">
                                            <?php else: ?>
                                                <div class="default-logo">
                                                    <?= substr($fixture['home_team'], 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="font-medium text-lg text-dark-800"><?= htmlspecialchars($fixture['home_team']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- VS Divider -->
                                    <div class="relative my-4">
                                        <div class="absolute inset-0 flex items-center">
                                            <div class="w-full border-t border-gray-200"></div>
                                        </div>
                                        <div class="relative flex justify-center">
                                            <span class="px-3 bg-white text-sm font-medium text-dark-500">VS</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Away Team -->
                                    <div class="flex items-center justify-between mt-6">
                                        <div class="flex items-center space-x-4">
                                            <?php if (!empty($fixture['away_logo'])): ?>
                                                <img src="/logos/<?= htmlspecialchars($fixture['away_logo']) ?>" alt="<?= htmlspecialchars($fixture['away_team']) ?>" class="team-logo">
                                            <?php else: ?>
                                                <div class="default-logo">
                                                    <?= substr($fixture['away_team'], 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="font-medium text-lg text-dark-800"><?= htmlspecialchars($fixture['away_team']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Match Footer -->
                                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                                    <span class="text-sm text-dark-500">
                                        <i class="fas fa-ticket-alt mr-1"></i> Tickets available
                                    </span>
                                    <button class="text-sm font-medium text-primary-700 hover:text-primary-800 flex items-center">
                                        More info <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content <?= $active_tab === 'results' ? 'active' : '' ?>" id="results-tab">
                <?php if (empty($completed_fixtures)): ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="mx-auto w-24 h-24 text-gray-300 mb-4">
                            <i class="far fa-frown text-7xl"></i>
                        </div>
                        <h3 class="text-xl font-heading font-medium text-dark-900 mb-2">No Results Found</h3>
                        <p class="text-dark-500 max-w-md mx-auto mb-6">There are currently no completed matches for the selected filters. Please try different filters or check back later.</p>
                        <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            View Previous Seasons
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Results Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dark-500 uppercase tracking-wider">Date & Time</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dark-500 uppercase tracking-wider">Competition</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dark-500 uppercase tracking-wider">Match</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dark-500 uppercase tracking-wider">Score</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dark-500 uppercase tracking-wider">Venue</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-dark-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($completed_fixtures as $fixture): ?>
                                        <tr class="result-row">
                                            <!-- Date & Time -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-dark-900"><?= date('M j, Y', strtotime($fixture['match_date'])) ?></div>
                                                <div class="text-sm text-dark-500"><?= date('H:i', strtotime($fixture['match_date'])) ?></div>
                                            </td>
                                            
                                            <!-- Competition -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <span class="badge bg-blue-100 text-blue-800 mr-2">
                                                        <?= htmlspecialchars($fixture['gender']) ?>
                                                    </span>
                                                    <span class="text-sm font-medium text-dark-900"><?= htmlspecialchars($fixture['competition']) ?></span>
                                                </div>
                                            </td>
                                            
                                            <!-- Match -->
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                        <?php if (!empty($fixture['home_logo'])): ?>
                                                            <img src="/logos/<?= htmlspecialchars($fixture['home_logo']) ?>" alt="<?= htmlspecialchars($fixture['home_team']) ?>" class="h-10 w-10 rounded-lg">
                                                        <?php else: ?>
                                                            <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center font-medium">
                                                                <?= substr($fixture['home_team'], 0, 1) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-sm font-medium text-dark-900"><?= htmlspecialchars($fixture['home_team']) ?></div>
                                                </div>
                                                <div class="flex items-center mt-2">
                                                    <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                        <?php if (!empty($fixture['away_logo'])): ?>
                                                            <img src="/logos/<?= htmlspecialchars($fixture['away_logo']) ?>" alt="<?= htmlspecialchars($fixture['away_team']) ?>" class="h-10 w-10 rounded-lg">
                                                        <?php else: ?>
                                                            <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center font-medium">
                                                                <?= substr($fixture['away_team'], 0, 1) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-sm font-medium text-dark-900"><?= htmlspecialchars($fixture['away_team']) ?></div>
                                                </div>
                                            </td>
                                            
                                            <!-- Score -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-2xl font-bold text-dark-900 text-center">
                                                    <?= $fixture['home_score'] ?> - <?= $fixture['away_score'] ?>
                                                </div>
                                                <div class="text-xs text-center mt-1">
                                                    <?php 
                                                        $home_score = intval($fixture['home_score']);
                                                        $away_score = intval($fixture['away_score']);
                                                        if ($home_score > $away_score) {
                                                            echo '<span class="text-green-600 font-medium">Home Win</span>';
                                                        } elseif ($away_score > $home_score) {
                                                            echo '<span class="text-red-600 font-medium">Away Win</span>';
                                                        } else {
                                                            echo '<span class="text-yellow-600 font-medium">Draw</span>';
                                                        }
                                                    ?>
                                                </div>
                                            </td>
                                            
                                            <!-- Venue -->
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-dark-500"><?= htmlspecialchars($fixture['stadium']) ?></div>
                                            </td>
                                            
                                            <!-- Actions -->
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button class="text-primary-600 hover:text-primary-900 mr-3">
                                                    <i class="fas fa-chart-line"></i>
                                                </button>
                                                <button class="text-primary-600 hover:text-primary-900">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="bg-white px-6 py-3 flex items-center justify-between border-t border-gray-200">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                                <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span class="font-medium"><?= count($completed_fixtures) ?></span> results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                        <a href="#" aria-current="page" class="z-10 bg-primary-50 border-primary-500 text-primary-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                            1
                                        </a>
                                        <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                            2
                                        </a>
                                        <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                            3
                                        </a>
                                        <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Next</span>
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-heading font-bold mb-4">1000 Hills Rugby</h3>
                    <p class="text-dark-300 mb-4">Passion, Pride, Performance</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-dark-300 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-dark-300 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-dark-300 hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-dark-300 hover:text-white transition-colors">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">Home</a></li>
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">Fixtures & Results</a></li>
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">Teams</a></li>
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">Membership</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Club Info</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">History</a></li>
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">Coaches</a></li>
                        <li><a href="#" class="text-dark-300 hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold uppercase tracking-wider mb-4">Newsletter</h4>
                    <p class="text-dark-300 mb-4">Subscribe to get updates on matches, events and club news.</p>
                    <form class="flex">
                        <input type="email" placeholder="Your email" class="px-4 py-2 rounded-l-lg focus:outline-none text-dark-900 w-full">
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 px-4 py-2 rounded-r-lg">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="border-t border-dark-700 mt-8 pt-8 text-sm text-dark-300">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p>&copy; <?= date('Y') ?> 1000 Hills Rugby Club. All rights reserved.</p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                
                // Update active tab button
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active', 'text-primary-700', 'border-b-2', 'border-primary-600');
                });
                button.classList.add('active', 'text-primary-700', 'border-b-2', 'border-primary-600');
                
                // Update active tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(`${tabId}-tab`).classList.add('active');
                
                // Update URL parameter without reload
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            });
        });
        
        // Initialize based on URL parameter
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam && ['fixtures', 'results'].includes(tabParam)) {
                const tabButton = document.querySelector(`.tab-button[data-tab="${tabParam}"]`);
                if (tabButton) {
                    tabButton.click();
                }
            }
        });
    </script>
</body>
</html>