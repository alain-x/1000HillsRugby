<?php
$servername = "localhost:3300";
$username = "root";
$password = "";
$dbname = "rugby_news";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $category = $_POST["category"];
    $content = $_POST["content"];

    // Define upload directory
    $upload_dir = "uploads/";

    // ✅ Ensure upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate a unique filename
    $image_name = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $upload_dir . $image_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // ✅ Insert into database with the correct column name
        $sql = "INSERT INTO articles (title, category, content, image_path) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $title, $category, $content, $target_file);

        if ($stmt->execute()) {
            echo "Article uploaded successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error uploading image.";
    }
}
$conn->close();
?>

<!-- HTML Form for Uploading -->
<form action="upload.php" method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Title" required><br>
    <input type="text" name="category" placeholder="Category" required><br>
    <textarea name="content" placeholder="Article Content" required></textarea><br>
    <input type="file" name="image" accept="image/*" required><br>
    <button type="submit">Upload Article</button>
</form>
