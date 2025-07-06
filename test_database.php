<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    // Test database connection
    echo "<h2>1. Testing Database Connection</h2>";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Database connection successful<br>";
    echo "Server info: " . $conn->server_info . "<br>";
    
    // Check if database exists
    echo "<h2>2. Checking Database</h2>";
    $result = $conn->query("SELECT DATABASE() as db_name");
    $row = $result->fetch_assoc();
    echo "Current database: " . $row['db_name'] . "<br>";
    
    // Check existing tables
    echo "<h2>3. Checking Existing Tables</h2>";
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    echo "Existing tables: " . implode(', ', $tables) . "<br>";
    
    // Check required tables
    $required_tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
    echo "<h2>4. Checking Required Tables</h2>";
    
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ Table '$table' exists<br>";
            
            // Check table structure
            $result = $conn->query("DESCRIBE $table");
            echo "  Columns: ";
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            echo implode(', ', $columns) . "<br>";
            
            // Check if table has data
            $result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $row = $result->fetch_assoc();
            echo "  Records: " . $row['count'] . "<br><br>";
        } else {
            echo "✗ Table '$table' MISSING<br>";
        }
    }
    
    // Test form submission
    echo "<h2>5. Testing Form Submission</h2>";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "POST data received:<br>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        
        if (isset($_POST['test_add_team'])) {
            $team_name = $_POST['team_name'] ?? '';
            $competition_id = $_POST['competition_id'] ?? '';
            $season_id = $_POST['season_id'] ?? '';
            $gender_id = $_POST['gender_id'] ?? '';
            
            echo "Team Name: $team_name<br>";
            echo "Competition ID: $competition_id<br>";
            echo "Season ID: $season_id<br>";
            echo "Gender ID: $gender_id<br>";
            
            // Test inserting into teams table
            if (in_array('teams', $tables)) {
                $stmt = $conn->prepare("INSERT INTO teams (name, logo) VALUES (?, 'default.png')");
                if ($stmt) {
                    $stmt->bind_param("s", $team_name);
                    if ($stmt->execute()) {
                        echo "✓ Successfully inserted team into teams table<br>";
                        $team_id = $stmt->insert_id;
                        echo "Team ID: $team_id<br>";
                        
                        // Test inserting into league_standings
                        if (in_array('league_standings', $tables)) {
                            $stmt2 = $conn->prepare("INSERT INTO league_standings (team_id, competition_id, season_id, gender_id) VALUES (?, ?, ?, ?)");
                            if ($stmt2) {
                                $stmt2->bind_param("iiii", $team_id, $competition_id, $season_id, $gender_id);
                                if ($stmt2->execute()) {
                                    echo "✓ Successfully inserted into league_standings<br>";
                                } else {
                                    echo "✗ Failed to insert into league_standings: " . $stmt2->error . "<br>";
                                }
                                $stmt2->close();
                            } else {
                                echo "✗ Failed to prepare league_standings insert: " . $conn->error . "<br>";
                            }
                        }
                    } else {
                        echo "✗ Failed to insert team: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    echo "✗ Failed to prepare team insert: " . $conn->error . "<br>";
                }
            }
        }
    }
    
    // Show test form
    echo "<h2>6. Test Form</h2>";
    echo "<form method='POST'>";
    echo "<input type='text' name='team_name' placeholder='Team Name' required><br>";
    echo "<select name='competition_id' required>";
    echo "<option value=''>Select Competition</option>";
    if (in_array('competitions', $tables)) {
        $result = $conn->query("SELECT id, name FROM competitions");
        while ($row = $result->fetch_assoc()) {
            echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
        }
    }
    echo "</select><br>";
    echo "<select name='season_id' required>";
    echo "<option value=''>Select Season</option>";
    if (in_array('seasons', $tables)) {
        $result = $conn->query("SELECT id, year FROM seasons");
        while ($row = $result->fetch_assoc()) {
            echo "<option value='" . $row['id'] . "'>" . $row['year'] . "</option>";
        }
    }
    echo "</select><br>";
    echo "<select name='gender_id' required>";
    echo "<option value=''>Select Gender</option>";
    if (in_array('genders', $tables)) {
        $result = $conn->query("SELECT id, name FROM genders");
        while ($row = $result->fetch_assoc()) {
            echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
        }
    }
    echo "</select><br>";
    echo "<button type='submit' name='test_add_team'>Test Add Team</button>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 