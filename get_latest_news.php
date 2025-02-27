<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the 5 latest news from database
$sql = "SELECT id, title, subtitle, category, img, created_at FROM news ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

$news = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
}

$conn->close();

echo json_encode($news);
?>