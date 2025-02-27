<?php
    // Database connection
    
$servername = "localhost";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    // Main Section
    $id = uniqid(); // Generate a unique string ID
    $title = !empty($_POST['title']) ? $_POST['title'] : NULL;
    $subtitle = !empty($_POST['subtitle']) ? $_POST['subtitle'] : NULL;
    $category = !empty($_POST['category']) ? $_POST['category'] : NULL;
    $content = !empty($_POST['content']) ? $_POST['content'] : NULL;
    $created_at = !empty($_POST['created_at']) ? $_POST['created_at'] : NULL;

    // Handle main image upload
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $main_image = NULL;
    if (!empty($_FILES["main_image"]["name"])) {
        $main_image = $uploadDir . basename($_FILES["main_image"]["name"]); // Include "uploads/" prefix
        move_uploaded_file($_FILES["main_image"]["tmp_name"], $main_image);
    }

    // Insert main news into database
    $sql = "INSERT INTO news (id, title, subtitle, category, content, img, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $id, $title, $subtitle, $category, $content, $main_image, $created_at);

    if ($stmt->execute()) {
        echo "<script>alert('News uploaded successfully!'); window.location='news.php';</script>";
    } else {
        echo "<script>alert('Error uploading news.');</script>";
    }

    $stmt->close();

    // Handle additional sections
    if (isset($_POST['section_title'])) {
        foreach ($_POST['section_title'] as $key => $section_title) {
            $section_title = !empty($section_title) ? $section_title : NULL;
            $section_subtitle = !empty($_POST['section_subtitle'][$key]) ? $_POST['section_subtitle'][$key] : NULL;
            $section_content = !empty($_POST['section_content'][$key]) ? $_POST['section_content'][$key] : NULL;

            if (!is_null($section_title) || !is_null($section_content)) {
                $section_id = uniqid(); // Generate a unique string ID for the section

                // Insert section into database
                $sql = "INSERT INTO news_sections (id, news_id, title, subtitle, content) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $section_id, $id, $section_title, $section_subtitle, $section_content);
                $stmt->execute();
                $stmt->close();

                // Handle section images upload (Optional)
                if (!empty($_FILES['section_images']['name'][$key][0])) {
                    foreach ($_FILES['section_images']['tmp_name'][$key] as $img_index => $tmp_name) {
                        if ($_FILES['section_images']['error'][$key][$img_index] == 0) {
                            $fileName = $uploadDir . basename($_FILES['section_images']['name'][$key][$img_index]); // Include "uploads/" prefix
                            $targetPath = $fileName;

                            if (move_uploaded_file($tmp_name, $targetPath)) {
                                // Insert each image path into the database
                                $stmt = $conn->prepare("INSERT INTO news_images (section_id, img) VALUES (?, ?)");
                                $stmt->bind_param("ss", $section_id, $targetPath);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                }
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Rugby News</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 p-6">
    <form action="" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-lg">
        <!-- Main Article Fields -->
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Title</label>
            <input type="text" name="title" placeholder="Enter title" class="w-full p-2 border rounded">
        </div>
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Subtitle</label>
            <input type="text" name="subtitle" placeholder="Enter subtitle" class="w-full p-2 border rounded">
        </div>
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Category</label>
            <input type="text" name="category" placeholder="Enter category" class="w-full p-2 border rounded">
        </div>
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Content</label>
            <textarea name="content" placeholder="Enter content" class="w-full p-2 border rounded"></textarea>
        </div>
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Publish Date and Time</label>
            <input type="datetime-local" name="created_at" class="w-full p-2 border rounded">
        </div>

        <!-- Main Image -->
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Main Image</label>
            <input type="file" name="main_image" accept="image/*" class="w-full p-2 border rounded">
        </div>

        <!-- Repeatable Fields for Subtitles, Content, and Multiple Images -->
        <div id="repeatable-fields" class="mb-6">
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-4">Section 1</h3>
                <input type="text" name="section_title[]" placeholder="Section Title" class="w-full p-2 border rounded mb-2">
                <input type="text" name="section_subtitle[]" placeholder="Section Subtitle" class="w-full p-2 border rounded mb-2">
                <textarea name="section_content[]" placeholder="Section Content" class="w-full p-2 border rounded mb-2"></textarea>
                <div class="image-upload-section mb-4">
                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                    <input type="file" name="section_images[0][]" accept="image/*" multiple class="w-full p-2 border rounded">
                </div>
            </div>
        </div>

        <!-- Add More Sections Button -->
        <button type="button" onclick="addFields()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Add More Sections
        </button>

        <!-- Submit Button -->
        <button type="submit" name="submit" class="mt-6 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
            Upload Article
        </button>
    </form>

    <script>
        let sectionCount = 1; // Track the number of sections

        function addFields() {
            sectionCount++; // Increment section number
            let container = document.getElementById("repeatable-fields");

            let div = document.createElement("div");
            div.className = "mb-6";
            div.innerHTML = `
                <h3 class="text-xl font-semibold mb-4">Section ${sectionCount}</h3>
                <input type="text" name="section_title[]" placeholder="Section Title" class="w-full p-2 border rounded mb-2">
                <input type="text" name="section_subtitle[]" placeholder="Section Subtitle" class="w-full p-2 border rounded mb-2">
                <textarea name="section_content[]" placeholder="Section Content" class="w-full p-2 border rounded mb-2"></textarea>
                <div class="image-upload-section mb-4">
                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                    <input type="file" name="section_images[${sectionCount - 1}][]" accept="image/*" multiple class="w-full p-2 border rounded">
                </div>
            `;
            container.appendChild(div);
        }
    </script>
</body>
</html>