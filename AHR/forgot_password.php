<?php
// forgot_password.php
require_once 'config.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// Check database structure
$check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token_hash'");
if ($check_columns->num_rows == 0) {
    die("Database configuration error: Required columns for password reset are missing. Please run: ALTER TABLE users ADD COLUMN reset_token_hash VARCHAR(64) NULL, ADD COLUMN reset_token_expires_at DATETIME NULL;");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists in the database
    $sql = "SELECT id, username FROM users WHERE username = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minutes expiry
        
        // Store the token in the database
        $update_sql = "UPDATE users SET 
                       reset_token_hash = '$token_hash', 
                       reset_token_expires_at = '$expiry' 
                       WHERE id = {$user['id']}";
        
        if ($conn->query($update_sql)) {
            // Dynamically generate the reset link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $reset_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . 
                          dirname($_SERVER['PHP_SELF']) . 
                          "/reset_password.php?token=$token";
            
            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'alainvalentin04@gmail.com'; // SMTP username
                $mail->Password   = 'tchq nals jyas wpon'; // SMTP password
                $mail->SMTPSecure = 'ssl'; // Enable SSL encryption
                $mail->Port       = 465; // TCP port to connect to
                
                // Recipients
                $mail->setFrom('alainvalentin04@gmail.com', 'Attendance System');
                $mail->addAddress($email); // Add a recipient
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Hello,<br><br>Click the link below to reset your password:<br><br>
                                   <a href='$reset_link'>$reset_link</a><br><br>This link expires in 30 minutes.";
                $mail->AltBody = "Hello,\n\nClick the link below to reset your password:\n\n$reset_link\n\nThis link expires in 30 minutes.";
                
                $mail->send();
                $success = "A password reset link has been sent to your email address. Please check your inbox (and spam folder).";
            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                // Optionally log this error for debugging
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        } else {
            $error = "Error processing your request. Please try again. Error: " . $conn->error;
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Attendance System</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .forgot-container {
            width: 100%;
            max-width: 450px;
        }

        .forgot-card {
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
            .forgot-card {
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
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="logo">
                <i class="fas fa-key"></i>
            </div>
            
            <h1>Forgot Password</h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
            
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
            
            <form method="post" id="forgotForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                           required autofocus>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            
            <div class="login-link">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('forgotForm')?.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>