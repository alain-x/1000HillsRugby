<?php
// Database connection with error handling
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$message = '';
$messageClass = '';

// Define allowed image types and max file size (2MB)
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

// Function to handle file upload with improved validation
function handleFileUpload($fileInput, $targetDir) {
    global $allowedImageTypes, $maxFileSize, $message, $messageClass;
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            $message = 'Failed to create directory for uploads.';
            $messageClass = 'bg-red-100 border-red-400 text-red-700';
            return null;
        }
    }
    
    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        // Handle specific upload errors
        switch ($fileInput['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'File size too large. Maximum 2MB allowed.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'File upload was incomplete.';
                break;
            case UPLOAD_ERR_NO_FILE:
                return null; // No file uploaded is not an error
            default:
                $message = 'File upload error occurred.';
        }
        $messageClass = 'bg-red-100 border-red-400 text-red-700';
        return null;
    }
    
    // Validate file size
    if ($fileInput['size'] > $maxFileSize) {
        $message = 'File size exceeds maximum allowed size of 2MB.';
        $messageClass = 'bg-red-100 border-red-400 text-red-700';
        return null;
    }
    
    // Validate file type
    $fileType = mime_content_type($fileInput['tmp_name']);
    if (!in_array($fileType, $allowedImageTypes)) {
        $message = 'Only JPG, PNG, and GIF files are allowed.';
        $messageClass = 'bg-red-100 border-red-400 text-red-700';
        return null;
    }
    
    // Generate unique filename
    $extension = pathinfo($fileInput['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . strtolower($extension);
    $targetPath = $targetDir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($fileInput['tmp_name'], $targetPath)) {
        return $targetPath;
    } else {
        $message = 'Error moving uploaded file.';
        $messageClass = 'bg-red-100 border-red-400 text-red-700';
        return null;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token (you should implement this)
    // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    //     die("Invalid CSRF token");
    // }
    
    if (isset($_POST['delete'])) {
        // Delete fixture
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM fixtures WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Fixture deleted successfully!';
            $messageClass = 'bg-green-100 border-green-400 text-green-700';
        } else {
            $message = 'Error deleting fixture: ' . $stmt->error;
            $messageClass = 'bg-red-100 border-red-400 text-red-700';
        }
        $stmt->close();
    } else {
        // Add/Edit fixture - validate and sanitize inputs
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $match_date = $conn->real_escape_string(trim($_POST['match_date']));
        $stadium = $conn->real_escape_string(trim($_POST['stadium']));
        $home_team = $conn->real_escape_string(trim($_POST['home_team']));
        $away_team = $conn->real_escape_string(trim($_POST['away_team']));
        
        // Handle scores - treat empty strings as NULL
        $home_score = isset($_POST['home_score']) && $_POST['home_score'] !== '' ? intval($_POST['home_score']) : NULL;
        $away_score = isset($_POST['away_score']) && $_POST['away_score'] !== '' ? intval($_POST['away_score']) : NULL;
        
        $competition = $conn->real_escape_string(trim($_POST['competition']));
        $gender = in_array($_POST['gender'], ['MEN', 'WOMEN']) ? $_POST['gender'] : 'MEN';
        $season = intval($_POST['season']);
        
        // Determine status
        $status = ($home_score !== NULL && $away_score !== NULL) ? 'COMPLETED' : 'UPCOMING';
        
        // Handle file uploads
        $home_logo = './logos_/logoT.jpg'; // Default logo
        $away_logo = ''; // No default for away team
        
        // Keep existing logos if editing and no new file uploaded
        if (isset($_POST['existing_home_logo']) && !empty($_POST['existing_home_logo'])) {
            $home_logo = $conn->real_escape_string($_POST['existing_home_logo']);
        }
        
        if (isset($_POST['existing_away_logo']) && !empty($_POST['existing_away_logo'])) {
            $away_logo = $conn->real_escape_string($_POST['existing_away_logo']);
        }
        
        // Process home logo upload
        if (!empty($_FILES['home_logo']['name'])) {
            $uploadedHomeLogo = handleFileUpload($_FILES['home_logo'], 'uploads/logos');
            if ($uploadedHomeLogo) {
                $home_logo = $uploadedHomeLogo;
                // Delete old logo if it's not the default
                if (isset($_POST['existing_home_logo']) && 
                    $_POST['existing_home_logo'] !== './logos_/logoT.jpg' && 
                    file_exists($_POST['existing_home_logo'])) {
                    unlink($_POST['existing_home_logo']);
                }
            }
        }
        
        // Process away logo upload
        if (!empty($_FILES['away_logo']['name'])) {
            $uploadedAwayLogo = handleFileUpload($_FILES['away_logo'], 'uploads/logos');
            if ($uploadedAwayLogo) {
                $away_logo = $uploadedAwayLogo;
                // Delete old logo if it exists
                if (isset($_POST['existing_away_logo']) && 
                    !empty($_POST['existing_away_logo']) && 
                    file_exists($_POST['existing_away_logo'])) {
                    unlink($_POST['existing_away_logo']);
                }
            }
        }
        
        // Prepare the database operation
        if ($id > 0) {
            // Update existing fixture
            $stmt = $conn->prepare("UPDATE fixtures SET 
                match_date = ?, stadium = ?, home_team = ?, home_logo = ?, 
                away_team = ?, away_logo = ?, home_score = ?, away_score = ?, 
                competition = ?, gender = ?, season = ?, status = ?
                WHERE id = ?");
            $stmt->bind_param("ssssssiissssi", 
                $match_date, $stadium, $home_team, $home_logo, 
                $away_team, $away_logo, $home_score, $away_score, 
                $competition, $gender, $season, $status, $id);
        } else {
            // Insert new fixture
            $stmt = $conn->prepare("INSERT INTO fixtures 
                (match_date, stadium, home_team, home_logo, away_team, away_logo, 
                home_score, away_score, competition, gender, season, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiissss", 
                $match_date, $stadium, $home_team, $home_logo, 
                $away_team, $away_logo, $home_score, $away_score, 
                $competition, $gender, $season, $status);
        }

        if ($stmt->execute()) {
            $message = 'Fixture ' . ($id > 0 ? 'updated' : 'added') . ' successfully!';
            $messageClass = 'bg-green-100 border-green-400 text-green-700';
            
            // Clear edit mode if successful
            if ($id > 0) {
                header("Location: upload_fixtures.php?success=1");
                exit();
            }
        } else {
            $message = 'Error: ' . $stmt->error;
            $messageClass = 'bg-red-100 border-red-400 text-red-700';
        }
        $stmt->close();
    }
}

// Get all competitions for dropdown
$competitions = [];
$result = $conn->query("SELECT DISTINCT competition FROM fixtures ORDER BY competition");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $competitions[] = stripslashes($row['competition']);
    }
}

// Get all fixtures for listing
$fixtures = [];
$result = $conn->query("SELECT * FROM fixtures ORDER BY match_date DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['competition'] = stripslashes($row['competition']);
        $fixtures[] = $row;
    }
}

$conn->close();

// Helper function to safely display text
function displayText($text) {
    return htmlspecialchars(stripslashes($text));
}

// Check if we're in edit mode
$editMode = isset($_GET['edit']);
$currentFixture = null;
if ($editMode) {
    $editId = intval($_GET['edit']);
    foreach ($fixtures as $fixture) {
        if ($fixture['id'] == $editId) {
            $currentFixture = $fixture;
            break;
        }
    }
    if (!$currentFixture) {
        header("Location: upload_fixtures.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fixtures - 1000 Hills Rugby Club</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .preview-image {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .logo-preview-container {
            margin-top: 8px;
        }
        .fixture-row:hover {
            background-color: #f7fafc;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="fixtures.php" class="flex items-center">
                <img src="./logos_/logoT.jpg" alt="Club Logo" class="h-12 rounded-full border-2 border-green-600">
                <span class="ml-2 text-xl font-bold text-gray-800">1000 Hills Rugby</span>
            </a>
            
            <nav class="hidden md:flex space-x-6">
                <a href="fixtures.php" class="text-gray-600 hover:text-green-600 font-bold">Fixtures</a>
                <a href="upload_fixtures.php" class="text-green-600 font-bold border-b-2 border-green-600 pb-1">Manage Fixtures</a>
            </nav>
            
            <button id="mobile-menu-button" class="md:hidden text-gray-600 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white py-2 px-4 shadow-lg">
            <a href="fixtures.php" class="block py-2 text-gray-600 hover:text-green-600 font-bold">Fixtures</a>
            <a href="upload_fixtures.php" class="block py-2 text-green-600 font-bold">Manage Fixtures</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Fixture <?php echo isset($_GET['edit']) ? 'updated' : 'added'; ?> successfully!
            </div>
        <?php elseif ($message): ?>
            <div class="<?php echo $messageClass; ?> border px-4 py-3 rounded mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Add/Edit Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <?php echo $editMode ? 'Edit Fixture' : 'Add New Fixture'; ?>
                    <?php if ($editMode): ?>
                        <span class="text-sm font-normal text-gray-500 ml-2">(ID: <?php echo $currentFixture['id']; ?>)</span>
                    <?php endif; ?>
                </h2>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $editMode ? $currentFixture['id'] : 0; ?>">
                    
                    <?php if ($editMode): ?>
                        <input type="hidden" name="existing_home_logo" value="<?php echo displayText($currentFixture['home_logo']); ?>">
                        <input type="hidden" name="existing_away_logo" value="<?php echo displayText($currentFixture['away_logo']); ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Match Date & Time *</label>
                            <input type="datetime-local" name="match_date" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                   value="<?php 
                                   if ($editMode) {
                                       echo date('Y-m-d\TH:i', strtotime($currentFixture['match_date']));
                                   } else {
                                       echo date('Y-m-d\TH:i');
                                   }
                                   ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stadium (Optional)</label>
                            <input type="text" name="stadium" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                   value="<?php echo $editMode ? displayText($currentFixture['stadium']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Home Team *</label>
                            <input type="text" name="home_team" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                   value="<?php echo $editMode ? displayText($currentFixture['home_team']) : '1000 Hills Rugby'; ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Home Team Logo</label>
                            <input type="file" name="home_logo" id="home_logo" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" accept="image/*">
                            <?php if ($editMode && !empty($currentFixture['home_logo'])): ?>
                                <div class="logo-preview-container">
                                    <p class="text-sm text-gray-500">Current Logo:</p>
                                    <img src="<?php echo displayText($currentFixture['home_logo']); ?>" class="preview-image">
                                </div>
                            <?php endif; ?>
                            <div id="home_logo_preview" class="logo-preview-container hidden">
                                <p class="text-sm text-gray-500">New Logo Preview:</p>
                                <img id="home_logo_preview_img" class="preview-image">
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Away Team *</label>
                            <input type="text" name="away_team" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                   value="<?php echo $editMode ? displayText($currentFixture['away_team']) : ''; ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Away Team Logo</label>
                            <input type="file" name="away_logo" id="away_logo" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" accept="image/*">
                            <?php if ($editMode && !empty($currentFixture['away_logo'])): ?>
                                <div class="logo-preview-container">
                                    <p class="text-sm text-gray-500">Current Logo:</p>
                                    <img src="<?php echo displayText($currentFixture['away_logo']); ?>" class="preview-image">
                                </div>
                            <?php endif; ?>
                            <div id="away_logo_preview" class="logo-preview-container hidden">
                                <p class="text-sm text-gray-500">New Logo Preview:</p>
                                <img id="away_logo_preview_img" class="preview-image">
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Home Score (Leave empty if upcoming)</label>
                            <input type="number" name="home_score" min="0" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                   value="<?php echo $editMode && $currentFixture['home_score'] !== NULL ? $currentFixture['home_score'] : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Away Score (Leave empty if upcoming)</label>
                            <input type="number" name="away_score" min="0" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                   value="<?php echo $editMode && $currentFixture['away_score'] !== NULL ? $currentFixture['away_score'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Competition *</label>
                            <input type="text" name="competition" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                                   value="<?php echo $editMode ? displayText($currentFixture['competition']) : ''; ?>" 
                                   required
                                   list="competitionList">
                            
                            <datalist id="competitionList">
                                <?php foreach ($competitions as $comp): ?>
                                    <option value="<?php echo displayText($comp); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                            <select name="gender" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" required>
                                <option value="MEN" <?php echo ($editMode && $currentFixture['gender'] == 'MEN') ? 'selected' : ''; ?>>Men</option>
                                <option value="WOMEN" <?php echo ($editMode && $currentFixture['gender'] == 'WOMEN') ? 'selected' : ''; ?>>Women</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Season *</label>
                            <select name="season" class="w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" required>
                                <?php 
                                $currentYear = date('Y');
                                for ($year = $currentYear; $year >= 2020; $year--) {
                                    $selected = ($editMode && $currentFixture['season'] == $year) ? 'selected' : '';
                                    echo "<option value='$year' $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <?php if ($editMode): ?>
                            <a href="upload_fixtures.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md transition duration-300">
                                Cancel
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                            <?php echo $editMode ? 'Update Fixture' : 'Add Fixture'; ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Fixtures List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">All Fixtures</h2>
                    <span class="text-sm text-gray-500"><?php echo count($fixtures); ?> fixtures</span>
                </div>
                
                <?php if (empty($fixtures)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No fixtures found. Add your first fixture using the form.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Competition</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($fixtures as $fixture): 
                                    $matchDate = new DateTime($fixture['match_date']);
                                    $isCompleted = $fixture['status'] == 'COMPLETED';
                                ?>
                                    <tr class="fixture-row <?php echo $isCompleted ? 'bg-gray-50' : ''; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $matchDate->format('M j, Y'); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $matchDate->format('g:i A'); ?></div>
                                            <?php if (!empty($fixture['stadium'])): ?>
                                                <div class="text-xs text-gray-400 mt-1"><?php echo displayText($fixture['stadium']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <?php if (!empty($fixture['home_logo'])): ?>
                                                        <img class="h-10 w-10 rounded-full" src="<?php echo displayText($fixture['home_logo']); ?>" alt="<?php echo displayText($fixture['home_team']); ?>">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                            <?php echo substr($fixture['home_team'], 0, 1); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo displayText($fixture['home_team']); ?></div>
                                                    <div class="text-sm text-gray-500">vs <?php echo displayText($fixture['away_team']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo displayText($fixture['competition']); ?></div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $fixture['gender'] == 'MEN' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                                    <?php echo $fixture['gender']; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($isCompleted): ?>
                                                <div class="text-sm font-bold text-gray-900">
                                                    <?php echo $fixture['home_score'] !== NULL ? $fixture['home_score'] : '?'; ?> - 
                                                    <?php echo $fixture['away_score'] !== NULL ? $fixture['away_score'] : '?'; ?>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">FINAL</div>
                                            <?php else: ?>
                                                <div class="text-sm text-gray-500">vs</div>
                                                <div class="text-xs text-gray-500 mt-1">UPCOMING</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="?edit=<?php echo $fixture['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this fixture?');">
                                                <input type="hidden" name="id" value="<?php echo $fixture['id']; ?>">
                                                <input type="hidden" name="delete" value="1">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

        // Image preview for uploads
        function handleFilePreview(inputId, previewId, previewContainerId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const previewContainer = document.getElementById(previewContainerId);
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '';
                previewContainer.classList.add('hidden');
            }
        }

        document.getElementById('home_logo').addEventListener('change', function() {
            handleFilePreview('home_logo', 'home_logo_preview_img', 'home_logo_preview');
        });

        document.getElementById('away_logo').addEventListener('change', function() {
            handleFilePreview('away_logo', 'away_logo_preview_img', 'away_logo_preview');
        });

        // Scroll to form when in edit mode
        <?php if ($editMode): ?>
            window.addEventListener('load', function() {
                document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
            });
        <?php endif; ?>
    </script>
</body>
</html>