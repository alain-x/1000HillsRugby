<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');
define('LOGO_DIR', __DIR__ . '/logos/');
define('DEFAULT_LOGO', 'default.png');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// Ensure the logos directory exists
if (!file_exists(LOGO_DIR)) {
    mkdir(LOGO_DIR, 0755, true);
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable on production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Initialize variables
$message = '';
$messageType = '';
$standings = [];
$competitions = [];
$seasons = [];
$genders = [];
$previous_positions = []; // To track position changes

// Create database connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database connection error. Please try again later.");
}

// Check if required tables exist
try {
    $required_tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            throw new Exception("Required table '$table' doesn't exist in the database.");
        }
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database configuration error. Please contact the administrator.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $stmt->bind_param("ss", $team_name, $team_logo);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add team: " . $conn->error);
                }
                
                $team_id = $stmt->insert_id;
                $stmt->close();
                
                // Insert into league standings
                $stmt = $conn->prepare("
                    INSERT INTO league_standings (
                        team_id, competition_id, season_id, gender_id
                    ) VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("iiii", $team_id, $competition_id, $season_id, $gender_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add team to standings: " . $conn->error);
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
            $position_change = $previous_position - $current_position; // Positive = moved up, Negative = moved down
            
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
            width: 30px;
            height: 30px;
            object-fit: contain;
            margin-right: 8px;
        }
        .team-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            margin-right: 8px;
            background-color: #9CA3AF;
            color: white;
            border-radius: 50%;
            font-weight: bold;
        }
        input[type="number"] {
            width: 60px;
            text-align: center;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .position-up {
            color: #10B981;
        }
        .position-down {
            color: #EF4444;
        }
        .position-same {
            color: #F59E0B;
        }
    </style>
</head>
<body class="bg-gray-50">
    <header class="bg-green-800 text-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="tables.php" class="flex items-center">
                <img src="<?= htmlspecialchars(LOGO_DIR . 'logoT.jpg', ENT_QUOTES, 'UTF-8') ?>" alt="1000 Hills Rugby" class="h-10">
                <span class="ml-2 text-lg font-bold">1000 Hills Rugby</span>
            </a>
            <nav class="flex space-x-4">
                <a href="tables.php" class="text-green-200 hover:text-white font-bold">League Table</a>
                <a href="uploadtables.php" class="text-white font-bold border-b-2 border-white pb-1">Manage Standings</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-md alert-<?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Add New Team Form -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-gray-800 text-white">
                    <h2 class="text-xl font-bold">Add New Team</h2>
                </div>
                <div class="p-4">
                    <form method="POST" enctype="multipart/form-data" class="space-y-4" id="teamForm">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 required-field">Team Name</label>
                            <input type="text" name="team_name" required minlength="2" maxlength="255"
                                   class="form-control" value="<?= htmlspecialchars($_POST['team_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 required-field">Competition</label>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1 required-field">Season</label>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1 required-field">Gender</label>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Team Logo</label>
                            <input type="file" name="team_logo" accept="image/jpeg, image/png" 
                                   class="form-control">
                            <p class="mt-1 text-xs text-gray-500">PNG or JPG (max 2MB)</p>
                        </div>
                        
                        <button type="submit" name="add_team" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i> Add Team
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Filters -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-gray-800 text-white">
                    <h2 class="text-xl font-bold">Available Filters</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        <div>
                            <h3 class="font-medium text-gray-700">Competitions</h3>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <?php foreach ($competitions as $comp): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-sm rounded"><?= htmlspecialchars($comp['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-medium text-gray-700">Seasons</h3>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <?php foreach ($seasons as $season): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-sm rounded"><?= htmlspecialchars($season['year'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-medium text-gray-700">Genders</h3>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <?php foreach ($genders as $gender): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-sm rounded"><?= htmlspecialchars($gender['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Standings Form -->
        <div class="mt-6 bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-gray-800 text-white">
                <h2 class="text-xl font-bold">Edit League Standings</h2>
            </div>
            
            <form method="POST" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Team</th>
                            <th class="px-3 py-3 text-left">Comp</th>
                            <th class="px-3 py-3 text-left">Season</th>
                            <th class="px-3 py-3 text-left">Gender</th>
                            <th class="px-3 py-3 text-center">Pld</th>
                            <th class="px-3 py-3 text-center">W</th>
                            <th class="px-3 py-3 text-center">D</th>
                            <th class="px-3 py-3 text-center">L</th>
                            <th class="px-3 py-3 text-center">T+</th>
                            <th class="px-3 py-3 text-center">T-</th>
                            <th class="px-3 py-3 text-center">PF</th>
                            <th class="px-3 py-3 text-center">PA</th>
                            <th class="px-3 py-3 text-center">Pts</th>
                            <th class="px-3 py-3 text-center">Form</th>
                            <th class="px-3 py-3 text-center">Change</th>
                            <th class="px-3 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($standings)): ?>
                            <tr>
                                <td colspan="16" class="px-4 py-4 text-center text-gray-500">
                                    No teams found in league standings. Add your first team above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($standings as $team): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="teams[<?= (int)$team['id'] ?>][id]" value="<?= (int)$team['id'] ?>">
                                        <div class="flex items-center">
                                            <?php if ($team['team_logo']): ?>
                                                <img src="<?= htmlspecialchars($team['team_logo'], ENT_QUOTES, 'UTF-8') ?>" 
                                                     alt="<?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                     class="team-logo">
                                            <?php else: ?>
                                                <div class="team-initial"><?= $team['first_letter'] ?></div>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3"><?= htmlspecialchars($team['competition_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-3"><?= htmlspecialchars($team['season_year'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-3"><?= htmlspecialchars($team['gender_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_played]" 
                                               value="<?= (int)$team['matches_played'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_won]" 
                                               value="<?= (int)$team['matches_won'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_drawn]" 
                                               value="<?= (int)$team['matches_drawn'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][matches_lost]" 
                                               value="<?= (int)$team['matches_lost'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][tries_for]" 
                                               value="<?= (int)$team['tries_for'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][tries_against]" 
                                               value="<?= (int)$team['tries_against'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][points_for]" 
                                               value="<?= (int)$team['points_for'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][points_against]" 
                                               value="<?= (int)$team['points_against'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" name="teams[<?= (int)$team['id'] ?>][league_points]" 
                                               value="<?= (int)$team['league_points'] ?>" min="0" 
                                               class="p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number" step="0.01" name="teams[<?= (int)$team['id'] ?>][form_score]" 
                                               value="<?= (float)$team['form_score'] ?>" min="0" max="1" 
                                               class="w-16 p-1 text-center border border-gray-300 rounded">
                                    </td>
                                    <td class="px-3 py-3 text-center">
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
                                    <td class="px-3 py-3 text-center">
                                        <a href="deleteteam.php?id=<?= (int)$team['team_id'] ?>" 
                                           class="text-red-600 hover:text-red-800 transition-colors"
                                           onclick="return confirm('Are you sure you want to delete this team?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($standings)): ?>
                    <div class="p-4 bg-gray-50 border-t">
                        <button type="submit" name="update_standings" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </main>

    <script>
        // Form validation
        document.getElementById('teamForm').addEventListener('submit', function(e) {
            const teamName = this.querySelector('[name="team_name"]').value.trim();
            const competition = this.querySelector('[name="competition_id"]').value;
            const season = this.querySelector('[name="season_id"]').value;
            const gender = this.querySelector('[name="gender_id"]').value;
            
            if (!teamName) {
                alert('Team name is required');
                e.preventDefault();
                return false;
            }
            
            if (!competition) {
                alert('Please select a competition');
                e.preventDefault();
                return false;
            }
            
            if (!season) {
                alert('Please select a season');
                e.preventDefault();
                return false;
            }
            
            if (!gender) {
                alert('Please select a gender');
                e.preventDefault();
                return false;
            }
            
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
                console.log(`Points difference for ${row.querySelector('td:first-child').textContent}: ${pd}`);
            });
        });
    </script>
</body>
</html>