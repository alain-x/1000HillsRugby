<?php
// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle delete action
    if (isset($_POST['delete_id'])) {
        $delete_id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM fixtures WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        header("Location: upload_fixtures.php?success=Fixture deleted");
        exit();
    }
    
    // Handle upload/edit action
    if (isset($_POST['home_team'])) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $home_team = $conn->real_escape_string($_POST['home_team']);
        $away_team = $conn->real_escape_string($_POST['away_team']);
        $match_date = $conn->real_escape_string($_POST['match_date']);
        $competition = $conn->real_escape_string($_POST['competition']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $season = intval($_POST['season']);
        $stadium = $conn->real_escape_string($_POST['stadium']);
        $status = $conn->real_escape_string($_POST['status']);
        $home_score = isset($_POST['home_score']) ? intval($_POST['home_score']) : NULL;
        $away_score = isset($_POST['away_score']) ? intval($_POST['away_score']) : NULL;
        
        // Handle file uploads
        $home_logo = '';
        if (isset($_FILES['home_logo']) && $_FILES['home_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'logos_/';
            $file_name = basename($_FILES['home_logo']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_name = 'home_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['home_logo']['tmp_name'], $target_path)) {
                $home_logo = $conn->real_escape_string($new_name);
            }
        }
        
        $away_logo = '';
        if (isset($_FILES['away_logo']) && $_FILES['away_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'logos_/';
            $file_name = basename($_FILES['away_logo']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_name = 'away_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $new_name;
            
            if (move_uploaded_file($_FILES['away_logo']['tmp_name'], $target_path)) {
                $away_logo = $conn->real_escape_string($new_name);
            }
        }
        
        if ($id > 0) {
            // Update existing fixture
            $sql = "UPDATE fixtures SET 
                    home_team = ?, 
                    away_team = ?, 
                    match_date = ?, 
                    competition = ?, 
                    gender = ?, 
                    season = ?, 
                    stadium = ?, 
                    status = ?, 
                    home_score = ?, 
                    away_score = ?";
            
            $params = [$home_team, $away_team, $match_date, $competition, $gender, $season, $stadium, $status, $home_score, $away_score];
            $types = "sssssisssi";
            
            if (!empty($home_logo)) {
                $sql .= ", home_logo = ?";
                $params[] = $home_logo;
                $types .= "s";
            }
            
            if (!empty($away_logo)) {
                $sql .= ", away_logo = ?";
                $params[] = $away_logo;
                $types .= "s";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
            
            header("Location: upload_fixtures.php?success=Fixture updated");
            exit();
        } else {
            // Insert new fixture
            $sql = "INSERT INTO fixtures (home_team, away_team, match_date, competition, gender, season, stadium, status, home_score, away_score, home_logo, away_logo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssisssiss", $home_team, $away_team, $match_date, $competition, $gender, $season, $stadium, $status, $home_score, $away_score, $home_logo, $away_logo);
            $stmt->execute();
            $stmt->close();
            
            header("Location: upload_fixtures.php?success=Fixture added");
            exit();
        }
    }
}

// Get all fixtures
$fixtures = [];
$result = $conn->query("SELECT * FROM fixtures ORDER BY match_date DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fixtures[] = $row;
    }
}

// Get all competitions for dropdown
$competitions = [];
$compResult = $conn->query("SELECT DISTINCT competition FROM fixtures ORDER BY competition");
if ($compResult->num_rows > 0) {
    while ($row = $compResult->fetch_assoc()) {
        $competitions[] = $row['competition'];
    }
}

$conn->close();

// Get fixture to edit (if specified)
$edit_fixture = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($fixtures as $fixture) {
        if ($fixture['id'] == $edit_id) {
            $edit_fixture = $fixture;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Fixtures - 1000 Hills Rugby</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .logo-preview {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
        }
        .fixture-row:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Fixtures</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4"><?php echo $edit_fixture ? 'Edit Fixture' : 'Add New Fixture'; ?></h2>
            
            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if ($edit_fixture): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_fixture['id']; ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Home Team</label>
                    <input type="text" name="home_team" required 
                           value="<?php echo $edit_fixture ? htmlspecialchars($edit_fixture['home_team']) : ''; ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Away Team</label>
                    <input type="text" name="away_team" required 
                           value="<?php echo $edit_fixture ? htmlspecialchars($edit_fixture['away_team']) : ''; ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Match Date & Time</label>
                    <input type="datetime-local" name="match_date" required 
                           value="<?php echo $edit_fixture ? str_replace(' ', 'T', substr($edit_fixture['match_date'], 0, 16)) : ''; ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Competition</label>
                    <input type="text" name="competition" list="competitions" required 
                           value="<?php echo $edit_fixture ? htmlspecialchars($edit_fixture['competition']) : ''; ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                    <datalist id="competitions">
                        <?php foreach ($competitions as $comp): ?>
                            <option value="<?php echo htmlspecialchars($comp); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="MEN" <?php echo ($edit_fixture && $edit_fixture['gender'] == 'MEN') ? 'selected' : ''; ?>>Men</option>
                        <option value="WOMEN" <?php echo ($edit_fixture && $edit_fixture['gender'] == 'WOMEN') ? 'selected' : ''; ?>>Women</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Season</label>
                    <input type="number" name="season" required min="2000" max="2100" 
                           value="<?php echo $edit_fixture ? $edit_fixture['season'] : date('Y'); ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stadium</label>
                    <input type="text" name="stadium" 
                           value="<?php echo $edit_fixture ? htmlspecialchars($edit_fixture['stadium']) : ''; ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full p-2 border border-gray-300 rounded-md">
                        <option value="SCHEDULED" <?php echo ($edit_fixture && $edit_fixture['status'] == 'SCHEDULED') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="COMPLETED" <?php echo ($edit_fixture && $edit_fixture['status'] == 'COMPLETED') ? 'selected' : ''; ?>>Completed</option>
                        <option value="POSTPONED" <?php echo ($edit_fixture && $edit_fixture['status'] == 'POSTPONED') ? 'selected' : ''; ?>>Postponed</option>
                        <option value="CANCELLED" <?php echo ($edit_fixture && $edit_fixture['status'] == 'CANCELLED') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Home Score</label>
                    <input type="number" name="home_score" min="0" 
                           value="<?php echo $edit_fixture ? $edit_fixture['home_score'] : ''; ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Away Score</label>
                    <input type="number" name="away_score" min="0" 
                           value="<?php echo $edit_fixture ? $edit_fixture['away_score'] : ''; ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Home Team Logo</label>
                    <input type="file" name="home_logo" accept="image/*" class="w-full p-2 border border-gray-300 rounded-md">
                    <?php if ($edit_fixture && !empty($edit_fixture['home_logo'])): ?>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Current logo:</p>
                            <img src="logos_/<?php echo htmlspecialchars($edit_fixture['home_logo']); ?>" class="logo-preview">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Away Team Logo</label>
                    <input type="file" name="away_logo" accept="image/*" class="w-full p-2 border border-gray-300 rounded-md">
                    <?php if ($edit_fixture && !empty($edit_fixture['away_logo'])): ?>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Current logo:</p>
                            <img src="logos_/<?php echo htmlspecialchars($edit_fixture['away_logo']); ?>" class="logo-preview">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="md:col-span-2 flex justify-end space-x-4 mt-4">
                    <?php if ($edit_fixture): ?>
                        <a href="upload_fixtures.php" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <?php echo $edit_fixture ? 'Update Fixture' : 'Add Fixture'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Fixtures List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <h2 class="text-xl font-semibold p-4 border-b">All Fixtures</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Match</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Competition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($fixtures as $fixture): ?>
                            <tr class="fixture-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y H:i', strtotime($fixture['match_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if (!empty($fixture['home_logo'])): ?>
                                                <img class="h-10 w-10 rounded-full" src="logos_/<?php echo htmlspecialchars($fixture['home_logo']); ?>" alt="<?php echo htmlspecialchars($fixture['home_team']); ?>">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold">
                                                    <?php echo substr($fixture['home_team'], 0, 1); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fixture['home_team']); ?></div>
                                            <div class="text-sm text-gray-500">vs <?php echo htmlspecialchars($fixture['away_team']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($fixture['competition']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($fixture['status'] == 'COMPLETED'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <?php echo $fixture['home_score']; ?> - <?php echo $fixture['away_score']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $fixture['status'] == 'SCHEDULED' ? 'bg-blue-100 text-blue-800' : 
                                                 ($fixture['status'] == 'POSTPONED' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo $fixture['status']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="upload_fixtures.php?edit=<?php echo $fixture['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this fixture?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $fixture['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>