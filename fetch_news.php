<?php
// fetch_news.php

header('Content-Type: application/json');

$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT a.id, a.title, a.category, a.date_published, a.main_image_path, ad.subtitle, ad.content, ad.image_path
        FROM articles a
        LEFT JOIN article_details ad ON a.id = ad.article_id
        ORDER BY a.date_published DESC";
$result = $conn->query($sql);

$articles = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $article_id = $row["id"];
        if (!isset($articles[$article_id])) {
            $articles[$article_id] = [
                "title" => $row["title"],
                "category" => $row["category"],
                "date_published" => $row["date_published"],
                "main_image_path" => $row["main_image_path"],
                "details" => []
            ];
        }
        if (!empty($row["subtitle"]) || !empty($row["content"]) || !empty($row["image_path"])) {
            $articles[$article_id]["details"][] = [
                "subtitle" => $row["subtitle"],
                "content" => $row["content"],
                "image_path" => $row["image_path"]
            ];
        }
    }
}

echo json_encode(array_values($articles));

$conn->close();
?>