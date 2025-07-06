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
    <title>Debug Team Addition</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6'>Debug Team Addition</h1>";

// Test database connection
echo "<h2 class='text-xl font-semibold mb-4'>1. Database Connection Test</h2>";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p class='text-green-600'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='text-red-600'>✗ " . $e->getMessage() . "</p>";
    exit;
}

// Check if tables exist
echo "<h2 class='text-xl font-semibold mb-4 mt-6'>2. Check Tables</h2>";
$tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p class='text-green-600'>✓ Table '$table' exists</p>";
    } else {
        echo "<p class='text-red-600'>✗ Table '$table' MISSING</p>";
    }
}

// Check if tables have data
echo "<h2 class='text-xl font-semibold mb-4 mt-6'>3. Check Table Data</h2>";
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $row = $result->fetch_assoc();
    echo "<p>Table '$table': " . $row['count'] . " records</p>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2 class='text-xl font-semibold mb-4 mt-6'>4. Form Submission Debug</h2>";
    echo "<p><strong>POST Data:</strong></p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    if (isset($_POST['add_team'])) {
        echo "<p><strong>Processing team addition...</strong></p>";
        
        $team_name = trim($_POST['team_name'] ?? '');
        $competition_id = (int)($_POST['competition_id'] ?? 0);
        $season_id = (int)($_POST['season_id'] ?? 0);
        $gender_id = (int)($_POST['gender_id'] ?? 0);
        
        echo "<p>Team Name: '$team_name'</p>";
        echo "<p>Competition ID: $competition_id</p>";
        echo "<p>Season ID: $season_id</p>";
        echo "<p>Gender ID: $gender_id</p>";
        
        // Validate inputs
        if (empty($team_name)) {
            echo "<p class='text-red-600'>✗ Team name is empty</p>";
        } elseif ($competition_id <= 0) {
            echo "<p class='text-red-600'>✗ Invalid competition ID</p>";
        } elseif ($season_id <= 0) {
            echo "<p class='text-red-600'>✗ Invalid season ID</p>";
        } elseif ($gender_id <= 0) {
            echo "<p class='text-red-600'>✗ Invalid gender ID</p>";
        } else {
            // Try to insert team
            echo "<p><strong>Attempting to insert team...</strong></p>";
            
            $conn->begin_transaction();
            
            try {
                // Check if team exists
                $stmt = $conn->prepare("SELECT id FROM teams WHERE name = ?");
                $stmt->bind_param("s", $team_name);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    throw new Exception("Team already exists");
                }
                $stmt->close();
                
                echo "<p>✓ Team name is unique</p>";
                
                // Insert team
                $stmt = $conn->prepare("INSERT INTO teams (name, logo) VALUES (?, ?)");
                $logo = 'default.png';
                $stmt->bind_param("ss", $team_name, $logo);
                
                if ($stmt->execute()) {
                    $team_id = $stmt->insert_id;
                    echo "<p class='text-green-600'>✓ Team inserted with ID: $team_id</p>";
                    $stmt->close();
                    
                    // Insert into standings
                    $stmt = $conn->prepare("INSERT INTO league_standings (team_id, competition_id, season_id, gender_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiii", $team_id, $competition_id, $season_id, $gender_id);
                    
                    if ($stmt->execute()) {
                        echo "<p class='text-green-600'>✓ Team added to standings</p>";
                        $conn->commit();
                        echo "<p class='text-green-600 font-bold'>✓ SUCCESS: Team added successfully!</p>";
                    } else {
                        throw new Exception("Failed to add to standings: " . $stmt->error);
                    }
                    $stmt->close();
                    
                } else {
                    throw new Exception("Failed to insert team: " . $stmt->error);
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                echo "<p class='text-red-600'>✗ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// Show current teams
echo "<h2 class='text-xl font-semibold mb-4 mt-6'>5. Current Teams</h2>";
$result = $conn->query("
    SELECT t.name as team_name, c.name as competition, s.year as season, g.name as gender
    FROM teams t
    LEFT JOIN league_standings ls ON t.id = ls.team_id
    LEFT JOIN competitions c ON ls.competition_id = c.id
    LEFT JOIN seasons s ON ls.season_id = s.id
    LEFT JOIN genders g ON ls.gender_id = g.id
    ORDER BY t.name
");

if ($result && $result->num_rows > 0) {
    echo "<table class='min-w-full divide-y divide-gray-200'>
        <thead class='bg-gray-50'>
            <tr>
                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Team</th>
                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Competition</th>
                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Season</th>
                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Gender</th>
            </tr>
        </thead>
        <tbody class='bg-white divide-y divide-gray-200'>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($row['team_name']) . "</td>
            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['competition'] ?? 'N/A') . "</td>
            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['season'] ?? 'N/A') . "</td>
            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['gender'] ?? 'N/A') . "</td>
        </tr>";
    }
    
    echo "</tbody></table>";
} else {
    echo "<p class='text-yellow-600'>No teams found in database</p>";
}

// Show form
echo "<h2 class='text-xl font-semibold mb-4 mt-6'>6. Add Team Form</h2>";
echo "<form method='POST' class='space-y-4'>
    <div>
        <label class='block text-sm font-medium text-gray-700'>Team Name</label>
        <input type='text' name='team_name' required class='mt-1 block w-full border border-gray-300 rounded-md px-3 py-2'>
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
    <button type='submit' name='add_team' class='px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700'>
        Add Team
    </button>
</form>";

echo "<div class='mt-8 p-4 bg-gray-50 rounded-lg'>
    <h3 class='text-lg font-semibold mb-2'>Quick Links</h3>
    <div class='flex gap-4'>
        <a href='uploadtables.php' class='px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700'>Main Upload Page</a>
        <a href='tables.php' class='px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700'>View Tables</a>
        <a href='setup_database.php' class='px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700'>Setup Database</a>
    </div>
</div>";

$conn->close();

echo "</div></div></body></html>";
?> 