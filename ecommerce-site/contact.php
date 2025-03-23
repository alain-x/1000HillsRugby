<?php require_once('header.php'); ?>

<?php
// Include PHPMailer manually
require 'admin/PHPMailer/src/Exception.php';
require 'admin/PHPMailer/src/PHPMailer.php';
require 'admin/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection and fetching settings
$statement = $pdo->prepare("SELECT * FROM tbl_page WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $contact_title = $row['contact_title'];
    $contact_banner = $row['contact_banner'];
}

$statement = $pdo->prepare("SELECT * FROM tbl_settings WHERE id=1");
$statement->execute();
$result = $statement->fetchAll(PDO::FETCH_ASSOC);                            
foreach ($result as $row) {
    $contact_map_iframe = $row['contact_map_iframe'];
    $contact_email = $row['contact_email'];
    $contact_phone = $row['contact_phone'];
    $contact_address = $row['contact_address'];
    $receive_email = $row['receive_email'];
    $receive_email_subject = $row['receive_email_subject'];
    $receive_email_thank_you_message = $row['receive_email_thank_you_message'];
}
?>

<div class="page-banner" style="background-image: url(assets/uploads/<?php echo $contact_banner; ?>);">
    <div class="inner" style="height: 20px;">
        <h1 style="font-size: 20px; margin-top: 1px;"><?php echo $contact_title; ?></h1> 
    </div>
</div>

<div class="page">
    <div class="container">
        <div class="row">            
            <div class="col-md-12">
                <h3>Contact Form</h3>
                <div class="row cform">
                    <div class="col-md-8">
                        <div class="well well-sm">
                            <?php
                            // After form submit checking everything for email sending
                            if(isset($_POST['form_contact']))
                            {
                                $error_message = '';
                                $success_message = '';

                                $valid = 1;
                                if(empty($_POST['visitor_name']))
                                {
                                    $valid = 0;
                                    $error_message .= 'Please enter your name.\n';
                                }

                                if(empty($_POST['visitor_phone']))
                                {
                                    $valid = 0;
                                    $error_message .= 'Please enter your phone number.\n';
                                }

                                if(empty($_POST['visitor_email']))
                                {
                                    $valid = 0;
                                    $error_message .= 'Please enter your email address.\n';
                                }
                                else
                                {
                                    // Email validation check
                                    if(!filter_var($_POST['visitor_email'], FILTER_VALIDATE_EMAIL))
                                    {
                                        $valid = 0;
                                        $error_message .= 'Please enter a valid email address.\n';
                                    }
                                }

                                if(empty($_POST['visitor_message']))
                                {
                                    $valid = 0;
                                    $error_message .= 'Please enter your message.\n';
                                }

                                if($valid == 1)
                                {
                                    $visitor_name = strip_tags($_POST['visitor_name']);
                                    $visitor_email = strip_tags($_POST['visitor_email']);
                                    $visitor_phone = strip_tags($_POST['visitor_phone']);
                                    $visitor_message = strip_tags($_POST['visitor_message']);

                                    // Create a new PHPMailer instance
                                    $mail = new PHPMailer(true);

                                    try {
                                        // Server settings
                                        $mail->isSMTP();
                                        $mail->Host       = 'mail.1000hillsrugby.rw'; // Replace with your SMTP server
                                        $mail->SMTPAuth   = true;
                                        $mail->Username   = 'info@1000hillsrugby.rw'; // Your email address
                                        $mail->Password   = 'M00dle??'; // Your email password
                                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                                        $mail->Port       = 587; // TCP port to connect to
                                        // Recipients
                                        $mail->setFrom($visitor_email, $visitor_name);
                                        $mail->addAddress($receive_email); // Add a recipient

                                        // Content
                                        $mail->isHTML(true);
                                        $mail->Subject = $receive_email_subject;
                                        $mail->Body    = '
                                        <html><body>
                                        <table>
                                        <tr>
                                        <td>Name</td>
                                        <td>'.$visitor_name.'</td>
                                        </tr>
                                        <tr>
                                        <td>Email</td>
                                        <td>'.$visitor_email.'</td>
                                        </tr>
                                        <tr>
                                        <td>Phone</td>
                                        <td>'.$visitor_phone.'</td>
                                        </tr>
                                        <tr>
                                        <td>Comment</td>
                                        <td>'.nl2br($visitor_message).'</td>
                                        </tr>
                                        </table>
                                        </body></html>';

                                        // Send email
                                        $mail->send();
                                        $success_message = $receive_email_thank_you_message;
                                    } catch (Exception $e) {
                                        $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                                    }
                                }
                            }

                            // Display error or success message
                            if($error_message != '') {
                                echo "<script>alert('".$error_message."')</script>";
                            }
                            if($success_message != '') {
                                echo "<script>alert('".$success_message."')</script>";
                            }
                            ?>

                            <form action="" method="post">
                            <?php $csrf->echoInputField(); ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" class="form-control" name="visitor_name" placeholder="Enter name">
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" class="form-control" name="visitor_email" placeholder="Enter email address">
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Phone Number</label>
                                        <input type="text" class="form-control" name="visitor_phone" placeholder="Enter phone number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Message</label>
                                        <textarea name="visitor_message" class="form-control" rows="9" cols="25" placeholder="Enter message"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <input type="submit" value="Send Message" class="btn btn-primary pull-right" name="form_contact" style="background-color: #ff6600; border-radius: 20px; border-color: #ff6600; width: 290px;">
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <legend><span class="glyphicon glyphicon-globe"></span> Our office</legend>
                        <address>
                            <?php echo nl2br($contact_address); ?>
                        </address>
                        <address>
                            <strong>Phone:</strong><br>
                            <span><?php echo $contact_phone; ?></span>
                        </address>
                        <address>
                            <strong>Email:</strong><br>
                            <a href="mailto:<?php echo $contact_email; ?>"><span><?php echo $contact_email; ?></span></a>
                        </address>
                    </div>
                </div>

                <h3>Find Us On Map</h3>
                <?php echo $contact_map_iframe; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once('footer.php'); ?>