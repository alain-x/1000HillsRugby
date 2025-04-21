<?php
// Increase PHP timeout and file upload limits
ini_set('max_execution_time', 300); // 5 minutes
ini_set('max_input_time', 300);     // 5 minutes
ini_set('upload_max_filesize', '50M'); // 50MB
ini_set('post_max_size', '50M');      // 50MB

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

// Handle form submission for adding/editing articles
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start a transaction for atomic operations
    $conn->begin_transaction();

    try {
        // Get main article details
        $title = $_POST["title"];
        $category = $_POST["category"];
        $date_published = $_POST["date_published"];
        
        // Check if we're editing an existing article
        $is_edit = isset($_POST["article_id"]) && !empty($_POST["article_id"]);
        
        if ($is_edit) {
            $article_id = $_POST["article_id"];
            
            // Update the article
            $sql = "UPDATE articles SET title = ?, category = ?, date_published = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $title, $category, $date_published, $article_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete existing details to replace them
            $sql = "DELETE FROM article_details WHERE article_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $article_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Generate a unique ID for the article
            $article_id = uniqid();

            // Insert into `articles` table
            $sql = "INSERT INTO articles (id, title, category, date_published) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $article_id, $title, $category, $date_published);
            $stmt->execute();
            $stmt->close();
        }

        // Define upload directory
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        // Process main image (if provided)
        if (!empty($_FILES["main_image"]["name"])) {
            $main_image_name = time() . "_" . basename($_FILES["main_image"]["name"]);
            $main_image_path = $upload_dir . $main_image_name;

            if (move_uploaded_file($_FILES["main_image"]["tmp_name"], $main_image_path)) {
                // Update the article with the main image path
                $sql = "UPDATE articles SET main_image_path = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $main_image_path, $article_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Process subtitles, contents, and multiple images
        foreach ($_POST["subtitle"] as $index => $subtitle) {
            $content = $_POST["content"][$index];
            $image_paths = [];

            // Handle multiple image uploads for this section
            if (!empty($_FILES["image"]["name"][$index])) {
                foreach ($_FILES["image"]["tmp_name"][$index] as $key => $tmp_name) {
                    if ($_FILES["image"]["error"][$index][$key] === UPLOAD_ERR_OK) {
                        $image_name = time() . "_" . basename($_FILES["image"]["name"][$index][$key]);
                        $target_file = $upload_dir . $image_name;

                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $image_paths[] = $target_file;
                        }
                    }
                }
            }

            // Insert into `article_details` table
            $sql = "INSERT INTO article_details (article_id, subtitle, content, image_path) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $image_paths_str = implode(",", $image_paths); // Store multiple image paths as a comma-separated string
            $stmt->bind_param("ssss", $article_id, $subtitle, $content, $image_paths_str);
            $stmt->execute();
            $stmt->close();
        }

        // Commit the transaction
        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $article_id = $_GET['delete'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First get image paths to delete files
        $sql = "SELECT main_image_path FROM articles WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $article_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $main_image_path = $row['main_image_path'] ?? null;
        
        // Get detail images
        $sql = "SELECT image_path FROM article_details WHERE article_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $article_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $detail_images = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['image_path'])) {
                $detail_images = array_merge($detail_images, explode(",", $row['image_path']));
            }
        }
        
        // Delete from database
        $sql = "DELETE FROM article_details WHERE article_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $article_id);
        $stmt->execute();
        $stmt->close();
        
        $sql = "DELETE FROM articles WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $article_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Delete files
        if ($main_image_path && file_exists($main_image_path)) {
            unlink($main_image_path);
        }
        
        foreach ($detail_images as $image_path) {
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}

// Fetch all articles for listing
$articles = [];
$sql = "SELECT * FROM articles ORDER BY date_published DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
}

// Check if we're editing an article
$edit_article = null;
$edit_details = [];
if (isset($_GET['edit'])) {
    $article_id = $_GET['edit'];
    
    // Get article
    $sql = "SELECT * FROM articles WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_article = $result->fetch_assoc();
    $stmt->close();
    
    // Get article details
    $sql = "SELECT * FROM article_details WHERE article_id = ? ORDER BY id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $edit_details[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rugby News Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-900 p-6">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-8">Rugby News Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">Article saved successfully!</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">Article deleted successfully!</span>
            </div>
        <?php endif; ?>
        
        <!-- Article List -->
        <div class="mb-12">
            <h2 class="text-2xl font-semibold mb-4">Articles</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Published</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($article['title']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($article['category']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= date('Y-m-d H:i', strtotime($article['date_published'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="?edit=<?= $article['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?= $article['id'] ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this article?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Article Form -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-6"><?= $edit_article ? 'Edit Article' : 'Add New Article' ?></h2>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <?php if ($edit_article): ?>
                    <input type="hidden" name="article_id" value="<?= $edit_article['id'] ?>">
                <?php endif; ?>
                
                <!-- Main Article Fields -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Title</label>
                    <input type="text" name="title" placeholder="Enter title" required 
                           value="<?= $edit_article ? htmlspecialchars($edit_article['title']) : '' ?>" 
                           class="w-full p-2 border rounded">
                </div>
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Category</label>
                    <input type="text" name="category" placeholder="Enter category" required 
                           value="<?= $edit_article ? htmlspecialchars($edit_article['category']) : '' ?>" 
                           class="w-full p-2 border rounded">
                </div>
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Date Published</label>
                    <input type="datetime-local" name="date_published" required 
                           value="<?= $edit_article ? date('Y-m-d\TH:i', strtotime($edit_article['date_published'])) : '' ?>" 
                           class="w-full p-2 border rounded">
                </div>

                <!-- Main Image -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Main Image</label>
                    <?php if ($edit_article && !empty($edit_article['main_image_path'])): ?>
                        <div class="mb-2">
                            <img src="<?= $edit_article['main_image_path'] ?>" alt="Current main image" class="h-32">
                            <p class="text-sm text-gray-500 mt-1">Current image. Upload a new one to replace.</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="main_image" accept="image/*" class="w-full p-2 border rounded">
                </div>

                <!-- Repeatable Fields for Subtitles, Content, and Multiple Images -->
                <div id="repeatable-fields" class="mb-6">
                    <?php if ($edit_article && !empty($edit_details)): ?>
                        <?php foreach ($edit_details as $index => $detail): ?>
                            <div class="mb-6 section-container">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-xl font-semibold">Section <?= $index + 1 ?></h3>
                                    <button type="button" onclick="removeSection(this)" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                                <input type="text" name="subtitle[]" placeholder="Subtitle" 
                                       value="<?= htmlspecialchars($detail['subtitle']) ?>" 
                                       class="w-full p-2 border rounded mb-2">
                                <textarea name="content[]" placeholder="Content" 
                                          class="w-full p-2 border rounded mb-2"><?= htmlspecialchars($detail['content']) ?></textarea>
                                <div class="image-upload-section mb-4">
                                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                                    <?php if (!empty($detail['image_path'])): ?>
                                        <div class="mb-2">
                                            <?php foreach (explode(",", $detail['image_path']) as $image_path): ?>
                                                <?php if (!empty($image_path)): ?>
                                                    <img src="<?= $image_path ?>" alt="Section image" class="h-24 inline-block mr-2 mb-2">
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <p class="text-sm text-gray-500">Current images. Upload new ones to replace.</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="image[<?= $index ?>][]" accept="image/*" multiple class="w-full p-2 border rounded">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="mb-6 section-container">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-xl font-semibold">Section 1</h3>
                                <button type="button" onclick="removeSection(this)" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                            <input type="text" name="subtitle[]" placeholder="Subtitle" class="w-full p-2 border rounded mb-2">
                            <textarea name="content[]" placeholder="Content" class="w-full p-2 border rounded mb-2"></textarea>
                            <div class="image-upload-section mb-4">
                                <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                                <input type="file" name="image[0][]" accept="image/*" multiple class="w-full p-2 border rounded">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add More Sections Button -->
                <button type="button" onclick="addFields()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-6">
                    <i class="fas fa-plus"></i> Add More Sections
                </button>

                <!-- Submit Button -->
                <button type="submit" name="submit" class="mt-6 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    <i class="fas fa-save"></i> <?= $edit_article ? 'Update Article' : 'Upload Article' ?>
                </button>
                
                <?php if ($edit_article): ?>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="ml-4 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        let sectionCount = <?= $edit_article ? count($edit_details) : 1 ?>; // Track the number of sections

        function addFields() {
            sectionCount++; // Increment section number
            let container = document.getElementById("repeatable-fields");

            let div = document.createElement("div");
            div.className = "mb-6 section-container";
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xl font-semibold">Section ${sectionCount}</h3>
                    <button type="button" onclick="removeSection(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                <input type="text" name="subtitle[]" placeholder="Subtitle" class="w-full p-2 border rounded mb-2">
                <textarea name="content[]" placeholder="Content" class="w-full p-2 border rounded mb-2"></textarea>
                <div class="image-upload-section mb-4">
                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                    <input type="file" name="image[${sectionCount - 1}][]" accept="image/*" multiple class="w-full p-2 border rounded">
                </div>
            `;
            container.appendChild(div);
        }
        
        function removeSection(button) {
            if (document.querySelectorAll('.section-container').length > 1) {
                button.closest('.section-container').remove();
                // Reindex the image arrays
                const containers = document.querySelectorAll('.section-container');
                containers.forEach((container, index) => {
                    const fileInput = container.querySelector('input[type="file"]');
                    if (fileInput) {
                        fileInput.name = `image[${index}][]`;
                    }
                });
                sectionCount = containers.length;
            } else {
                alert("You must have at least one section.");
            }
        }
    </script>
</body>
</html>