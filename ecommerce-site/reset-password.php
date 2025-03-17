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

// Check if email and token are provided in the URL
if (!isset($_GET['email']) || !isset($_GET['token'])) {
    header('location: ' . BASE_URL . 'login.php');
    exit;
}

$email = $_GET['email'];
$token = $_GET['token'];

// Debugging: Print email and token
echo "Email: $email<br>";
echo "Token: $token<br>";

// Validate the token and email
$statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email = ? AND cust_token = ?");
$statement->execute([$email, $token]);
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
$tot = $statement->rowCount();

// Debugging: Print query result
echo "Query Result Count: $tot<br>";

if ($tot == 0) {
    echo "Invalid token or email.<br>";
    header('location: ' . BASE_URL . 'login.php');
    exit;
}

foreach ($result as $row) {
    $saved_time = $row['cust_timestamp'];
}

// Debugging: Print saved timestamp
echo "Saved Timestamp: $saved_time<br>";

// Check if the token has expired (24 hours)
$error_message2 = '';
if (time() - $saved_time > 86400) {
    $error_message2 = LANG_VALUE_144; // Token expired
    echo "Token expired.<br>";
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

        // Update the password and clear the token and timestamp
        $statement = $pdo->prepare("UPDATE tbl_customer SET cust_password = ?, cust_token = NULL, cust_timestamp = NULL WHERE cust_email = ?");
        $statement->execute([$cust_new_password, $email]);

        // Redirect to success page
        header('location: ' . BASE_URL . 'reset-password-success.php');
        exit;
    }
}
?>

<!-- Page Banner -->
<div class="page-banner" style="background-color:#444;background-image: url(assets/uploads/<?php echo $banner_reset_password; ?>);">
    <div class="inner">
        <h1><?php echo LANG_VALUE_149; ?></h1> <!-- Reset Password -->
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
                                        <input type="submit" class="btn btn-primary" value="<?php echo LANG_VALUE_149; ?>" name="form1"> <!-- Reset Password -->
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