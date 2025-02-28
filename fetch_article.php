<?php
header('Content-Type: application/json');
// Database connection
    
$servername = "localhost";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

if (isset($_GET["id"])) {
    $articleId = intval($_GET["id"]);
    $sql = "SELECT title, date_published, main_image_path, content FROM articles WHERE id = $articleId";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "Article not found"]);
    }
}

$conn->close();
?>
