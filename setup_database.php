<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');

// Error reporting and access control (restrict to localhost)
$__remote = $_SERVER['REMOTE_ADDR'] ?? '';
$__isLocal = in_array($__remote, ['127.0.0.1', '::1']);
if ($__isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup - 1000 Hills Rugby</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        .success { color: #10B981; }
        .error { color: #EF4444; }
        .warning { color: #F59E0B; }
        .info { color: #3B82F6; }
    </style>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6 flex items-center'>
                <i class='fas fa-database mr-3 text-green-600'></i>
                Database Setup - 1000 Hills Rugby
            </h1>";

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "<div class='mb-6 p-4 bg-green-100 border border-green-300 rounded-lg'>
        <i class='fas fa-check-circle text-green-600 mr-2'></i>
        <strong>Database connection successful!</strong>
    </div>";
    
    // Create genders table
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4'>1. Setting up Genders Table</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS genders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'><i class='fas fa-check mr-2'></i>Genders table created/verified</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times mr-2'></i>Error creating genders table: " . $conn->error . "</p>";
    }
    
    // Insert default genders if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM genders");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO genders (name) VALUES ('Men'), ('Women')");
        echo "<p class='success'><i class='fas fa-check mr-2'></i>Default genders (Men, Women) inserted</p>";
    } else {
        echo "<p class='info'><i class='fas fa-info-circle mr-2'></i>Genders table already has " . $row['count'] . " records</p>";
    }
    
    // Create seasons table
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4 mt-6'>2. Setting up Seasons Table</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS seasons (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        year INT(4) NOT NULL UNIQUE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'><i class='fas fa-check mr-2'></i>Seasons table created/verified</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times mr-2'></i>Error creating seasons table: " . $conn->error . "</p>";
    }
    
    // Insert default seasons if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM seasons");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $current_year = date('Y');
        for ($year = $current_year; $year >= $current_year - 5; $year--) {
            $conn->query("INSERT INTO seasons (year) VALUES ($year)");
        }
        echo "<p class='success'><i class='fas fa-check mr-2'></i>Default seasons (" . ($current_year - 5) . " to $current_year) inserted</p>";
    } else {
        echo "<p class='info'><i class='fas fa-info-circle mr-2'></i>Seasons table already has " . $row['count'] . " records</p>";
    }
    
    // Create competitions table
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4 mt-6'>3. Setting up Competitions Table</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS competitions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'><i class='fas fa-check mr-2'></i>Competitions table created/verified</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times mr-2'></i>Error creating competitions table: " . $conn->error . "</p>";
    }
    
    // Insert default competitions if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM competitions");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $competitions = [
            'GMT 7s',
            'Rwanda National League 15s',
            'Premier League',
            'Division 1',
            'Division 2',
            'Cup Competition'
        ];
        
        foreach ($competitions as $comp) {
            $stmt = $conn->prepare("INSERT INTO competitions (name) VALUES (?)");
            $stmt->bind_param("s", $comp);
            $stmt->execute();
            $stmt->close();
        }
        echo "<p class='success'><i class='fas fa-check mr-2'></i>Default competitions inserted</p>";
    } else {
        echo "<p class='info'><i class='fas fa-info-circle mr-2'></i>Competitions table already has " . $row['count'] . " records</p>";
    }
    
    // Create teams table
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4 mt-6'>4. Setting up Teams Table</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS teams (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        logo VARCHAR(255) DEFAULT 'default.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'><i class='fas fa-check mr-2'></i>Teams table created/verified</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times mr-2'></i>Error creating teams table: " . $conn->error . "</p>";
    }
    
    // Create league_standings table
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4 mt-6'>5. Setting up League Standings Table</h2>";
    $sql = "CREATE TABLE IF NOT EXISTS league_standings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        team_id INT(11) NOT NULL,
        competition_id INT(11) NOT NULL,
        season_id INT(11) NOT NULL,
        gender_id INT(11) NOT NULL,
        matches_played INT(11) DEFAULT 0,
        matches_won INT(11) DEFAULT 0,
        matches_drawn INT(11) DEFAULT 0,
        matches_lost INT(11) DEFAULT 0,
        tries_for INT(11) DEFAULT 0,
        tries_against INT(11) DEFAULT 0,
        points_for INT(11) DEFAULT 0,
        points_against INT(11) DEFAULT 0,
        points_difference INT(11) DEFAULT 0,
        league_points INT(11) DEFAULT 0,
        form_score DECIMAL(3,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
        FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
        FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
        FOREIGN KEY (gender_id) REFERENCES genders(id) ON DELETE CASCADE,
        UNIQUE KEY unique_team_competition (team_id, competition_id, season_id, gender_id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'><i class='fas fa-check mr-2'></i>League standings table created/verified</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times mr-2'></i>Error creating league standings table: " . $conn->error . "</p>";
    }
    
    // Show current data summary
    echo "<h2 class='text-xl font-semibold text-gray-700 mb-4 mt-6'>6. Current Database Summary</h2>";
    echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
    
    $tables = ['genders', 'seasons', 'competitions', 'teams', 'league_standings'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $row = $result->fetch_assoc();
        echo "<div class='bg-gray-50 p-4 rounded-lg'>
            <h3 class='font-semibold text-gray-700'>$table</h3>
            <p class='text-2xl font-bold text-green-600'>" . $row['count'] . " records</p>
        </div>";
    }
    echo "</div>";
    
    echo "<div class='mt-8 p-6 bg-green-50 border border-green-200 rounded-lg'>
        <h3 class='text-lg font-semibold text-green-800 mb-2'>
            <i class='fas fa-check-circle mr-2'></i>Database setup completed successfully!
        </h3>
        <p class='text-green-700 mb-4'>All required tables have been created and populated with default data.</p>
        <div class='flex flex-wrap gap-4'>
            <a href='uploadtables' class='px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors'>
                <i class='fas fa-cog mr-2'></i>Manage Standings
            </a>
            <a href='tables' class='px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors'>
                <i class='fas fa-table mr-2'></i>View League Tables
            </a>
        </div>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='p-6 bg-red-50 border border-red-200 rounded-lg'>
        <h3 class='text-lg font-semibold text-red-800 mb-2'>
            <i class='fas fa-exclamation-triangle mr-2'></i>Setup Error
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