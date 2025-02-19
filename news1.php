<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rugby News</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-100 text-gray-900">

<?php
$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM articles ORDER BY date_published DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<section class="w-10/12 flex flex-col mx-auto gap-6 mt-28 bg-white p-6 rounded-lg shadow-lg">';
        echo '<div class="flex gap-2">';
        echo '<i class="text-[#1b75bc] text-2xl fa-regular fa-bookmark"></i>';
        echo '<p class="lg:text-lg font-semibold">' . strtoupper($row["category"]) . '</p>';
        echo '</div>';
        echo '<div>';
        echo '<p class="lg:w-11/12 text-[40px] font-bold hover:text-[#1b75bc] uppercase">' . $row["title"] . '</p>';
        echo '</div>';
        echo '<div class="flex gap-2 items-center">';
        echo '<i class="text-[#1b75bc] text-2xl fa-regular fa-clock"></i>';
        echo '<p class="lg:text-lg">' . $row["date_published"] . '</p>';
        echo '</div>';

        // Display Image
        if (!empty($row["image_path"]) && file_exists($row["image_path"])) {
            echo '<div>';
            echo '<img class="w-[500px] h-[500px] object-cover rounded-lg" src="' . $row["image_path"] . '" alt="' . $row["title"] . '" />';
            echo '</div>';
        }

        echo '<div class="w-10/12">';
        echo '<p class="text-gray-700 leading-relaxed">' . $row["content"] . '</p>';
        echo '</div>';
        echo '</section>';
    }
} else {
    echo "<p class='text-center text-xl font-semibold mt-10'>No articles found.</p>";
}

$conn->close();
?>

</body>
</html>
