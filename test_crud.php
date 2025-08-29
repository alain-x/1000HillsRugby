<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');
define('LOGO_DIR', __DIR__ . '/logos_/');
define('DEFAULT_LOGO', 'default.png');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>CRUD Test - 1000 Hills Rugby</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6'>
                <i class='fas fa-vial mr-3 text-blue-600'></i>CRUD Operations Test
            </h1>";

try {
    // Test database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "<div class='mb-6 p-4 bg-green-100 border border-green-300 rounded-lg'>
        <i class='fas fa-check-circle text-green-600 mr-2'></i>
        <strong>Database connection successful!</strong>
    </div>";
    
    // Test CREATE operation
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4'>1. Testing CREATE Operation</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_create'])) {
        $team_name = $_POST['team_name'] ?? 'Test Team';
        $competition_id = $_POST['competition_id'] ?? 1;
        $season_id = $_POST['season_id'] ?? 1;
        $gender_id = $_POST['gender_id'] ?? 1;
        
        $conn->begin_transaction();
        
        try {
            // Insert team
            $stmt = $conn->prepare("INSERT INTO teams (name, logo) VALUES (?, ?)");
            $logo = 'default.png';
            $stmt->bind_param("ss", $team_name, $logo);
            
            if ($stmt->execute()) {
                $team_id = $stmt->insert_id;
                echo "<p class='text-green-600'><i class='fas fa-check mr-2'></i>Team created with ID: $team_id</p>";
                
                // Insert into standings
                $stmt2 = $conn->prepare("INSERT INTO league_standings (team_id, competition_id, season_id, gender_id) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iiii", $team_id, $competition_id, $season_id, $gender_id);
                
                if ($stmt2->execute()) {
                    echo "<p class='text-green-600'><i class='fas fa-check mr-2'></i>Team added to standings</p>";
                    $conn->commit();
                } else {
                    throw new Exception("Failed to add to standings: " . $stmt2->error);
                }
                $stmt2->close();
            } else {
                throw new Exception("Failed to create team: " . $stmt->error);
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<p class='text-red-600'><i class='fas fa-times mr-2'></i>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test READ operation
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4 mt-6'>2. Testing READ Operation</h2>";
    
    $result = $conn->query("
        SELECT t.name as team_name, c.name as competition, s.year as season, g.name as gender
        FROM teams t
        JOIN league_standings ls ON t.id = ls.team_id
        JOIN competitions c ON ls.competition_id = c.id
        JOIN seasons s ON ls.season_id = s.id
        JOIN genders g ON ls.gender_id = g.id
        ORDER BY t.name
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='overflow-x-auto'>
            <table class='min-w-full divide-y divide-gray-200'>
                <thead class='bg-gray-50'>
                    <tr>
                        <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Team</th>
                        <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Competition</th>
                        <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Season</th>
                        <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Gender</th>
                    </tr>
                </thead>
                <tbody class='bg-white divide-y divide-gray-200'>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($row['team_name']) . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['competition']) . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['season']) . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['gender']) . "</td>
            </tr>";
        }
        
        echo "</tbody></table></div>";
    } else {
        echo "<p class='text-yellow-600'><i class='fas fa-exclamation-triangle mr-2'></i>No teams found in database</p>";
    }
    
    // Test form for CREATE
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4 mt-6'>3. Test Create Team</h2>";
    echo "<form method='POST' class='space-y-4'>
        <div>
            <label class='block text-sm font-medium text-gray-700'>Team Name</label>
            <input type='text' name='team_name' value='Test Team " . date('His') . "' required class='mt-1 block w-full border border-gray-300 rounded-md px-3 py-2'>
        </div>
        <div class='grid grid-cols-3 gap-4'>
            <div>
                <label class='block text-sm font-medium text-gray-700'>Competition</label>
                <select name='competition_id' required class='mt-1 block w-full border border-gray-300 rounded-md px-3 py-2'>";
    
    $result = $conn->query("SELECT id, name FROM competitions ORDER BY name");
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
    }
    
    echo "</select></div>
            <div>
                <label class='block text-sm font-medium text-gray-700'>Season</label>
                <select name='season_id' required class='mt-1 block w-full border border-gray-300 rounded-md px-3 py-2'>";
    
    $result = $conn->query("SELECT id, year FROM seasons ORDER BY year DESC");
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['year']) . "</option>";
    }
    
    echo "</select></div>
            <div>
                <label class='block text-sm font-medium text-gray-700'>Gender</label>
                <select name='gender_id' required class='mt-1 block w-full border border-gray-300 rounded-md px-3 py-2'>";
    
    $result = $conn->query("SELECT id, name FROM genders ORDER BY name");
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
    }
    
    echo "</select></div>
        </div>
        <button type='submit' name='test_create' class='px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700'>
            <i class='fas fa-plus mr-2'></i>Create Test Team
        </button>
    </form>";
    
    // Navigation links
    echo "<div class='mt-8 p-6 bg-gray-50 rounded-lg'>
        <h3 class='text-lg font-semibold text-gray-700 mb-4'>Navigation</h3>
        <div class='flex flex-wrap gap-4'>
            <a href='uploadtables' class='px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors'>
                <i class='fas fa-cog mr-2'></i>Manage Standings
            </a>
            <a href='tables' class='px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors'>
                <i class='fas fa-table mr-2'></i>View League Tables
            </a>
            <a href='setup_database.php' class='px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors'>
                <i class='fas fa-database mr-2'></i>Setup Database
            </a>
        </div>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='p-6 bg-red-50 border border-red-200 rounded-lg'>
        <h3 class='text-lg font-semibold text-red-800 mb-2'>
            <i class='fas fa-exclamation-triangle mr-2'></i>Test Error
        </h3>
        <p class='text-red-700'>" . $e->getMessage() . "</p>
    </div>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo "</div></div></body></html>";
?> 