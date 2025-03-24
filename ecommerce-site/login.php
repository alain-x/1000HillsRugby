<?php
require_once('header.php');

$error_message = '';

if (isset($_POST['form1'])) {
    if (empty($_POST['cust_email']) || empty($_POST['cust_password'])) {
        $error_message = LANG_VALUE_132 . '<br>';
    } else {
        $cust_email = strip_tags($_POST['cust_email']);
        $cust_password = strip_tags($_POST['cust_password']);

        $statement = $pdo->prepare("SELECT * FROM tbl_customer WHERE cust_email=?");
        $statement->execute([$cust_email]);
        $total = $statement->rowCount();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if ($total == 0) {
            $error_message .= LANG_VALUE_133 . '<br>';
        } else {
            // Check both password formats (md5 for old passwords and password_verify for new ones)
            $password_valid = false;
            
            // First try modern password_verify
            if (password_verify($cust_password, $result['cust_password'])) {
                $password_valid = true;
            } 
            // Then fall back to md5 for legacy passwords
            elseif ($result['cust_password'] == md5($cust_password)) {
                $password_valid = true;
                // Upgrade to modern hashing
                $new_hash = password_hash($cust_password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE tbl_customer SET cust_password = ? WHERE cust_id = ?")
                   ->execute([$new_hash, $result['cust_id']]);
            }

            if (!$password_valid) {
                $error_message .= LANG_VALUE_139 . '<br>';
            } else {
                if ($result['cust_status'] == 0) {
                    $error_message .= LANG_VALUE_148 . '<br>';
                } else {
                    $_SESSION['customer'] = $result;
                    header("location: " . BASE_URL . "index.php");
                    exit;
                }
            }
        }
    }
}
?>

<div class="page-banner" style="background-color:#444;background-image: url(assets/uploads/<?php echo $banner_login; ?>);">
    <div class="inner" style="height: 20px;"> 
        <h1 style="font-size: 20px; margin-top: 1px;"><?php echo LANG_VALUE_10; ?></h1>
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="user-content">
                    <form action="" method="post">
                        <?php $csrf->echoInputField(); ?>
                        <div class="row">
                            <div class="col-md-4"></div>
                            <div class="col-md-4">
                                <?php if ($error_message): ?>
                                    <div class="error" style="padding: 10px;background:#f1f1f1;margin-bottom:20px;">
                                        <?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label for=""><?php echo LANG_VALUE_94; ?> *</label>
                                    <input type="email" class="form-control" name="cust_email" required>
                                </div>
                                <div class="form-group">
                                    <label for=""><?php echo LANG_VALUE_96; ?> *</label>
                                    <input type="password" class="form-control" name="cust_password" required>
                                </div>
                                <div class="form-group">
                                    <input type="submit" class="btn btn-success"  style="background-color: #ff6600; border-radius:20px; border-color: #ff6600;" value="<?php echo LANG_VALUE_4; ?>" name="form1">
                                </div>
                                <a href="forget-password.php" style="color:#e4144d;"><?php echo LANG_VALUE_97; ?>?</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>