<?php require_once('header.php'); ?>

<?php
// Include PHPMailer library
require 'admin/PHPMailer/src/Exception.php';
require 'admin/PHPMailer/src/PHPMailer.php';
require 'admin/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_forget_password = $row['banner_forget_password'];
}
?>

<?php
if(isset($_POST['form1'])) {

    $valid = 1;
    $error_message = '';
    $success_message = '';

    if(empty($_POST['cust_email'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_131."\\n"; // Email is required
    } else {
        if (filter_var($_POST['cust_email'], FILTER_VALIDATE_EMAIL) === false) {
            $valid = 0;
            $error_message .= LANG_VALUE_134."\\n"; // Invalid email format
        } else {
            $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=?");
            $statement->execute(array($_POST['cust_email']));
            $total = $statement->rowCount();                        
            if(!$total) {
                $valid = 0;
                $error_message .= LANG_VALUE_135."\\n"; // Email not found
            }
        }
    }

    if($valid == 1) {

        // Fetch the forget password message from settings
        $statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);                           
        foreach ($result as $row) {
            $forget_password_message = $row['forget_password_message'];
        }

        // Generate a unique token and timestamp
        $token = md5(rand());
        $now = time();

        // Update the customer's token and timestamp in the database
        $statement = $pdo->prepare("UPDATE tbl_customer SET cust_token=?, cust_timestamp=? WHERE cust_email=?");
        $statement->execute(array($token, $now, strip_tags($_POST['cust_email'])));

        // Prepare the reset password link
        $reset_link = BASE_URL.'reset-password.php?email='.$_POST['cust_email'].'&token='.$token;

        // Email content
        $message = '<p>'.LANG_VALUE_142.'<br> <a href="'.$reset_link.'">Click here to reset your password</a></p>';

        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP(); // Use SMTP
            $mail->Host       = 'mail.1000hillsrugby.rw'; // Gmail SMTP server
            $mail->SMTPAuth   = true; // Enable SMTP authentication
            $mail->Username   = 'info@1000hillsrugby.rw'; // Your Gmail email
            $mail->Password   = 'M00dle??'; // Use the App Password here
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port       = 587; // TCP port to connect to

            // Recipients
            $mail->setFrom('info@1000hillsrugby.rw', '1000 Hills Rugby'); // Use a valid email address
            $mail->addAddress($_POST['cust_email']); // Recipient email

            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = LANG_VALUE_143; // Subject: Password Reset Request
            $mail->Body    = $message; // Email body

            $mail->send(); // Send the email
            $success_message = $forget_password_message; // Success message
        } catch (Exception $e) {
            $error_message = "Email could not be sent. Error: {$mail->ErrorInfo}"; // Error message
        }
    }
}
?>

<!-- Page Banner -->
<div class="page-banner" style="background-color:#444;background-image: url(assets/uploads/<?php echo $banner_forget_password; ?>);">
    <div class="inner" style="height: 2px;">
        <h1 style="font-size: 20px; margin-top: 1px;"><?php echo LANG_VALUE_97; ?></h1> <!-- Forgot Password -->
    </div>
</div>

<!-- Page Content -->
<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="user-content">
                    <?php
                    // Display error message
                    if($error_message != '') {
                        echo "<script>alert('".$error_message."')</script>";
                    }
                    // Display success message
                    if($success_message != '') {
                        echo "<script>alert('".$success_message."')</script>";
                    }
                    ?>
                    <!-- Forgot Password Form -->
                    <form action="" method="post">
                        <?php $csrf->echoInputField(); ?> <!-- CSRF Token -->
                        <div class="row">
                            <div class="col-md-4"></div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for=""><?php echo LANG_VALUE_94; ?> *</label> <!-- Email Address -->
                                    <input type="email" class="form-control" name="cust_email" required>
                                </div>
                                <div class="form-group">
                                    <label for=""></label>
                                    <input type="submit" class="btn btn-primary" value="<?php echo LANG_VALUE_4; ?>" name="form1"> <!-- Submit -->
                                </div>
                                <a href="login.php" style="color:#e4144d;"><?php echo LANG_VALUE_12; ?></a> <!-- Back to Login -->
                            </div>
                        </div>                        
                    </form>
                </div>                
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>