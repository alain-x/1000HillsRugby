<?php
$conn = new mysqli('localhost', 'root', '1234', '1000hills_rugby');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$error = '';
$uploadDir = 'uploads/sponsors/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_FILES['logo']['name'])) {
            throw new Exception("Please select an image file");
        }

        $file = $_FILES['logo'];
        
        // Validate image
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error: " . $file['error']);
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, $allowedTypes)) {
            throw new Exception("Only JPG, PNG, and GIF files are allowed");
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large (max 2MB)");
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('sponsor_') . '.' . $ext;
        $destination = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to save file");
        }
        
        // Store in database
        $stmt = $conn->prepare("INSERT INTO sponsors (logo_path) VALUES (?)");
        $stmt->bind_param("s", $destination);
        
        if (!$stmt->execute()) {
            unlink($destination); // Remove the uploaded file if DB insert fails
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $message = "Sponsor logo uploaded successfully";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Sponsor</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .upload-form {
            max-width: 500px;
            margin: 30px auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="upload-form">
        <h1>Upload Sponsor Logo</h1>
        <a href="sponsors.html">← Back to Sponsors</a>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select Logo Image:</label>
                <input type="file" name="logo" accept="image/*" required>
                <p><small>Accepted formats: JPG, PNG, GIF (Max 2MB)</small></p>
            </div>
            <button type="submit">Upload Logo</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>