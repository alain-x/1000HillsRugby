<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');
define('LOGO_DIR', __DIR__ . '/logos_/');
define('DEFAULT_LOGO', 'default.png');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// Ensure the logos directory exists
if (!file_exists(LOGO_DIR)) {
    mkdir(LOGO_DIR, 0755, true);
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable display errors for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Initialize variables
$message = '';
$messageType = '';

// Handle messages from redirects (e.g., from delete operation)
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');
    $messageType = $_GET['type'] ?? 'success';
} elseif (isset($_GET['error'])) {
    $message = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
    $messageType = 'error';
}

$standings = [];
$competitions = [];
$seasons = [];
$genders = [];
$previous_positions = [];

// Create database connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    
    // Check if required tables exist
    $required_tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            throw new Exception("Required table '$table' doesn't exist. Please run setup_database.php first.");
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database error: " . $e->getMessage() . "<br><a href='setup_database.php'>Run Database Setup</a>");
}

// Handle delete team request
if (isset($_GET['delete_team']) && is_numeric($_GET['delete_team'])) {
    $team_id = (int)$_GET['delete_team'];
    
    try {
        $conn->begin_transaction();
        
        // Get team logo before deletion
        $stmt = $conn->prepare("SELECT logo FROM teams WHERE id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $team = $result->fetch_assoc();
        $stmt->close();
        
        if ($team) {
            // Delete from league_standings first
            $stmt = $conn->prepare("DELETE FROM league_standings WHERE team_id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete from teams
            $stmt = $conn->prepare("DELETE FROM teams WHERE id = ?");
            $stmt->bind_param("i", $team_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete logo file if it exists and is not default
            if ($team['logo'] && $team['logo'] !== DEFAULT_LOGO && file_exists(LOGO_DIR . $team['logo'])) {
                unlink(LOGO_DIR . $team['logo']);
            }
            
            $conn->commit();
            $message = "Team deleted successfully!";
            $messageType = "success";
        } else {
            throw new Exception("Team not found");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error deleting team: " . $e->getMessage();
        $messageType = "error";
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("POST data received: " . print_r($_POST, true));
    
    if (isset($_POST['add_team'])) {
        try {
            // Validate and sanitize input
            $team_name = trim($_POST['team_name'] ?? '');
            $competition_id = filter_input(INPUT_POST, 'competition_id', FILTER_VALIDATE_INT);
            $season_id = filter_input(INPUT_POST, 'season_id', FILTER_VALIDATE_INT);
            $gender_id = filter_input(INPUT_POST, 'gender_id', FILTER_VALIDATE_INT);
            
            // Validate required fields
            $errors = [];
            
            if (empty($team_name)) {
                $errors[] = "Team name is required";
            } elseif (strlen($team_name) > 255) {
                $errors[] = "Team name is too long (max 255 characters)";
            }
            
            if (!$competition_id || $competition_id <= 0) {
                $errors[] = "Please select a valid competition";
            }
            
            if (!$season_id || $season_id <= 0) {
                $errors[] = "Please select a valid season";
            }
            
            if (!$gender_id || $gender_id <= 0) {
                $errors[] = "Please select a valid gender";
            }
            
            if (!empty($errors)) {
                throw new Exception(implode("<br>", $errors));
            }
            
            // Process logo upload
            $team_logo = DEFAULT_LOGO;
            
            if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['team_logo'];
                
                // Validate file
                $allowed_types = ['image/jpeg', 'image/png'];
                $max_size = MAX_FILE_SIZE;
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $file['tmp_name']);
                finfo_close($file_info);
                
                if (!in_array($mime_type, $allowed_types)) {
                    throw new Exception("Invalid file type. Only JPEG and PNG are allowed.");
                }
                
                if ($file['size'] > $max_size) {
                    throw new Exception("File is too large. Maximum size is " . ($max_size / 1024 / 1024) . "MB");
                }
                
                // Generate safe filename
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safe_name = preg_replace('/[^a-zA-Z0-9]/', '', $team_name);
                $filename = uniqid() . '_' . $safe_name . '.' . $extension;
                $upload_path = LOGO_DIR . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $team_logo = $filename;
                } else {
                    throw new Exception("Failed to upload team logo");
                }
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Check if team name already exists
                $stmt = $conn->prepare("SELECT id FROM teams WHERE name = ?");
                $stmt->bind_param("s", $team_name);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    throw new Exception("A team with this name already exists");
                }
                $stmt->close();
                
                // Insert team
                $stmt = $conn->prepare("INSERT INTO teams (name, logo) VALUES (?, ?)");
                if (!$stmt) {
                    throw new Exception("Failed to prepare team insert: " . $conn->error);
                }
                $stmt->bind_param("ss", $team_name, $team_logo);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add team: " . $stmt->error);
                }
                
                $team_id = $stmt->insert_id;
                $stmt->close();
                
                // Insert into league standings
                $stmt = $conn->prepare("
                    INSERT INTO league_standings (
                        team_id, competition_id, season_id, gender_id
                    ) VALUES (?, ?, ?, ?)
                ");
                if (!$stmt) {
                    throw new Exception("Failed to prepare standings insert: " . $conn->error);
                }
                $stmt->bind_param("iiii", $team_id, $competition_id, $season_id, $gender_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add team to standings: " . $stmt->error);
                }
                
                $conn->commit();
                
                $message = "Team added successfully!";
                $messageType = "success";
                
                // Clear POST data
                $_POST = [];
                
            } catch (Exception $e) {
                $conn->rollback();
                
                // Clean up uploaded file if exists
                if (isset($filename) && file_exists(LOGO_DIR . $filename)) {
                    unlink(LOGO_DIR . $filename);
                }
                
                throw $e;
            }
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "error";
        }
        
    } elseif (isset($_POST['update_standings'])) {
        try {
            $conn->begin_transaction();
            
            foreach ($_POST['teams'] as $id => $data) {
                // Validate and sanitize input
                $id = filter_var($id, FILTER_VALIDATE_INT);
                $matches_played = filter_var($data['matches_played'] ?? 0, FILTER_VALIDATE_INT);
                $matches_won = filter_var($data['matches_won'] ?? 0, FILTER_VALIDATE_INT);
                $matches_drawn = filter_var($data['matches_drawn'] ?? 0, FILTER_VALIDATE_INT);
                $matches_lost = filter_var($data['matches_lost'] ?? 0, FILTER_VALIDATE_INT);
                $tries_for = filter_var($data['tries_for'] ?? 0, FILTER_VALIDATE_INT);
                $tries_against = filter_var($data['tries_against'] ?? 0, FILTER_VALIDATE_INT);
                $points_for = filter_var($data['points_for'] ?? 0, FILTER_VALIDATE_INT);
                $points_against = filter_var($data['points_against'] ?? 0, FILTER_VALIDATE_INT);
                $league_points = filter_var($data['league_points'] ?? 0, FILTER_VALIDATE_INT);
                $form_score = filter_var($data['form_score'] ?? 0, FILTER_VALIDATE_FLOAT);
                
                $points_difference = $points_for - $points_against;
                
                $stmt = $conn->prepare("
                    UPDATE league_standings SET
                        matches_played = ?,
                        matches_won = ?,
                        matches_drawn = ?,
                        matches_lost = ?,
                        tries_for = ?,
                        tries_against = ?,
                        points_for = ?,
                        points_against = ?,
                        points_difference = ?,
                        league_points = ?,
                        form_score = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "iiiiiiiiidii",
                    $matches_played,
                    $matches_won,
                    $matches_drawn,
                    $matches_lost,
                    $tries_for,
                    $tries_against,
                    $points_for,
                    $points_against,
                    $points_difference,
                    $league_points,
                    $form_score,
                    $id
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update standings: " . $conn->error);
                }
                
                $stmt->close();
            }
            
            $conn->commit();
            $message = "Standings updated successfully!";
            $messageType = "success";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get current standings
try {
    // First get previous positions to track movement
    $prev_result = $conn->query("
        SELECT team_id, league_points 
        FROM league_standings 
        ORDER BY league_points DESC
    ");
    
    $position = 1;
    while ($row = $prev_result->fetch_assoc()) {
        $previous_positions[$row['team_id']] = $position++;
    }
    
    // Get current standings
    $query = "
        SELECT ls.id, t.id as team_id, t.name as team_name, t.logo as team_logo, 
               c.name as competition_name, s.year as season_year, g.name as gender_name,
               ls.matches_played, ls.matches_won, ls.matches_drawn, ls.matches_lost,
               ls.tries_for, ls.tries_against, ls.points_for, ls.points_against,
               ls.points_difference, ls.league_points, ls.form_score
        FROM league_standings ls
        JOIN teams t ON ls.team_id = t.id
        JOIN competitions c ON ls.competition_id = c.id
        JOIN seasons s ON ls.season_id = s.id
        JOIN genders g ON ls.gender_id = g.id
        ORDER BY ls.competition_id, ls.season_id, ls.gender_id, ls.league_points DESC
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        $position = 1;
        while ($row = $result->fetch_assoc()) {
            $team_id = (int)$row['team_id'];
            $current_position = $position++;
            $previous_position = $previous_positions[$team_id] ?? $current_position;
            $position_change = $previous_position - $current_position;
            
            $logoPath = (!empty($row['team_logo']) && file_exists(LOGO_DIR . $row['team_logo'])) 
                ? LOGO_DIR . $row['team_logo'] 
                : null;
            
            $standings[] = [
                'id' => (int)$row['id'],
                'team_id' => $team_id,
                'team_name' => htmlspecialchars($row['team_name'], ENT_QUOTES, 'UTF-8'),
                'team_logo' => $logoPath,
                'first_letter' => strtoupper(substr($row['team_name'], 0, 1)),
                'competition_name' => htmlspecialchars($row['competition_name'], ENT_QUOTES, 'UTF-8'),
                'season_year' => htmlspecialchars($row['season_year'], ENT_QUOTES, 'UTF-8'),
                'gender_name' => htmlspecialchars($row['gender_name'], ENT_QUOTES, 'UTF-8'),
                'matches_played' => (int)$row['matches_played'],
                'matches_won' => (int)$row['matches_won'],
                'matches_drawn' => (int)$row['matches_drawn'],
                'matches_lost' => (int)$row['matches_lost'],
                'tries_for' => (int)$row['tries_for'],
                'tries_against' => (int)$row['tries_against'],
                'points_for' => (int)$row['points_for'],
                'points_against' => (int)$row['points_against'],
                'points_difference' => (int)$row['points_difference'],
                'league_points' => (int)$row['league_points'],
                'form_score' => (float)$row['form_score'],
                'position_change' => $position_change
            ];
        }
        $result->free();
    }
    
    // Get available competitions, seasons, and genders
    $competitions = $conn->query("SELECT id, name FROM competitions WHERE is_active = TRUE ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    $seasons = $conn->query("SELECT id, year FROM seasons ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);
    $genders = $conn->query("SELECT id, name FROM genders ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    $message = "Error retrieving data from database";
    $messageType = "error";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Standings | 1000 Hills Rugby</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .team-logo {
            width: 35px;
            height: 35px;
            object-fit: contain;
            margin-right: 10px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .team-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            margin-right: 10px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #374151;
            background-color: #fff;
            background-clip: padding-box;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
        }
        .required-field::after {
            content: " *";
            color: #ef4444;
        }
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #10b981;
        }
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #b91c1c;
            border: 1px solid #ef4444;
        }
        .position-up {
            color: #10B981;
            font-weight: bold;
        }
        .position-down {
            color: #EF4444;
            font-weight: bold;
        }
        .position-same {
            color: #F59E0B;
            font-weight: bold;
        }
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background: linear-gradient(135deg, #065f46 0%, #047857 100%);
            color: white;
            padding: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .input-number {
            width: 60px;
            text-align: center;
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        .input-number:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
        }
        .nav-container {
            background: linear-gradient(135deg, #065f46 0%, #047857 50%, #065f46 100%);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .nav-item {
            position: relative;
            color: white;
            transition: all 0.3s ease;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .nav-item:hover {
            color: #d1fae5;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }
        .nav-item.active {
            color: white;
            background-color: rgba(52, 211, 153, 0.2);
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 12px;
            }
            .team-logo, .team-initial {
                width: 25px;
                height: 25px;
                margin-right: 6px;
            }
            .input-number {
                width: 50px;
                padding: 0.25rem;
            }
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
                        <span class="ml-3 text-xl font-bold text-white">1000 Hills Rugby</span>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center space-x-2">
                    <a href="tables.php" class="nav-item font-medium text-sm uppercase tracking-wider">
                        <i class="fas fa-table mr-2"></i>League Table
                    </a>
                    <a href="uploadtables.php" class="nav-item active font-medium text-sm uppercase tracking-wider">
                        <i class="fas fa-cog mr-2"></i>Manage Standings
                    </a>
                </nav>
                
                <button id="mobile-menu-button" class="md:hidden text-white focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-green-800 py-2 px-4 shadow-lg">
            <a href="tables.php" class="block py-3 px-4 text-white hover:bg-green-700 rounded-md">
                <i class="fas fa-table mr-3"></i>League Table
            </a>
            <a href="uploadtables.php" class="block py-3 px-4 text-white bg-green-700 rounded-md">
                <i class="fas fa-cog mr-3"></i>Manage Standings
            </a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg alert-<?= $messageType ?> shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> mr-3 text-lg"></i>
                    <div><?= $message ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Add New Team Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="text-xl font-bold flex items-center">
                        <i class="fas fa-plus-circle mr-3"></i>Add New Team
                    </h2>
                </div>
                <div class="p-6">
                    <form method="POST" enctype="multipart/form-data" class="space-y-6" id="teamForm">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2 required-field">
                                <i class="fas fa-users mr-1 text-green-600"></i>Team Name
                            </label>
                            <input type="text" name="team_name" required minlength="2" maxlength="255"
                                   class="form-control" value="<?= htmlspecialchars($_POST['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Enter team name">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 required-field">
                                    <i class="fas fa-trophy mr-1 text-green-600"></i>Competition
                                </label>
                                <select name="competition_id" required class="form-control">
                                    <option value="">Select Competition</option>
                                    <?php foreach ($competitions as $comp): ?>
                                        <option value="<?= (int)$comp['id'] ?>" <?= ($_POST['competition_id'] ?? '') == $comp['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($comp['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 required-field">
                                    <i class="fas fa-calendar mr-1 text-green-600"></i>Season
                                </label>
                                <select name="season_id" required class="form-control">
                                    <option value="">Select Season</option>
                                    <?php foreach ($seasons as $season): ?>
                                        <option value="<?= (int)$season['id'] ?>" <?= ($_POST['season_id'] ?? '') == $season['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($season['year'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2 required-field">
                                    <i class="fas fa-venus-mars mr-1 text-green-600"></i>Gender
                                </label>
                                <select name="gender_id" required class="form-control">
                                    <option value="">Select Gender</option>
                                    <?php foreach ($genders as $gender): ?>
                                        <option value="<?= (int)$gender['id'] ?>" <?= ($_POST['gender_id'] ?? '') == $gender['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($gender['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-image mr-1 text-green-600"></i>Team Logo
                            </label>
                            <input type="file" name="team_logo" accept="image/jpeg, image/png" 
                                   class="form-control">
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>PNG or JPG (max 2MB)
                            </p>
                        </div>
                        
                        <button type="submit" name="add_team" class="btn-primary w-full">
                            <i class="fas fa-plus mr-2"></i> Add Team
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h2 class="text-xl font-bold flex items-center">
                        <i class="fas fa-chart-bar mr-3"></i>League Statistics
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-trophy mr-2 text-yellow-500"></i>Competitions
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($competitions as $comp): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full font-medium">
                                        <?= htmlspecialchars($comp['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-calendar mr-2 text-blue-500"></i>Seasons
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($seasons as $season): ?>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full font-medium">
                                        <?= htmlspecialchars($season['year'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-users mr-2 text-purple-500"></i>Genders
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($genders as $gender): ?>
                                    <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full font-medium">
                                        <?= htmlspecialchars($gender['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-gray-500"></i>Quick Info
                            </h3>
                            <p class="text-sm text-gray-600">
                                Total Teams: <span class="font-semibold text-green-600"><?= count($standings) ?></span>
                            </p>
                            <p class="text-sm text-gray-600">
                                Last Updated: <span class="font-semibold text-green-600"><?= date('M j, Y H:i') ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Standings Form -->
        <div class="mt-8 card">
            <div class="card-header">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="fas fa-edit mr-3"></i>Edit League Standings
                </h2>
            </div>
            
            <form method="POST" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-4 text-left font-semibold text-gray-700">Team</th>
                            <th class="px-3 py-4 text-left font-semibold text-gray-700">Comp</th>
                            <th class="px-3 py-4 text-left font-semibold text-gray-700">Season</th>
                            <th class="px-3 py-4 text-left font-semibold text-gray-700">Gender</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">Pld</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">W</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">D</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">L</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">T+</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">T-</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">PF</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">PA</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">Pts</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">Form</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">Change</th>
                            <th class="px-3 py-4 text-center font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($standings)): ?>
                            <tr>
                                <td colspan="16" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-lg font-medium">No teams found in league standings</p>
                                        <p class="text-sm">Add your first team using the form above.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($standings as $team): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-4">
                                        <input type="hidden" name="teams[<?= (int)$team['id'] ?>][id]" value="<?= (int)$team['id'] ?>">
                                        <div class="flex items-center">
                                            <?php if ($team['team_logo']): ?>
                                                <img src="<?= htmlspecialchars($team['team_logo'], ENT_QUOTES, 'UTF-8') ?>" 
                                                     alt="<?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                     class="team-logo">
                                            <?php else: ?>
                                                <div class="team-initial"><?= $team['first_letter'] ?></div>
                                            <?php endif; ?>
                                            <span class="font-medium text-gray-900"><?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-600"><?= htmlspecialchars($team['competition_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-4 text-sm text-gray-600"><?= htmlspecialchars($team['season_year'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-4 text-sm text-gray-600"><?= htmlspecialchars($team['gender_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_played]" 
                                               value="<?= (int)$team['matches_played'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_won]" 
                                               value="<?= (int)$team['matches_won'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_drawn]" 
                                               value="<?= (int)$team['matches_drawn'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_lost]" 
                                               value="<?= (int)$team['matches_lost'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][tries_for]" 
                                               value="<?= (int)$team['tries_for'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][tries_against]" 
                                               value="<?= (int)$team['tries_against'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][points_for]" 
                                               value="<?= (int)$team['points_for'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][points_against]" 
                                               value="<?= (int)$team['points_against'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][league_points]" 
                                               value="<?= (int)$team['league_points'] ?>" min="0" 
                                               class="input-number">
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="number" step="0.01" name="teams[<?= (int)$team['id'] ?>][form_score]" 
                                               value="<?= (float)$team['form_score'] ?>" min="0" max="1" 
                                               class="w-16 input-number">
                                    </td>
                                    <td class="px-3 py-4 text-center">
                                        <?php if ($team['position_change'] > 0): ?>
                                            <span class="position-up">
                                                <i class="fas fa-arrow-up"></i> <?= abs($team['position_change']) ?>
                                            </span>
                                        <?php elseif ($team['position_change'] < 0): ?>
                                            <span class="position-down">
                                                <i class="fas fa-arrow-down"></i> <?= abs($team['position_change']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="position-same">
                                                <i class="fas fa-equals"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-4 text-center">
                                        <a href="deleteteam.php?id=<?= (int)$team['team_id'] ?>" 
                                           class="btn-danger"
                                           onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?>? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($standings)): ?>
                    <div class="p-6 bg-gray-50 border-t border-gray-200">
                        <button type="submit" name="update_standings" class="btn-primary">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </main>

    <script>
        // Form validation with enhanced UX
        document.getElementById('teamForm').addEventListener('submit', function(e) {
            const teamName = this.querySelector('[name="team_name"]').value.trim();
            const competition = this.querySelector('[name="competition_id"]').value;
            const season = this.querySelector('[name="season_id"]').value;
            const gender = this.querySelector('[name="gender_id"]').value;
            
            let errors = [];
            
            if (!teamName) {
                errors.push('Team name is required');
            }
            
            if (!competition) {
                errors.push('Please select a competition');
            }
            
            if (!season) {
                errors.push('Please select a season');
            }
            
            if (!gender) {
                errors.push('Please select a gender');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            
            // Show loading state
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding Team...';
            button.disabled = true;
            
            return true;
        });

        // Auto-calculate points difference
        document.querySelectorAll('input[name*="points_for"], input[name*="points_against"]').forEach(input => {
            input.addEventListener('change', function() {
                const row = this.closest('tr');
                const pf = parseInt(row.querySelector('input[name*="points_for"]').value) || 0;
                const pa = parseInt(row.querySelector('input[name*="points_against"]').value) || 0;
                const pd = pf - pa;
                
                // You could update a points difference display here if you add one
                console.log(`Points difference: ${pd}`);
            });
        });

        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>