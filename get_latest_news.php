<?php
header('Content-Type: application/json');

$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the latest 5 articles
$sql = "SELECT id, title, category, date_published, main_image_path, author 
        FROM articles 
        ORDER BY date_published DESC 
        LIMIT 5";
$result = $conn->query($sql);

$latestNews = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latestNews[] = $row;
    }
}

$conn->close();

echo json_encode($latestNews);
?>