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
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Title</label>
            <input type="text" name="title" required class="w-full p-2 border rounded">
        </div>
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Category</label>
            <input type="text" name="category" required class="w-full p-2 border rounded">
        </div>
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Date Published</label>
            <input type="datetime-local" name="date_published" required class="w-full p-2 border rounded">
        </div>
        <div class="mb-6">
            <label class="block text-lg font-semibold mb-2">Main Image</label>
            <input type="file" name="main_image" accept="image/*" class="w-full p-2 border rounded">
        </div>

        <div id="repeatable-fields" class="mb-6">
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-4">Section 1</h3>
                <input type="text" name="subtitle[]" placeholder="Subtitle 1" class="w-full p-2 border rounded mb-2">
                <textarea name="content[]" placeholder="Content 1" class="w-full p-2 border rounded mb-2"></textarea>
                <div class="image-upload-section mb-4">
                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                    <input type="file" name="image_0[]" accept="image/*" multiple class="w-full p-2 border rounded">
                </div>
            </div>
        </div>

        <button type="button" onclick="addFields()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Add More Sections
        </button>

        <button type="submit" name="submit" class="mt-6 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
            Upload Article
        </button>
    </form>

    <script>
        let sectionCount = 1;
        function addFields() {
            sectionCount++;
            let container = document.getElementById("repeatable-fields");

            let div = document.createElement("div");
            div.className = "mb-6";
            div.innerHTML = `
                <h3 class="text-xl font-semibold mb-4">Section ${sectionCount}</h3>
                <input type="text" name="subtitle[]" placeholder="Subtitle ${sectionCount}" class="w-full p-2 border rounded mb-2">
                <textarea name="content[]" placeholder="Content ${sectionCount}" class="w-full p-2 border rounded mb-2"></textarea>
                <div class="image-upload-section mb-4">
                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                    <input type="file" name="image_${sectionCount - 1}[]" accept="image/*" multiple class="w-full p-2 border rounded">
                </div>
            `;
            container.appendChild(div);
        }
    </script>

    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $servername = "localhost"; 
    $username = "hillsrug_gasore";
    $password = "M00dle??";
    $dbname = "hillsrug_db";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
        $title = $_POST["title"];
        $category = $_POST["category"];
        $date_published = $_POST["date_published"];
        $article_id = uniqid();

        $sql = "INSERT INTO articles (id, title, category, date_published) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $article_id, $title, $category, $date_published);
        $stmt->execute();
        $stmt->close();

        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!empty($_FILES["main_image"]["name"])) {
            $main_image_name = time() . "_" . basename($_FILES["main_image"]["name"]);
            $main_image_path = $upload_dir . $main_image_name;

            if (move_uploaded_file($_FILES["main_image"]["tmp_name"], $main_image_path)) {
                $sql = "UPDATE articles SET main_image_path = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $main_image_path, $article_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        foreach ($_POST["subtitle"] as $index => $subtitle) {
            $content = $_POST["content"][$index];
            $image_paths = [];

            $input_name = "image_".$index;
            if (!empty($_FILES[$input_name]["name"][0])) {
                foreach ($_FILES[$input_name]["tmp_name"] as $key => $tmp_name) {
                    if ($_FILES[$input_name]["error"][$key] === UPLOAD_ERR_OK) {
                        $image_name = time() . "_" . basename($_FILES[$input_name]["name"][$key]);
                        $target_file = $upload_dir . $image_name;

                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $image_paths[] = $target_file;
                        }
                    }
                }
            }

            $sql = "INSERT INTO article_details (article_id, subtitle, content, image_path) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $image_paths_str = implode(",", $image_paths);
            $stmt->bind_param("ssss", $article_id, $subtitle, $content, $image_paths_str);
            $stmt->execute();
            $stmt->close();
        }

        echo "Article and details uploaded successfully!";
    }

    $conn->close();
    ?>
</body>
</html>
