<?php
// Database connection
$conn = new mysqli('localhost', 'root', '1234', '1000hills_rugby');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = '';
$messageClass = '';
$editMode = false;
$currentPlayer = null;
$players = [];

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // First get the image path to delete the file
    $result = $conn->query("SELECT img FROM players WHERE id = $id");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['img']) && file_exists($row['img'])) {
            unlink($row['img']); // Delete the image file
        }
    }
    
    $sql = "DELETE FROM players WHERE id = $id";
    if ($conn->query($sql)) {
        $message = 'Player deleted successfully!';
        $messageClass = 'alert-success';
    } else {
        $message = 'Error deleting player: ' . $conn->error;
        $messageClass = 'alert-error';
    }
}

// Handle form submission for adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
    $role = $conn->real_escape_string(trim($_POST['role'] ?? ''));
    $position_category = $conn->real_escape_string(trim($_POST['player_category'] ?? ''));
    $special_role = $conn->real_escape_string(trim($_POST['category'] ?? ''));
    $team = $conn->real_escape_string(trim($_POST['team'] ?? 'men'));
    $weight = $conn->real_escape_string(trim($_POST['weight'] ?? ''));
    $height = $conn->real_escape_string(trim($_POST['height'] ?? ''));
    $games = isset($_POST['games']) ? intval($_POST['games']) : 0;
    $points = isset($_POST['points']) ? intval($_POST['points']) : 0;
    $tries = isset($_POST['tries']) ? intval($_POST['tries']) : 0;
    $placeOfBirth = $conn->real_escape_string(trim($_POST['placeOfBirth'] ?? ''));
    $nationality = $conn->real_escape_string(trim($_POST['nationality'] ?? ''));
    $honours = $conn->real_escape_string(trim($_POST['honours'] ?? ''));
    $joined = $conn->real_escape_string(trim($_POST['joined'] ?? ''));
    $previousClubs = $conn->real_escape_string(trim($_POST['previousClubs'] ?? ''));
    $sponsor = $conn->real_escape_string(trim($_POST['sponsor'] ?? ''));
    $sponsorDesc = $conn->real_escape_string(trim($_POST['sponsorDesc'] ?? ''));

    // Handle image upload
    $imgPath = $currentPlayer['img'] ?? ''; // Keep existing image if not changed
    
    if (isset($_FILES['player_image']) && $_FILES['player_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/players/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileExt = pathinfo($_FILES['player_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('player_') . '.' . $fileExt;
        $targetPath = $uploadDir . $filename;
        
        // Validate image file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['player_image']['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['player_image']['tmp_name'], $targetPath)) {
                // Delete old image if it exists
                if (!empty($currentPlayer['img']) && file_exists($currentPlayer['img'])) {
                    unlink($currentPlayer['img']);
                }
                $imgPath = $targetPath;
            } else {
                $message = 'Error uploading image file.';
                $messageClass = 'alert-error';
            }
        } else {
            $message = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
            $messageClass = 'alert-error';
        }
    }
    
    // Only proceed with database operation if there were no errors
    if (empty($message)) {
        if ($editMode && $id > 0) {
            // Update existing player
            $sql = "UPDATE players SET 
                    name = '$name', 
                    img = '$imgPath', 
                    age = $age, 
                    role = '$role', 
                    position_category = '$position_category', 
                    special_role = '$special_role', 
                    team = '$team',
                    weight = '$weight', 
                    height = '$height', 
                    games = $games, 
                    points = $points, 
                    tries = $tries,
                    placeOfBirth = '$placeOfBirth', 
                    nationality = '$nationality', 
                    honours = '$honours', 
                    joined = '$joined', 
                    previousClubs = '$previousClubs', 
                    sponsor = '$sponsor', 
                    sponsorDesc = '$sponsorDesc'
                    WHERE id = $id";
        } else {
            // Insert new player
            $sql = "INSERT INTO players (name, img, age, role, position_category, special_role, team, weight, height, games, points, tries, 
                    placeOfBirth, nationality, honours, joined, previousClubs, sponsor, sponsorDesc)
                    VALUES ('$name', '$imgPath', $age, '$role', '$position_category', '$special_role', '$team', '$weight', '$height', $games, $points, $tries,
                    '$placeOfBirth', '$nationality', '$honours', '$joined', '$previousClubs', '$sponsor', '$sponsorDesc')";
        }
        
        if ($conn->query($sql)) {
            $message = 'Player profile ' . ($editMode ? 'updated' : 'added') . ' successfully!';
            $messageClass = 'alert-success';
            $editMode = false;
            $currentPlayer = null;
        } else {
            $message = 'Error: ' . $conn->error;
            $messageClass = 'alert-error';
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM players WHERE id = $id");
    if ($result->num_rows > 0) {
        $currentPlayer = $result->fetch_assoc();
    }
}

// Get all players for listing
$result = $conn->query("SELECT * FROM players ORDER BY 
    CASE 
        WHEN team = 'men' THEN 1
        WHEN team = 'women' THEN 2
        WHEN team = 'academy_u18_boys' THEN 3
        WHEN team = 'academy_u18_girls' THEN 4
        WHEN team = 'academy_u16_boys' THEN 5
        WHEN team = 'academy_u16_girls' THEN 6
        ELSE 7
    END, name");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1000 Hills Rugby Club - Player Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS remains the same */
        :root {
            --primary-color: #0a9113;
            --primary-dark: #077a0e;
            --secondary-color: #1a1a1a;
            --secondary-light: #2e2e2e;
            --accent-color: #f8f8f8;
            --text-color: #333;
            --light-text: #777;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --women-color: #e91e63;
            --women-dark: #c2185b;
            --academy-u18-color: #2196F3;
            --academy-u18-dark: #1976D2;
            --academy-u16-color: #FF9800;
            --academy-u16-dark: #F57C00;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Montserrat", sans-serif;
            color: var(--text-color);
            background: var(--accent-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--white);
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo img {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            border: 2px solid var(--white);
            object-fit: cover;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
        }

        .logo span {
            color: var(--primary-color);
        }

        .club-motto {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            font-weight: 600;
            transition: var(--transition);
            position: relative;
            color: var(--white);
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-links a.active {
            color: var(--primary-color);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Form Styles */
        .form-container {
            background: var(--white);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin: 2rem 0;
        }

        .section-title {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            font-size: 1.75rem;
        }

        .section-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(10, 145, 19, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Image Upload Styles */
        .image-upload-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .image-preview {
            width: 150px;
            height: 150px;
            border-radius: 0.25rem;
            overflow: hidden;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .image-preview-placeholder {
            color: #ccc;
            font-size: 3rem;
        }

        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .upload-btn {
            border: 2px dashed #ccc;
            color: #666;
            background-color: white;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .upload-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .upload-btn-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-women {
            background-color: var(--women-color);
            color: var(--white);
        }

        .btn-women:hover {
            background-color: var(--women-dark);
            transform: translateY(-2px);
        }

        .btn-academy-u18 {
            background-color: var(--academy-u18-color);
            color: var(--white);
        }

        .btn-academy-u18:hover {
            background-color: var(--academy-u18-dark);
            transform: translateY(-2px);
        }

        .btn-academy-u16 {
            background-color: var(--academy-u16-color);
            color: var(--white);
        }

        .btn-academy-u16:hover {
            background-color: var(--academy-u16-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Player Grid */
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 2rem 0;
        }

        .player-card {
            background: var(--white);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }

        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .player-image-container {
            height: 200px;
            position: relative;
            overflow: hidden;
        }

        .player-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .player-card:hover .player-image {
            transform: scale(1.05);
        }

        .player-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
            color: #ccc;
            font-size: 3rem;
        }

        .player-info {
            padding: 1.25rem;
        }

        .player-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .player-position {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .player-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.7rem;
            color: var(--light-text);
        }

        .player-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .edit-btn {
            color: var(--primary-color);
        }

        .edit-btn:hover {
            color: var(--primary-dark);
        }

        .delete-btn {
            color: #dc3545;
        }

        .delete-btn:hover {
            color: #c82333;
        }

        /* Team Badge */
        .team-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .team-men {
            background-color: rgba(10, 145, 19, 0.1);
            color: var(--primary-color);
        }

        .team-women {
            background-color: rgba(233, 30, 99, 0.1);
            color: var(--women-color);
        }

        .team-academy-u18 {
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--academy-u18-color);
        }

        .team-academy-u16 {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--academy-u16-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .nav-links {
                display: none;
                flex-direction: column;
                width: 100%;
                margin-top: 1rem;
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .player-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="https://via.placeholder.com/50x50" alt="Club Logo" />
                    <div class="logo-text">
                        <h1><span>1000 Hills</span> Rugby Club</h1>
                        <span class="club-motto">Strength in Unity</span>
                    </div>
                </div>

                <nav class="nav-links">
                    <a href="?team=men">Men's Squad</a>
                    <a href="?team=women">Women's Squad</a>
                    <a href="uploadprofile.php" class="active">Player Management</a>
                </nav>

                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <!-- Player Form -->
        <div class="form-container">
            <h2 class="section-title"><?php echo $editMode ? 'Edit Player' : 'Add New Player'; ?></h2>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $messageClass; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="uploadprofile.php" enctype="multipart/form-data">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo $currentPlayer['id'] ?? ''; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name" class="form-label">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($currentPlayer['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Player Image</label>
                    <div class="image-upload-container">
                        <div class="image-preview" id="imagePreview">
                            <?php if (!empty($currentPlayer['img'])): ?>
                                <img src="<?php echo htmlspecialchars($currentPlayer['img']); ?>" alt="Current Player Image">
                            <?php else: ?>
                                <div class="image-preview-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="upload-btn-wrapper">
                            <button type="button" class="upload-btn" id="uploadBtn">
                                <i class="fas fa-upload"></i> Choose Image
                            </button>
                            <input type="file" id="player_image" name="player_image" accept="image/*">
                        </div>
                        <small class="text-muted">Max file size: 2MB (JPEG, PNG, GIF, WEBP)</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="team" class="form-label">Team *</label>
                        <select id="team" name="team" class="form-control" required>
                            <option value="men" <?php echo (isset($currentPlayer['team']) && $currentPlayer['team'] == 'men') ? 'selected' : ''; ?>>Men's Team</option>
                            <option value="women" <?php echo (isset($currentPlayer['team']) && $currentPlayer['team'] == 'women') ? 'selected' : ''; ?>>Women's Team</option>
                            <option value="academy_u18_boys" <?php echo (isset($currentPlayer['team']) && $currentPlayer['team'] == 'academy_u18_boys') ? 'selected' : ''; ?>>Academy U18 Boys</option>
                            <option value="academy_u18_girls" <?php echo (isset($currentPlayer['team']) && $currentPlayer['team'] == 'academy_u18_girls') ? 'selected' : ''; ?>>Academy U18 Girls</option>
                            <option value="academy_u16_boys" <?php echo (isset($currentPlayer['team']) && $currentPlayer['team'] == 'academy_u16_boys') ? 'selected' : ''; ?>>Academy U16 Boys</option>
                            <option value="academy_u16_girls" <?php echo (isset($currentPlayer['team']) && $currentPlayer['team'] == 'academy_u16_girls') ? 'selected' : ''; ?>>Academy U16 Girls</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" id="age" name="age" class="form-control" min="16" max="3000"
                               value="<?php echo htmlspecialchars($currentPlayer['age'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="role" class="form-label">Position/Role *</label>
                        <input type="text" id="role" name="role" class="form-control" 
                               value="<?php echo htmlspecialchars($currentPlayer['role'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="player_category" class="form-label">Player Category *</label>
                        <select id="player_category" name="player_category" class="form-control" required>
                            <option value="Backs" <?php echo (isset($currentPlayer['position_category']) && $currentPlayer['position_category'] == 'Backs') ? 'selected' : ''; ?>>Backs</option>
                            <option value="Forwards" <?php echo (isset($currentPlayer['position_category']) && $currentPlayer['position_category'] == 'Forwards') ? 'selected' : ''; ?>>Forwards</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                <div class="form-group">
    <label for="category" class="form-label">Special Role</label>
    <select id="category" name="category" class="form-control">
        <option value="">Regular Player</option>
        <option value="Captain" <?php echo (isset($currentPlayer['special_role']) && $currentPlayer['special_role'] == 'Captain') ? 'selected' : ''; ?>>Captain</option>
        <option value="Vice-Captain" <?php echo (isset($currentPlayer['special_role']) && $currentPlayer['special_role'] == 'Vice-Captain') ? 'selected' : ''; ?>>Vice-Captain</option>
    </select>
</div>

                    
                    <div class="form-group">
                        <label for="joined" class="form-label">Year Joined</label>
                        <input type="text" id="joined" name="joined" class="form-control" 
                               value="<?php echo htmlspecialchars($currentPlayer['joined'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="height" class="form-label">Height (cm)</label>
                        <input type="text" id="height" name="height" class="form-control" 
                               value="<?php echo htmlspecialchars($currentPlayer['height'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="weight" class="form-label">Weight (kg)</label>
                        <input type="text" id="weight" name="weight" class="form-control" 
                               value="<?php echo htmlspecialchars($currentPlayer['weight'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="games" class="form-label">Games Played</label>
                        <input type="number" id="games" name="games" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($currentPlayer['games'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="points" class="form-label">Total Points</label>
                        <input type="number" id="points" name="points" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($currentPlayer['points'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tries" class="form-label">Total Tries</label>
                        <input type="number" id="tries" name="tries" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($currentPlayer['tries'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="nationality" class="form-label">Nationality</label>
                        <input type="text" id="nationality" name="nationality" class="form-control" 
                               value="<?php echo htmlspecialchars($currentPlayer['nationality'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="placeOfBirth" class="form-label">Place of Birth</label>
                    <input type="text" id="placeOfBirth" name="placeOfBirth" class="form-control" 
                           value="<?php echo htmlspecialchars($currentPlayer['placeOfBirth'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="honours" class="form-label">Honours/Achievements</label>
                    <textarea id="honours" name="honours" class="form-control" rows="3"><?php echo htmlspecialchars($currentPlayer['honours'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="previousClubs" class="form-label">Previous Clubs</label>
                    <textarea id="previousClubs" name="previousClubs" class="form-control" rows="2"><?php echo htmlspecialchars($currentPlayer['previousClubs'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sponsor" class="form-label">Sponsor</label>
                        <input type="text" id="sponsor" name="sponsor" class="form-control" 
                               value="<?php echo htmlspecialchars($currentPlayer['sponsor'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="sponsorDesc" class="form-label">Sponsor Description</label>
                        <input type="text" id="sponsorDesc" name="sponsorDesc" class="form-control" 
                               value="<?php echo htmlspecialchars($currentPlayer['sponsorDesc'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <?php if ($editMode): ?>
                        <a href="uploadprofile.php" class="btn btn-outline">Cancel</a>
                    <?php else: ?>
                        <button type="reset" class="btn btn-outline">Reset</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?php echo $editMode ? 'Update' : 'Save'; ?> Player</button>
                </div>
            </form>
        </div>

        <!-- Player List -->
        <div class="form-container">
            <h2 class="section-title">Player Roster</h2>
            
            <?php if (empty($players)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No players found. Add your first player above.
                </div>
            <?php else: ?>
                <div class="player-grid">
                    <?php foreach ($players as $player): ?>
                        <div class="player-card">
                            <div class="player-image-container">
                                <?php if (!empty($player['img'])): ?>
                                    <img src="<?php echo htmlspecialchars($player['img']); ?>" alt="<?php echo htmlspecialchars($player['name']); ?>" class="player-image">
                                <?php else: ?>
                                    <div class="player-image-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="player-info">
                                <span class="team-badge team-<?php 
                                    if (strpos($player['team'], 'academy_u18') !== false) echo 'academy-u18';
                                    elseif (strpos($player['team'], 'academy_u16') !== false) echo 'academy-u16';
                                    else echo $player['team'];
                                ?>">
                                    <?php 
                                        $teamName = $player['team'];
                                        if ($teamName == 'men') echo "Men's Team";
                                        elseif ($teamName == 'women') echo "Women's Team";
                                        elseif ($teamName == 'academy_u18_boys') echo "Academy U18 Boys";
                                        elseif ($teamName == 'academy_u18_girls') echo "Academy U18 Girls";
                                        elseif ($teamName == 'academy_u16_boys') echo "Academy U16 Boys";
                                        elseif ($teamName == 'academy_u16_girls') echo "Academy U16 Girls";
                                        else echo ucfirst($teamName);
                                    ?>
                                </span>
                                <h3 class="player-name"><?php echo htmlspecialchars($player['name']); ?></h3>
                                <p class="player-position"><?php echo htmlspecialchars($player['role']); ?></p>
                                <?php if (!empty($player['special_role'])): ?>
                                    <p><small><strong><?php echo htmlspecialchars($player['special_role']); ?></strong> (<?php echo htmlspecialchars($player['position_category']); ?>)</small></p>
                                <?php else: ?>
                                    <p><small><?php echo htmlspecialchars($player['position_category']); ?></small></p>
                                <?php endif; ?>
                                <div class="player-stats">
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo htmlspecialchars($player['age']); ?></span>
                                        <span class="stat-label">Age</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo htmlspecialchars($player['height']); ?> cm</span>
                                        <span class="stat-label">Height</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo htmlspecialchars($player['weight']); ?> kg</span>
                                        <span class="stat-label">Weight</span>
                                    </div>
                                </div>
                                <div class="player-actions">
                                    <a href="?edit=<?php echo $player['id']; ?>" class="action-btn edit-btn" title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $player['id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this player?')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="https://via.placeholder.com/40x40" alt="Club Logo" />
                        <div class="footer-logo-text">
                            <h3><span>1000 Hills</span> Rugby Club</h1>
                            <p class="footer-motto">Strength in Unity</p>
                        </div>
                    </div>
                    <p>
                        Founded in 2010, 1000 Hills Rugby Club is one of Rwanda's premier
                        rugby clubs, dedicated to developing talent and promoting the
                        sport nationwide.
                    </p>
                </div>

                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="?team=men">Men's Squad</a></li>
                        <li><a href="?team=women">Women's Squad</a></li>
                        <li><a href="uploadprofile.php">Player Management</a></li>
                    </ul>
                </div>

                <div class="footer-contact">
                    <h4>Contact Us</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Kigali, Rwanda</p>
                    <p><i class="fas fa-phone"></i> +250 788 123 456</p>
                    <p><i class="fas fa-envelope"></i> info@1000hillsrugby.com</p>

                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> 1000 Hills Rugby Club. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-links a').forEach(function(link) {
            link.addEventListener('click', function() {
                document.querySelector('.nav-links').classList.remove('active');
            });
        });

        // Image preview functionality
        const playerImageInput = document.getElementById('player_image');
        const imagePreview = document.getElementById('imagePreview');
        const uploadBtn = document.getElementById('uploadBtn');
        
        if (playerImageInput) {
            playerImageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    
                    reader.addEventListener('load', function() {
                        imagePreview.innerHTML = '';
                        const img = document.createElement('img');
                        img.src = this.result;
                        imagePreview.appendChild(img);
                    });
                    
                    reader.readAsDataURL(file);
                }
            });
            
            uploadBtn.addEventListener('click', function() {
                playerImageInput.click();
            });
        }

        // Validate captain/vice-captain comes from backs or forwards
        const categorySelect = document.getElementById('category');
        const playerCategorySelect = document.getElementById('player_category');
        
        if (categorySelect && playerCategorySelect) {
            categorySelect.addEventListener('change', function() {
                if (this.value === 'Captain' || this.value === 'Vice-Captain') {
                    if (playerCategorySelect.value !== 'Backs' && playerCategorySelect.value !== 'Forwards') {
                        alert('Captain and Vice-Captain must be selected from Backs or Forwards');
                        this.value = '';
                    }
                }
            });
        }
    </script>
</body>
</html>