<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');

try {
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "<h2>Updating Database Structure</h2>";
    
    // Add is_deleted column to all tables with ON DELETE CASCADE
    $tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
    
    foreach ($tables as $table) {
        // Check if is_deleted column exists
        $check_sql = "SHOW COLUMNS FROM `$table` LIKE 'is_decked'";
        $result = $conn->query($check_sql);
        
        if ($result->num_rows == 0) {
            // Add is_deleted column
            $alter_sql = "ALTER TABLE `$table` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0";
            if ($conn->query($alter_sql) === TRUE) {
                echo "<p>✓ Added is_deleted column to $table</p>";
            } else {
                echo "<p>Error adding is_deleted to $table: " . $conn->error . "</p>";
            }
            
            // Add deleted_at column if it doesn't exist
            $check_deleted_at = "SHOW COLUMNS FROM `$table` LIKE 'deleted_at'";
            $result_deleted_at = $conn->query($check_deleted_at);
            
            if ($result_deleted_at->num_rows == 0) {
                $alter_sql = "ALTER TABLE `$table` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL";
                if ($conn->query($alter_sql) === TRUE) {
                    echo "<p>✓ Added deleted_at column to $table</p>";
                } else {
                    echo "<p>Error adding deleted_at to $table: " . $conn->error . "</p>";
                }
            }
        } else {
            echo "<p>✓ is_deleted column already exists in $table</p>";
        }
    }
    
    // Update foreign key constraints to use soft delete
    echo "<h3>Updating foreign key constraints...</h3>";
    
    // Drop existing foreign key constraints
    $constraints_sql = "SELECT TABLE_NAME, CONSTRAINT_NAME 
                       FROM information_schema.TABLE_CONSTRAINTS 
                       WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                       AND TABLE_SCHEMA = '" . DB_NAME . "'";
    
    $result = $conn->query($constraints_sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $drop_fk_sql = "ALTER TABLE `{$row['TABLE_NAME']}` DROP FOREIGN KEY `{$row['CONSTRAINT_NAME']}`";
            if ($conn->query($drop_fk_sql) === TRUE) {
                echo "<p>✓ Dropped foreign key constraint {$row['CONSTRAINT_NAME']} from {$row['TABLE_NAME']}</p>";
            } else {
                echo "<p>Error dropping foreign key constraint: " . $conn->error . "</p>";
            }
        }
    }
    
    // Recreate foreign key constraints with RESTRICT on DELETE
    $recreate_fk_sql = [
        "ALTER TABLE `league_standings` 
         ADD CONSTRAINT `fk_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE RESTRICT",
        
        "ALTER TABLE `league_standings` 
         ADD CONSTRAINT `fk_competition` FOREIGN KEY (`competition_id`) REFERENCES `competitions`(`id`) ON DELETE RESTRICT",
        
        "ALTER TABLE `league_standings` 
         ADD CONSTRAINT `fk_season` FOREIGN KEY (`season_id`) REFERENCES `seasons`(`id`) ON DELETE RESTRICT",
        
        "ALTER TABLE `league_standings` 
         ADD CONSTRAINT `fk_gender` FOREIGN KEY (`gender_id`) REFERENCES `genders`(`id`) ON DELETE RESTRICT"
    ];
    
    foreach ($recreate_fk_sql as $sql) {
        if ($conn->query($sql) === TRUE) {
            echo "<p>✓ Recreated foreign key constraint</p>";
        } else {
            echo "<p>Error recreating foreign key: " . $conn->error . "</p>";
        }
    }
    
    // Create a trigger for soft delete on each table
    $tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];
    
    foreach ($tables as $table) {
        $trigger_name = "before_delete_$table";
        
        // Drop trigger if it exists
        $drop_trigger_sql = "DROP TRIGGER IF EXISTS `$trigger_name`";
        $conn->query($drop_trigger_sql);
        
        // Create new trigger
        $trigger_sql = "
        CREATE TRIGGER `$trigger_name`
        BEFORE DELETE ON `$table`
        FOR EACH ROW
        BEGIN
            -- Prevent actual deletion, update is_deleted instead
            SET @prevent_delete = 1;
            
            -- Update the record instead of deleting
            SET @update_sql = CONCAT('UPDATE `$table` SET is_deleted = 1, deleted_at = NOW() WHERE id = ', OLD.id);
            PREPARE stmt FROM @update_sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Signal an error to prevent the actual delete
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Record not deleted. Use is_deleted = 1 for soft delete.';
        END;
        ";
        
        if ($conn->multi_query($trigger_sql)) {
            echo "<p>✓ Created soft delete trigger for $table</p>";
            // Clear any remaining results
            while ($conn->next_result()) {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            }
        } else {
            echo "<p>Error creating trigger for $table: " . $conn->error . "</p>";
        }
    }
    
    echo "<h3>Database structure update complete!</h3>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    error_log("Database update error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h2, h3 { color: #2c3e50; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>Database Update Complete</h1>
    <p>The database structure has been updated with soft delete functionality.</p>
    <p>Please verify the changes in your database.</p>
</body>
</html>
