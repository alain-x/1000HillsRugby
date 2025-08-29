<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validate and process donation
$campaign_id = isset($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'card';
$is_monthly = isset($_POST['is_monthly']) ? true : false;
$donor_name = trim($_POST['donor_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$note = trim($_POST['note'] ?? '');
$is_public = isset($_POST['is_public']) ? true : false;
$csrf_token = $_POST['csrf_token'] ?? '';

// Basic validation
$errors = [];
if ($campaign_id <= 0) $errors[] = 'Invalid campaign';
if ($amount <= 0) $errors[] = 'Invalid amount';
if (empty($donor_name)) $errors[] = 'Name is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (!in_array($payment_method, ['card', 'paypal', 'crypto', 'stocks', 'daf', 'venmo', 'gift_card', 'check', 'wire'])) {
    $errors[] = 'Invalid payment method';
}
if (!verifyCsrfToken($csrf_token)) {
    $errors[] = 'Invalid request. Please refresh and try again.';
}

if (empty($errors)) {
    $donation_data = [
        'campaign_id' => $campaign_id,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'is_monthly' => $is_monthly,
        'donor_name' => $donor_name,
        'email' => $email,
        'note' => $note,
        'is_public' => $is_public
    ];
    
    $donation_id = createDonation($donation_data);
    
    if ($donation_id) {
        header("Location: thank_you.php?id=$donation_id");
        exit;
    } else {
        $errors[] = 'Failed to process donation. Please try again.';
    }
}

// If we get here, there were errors
require_once 'includes/header.php';
?>
<div class="container">
    <h1>Donation Error</h1>
    <div class="alert alert-danger">
        <p>We encountered the following errors with your donation:</p>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <a href="campaign.php?id=<?= $campaign_id ?>" class="btn">Back to Campaign</a>
</div>
<?php
require_once 'includes/footer.php';
?>