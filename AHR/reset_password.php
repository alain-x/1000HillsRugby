<?php
require_once 'config.php';

$error = '';
$success = '';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    header("Location: forgot_password.php");
    exit();
}

// Get and validate token
$token = trim($_GET['token']);
$token_hash = hash('sha256', $token);

// Check token validity
$stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token_hash = ?");
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Invalid reset link. Please request a new password reset.";
} else {
    $user = $result->fetch_assoc();
    
    // Check if token has expired
    if (strtotime($user['reset_token_expires_at']) < time()) {
        $error = "This reset link has expired. Please request a new one.";
    } else {
        $user_id = $user['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($password)) {
                $error = "Password is required.";
            } elseif (strlen($password) < 8) {
                $error = "Password must be at least 8 characters.";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Password reset successful. <a href='login.php'>Login now</a>";
                } else {
                    $error = "Failed to reset password. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain exactly the same */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 6px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: var(--light-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .reset-container {
            width: 100%;
            max-width: 450px;
        }

        .reset-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 40px;
            text-align: center;
        }

        .logo {
            margin-bottom: 25px;
        }

        .logo i {
            font-size: 50px;
            color: var(--primary-color);
        }

        h1 {
            color: var(--dark-color);
            margin-bottom: 25px;
            font-size: 28px;
        }

        p {
            margin-bottom: 25px;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .btn {
            display: block;
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            margin-top: 25px;
        }

        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-size: 16px;
            text-align: left;
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .reset-card {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .logo i {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="logo">
                <i class="fas fa-lock"></i>
            </div>
            
            <h1>Reset Password</h1>
            <p>Enter your new password below.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success && empty($error)): ?>
                <form method="post" id="resetForm">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="login-link">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('resetForm')?.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>