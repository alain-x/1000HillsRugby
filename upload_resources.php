
<?php
// Ensure UTF-8 output
header('Content-Type: text/html; charset=utf-8');

// Allow reasonably large PDFs
ini_set('max_execution_time', 120);
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '25M');
ini_set('memory_limit', '256M');

// Database connection (match public site config)
$servername = "localhost";
$username   = "hillsrug_gasore";
$password   = "M00dle??";
$dbname     = "hillsrug_db";
$port       = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Ensure resources table exists
$createTableSql = "CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    year INT NULL,
    file_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($createTableSql);

$message      = '';
$messageClass = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Get file path
    $sql  = "SELECT file_path FROM resources WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($filePath);
        if ($stmt->fetch()) {
            $stmt->close();

            // Delete DB row
            $del = $conn->prepare("DELETE FROM resources WHERE id = ?");
            if ($del) {
                $del->bind_param('i', $id);
                if ($del->execute()) {
                    $message      = 'Resource deleted successfully.';
                    $messageClass = 'alert-success';

                    // Delete file from disk if it exists and is under uploads/
                    if (!empty($filePath) && strpos($filePath, 'uploads/') === 0 && file_exists($filePath)) {
                        @unlink($filePath);
                    }
                } else {
                    $message      = 'Failed to delete resource from database: ' . $conn->error;
                    $messageClass = 'alert-error';
                }
                $del->close();
            }
        } else {
            $stmt->close();
            $message      = 'Resource not found.';
            $messageClass = 'alert-error';
        }
    }
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $year        = isset($_POST['year']) && $_POST['year'] !== '' ? (int) $_POST['year'] : null;

    if ($title === '' || empty($_FILES['pdf']['name'])) {
        $message      = 'Title and PDF file are required.';
        $messageClass = 'alert-error';
    } else {
        $uploadDir = 'uploads/resources/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = basename($_FILES['pdf']['name']);
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Allow common document formats
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

        if (!in_array($extension, $allowedExtensions, true)) {
            $message      = 'Invalid file type. Allowed: PDF, Word, Excel, PowerPoint.';
            $messageClass = 'alert-error';
        } else {
            $safeName   = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $originalName);
            $uniqueName = time() . '_' . uniqid() . '_' . $safeName;
            $targetPath = $uploadDir . $uniqueName;

            if (move_uploaded_file($_FILES['pdf']['tmp_name'], $targetPath)) {
                $sql  = "INSERT INTO resources (title, description, category, year, file_path) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    // For year, use i or null binding
                    if ($year === null) {
                        $nullYear = null;
                        $stmt->bind_param('sssis', $title, $description, $category, $nullYear, $targetPath);
                    } else {
                        $stmt->bind_param('sssIs', $title, $description, $category, $year, $targetPath);
                    }

                    // Simpler: always bind as string for year to avoid format mismatch
                }

                // Rebuild statement cleanly using string year to avoid driver issues
                if (isset($stmt)) {
                    $stmt->close();
                }

                $yearStr = $year === null ? null : (string) $year;
                $sql2    = "INSERT INTO resources (title, description, category, year, file_path) VALUES (?, ?, ?, ?, ?)";
                $stmt2   = $conn->prepare($sql2);
                if ($stmt2) {
                    $stmt2->bind_param('sssss', $title, $description, $category, $yearStr, $targetPath);
                    if ($stmt2->execute()) {
                        $message      = 'Resource uploaded successfully.';
                        $messageClass = 'alert-success';
                    } else {
                        $message      = 'Failed to save resource in database: ' . $conn->error;
                        $messageClass = 'alert-error';
                    }
                    $stmt2->close();
                } else {
                    $message      = 'Database error preparing statement: ' . $conn->error;
                    $messageClass = 'alert-error';
                }
            } else {
                $message      = 'Failed to move uploaded file. Please check folder permissions.';
                $messageClass = 'alert-error';
            }
        }
    }
}

// Fetch existing resources
$resources = [];
$sqlList   = "SELECT id, title, description, category, year, file_path, created_at FROM resources ORDER BY year DESC, created_at DESC";
$result    = $conn->query($sqlList);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources | 1000 Hills Rugby</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert { padding: 1rem; border-radius: 0.25rem; margin-bottom: 1.5rem; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 p-6">
    <div class="container mx-auto max-w-5xl">
        <h1 class="text-3xl font-bold mb-6 flex items-center gap-3">
            <i class="fas fa-file-pdf text-red-500"></i>
            Manage Resources (PDF Reports)
        </h1>

        <p class="mb-6 text-gray-600">
            Upload official documents such as annual reports, policies, and other PDF resources.
            These will be displayed on the public <code>resources</code> page for visitors to view and download.
        </p>

        <?php if ($message): ?>
            <div class="alert <?php echo htmlspecialchars($messageClass); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-upload text-green-600"></i>
                Upload New Resource
            </h2>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Title *</label>
                    <input type="text" name="title" required class="w-full p-2 border rounded" placeholder="e.g. 2024 Annual Report">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <input type="text" name="category" class="w-full p-2 border rounded" placeholder="e.g. Annual Report, Policy, Strategy">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Year</label>
                        <input type="number" name="year" class="w-full p-2 border rounded" placeholder="e.g. 2024" min="2000" max="2100">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Short Description</label>
                    <textarea name="description" rows="3" class="w-full p-2 border rounded" placeholder="Briefly describe the document."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Document File *</label>
                    <input type="file" name="pdf" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" required class="w-full p-2 border rounded">
                    <p class="text-xs text-gray-500 mt-1">Allowed formats: PDF, Word (.doc, .docx), Excel (.xls, .xlsx), PowerPoint (.ppt, .pptx). Max size ~20MB.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" name="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center gap-2">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Save Resource
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-list text-blue-600"></i>
                Existing Resources
            </h2>

            <?php if (empty($resources)): ?>
                <p class="text-gray-500">No resources have been uploaded yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100 text-left">
                                <th class="px-3 py-2">Title</th>
                                <th class="px-3 py-2">Category</th>
                                <th class="px-3 py-2">Year</th>
                                <th class="px-3 py-2">Created</th>
                                <th class="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $res): ?>
                                <tr class="border-t">
                                    <td class="px-3 py-2 font-medium"><?php echo htmlspecialchars($res['title']); ?></td>
                                    <td class="px-3 py-2"><?php echo htmlspecialchars($res['category'] ?? ''); ?></td>
                                    <td class="px-3 py-2"><?php echo htmlspecialchars($res['year'] ?? ''); ?></td>
                                    <td class="px-3 py-2"><?php echo htmlspecialchars(date('Y-m-d', strtotime($res['created_at']))); ?></td>
                                    <td class="px-3 py-2 flex items-center gap-3">
                                        <a href="<?php echo htmlspecialchars($res['file_path']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                            <i class="fas fa-download"></i>
                                            View
                                        </a>
                                        <a href="?delete=<?php echo (int) $res['id']; ?>" onclick="return confirm('Delete this resource?');" class="text-red-600 hover:text-red-800 flex items-center gap-1">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

