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

// Initialize variables
$message = '';
$messageClass = '';
$editMode = false;
$currentArticle = null;
$currentArticleDetails = [];
$articles = [];

// Handle delete action
if (isset($_GET['delete'])) {
    $article_id = $conn->real_escape_string($_GET['delete']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First get all image paths to delete files
        $main_image_path = '';
        $detail_images = [];
        
        // Get main article image
        $sql = "SELECT main_image_path FROM articles WHERE id = '$article_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $main_image_path = $row['main_image_path'] ?? '';
        }
        
        // Get detail images
        $sql = "SELECT image_path FROM article_details WHERE article_id = '$article_id'";
        $result = $conn->query($sql);
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
        if (!empty($main_image_path) && file_exists($main_image_path)) {
            unlink($main_image_path);
        }
        
        foreach ($detail_images as $image_path) {
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'Error deleting article: ' . $e->getMessage();
        $messageClass = 'alert-error';
    }
}

// Handle form submission for adding/editing articles
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $conn->begin_transaction();

    try {
        // Get main article details
        $title = $conn->real_escape_string(trim($_POST["title"] ?? ''));
        $category = $conn->real_escape_string(trim($_POST["category"] ?? ''));
        $date_published = $conn->real_escape_string(trim($_POST["date_published"] ?? ''));
        
        // Check if editing existing article
        $is_edit = isset($_POST["article_id"]) && !empty($_POST["article_id"]);
        
        if ($is_edit) {
            $article_id = $conn->real_escape_string($_POST["article_id"]);
            
            // Update the article
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
                // Delete old image if it exists
                if (!empty($_POST["existing_main_image"]) && file_exists($_POST["existing_main_image"])) {
                    unlink($_POST["existing_main_image"]);
                }
                
                $main_image_path = $conn->real_escape_string($main_image_path);
                $sql = "UPDATE articles SET main_image_path = '$main_image_path' WHERE id = '$article_id'";
                if (!$conn->query($sql)) {
                    throw new Exception("Error updating main image: " . $conn->error);
                }
            }
        } elseif ($is_edit && !empty($_POST["existing_main_image"])) {
            // Keep existing image if no new one was uploaded
            $main_image_path = $conn->real_escape_string($_POST["existing_main_image"]);
            $sql = "UPDATE articles SET main_image_path = '$main_image_path' WHERE id = '$article_id'";
            if (!$conn->query($sql)) {
                throw new Exception("Error updating main image: " . $conn->error);
            }
        }

        // Process sections
        foreach ($_POST["subtitle"] as $index => $subtitle) {
            $subtitle = $conn->real_escape_string(trim($subtitle));
            $content = $conn->real_escape_string(trim($_POST["content"][$index] ?? ''));
            $image_paths = [];

            // Handle multiple image uploads for this section
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
            } elseif ($is_edit && !empty($_POST["existing_images"][$index])) {
                // Keep existing images if no new ones were uploaded
                $image_paths = explode(",", $_POST["existing_images"][$index]);
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
        $message = 'Error: ' . $e->getMessage();
        $messageClass = 'alert-error';
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $article_id = $conn->real_escape_string($_GET['edit']);
    
    // Get main article
    $sql = "SELECT * FROM articles WHERE id = '$article_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $currentArticle = $result->fetch_assoc();
    } else {
        // Article not found, redirect
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Get article details
    $sql = "SELECT * FROM article_details WHERE article_id = '$article_id' ORDER BY id";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $currentArticleDetails[] = $row;
    }
}

// Get all articles for listing
$sql = "SELECT * FROM articles ORDER BY date_published DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
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
    <style>
        .alert {
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .section-container {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .image-preview {
            width: 200px;
            height: 150px;
            border-radius: 0.25rem;
            overflow: hidden;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .image-preview-placeholder {
            color: #ccc;
            font-size: 3rem;
        }
        .remove-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }
        .remove-image-btn:hover {
            background-color: #dc3545;
        }
        .article-card {
            transition: all 0.3s ease;
        }
        .article-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 p-6">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold mb-8">Rugby News Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Article saved successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                Article deleted successfully!
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $messageClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Article Form -->
        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
            <h2 class="text-2xl font-semibold mb-6"><?php echo $editMode ? 'Edit Article' : 'Add New Article'; ?></h2>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <?php if ($editMode && isset($currentArticle['id'])): ?>
                    <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($currentArticle['id']); ?>">
                    <input type="hidden" name="existing_main_image" value="<?php echo htmlspecialchars($currentArticle['main_image_path'] ?? ''); ?>">
                <?php endif; ?>
                
                <!-- Main Article Fields -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Title *</label>
                    <input type="text" name="title" placeholder="Enter title" required 
                           value="<?php echo htmlspecialchars($currentArticle['title'] ?? ''); ?>" 
                           class="w-full p-2 border rounded">
                </div>
                
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Category *</label>
                    <input type="text" name="category" placeholder="Enter category" required 
                           value="<?php echo htmlspecialchars($currentArticle['category'] ?? ''); ?>" 
                           class="w-full p-2 border rounded">
                </div>
                
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Date Published *</label>
                    <input type="datetime-local" name="date_published" required 
                           value="<?php echo $editMode ? date('Y-m-d\TH:i', strtotime($currentArticle['date_published'])) : ''; ?>" 
                           class="w-full p-2 border rounded">
                </div>

                <!-- Main Image -->
                <div class="mb-6">
                    <label class="block text-lg font-semibold mb-2">Main Image</label>
                    <?php if ($editMode && !empty($currentArticle['main_image_path'])): ?>
                        <div class="mb-4">
                            <div class="image-preview">
                                <img src="<?php echo htmlspecialchars($currentArticle['main_image_path']); ?>" alt="Current main image">
                                <button type="button" class="remove-image-btn" onclick="document.getElementById('main_image').value = ''; document.getElementById('remove_main_image').value = '1'; this.parentElement.innerHTML = '<div class=\"image-preview-placeholder\"><i class=\"fas fa-image\"></i></div>';">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="hidden" id="remove_main_image" name="remove_main_image" value="0">
                        </div>
                    <?php else: ?>
                        <div class="image-preview mb-4">
                            <div class="image-preview-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="main_image" name="main_image" accept="image/*" class="w-full p-2 border rounded">
                </div>

                <!-- Sections -->
                <div id="repeatable-fields" class="mb-6">
                    <?php if ($editMode && !empty($currentArticleDetails)): ?>
                        <?php foreach ($currentArticleDetails as $index => $detail): ?>
                            <div class="section-container mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-xl font-semibold">Section <?php echo $index + 1; ?></h3>
                                    <button type="button" onclick="removeSection(this)" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i> Remove Section
                                    </button>
                                </div>
                                <input type="hidden" name="existing_images[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($detail['image_path'] ?? ''); ?>">
                                <input type="text" name="subtitle[]" placeholder="Subtitle" 
                                       value="<?php echo htmlspecialchars($detail['subtitle'] ?? ''); ?>" 
                                       class="w-full p-2 border rounded mb-2">
                                <textarea name="content[]" placeholder="Content" 
                                          class="w-full p-2 border rounded mb-2"><?php echo htmlspecialchars($detail['content'] ?? ''); ?></textarea>
                                <div class="image-upload-section mb-4">
                                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                                    <?php if (!empty($detail['image_path'])): ?>
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            <?php foreach (explode(",", $detail['image_path']) as $imgPath): ?>
                                                <?php if (!empty($imgPath)): ?>
                                                    <div class="relative">
                                                        <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Section image" class="h-24 object-cover">
                                                        <button type="button" class="remove-image-btn" onclick="removeSectionImage(this, <?php echo $index; ?>, '<?php echo htmlspecialchars($imgPath); ?>')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="image[<?php echo $index; ?>][]" accept="image/*" multiple class="w-full p-2 border rounded">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="section-container mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-xl font-semibold">Section 1</h3>
                                <button type="button" onclick="removeSection(this)" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i> Remove Section
                                </button>
                            </div>
                            <input type="hidden" name="existing_images[0]" value="">
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

                <!-- Form Actions -->
                <div class="flex items-center">
                    <?php if ($editMode): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 mr-4">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php else: ?>
                        <button type="reset" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 mr-4">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    <?php endif; ?>
                    <button type="submit" name="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        <i class="fas fa-save"></i> <?php echo $editMode ? 'Update Article' : 'Save Article'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Articles List -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-2xl font-semibold mb-6">Articles</h2>
            
            <?php if (empty($articles)): ?>
                <div class="alert alert-info">
                    No articles found. Add your first article above.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($articles as $article): ?>
                        <div class="article-card bg-white rounded-lg shadow overflow-hidden">
                            <?php if (!empty($article['main_image_path'])): ?>
                                <div class="h-48 overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($article['main_image_path']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="h-48 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($article['title']); ?></h3>
                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($article['category']); ?></span>
                                </div>
                                <p class="text-gray-600 text-sm mb-4"><?php echo date('M j, Y H:i', strtotime($article['date_published'])); ?></p>
                                <div class="flex justify-between">
                                    <a href="?edit=<?php echo $article['id']; ?>" class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $article['id']; ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this article?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let sectionCount = <?php echo $editMode ? count($currentArticleDetails) : 1; ?>;
        
        // Add new section
        function addFields() {
            sectionCount++;
            let container = document.getElementById("repeatable-fields");
            
            let div = document.createElement("div");
            div.className = "section-container mb-4";
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xl font-semibold">Section ${sectionCount}</h3>
                    <button type="button" onclick="removeSection(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i> Remove Section
                    </button>
                </div>
                <input type="hidden" name="existing_images[${sectionCount - 1}]" value="">
                <input type="text" name="subtitle[]" placeholder="Subtitle" class="w-full p-2 border rounded mb-2">
                <textarea name="content[]" placeholder="Content" class="w-full p-2 border rounded mb-2"></textarea>
                <div class="image-upload-section mb-4">
                    <label class="block text-lg font-semibold mb-2">Images (Multiple)</label>
                    <input type="file" name="image[${sectionCount - 1}][]" accept="image/*" multiple class="w-full p-2 border rounded">
                </div>
            `;
            container.appendChild(div);
        }
        
        // Remove section
        function removeSection(button) {
            const sections = document.querySelectorAll('.section-container');
            if (sections.length > 1) {
                const sectionToRemove = button.closest('.section-container');
                sectionToRemove.remove();
                
                // Reindex remaining sections
                const remainingSections = document.querySelectorAll('.section-container');
                remainingSections.forEach((section, index) => {
                    // Update the file input name
                    const fileInput = section.querySelector('input[type="file"]');
                    if (fileInput) {
                        fileInput.name = `image[${index}][]`;
                    }
                    
                    // Update the existing images input name
                    const existingImagesInput = section.querySelector('input[name^="existing_images"]');
                    if (existingImagesInput) {
                        existingImagesInput.name = `existing_images[${index}]`;
                    }
                    
                    // Update the section title
                    const sectionTitle = section.querySelector('h3');
                    if (sectionTitle) {
                        sectionTitle.textContent = `Section ${index + 1}`;
                    }
                });
                
                sectionCount = remainingSections.length;
            } else {
                alert("You must have at least one section.");
            }
        }
        
        // Remove individual section image
        function removeSectionImage(button, sectionIndex, imagePath) {
            if (confirm('Are you sure you want to remove this image?')) {
                // Create a hidden input to track removed images
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `removed_images[${sectionIndex}][]`;
                hiddenInput.value = imagePath;
                document.querySelector('form').appendChild(hiddenInput);
                
                // Remove the image element
                button.parentElement.remove();
            }
        }
        
        // Preview main image when selected
        document.getElementById('main_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.querySelector('.image-preview');
                    preview.innerHTML = `
                        <img src="${event.target.result}" alt="Preview">
                        <button type="button" class="remove-image-btn" onclick="document.getElementById('main_image').value = ''; document.getElementById('remove_main_image').value = '1'; this.parentElement.innerHTML = '<div class=\"image-preview-placeholder\"><i class=\"fas fa-image\"></i></div>';">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>