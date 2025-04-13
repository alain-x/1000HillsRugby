<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$username = '';
$is_admin = 0;
$error = '';
$success = '';

// Fetch user data if editing an existing user
if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $is_admin = $user['is_admin'];
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $username = trim($_POST['username']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $password = trim($_POST['password']);
    
    if (empty($username)) {
        $error = "Username is required.";
    } else {
        // Check if username already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            // Update or insert user
            if ($id > 0) {
                // Update existing user
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, is_admin = ? WHERE id = ?");
                    $stmt->bind_param("ssii", $username, $hashed_password, $is_admin, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, is_admin = ? WHERE id = ?");
                    $stmt->bind_param("sii", $username, $is_admin, $id);
                }
            } else {
                // Create new user (password is required)
                if (empty($password)) {
                    $error = "Password is required for new users.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssi", $username, $hashed_password, $is_admin);
                }
            }
            
            if (empty($error)) {
                if ($stmt->execute()) {
                    $success = $id > 0 ? "User updated successfully!" : "User created successfully!";
                    if ($id === 0) {
                        // For new users, redirect to edit page with the new ID
                        $id = $stmt->insert_id;
                        header("Location: edit_user.php?id=" . $id . "&success=1");
                        exit();
                    }
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Display success message from redirect
if (isset($_GET['success'])) {
    $success = "User created successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id > 0 ? 'Edit' : 'Create' ?> User</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        h1 { color: #2c3e50; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input[type="checkbox"] { margin-right: 10px; }
        .btn { display: inline-block; background-color: #3498db; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background-color: #2980b9; }
        .btn-back { background-color: #95a5a6; margin-right: 10px; }
        .btn-back:hover { background-color: #7f8c8d; }
        .error { color: #e74c3c; margin-bottom: 15px; }
        .success { color: #2ecc71; margin-bottom: 15px; }
        .checkbox-label { display: flex; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><?= $id > 0 ? 'Edit User' : 'Create New User' ?></h1>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="<?= $id > 0 ? 'Leave blank to keep current password' : '' ?>">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_admin" name="is_admin" value="1" <?= $is_admin ? 'checked' : '' ?>>
                        Admin User
                    </label>
                </div>
                
                <div class="form-group"> 
                    <a href="admin_dashboard?tab=users" class="btn btn-back">Back to Users</a>
                    <button type="submit" class="btn">Save User</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>