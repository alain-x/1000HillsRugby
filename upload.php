<?php
// Increase PHP timeout and file upload limits
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');

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
    $conn->begin_transaction();

    try {
        $title = $conn->real_escape_string($_POST["title"]);
        $category = $conn->real_escape_string($_POST["category"]);
        $date_published = $conn->real_escape_string($_POST["date_published"]);
        
        // Check if editing existing article
        if (isset($_POST["article_id"]) && !empty($_POST["article_id"])) {
            $article_id = $conn->real_escape_string($_POST["article_id"]);
            
            // Update article
            $sql = "UPDATE articles SET title = '$title', category = '$category', 
                    date_published = '$date_published' WHERE id = '$article_id'";
            if (!$conn->query($sql)) {
                throw new Exception("Error updating article: " . $conn->error);
            }
            
            // Delete existing details to replace them
            $sql = "DELETE FROM article_details WHERE article_id = '$article_id'";
            if (!$conn->query($sql)) {
                throw new Exception("Error deleting article details: " . $conn->error);
            }
        } else {
            // Create new article
            $article_id = uniqid();
            $sql = "INSERT INTO articles (id, title, category, date_published) 
                    VALUES ('$article_id', '$title', '$category', '$date_published')";
            if (!$conn->query($sql)) {
                throw new Exception("Error creating article: " . $conn->error);
            }
        }

        // Process main image
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!empty($_FILES["main_image"]["name"])) {
            $main_image_name = time() . "_" . basename($_FILES["main_image"]["name"]);
            $main_image_path = $upload_dir . $main_image_name;

            if (move_uploaded_file($_FILES["main_image"]["tmp_name"], $main_image_path)) {
                $main_image_path = $conn->real_escape_string($main_image_path);
                $sql = "UPDATE articles SET main_image_path = '$main_image_path' WHERE id = '$article_id'";
                if (!$conn->query($sql)) {
                    throw new Exception("Error updating main image: " . $conn->error);
                }
            }
        }

        // Process sections
        foreach ($_POST["subtitle"] as $index => $subtitle) {
            $subtitle = $conn->real_escape_string($subtitle);
            $content = $conn->real_escape_string($_POST["content"][$index]);
            $image_paths = [];

            if (!empty($_FILES["image"]["name"][$index])) {
                foreach ($_FILES["image"]["tmp_name"][$index] as $key => $tmp_name) {
                    if ($_FILES["image"]["error"][$index][$key] === UPLOAD_ERR_OK) {
                        $image_name = time() . "_" . basename($_FILES["image"]["name"][$index][$key]);
                        $target_file = $upload_dir . $image_name;

                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $image_paths[] = $conn->real_escape_string($target_file);
                        }
                    }
                }
            }

            $image_paths_str = implode(",", $image_paths);
            $sql = "INSERT INTO article_details (article_id, subtitle, content, image_path) 
                    VALUES ('$article_id', '$subtitle', '$content', '$image_paths_str')";
            if (!$conn->query($sql)) {
                throw new Exception("Error inserting article details: " . $conn->error);
            }
        }

        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $article_id = $conn->real_escape_string($_GET['delete']);
    
    $conn->begin_transaction();
    
    try {
        // Get images to delete from filesystem
        $sql = "SELECT main_image_path FROM articles WHERE id = '$article_id'";
        $result = $conn->query($sql);
        $main_image_path = $result->fetch_assoc()['main_image_path'] ?? null;
        
        $sql = "SELECT image_path FROM article_details WHERE article_id = '$article_id'";
        $result = $conn->query($sql);
        $detail_images = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['image_path'])) {
                $detail_images = array_merge($detail_images, explode(",", $row['image_path']));
            }
        }
        
        // Delete from database
        $sql = "DELETE FROM article_details WHERE article_id = '$article_id'";
        if (!$conn->query($sql)) {
            throw new Exception("Error deleting article details: " . $conn->error);
        }
        
        $sql = "DELETE FROM articles WHERE id = '$article_id'";
        if (!$conn->query($sql)) {
            throw new Exception("Error deleting article: " . $conn->error);
        }
        
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

// Fetch all articles
$articles = [];
$sql = "SELECT * FROM articles ORDER BY date_published DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
}

// Check if editing an article
$edit_article = null;
$edit_details = [];
if (isset($_GET['edit'])) {
    $article_id = $conn->real_escape_string($_GET['edit']);
    
    $sql = "SELECT * FROM articles WHERE id = '$article_id'";
    $result = $conn->query($sql);
    $edit_article = $result->fetch_assoc();
    
    $sql = "SELECT * FROM article_details WHERE article_id = '$article_id' ORDER BY id";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $edit_details[] = $row;
    }
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Confirm before deleting
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this article?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</head>
<body class="bg-gray-100 text-gray-900 p-6">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-8">Rugby News Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                Article saved successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                Article deleted successfully!
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
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
                                    <a href="?delete=<?= $article['id'] ?>" class="text-red-500 hover:text-red-700 delete-btn">
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

                <!-- Sections -->
                <div id="repeatable-fields" class="mb-6">
                    <?php if ($edit_article && !empty($edit_details)): ?>
                        <?php foreach ($edit_details as $index => $detail): ?>
                            <div class="mb-6 section-container border p-4 rounded-lg">
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
                                    <label class="block text-lg font-semibold mb-2">Images</label>
                                    <?php if (!empty($detail['image_path'])): ?>
                                        <div class="mb-2 flex flex-wrap gap-2">
                                            <?php foreach (explode(",", $detail['image_path']) as $image_path): ?>
                                                <?php if (!empty($image_path)): ?>
                                                    <div class="relative">
                                                        <img src="<?= $image_path ?>" alt="Section image" class="h-24 object-cover">
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <p class="text-sm text-gray-500 w-full">Current images. Upload new ones to replace.</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="image[<?= $index ?>][]" accept="image/*" multiple class="w-full p-2 border rounded">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="mb-6 section-container border p-4 rounded-lg">
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

                <button type="button" onclick="addFields()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-6">
                    <i class="fas fa-plus"></i> Add More Sections
                </button>

                <div class="flex items-center">
                    <button type="submit" name="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        <i class="fas fa-save"></i> <?= $edit_article ? 'Update Article' : 'Save Article' ?>
                    </button>
                    
                    <?php if ($edit_article): ?>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="ml-4 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        let sectionCount = <?= $edit_article ? count($edit_details) : 1 ?>;
        
        function addFields() {
            sectionCount++;
            let container = document.getElementById("repeatable-fields");
            
            let div = document.createElement("div");
            div.className = "mb-6 section-container border p-4 rounded-lg";
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
            const sections = document.querySelectorAll('.section-container');
            if (sections.length > 1) {
                const sectionToRemove = button.closest('.section-container');
                sectionToRemove.remove();
                
                // Reindex remaining sections
                const remainingSections = document.querySelectorAll('.section-container');
                remainingSections.forEach((section, index) => {
                    const fileInput = section.querySelector('input[type="file"]');
                    if (fileInput) {
                        fileInput.name = `image[${index}][]`;
                    }
                });
                
                sectionCount = remainingSections.length;
            } else {
                alert("You must have at least one section.");
            }
        }
    </script>
</body>
</html>