<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('header.php');

// Fetch settings
$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $banner_reset_password = $row['banner_reset_password'];
}

// Initialize variables
$error_message = '';
$error_message2 = '';

// Check if email and token are provided in the URL
if (!isset($_GET['email']) || !isset($_GET['token'])) {
    header('location: ' . BASE_URL . 'login.php');
    exit;
}

$email = $_GET['email'];
$token = $_GET['token'];

// Validate the token and email
$statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email = ? AND cust_token = ?");
$statement->execute([$email, $token]);
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
$tot = $statement->rowCount();

if ($tot == 0) {
    header('location: ' . BASE_URL . 'login.php');
    exit;
}

foreach ($result as $row) {
    $saved_time = $row['cust_timestamp'];
}

// Check if the token has expired (24 hours)
if (time() - $saved_time > 86400) {
    $error_message2 = LANG_VALUE_144; // Token expired
}

// Handle form submission
if (isset($_POST['form1'])) {
    $valid = 1;
    $error_message = '';

    // Validate password fields
    if (empty($_POST['cust_new_password']) || empty($_POST['cust_re_password'])) {
        $valid = 0;
        $error_message .= LANG_VALUE_140 . '\\n'; // Password fields are required
    } else {
        if ($_POST['cust_new_password'] != $_POST['cust_re_password']) {
            $valid = 0;
            $error_message .= LANG_VALUE_139 . '\\n'; // Passwords do not match
        }
    }

    if ($valid == 1) {
        // Hash the new password
        $cust_new_password = password_hash($_POST['cust_new_password'], PASSWORD_DEFAULT);

        // Update the password and reset token fields
        // Using 0 for timestamp instead of NULL since column doesn't allow NULL
        $statement = $pdo->prepare("UPDATE tbl_customer SET 
                                    cust_password = ?, 
                                    cust_token = '', 
                                    cust_timestamp = 0 
                                    WHERE cust_email = ?");
        $statement->execute([$cust_new_password, $email]);

        // Redirect to success page
        header('location: ' . BASE_URL . 'reset-password-success.php');
        exit;
    }
}
?>

<!-- Page Banner -->
<div class="page-banner" style="background-color:#444;background-image: url(assets/uploads/<?php echo $banner_reset_password; ?>);">
    <div class="inner" style="height: 20px;">
        <h1 style="font-size: 20px; margin-top: 1px;"><?php echo LANG_VALUE_149; ?></h1> <!-- Reset Password -->
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
                    if ($error_message != '') {
                        echo "<script>alert('" . $error_message . "')</script>";
                    }
                    ?>
                    <?php if ($error_message2 != ''): ?>
                        <div class="error"><?php echo $error_message2; ?></div>
                        <p><a href="<?php echo BASE_URL; ?>forget-password.php" class="btn btn-primary"><?php echo LANG_VALUE_150; ?></a></p> <!-- Get New Token -->
                    <?php else: ?>
                        <!-- Reset Password Form -->
                        <form action="" method="post">
                            <?php $csrf->echoInputField(); ?> <!-- CSRF Token -->
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for=""><?php echo LANG_VALUE_100; ?> *</label> <!-- New Password -->
                                        <input type="password" class="form-control" name="cust_new_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for=""><?php echo LANG_VALUE_101; ?> *</label> <!-- Confirm Password -->
                                        <input type="password" class="form-control" name="cust_re_password" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="submit" class="btn btn-primary" style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;"value="<?php echo LANG_VALUE_149; ?>" name="form1"> <!-- Reset Password -->
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>