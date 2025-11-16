<?php
// Database connection with error handling
try {
    $conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");

    // Get current season
    $current_season = date('Y');

    // Get all competitions for filter
    $competitions = [];
    $compResult = $conn->query("SELECT DISTINCT competition FROM fixtures ORDER BY competition");
    if ($compResult->num_rows > 0) {
        while ($row = $compResult->fetch_assoc()) {
            $competitions[] = $row['competition'];
        }
    }

    // Get filter parameters with validation
    $selected_season = isset($_GET['season']) ? max(2014, min(intval($_GET['season']), $current_season)) : $current_season;
    $selected_competition = isset($_GET['competition']) ? $conn->real_escape_string($_GET['competition']) : '';
    $selected_gender = isset($_GET['gender']) ? (in_array($_GET['gender'], ['MEN', 'WOMEN']) ? $_GET['gender'] : '') : '';
    $active_tab = isset($_GET['tab']) ? (in_array($_GET['tab'], ['fixtures', 'results']) ? $_GET['tab'] : 'fixtures') : 'fixtures';

    // Build query for fixtures with prepared statements
    $where = ["season = ?"];
    $params = [$selected_season];
    $types = 'i';

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

    // Get upcoming fixtures
    $upcoming_fixtures = [];
    $upcoming_query = "SELECT * FROM fixtures $where_clause AND status = 'SCHEDULED' ORDER BY match_date ASC";
    $stmt = $conn->prepare($upcoming_query);
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $upcoming_fixtures[] = $row;
    }
    $stmt->close();

    // Get completed fixtures
    $completed_fixtures = [];
    $completed_query = "SELECT * FROM fixtures $where_clause AND status = 'COMPLETED' ORDER BY match_date DESC LIMIT 50";
    $stmt = $conn->prepare($completed_query);
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $completed_fixtures[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    // Log error and display user-friendly message
    error_log($e->getMessage());
    $error_message = "We're experiencing technical difficulties. Please try again later.";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-4R4W5PDJ93"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-4R4W5PDJ93');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixtures & Results | 1000 Hills Rugby Club</title>
    <meta name="description" content="View upcoming matches and results for 1000 Hills Rugby Club teams">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.1000hillsrugby.rw/fixtures">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Fixtures & Results | 1000 Hills Rugby Club">
    <meta property="og:description" content="View upcoming matches and results for 1000 Hills Rugby Club teams.">
    <meta property="og:url" content="https://www.1000hillsrugby.rw/fixtures">
    <meta property="og:image" content="https://www.1000hillsrugby.rw/images/1000-hills-logo.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Fixtures & Results | 1000 Hills Rugby Club">
    <meta name="twitter:description" content="View upcoming matches and results for 1000 Hills Rugby Club teams.">
    <meta name="twitter:image" content="https://www.1000hillsrugby.rw/images/1000-hills-logo.png">
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        :root { --header-height: 80px; }
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .tab-content { display: none; animation: fadeIn 0.3s ease-out; }
        .tab-content.active { display: block; }
        .fixture-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .fixture-card:hover { transform: translateY(-4px); box-shadow: var(--tw-shadow-card-hover); }
        .team-logo { height: 60px; width: 60px; object-fit: contain; }
        .default-logo { height: 60px; width: 60px; display: flex; align-items: center; justify-content: center; background-color: #F3F4F6; border-radius: 0.5rem; font-weight: 700; color: #9CA3AF; font-family: 'Montserrat', sans-serif; font-size: 1.25rem; }
        .result-row { transition: background-color 0.2s ease; }
        .result-row:hover { background-color: #EFF6FF; }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; line-height: 1; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Horizontal team layout */
        .teams-horizontal {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        .team-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            padding: 0.5rem;
        }
        .vs-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            font-weight: bold;
            color: #6B7280;
        }
        .team-name {
            text-align: center;
            margin-top: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        /* Navigation styles - aligned with index.html (no gradients) */
        .nav-container {
            background: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .nav-item {
            position: relative;
            color: #1f2937; /* text-gray-800 */
            transition: all 0.3s ease;
        }
        .nav-item:hover {
            color: #16a34a; /* text-green-600 */
        }
        .nav-item.active {
            color: #16a34a; /* emphasize active with green text (no underline bar) */
        }
        .mobile-nav {
            background: #ffffff;
        }
        .mobile-nav-item {
            color: #1f2937; /* text-gray-800 */
            transition: all 0.2s ease;
        }
        .mobile-nav-item:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
        }
        .mobile-nav-item.active {
            background-color: #f3f4f6; /* subtle highlight */
            color: #1f2937;
        }
        @media (max-width: 640px) {
            .teams-horizontal {
                flex-direction: row;
            }
            .team-logo, .default-logo {
                height: 50px;
                width: 50px;
            }
            .team-name {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">
    <!-- Professional Header -->
    <header class="nav-container">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="./" class="flex items-center">
                        <img src="./images/1000-hills-logo.png" alt="1000 Hills Rugby" class="w-[60px]">
                        <span class="ml-3 text-xl font-bold text-gray-800"></span>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8 font-600 text-gray-800 text-sm tracking-wider">
                    <a href="/" class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 py-4">Home</a>
                    <a href="fixtures?tab=fixtures" class="<?php echo $active_tab === 'fixtures' ? 'text-green-600 border-b-2 border-green-600' : ''; ?> hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 py-4">Fixtures</a>
                    <a href="fixtures" class="<?php echo $active_tab === 'results' ? 'text-green-600 border-b-2 border-green-600' : ''; ?> hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 py-4">Results</a>
                    <a href="tables" class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 py-4">League Tables</a>
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
            <a href="tables" class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300 rounded-md">League Tables</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-[calc(100vh-80px)]">
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Page Header (compact) -->
            <div class="mb-4"></div>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-500 mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">We encountered an error</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p><?php echo htmlspecialchars($error_message); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                <form method="GET" class="space-y-0">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <!-- Season -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Season</label>
                            <div class="relative">
                                <select name="season" class="w-full pl-3 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 appearance-none bg-white bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjdHJpbWJsZS1kYXJrLTYwMCIgc3Rya2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgY2xhc3M9Imx1Y2lkZSBsdWNpZGUtdmNoZXZyb24tZG93biI+PHBhdGggZD0ibTYgOSA2IDYgNi02Ii8+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem]">
                                    <?php for ($year = $current_season; $year >= 2014; $year--): ?>
                                        <option value="<?php echo $year; ?>" <?php echo $year == $selected_season ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Competition -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Competition</label>
                            <div class="relative">
                                <select name="competition" class="w-full pl-3 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 appearance-none bg-white bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjdHJpbWJsZS1kYXJrLTYwMCIgc3Rya2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgY2xhc3M9Imx1Y2lkZSBsdWNpZGUtdmNoZXZyb24tZG93biI+PHBhdGggZD0ibTYgOSA2IDYgNi02Ii8+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem]">
                                    <option value="">All Competitions</option>
                                    <?php foreach ($competitions as $comp): ?>
                                        <option value="<?php echo htmlspecialchars($comp); ?>" <?php echo $comp == $selected_competition ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($comp); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                            <div class="relative">
                                <select name="gender" class="w-full pl-3 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 appearance-none bg-white bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjdHJpbWJsZS1kYXJrLTYwMCIgc3Rya2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgY2xhc3M9Imx1Y2lkZSBsdWNpZGUtdmNoZXZyb24tZG93biI+PHBhdGggZD0ibTYgOSA2IDYgNi02Ii8+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem]">
                                    <option value="">All Genders</option>
                                    <option value="MEN" <?php echo $selected_gender == 'MEN' ? 'selected' : ''; ?>>Men's</option>
                                    <option value="WOMEN" <?php echo $selected_gender == 'WOMEN' ? 'selected' : ''; ?>>Women's</option>
                                </select>
                            </div>
                        </div>

                        <!-- Apply -->
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 flex items-center justify-center">
                                <i class="fas fa-filter mr-2"></i>
                                Apply Filters
                            </button>
                        </div>

                        <!-- Reset -->
                        <div class="flex items-end">
                            <a href="?tab=<?php echo urlencode($active_tab); ?>" class="w-full px-4 py-3 border border-gray-300 text-gray-600 font-medium rounded-lg hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 text-center inline-flex items-center justify-center">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200 mb-6">
                <a href="?tab=fixtures&season=<?php echo $selected_season; ?>&competition=<?php echo urlencode($selected_competition); ?>&gender=<?php echo urlencode($selected_gender); ?>" class="tab-button px-6 py-3 font-medium <?php echo $active_tab === 'fixtures' ? 'text-green-700 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600'; ?> transition-colors mr-1">
                    <i class="far fa-calendar-alt mr-2"></i>Upcoming Fixtures
                </a>
                <a href="fixtures?season=<?php echo $selected_season; ?>&competition=<?php echo urlencode($selected_competition); ?>&gender=<?php echo urlencode($selected_gender); ?>" class="tab-button px-6 py-3 font-medium <?php echo $active_tab === 'results' ? 'text-green-700 border-b-2 border-green-600' : 'text-gray-500 hover:text-green-600'; ?> transition-colors">
                    <i class="far fa-list-alt mr-2"></i>Match Results
                </a>
            </div>
            
            <!-- Tab Contents -->
            <div class="tab-content <?php echo $active_tab === 'fixtures' ? 'active' : ''; ?>" id="fixtures-tab">
                <?php if (empty($upcoming_fixtures)): ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="mx-auto w-24 h-24 text-gray-300 mb-4">
                            <i class="far fa-calendar-times text-7xl"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 mb-2">No Upcoming Fixtures</h3>
                        <p class="text-gray-500 max-w-md mx-auto mb-6">There are currently no scheduled matches for the selected filters. Please check back later or try different filters.</p> 
                    </div>
                <?php else: ?>
                    <!-- Fixtures Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($upcoming_fixtures as $fixture): ?>
                            <div class="fixture-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md">
                                <!-- Match Header -->
                                <div class="p-5 bg-gradient-to-r from-green-50 to-green-100 border-b border-green-200">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-green-800">
                                            <?php echo htmlspecialchars($fixture['competition']); ?>
                                        </span>
                                        <span class="badge bg-green-100 text-green-800">
                                            <?php echo htmlspecialchars($fixture['gender']); ?>
                                        </span>
                                    </div>
                                    <div class="mt-3 text-sm text-green-700">
                                        <div class="flex items-center">
                                            <i class="far fa-calendar mr-2"></i>
                                            <?php echo date('l, F j, Y', strtotime($fixture['match_date'])); ?>
                                        </div>
                                        <div class="flex items-center mt-2">
                                            <i class="far fa-clock mr-2"></i>
                                            <?php echo date('H:i', strtotime($fixture['match_date'])); ?>
                                            <?php if (!empty($fixture['stadium'])): ?>
                                                <span class="ml-4">
                                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                                    <?php echo htmlspecialchars($fixture['stadium']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Teams - Horizontal Layout -->
                                <div class="p-5">
                                    <div class="teams-horizontal">
                                        <!-- Home Team -->
                                        <div class="team-container">
                                            <?php if (!empty($fixture['home_logo'])): ?>
                                                <img src="logos_/<?php echo htmlspecialchars($fixture['home_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['home_team']); ?>" class="team-logo">
                                            <?php else: ?>
                                                <div class="default-logo">
                                                    <?php echo substr($fixture['home_team'], 0, 1); ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="team-name"><?php echo htmlspecialchars($fixture['home_team']); ?></span>
                                        </div>
                                        
                                        <!-- VS Divider -->
                                        <div class="vs-container">
                                            <span>VS</span>
                                        </div>
                                        
                                        <!-- Away Team -->
                                        <div class="team-container">
                                            <?php if (!empty($fixture['away_logo'])): ?>
                                                <img src="logos_/<?php echo htmlspecialchars($fixture['away_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['away_team']); ?>" class="team-logo">
                                            <?php else: ?>
                                                <div class="default-logo">
                                                    <?php echo substr($fixture['away_team'], 0, 1); ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="team-name"><?php echo htmlspecialchars($fixture['away_team']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Match Footer -->
                                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-ticket-alt mr-1"></i> Tickets support Team
                                    </span>
                                    <button class="text-sm font-medium text-green-700 hover:text-green-800 flex items-center">
                                        More info <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content <?php echo $active_tab === 'results' ? 'active' : ''; ?>" id="results-tab">
                <?php if (empty($completed_fixtures)): ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="mx-auto w-24 h-24 text-gray-300 mb-4">
                            <i class="far fa-frown text-7xl"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 mb-2">No Results Found</h3>
                        <p class="text-gray-500 max-w-md mx-auto mb-6">There are currently no completed matches for the selected filters. Please try different filters or check back later.</p> 
                    </div>
                <?php else: ?>
                    <!-- Results Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Competition</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($completed_fixtures as $fixture): ?>
                                        <tr class="result-row">
                                            <!-- Date & Time -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo date('M j, Y', strtotime($fixture['match_date'])); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($fixture['match_date'])); ?></div>
                                            </td>
                                            
                                            <!-- Competition -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <span class="badge bg-green-100 text-green-800 mr-2">
                                                        <?php echo htmlspecialchars($fixture['gender']); ?>
                                                    </span>
                                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fixture['competition']); ?></span>
                                                </div>
                                            </td>
                                            
                                            <!-- Match - Horizontal Teams -->
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                            <?php if (!empty($fixture['home_logo'])): ?>
                                                                <img src="logos_/<?php echo htmlspecialchars($fixture['home_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['home_team']); ?>" class="h-10 w-10 rounded-lg">
                                                            <?php else: ?>
                                                                <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center font-medium">
                                                                    <?php echo substr($fixture['home_team'], 0, 1); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fixture['home_team']); ?></div>
                                                    </div>
                                                    <span class="mx-2 text-gray-400">vs</span>
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                            <?php if (!empty($fixture['away_logo'])): ?>
                                                                <img src="logos_/<?php echo htmlspecialchars($fixture['away_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['away_team']); ?>" class="h-10 w-10 rounded-lg">
                                                            <?php else: ?>
                                                                <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center font-medium">
                                                                    <?php echo substr($fixture['away_team'], 0, 1); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fixture['away_team']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Score -->
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-2xl font-bold text-gray-900 text-center">
                                                    <?php echo $fixture['home_score']; ?> - <?php echo $fixture['away_score']; ?>
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
    </main>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Tab functionality - now handled by server-side links
        // This script is just for any additional interactivity you might want
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight active tab based on URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam && ['fixtures', 'results'].includes(tabParam)) {
                // Remove all active classes first
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Add active class to the correct tab content
                document.getElementById(`${tabParam}-tab`).classList.add('active');
            }
        });
    </script>
</body>
</html>