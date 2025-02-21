<?php
header("Content-Type: application/json");

$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Fetch latest news articles
$sql = "SELECT id, title, date_published, main_image_path FROM articles ORDER BY date_published DESC LIMIT 5";
$result = $conn->query($sql);

$news = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $news[] = [
            "id" => $row["id"],
            "title" => $row["title"],
            "date_published" => date("j F Y", strtotime($row["date_published"])),
            "main_image_path" => $row["main_image_path"]
        ];
    }
}

$conn->close();
echo json_encode($news);
?>
