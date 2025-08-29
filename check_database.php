<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'hillsrug_hillsrug');
define('DB_PASS', 'M00dle??');
define('DB_NAME', 'hillsrug_1000hills_rugby_db');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Check</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-6xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6'>Database Structure Check</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p class='text-green-600 text-lg'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='text-red-600 text-lg'>✗ " . $e->getMessage() . "</p>";
    exit;
}

// Check all tables
$tables = ['teams', 'competitions', 'seasons', 'genders', 'league_standings'];

foreach ($tables as $table) {
    echo "<h2 class='text-xl font-semibold mb-4 mt-6'>Table: $table</h2>";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "<p class='text-red-600'>✗ Table '$table' does not exist</p>";
        continue;
    }
    
    echo "<p class='text-green-600'>✓ Table '$table' exists</p>";
    
    // Show table structure
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "<table class='min-w-full divide-y divide-gray-200 mb-4'>
            <thead class='bg-gray-50'>
                <tr>
                    <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Field</th>
                    <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Type</th>
                    <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Null</th>
                    <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Key</th>
                    <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>Default</th>
                </tr>
            </thead>
            <tbody class='bg-white divide-y divide-gray-200'>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($row['Field']) . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['Type']) . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['Null']) . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['Key']) . "</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>
            </tr>";
        }
        
        echo "</tbody></table>";
    }
    
    // Show table data count
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $row = $result->fetch_assoc();
    echo "<p><strong>Records:</strong> " . $row['count'] . "</p>";
    
    // Show sample data
    if ($row['count'] > 0) {
        $result = $conn->query("SELECT * FROM $table LIMIT 3");
        if ($result && $result->num_rows > 0) {
            echo "<p><strong>Sample Data:</strong></p>";
            echo "<table class='min-w-full divide-y divide-gray-200 mb-4'>
                <thead class='bg-gray-50'>
                    <tr>";
            
            $first_row = $result->fetch_assoc();
            foreach ($first_row as $key => $value) {
                echo "<th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase'>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr></thead><tbody class='bg-white divide-y divide-gray-200'>";
            
            // Reset result pointer
            $result->data_seek(0);
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</tbody></table>";
        }
    }
}

echo "<div class='mt-8 p-4 bg-gray-50 rounded-lg'>
    <h3 class='text-lg font-semibold mb-2'>Actions</h3>
    <div class='flex gap-4'>
        <a href='setup_database.php' class='px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700'>Setup Database</a>
        <a href='debug_upload.php' class='px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700'>Debug Upload</a>
        <a href='uploadtables' class='px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700'>Main Upload Page</a>
    </div>
</div>";

$conn->close();

echo "</div></div></body></html>";
?> 