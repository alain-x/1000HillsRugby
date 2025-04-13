<?php
require_once 'config.php';

if (isLoggedIn() && !isAdmin()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Check if username exists
    $check_sql = "SELECT id FROM users WHERE username = '$username'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $error = "Username already exists";
    } else {
        $sql = "INSERT INTO users (username, password, is_admin) VALUES ('$username', '$password', $is_admin)";
        
        if ($conn->query($sql)) {
            $success = "User registered successfully";
            // Clear form on success
            $_POST = array();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            padding: 0;
            margin: 0;
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 20px;
        }

        h1 {
            color: var(--dark-color);
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }

        .text-center {
            text-align: center;
        }

        .login-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: inline-block;
            margin-top: 15px;
        }

        .login-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }

        @media (max-width: 576px) {
            .container {
                margin: 20px auto;
            }
            
            .card {
                padding: 20px;
            }
            
            h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-user-plus"></i> Register New User</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="registrationForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                           required>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <i class="fas fa-eye password-toggle-icon" id="togglePassword"></i>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="checkbox-container">
                    <input type="checkbox" id="is_admin" name="is_admin" 
                           <?= isset($_POST['is_admin']) ? 'checked' : '' ?>>
                    <label for="is_admin">Admin User</label>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Register User
                </button>
            </form>
            
            <div class="text-center">
                <a href="login.php" class="login-link">
                    <i class="fas fa-sign-in-alt"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Form validation
        document.getElementById('registrationForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            if (password && password.value.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                password.focus();
            }
        });
    </script>
</body>
</html>