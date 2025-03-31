<?php
// Start session and check admin login
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM fixtures WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Fixture deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting fixture: " . $stmt->error;
    }
    $stmt->close();
    header("Location: upload_fixtures.php");
    exit();
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $match_date = $conn->real_escape_string($_POST['match_date']);
    $home_team = $conn->real_escape_string($_POST['home_team']);
    $away_team = $conn->real_escape_string($_POST['away_team']);
    $competition = $conn->real_escape_string($_POST['competition']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $season = intval($_POST['season']);
    $stadium = $conn->real_escape_string($_POST['stadium']);
    $home_logo = $conn->real_escape_string($_POST['home_logo']);
    $away_logo = $conn->real_escape_string($_POST['away_logo']);
    $status = $conn->real_escape_string($_POST['status']);
    $home_score = isset($_POST['home_score']) ? intval($_POST['home_score']) : NULL;
    $away_score = isset($_POST['away_score']) ? intval($_POST['away_score']) : NULL;

    if ($id > 0) {
        // Update existing fixture
        $stmt = $conn->prepare("UPDATE fixtures SET 
            match_date = ?, home_team = ?, away_team = ?, competition = ?, 
            gender = ?, season = ?, stadium = ?, home_logo = ?, away_logo = ?, 
            status = ?, home_score = ?, away_score = ? 
            WHERE id = ?");
        $stmt->bind_param("sssssisssssii", 
            $match_date, $home_team, $away_team, $competition, 
            $gender, $season, $stadium, $home_logo, $away_logo, 
            $status, $home_score, $away_score, $id);
    } else {
        // Insert new fixture
        $stmt = $conn->prepare("INSERT INTO fixtures 
            (match_date, home_team, away_team, competition, gender, 
            season, stadium, home_logo, away_logo, status, home_score, away_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssisssssii", 
            $match_date, $home_team, $away_team, $competition, 
            $gender, $season, $stadium, $home_logo, $away_logo, 
            $status, $home_score, $away_score);
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = $id > 0 ? "Fixture updated successfully" : "Fixture added successfully";
    } else {
        $_SESSION['error'] = "Error saving fixture: " . $stmt->error;
    }
    $stmt->close();
    header("Location: upload_fixtures.php");
    exit();
}

// Get all fixtures
$fixtures = [];
$result = $conn->query("SELECT * FROM fixtures ORDER BY match_date DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fixtures[] = $row;
    }
}

// Get unique competitions for dropdown
$competitions = [];
$compResult = $conn->query("SELECT DISTINCT competition FROM fixtures ORDER BY competition");
if ($compResult->num_rows > 0) {
    while ($row = $compResult->fetch_assoc()) {
        $competitions[] = $row['competition'];
    }
}

// Get fixture data for editing
$editFixture = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM fixtures WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editFixture = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fixtures - 1000 Hills Rugby</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .logo-preview {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 10px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Manage Fixtures</h1>
            <a href="admin_dashboard.php" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">
                <?php echo $editFixture ? 'Edit Fixture' : 'Add New Fixture'; ?>
            </h2>
            
            <form method="POST" action="upload_fixtures.php">
                <input type="hidden" name="id" value="<?php echo $editFixture ? $editFixture['id'] : 0; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Match Date & Time</label>
                        <input type="datetime-local" name="match_date" 
                            value="<?php echo $editFixture ? str_replace(' ', 'T', substr($editFixture['match_date'], 0, 16)) : ''; ?>" 
                            class="w-full px-3 py-2 border rounded" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Competition</label>
                        <input type="text" name="competition" list="competitions" 
                            value="<?php echo $editFixture ? htmlspecialchars($editFixture['competition']) : ''; ?>" 
                            class="w-full px-3 py-2 border rounded" required>
                        <datalist id="competitions">
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?php echo htmlspecialchars($comp); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Home Team</label>
                        <input type="text" name="home_team" 
                            value="<?php echo $editFixture ? htmlspecialchars($editFixture['home_team']) : ''; ?>" 
                            class="w-full px-3 py-2 border rounded" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Away Team</label>
                        <input type="text" name="away_team" 
                            value="<?php echo $editFixture ? htmlspecialchars($editFixture['away_team']) : ''; ?>" 
                            class="w-full px-3 py-2 border rounded" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Stadium/Venue</label>
                        <input type="text" name="stadium" 
                            value="<?php echo $editFixture ? htmlspecialchars($editFixture['stadium']) : ''; ?>" 
                            class="w-full px-3 py-2 border rounded">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Home Team Logo URL</label>
                        <div class="flex items-center">
                            <?php if ($editFixture && !empty($editFixture['home_logo'])): ?>
                                <img src="<?php echo htmlspecialchars($editFixture['home_logo']); ?>" class="logo-preview">
                            <?php endif; ?>
                            <input type="text" name="home_logo" 
                                value="<?php echo $editFixture ? htmlspecialchars($editFixture['home_logo']) : ''; ?>" 
                                class="flex-1 px-3 py-2 border rounded" placeholder="URL to logo">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Away Team Logo URL</label>
                        <div class="flex items-center">
                            <?php if ($editFixture && !empty($editFixture['away_logo'])): ?>
                                <img src="<?php echo htmlspecialchars($editFixture['away_logo']); ?>" class="logo-preview">
                            <?php endif; ?>
                            <input type="text" name="away_logo" 
                                value="<?php echo $editFixture ? htmlspecialchars($editFixture['away_logo']) : ''; ?>" 
                                class="flex-1 px-3 py-2 border rounded" placeholder="URL to logo">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Gender</label>
                        <select name="gender" class="w-full px-3 py-2 border rounded" required>
                            <option value="MEN" <?php echo ($editFixture && $editFixture['gender'] == 'MEN') ? 'selected' : ''; ?>>Men</option>
                            <option value="WOMEN" <?php echo ($editFixture && $editFixture['gender'] == 'WOMEN') ? 'selected' : ''; ?>>Women</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Season</label>
                        <input type="number" name="season" min="2000" max="2099" 
                            value="<?php echo $editFixture ? $editFixture['season'] : date('Y'); ?>" 
                            class="w-full px-3 py-2 border rounded" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-3 py-2 border rounded" required>
                            <option value="SCHEDULED" <?php echo ($editFixture && $editFixture['status'] == 'SCHEDULED') ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="POSTPONED" <?php echo ($editFixture && $editFixture['status'] == 'POSTPONED') ? 'selected' : ''; ?>>Postponed</option>
                            <option value="CANCELLED" <?php echo ($editFixture && $editFixture['status'] == 'CANCELLED') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="COMPLETED" <?php echo ($editFixture && $editFixture['status'] == 'COMPLETED') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Home Score</label>
                        <input type="number" name="home_score" min="0" 
                            value="<?php echo $editFixture ? $editFixture['home_score'] : ''; ?>" 
                            class="w-full px-3 py-2 border rounded">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Away Score</label>
                        <input type="number" name="away_score" min="0" 
                            value="<?php echo $editFixture ? $editFixture['away_score'] : ''; ?>" 
                            class="w-full px-3 py-2 border rounded">
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <?php if ($editFixture): ?>
                        <a href="upload_fixtures.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded mr-2 hover:bg-gray-400 transition">
                            Cancel
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        <?php echo $editFixture ? 'Update Fixture' : 'Add Fixture'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Fixtures List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">All Fixtures</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-800 text-white">
                            <th class="py-2 px-4">Date & Time</th>
                            <th class="py-2 px-4">Match</th>
                            <th class="py-2 px-4">Competition</th>
                            <th class="py-2 px-4">Gender</th>
                            <th class="py-2 px-4">Status</th>
                            <th class="py-2 px-4">Score</th>
                            <th class="py-2 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fixtures)): ?>
                            <tr>
                                <td colspan="7" class="py-4 px-4 text-center text-gray-500">No fixtures found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fixtures as $fixture): 
                                $matchDate = new DateTime($fixture['match_date']);
                                $isCompleted = $fixture['status'] == 'COMPLETED';
                            ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <?php echo $matchDate->format('M j, Y H:i'); ?>
                                    </td>
                                    <td class="py-3 px-4 font-medium">
                                        <?php echo htmlspecialchars($fixture['home_team']) . ' vs ' . htmlspecialchars($fixture['away_team']); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php echo htmlspecialchars($fixture['competition']); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs 
                                            <?php echo $fixture['gender'] == 'MEN' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                            <?php echo $fixture['gender']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs 
                                            <?php echo $isCompleted ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $fixture['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($isCompleted && $fixture['home_score'] !== null && $fixture['away_score'] !== null): ?>
                                            <span class="font-bold">
                                                <?php echo $fixture['home_score'] . ' - ' . $fixture['away_score']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <a href="upload_fixtures.php?edit=<?php echo $fixture['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="upload_fixtures.php?delete=<?php echo $fixture['id']; ?>" 
                                               class="text-red-600 hover:text-red-800 transition" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this fixture?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Show logo preview when URL changes
        document.querySelector('input[name="home_logo"]').addEventListener('input', function(e) {
            const preview = this.previousElementSibling;
            if (this.value && preview && preview.tagName === 'IMG') {
                preview.src = this.value;
            }
        });
        
        document.querySelector('input[name="away_logo"]').addEventListener('input', function(e) {
            const preview = this.previousElementSibling;
            if (this.value && preview && preview.tagName === 'IMG') {
                preview.src = this.value;
            }
        });
    </script>
</body>
</html>