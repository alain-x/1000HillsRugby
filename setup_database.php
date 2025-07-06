<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "<h2>Setting up database tables...</h2>";
    
    // Create genders table
    $sql = "CREATE TABLE IF NOT EXISTS genders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Genders table created/verified<br>";
    } else {
        echo "✗ Error creating genders table: " . $conn->error . "<br>";
    }
    
    // Insert default genders if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM genders");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO genders (name) VALUES ('Men'), ('Women')");
        echo "✓ Default genders inserted<br>";
    }
    
    // Create seasons table
    $sql = "CREATE TABLE IF NOT EXISTS seasons (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        year INT(4) NOT NULL UNIQUE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Seasons table created/verified<br>";
    } else {
        echo "✗ Error creating seasons table: " . $conn->error . "<br>";
    }
    
    // Insert default seasons if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM seasons");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $current_year = date('Y');
        for ($year = $current_year; $year >= $current_year - 5; $year--) {
            $conn->query("INSERT INTO seasons (year) VALUES ($year)");
        }
        echo "✓ Default seasons inserted<br>";
    }
    
    // Create competitions table
    $sql = "CREATE TABLE IF NOT EXISTS competitions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Competitions table created/verified<br>";
    } else {
        echo "✗ Error creating competitions table: " . $conn->error . "<br>";
    }
    
    // Insert default competitions if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM competitions");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $competitions = [
            'Premier League',
            'Division 1',
            'Division 2',
            '7s Festival',
            'Cup Competition'
        ];
        
        foreach ($competitions as $comp) {
            $stmt = $conn->prepare("INSERT INTO competitions (name) VALUES (?)");
            $stmt->bind_param("s", $comp);
            $stmt->execute();
            $stmt->close();
        }
        echo "✓ Default competitions inserted<br>";
    }
    
    // Create teams table
    $sql = "CREATE TABLE IF NOT EXISTS teams (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        logo VARCHAR(255) DEFAULT 'default.png',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Teams table created/verified<br>";
    } else {
        echo "✗ Error creating teams table: " . $conn->error . "<br>";
    }
    
    // Create league_standings table
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
        echo "✓ League standings table created/verified<br>";
    } else {
        echo "✗ Error creating league standings table: " . $conn->error . "<br>";
    }
    
    echo "<h3>Database setup completed!</h3>";
    echo "<p><a href='uploadtables.php'>Go to Manage Standings</a></p>";
    echo "<p><a href='tables.php'>Go to League Tables</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 