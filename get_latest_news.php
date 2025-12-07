<?php
header('Content-Type: application/json');


// Database connection (keep consistent with upload.php)
$servername = "localhost";
$username   = "hillsrug_gasore";
$password   = "M00dle??";
$dbname     = "hillsrug_db";
$port       = 3306;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fetch the latest 5 articles
$sql = "SELECT id, title, category, date_published, main_image_path 
        FROM articles 
        ORDER BY date_published DESC 
        LIMIT 5";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    die(json_encode(["error" => "Query failed: " . $conn->error]));
}

$latestNews = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Sanitize data before sending it to the client
        $row['title'] = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
        $row['category'] = htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8');
        $row['main_image_path'] = htmlspecialchars($row['main_image_path'], ENT_QUOTES, 'UTF-8');
        $latestNews[] = $row;
    }
}

$conn->close();

// Send JSON response
echo json_encode($latestNews);
?>