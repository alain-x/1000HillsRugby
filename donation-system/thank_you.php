<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$donation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$donation = getDonation($donation_id);

if (!$donation) {
    header('Location: index.php');
    exit;
}
?>

<div class="container thank-you-page">
    <div class="thank-you-card">
        <div class="thank-you-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
        </div>
        
        <h1>Thank You for Your Donation!</h1>
        <p class="thank-you-message">Your generous contribution to <strong><?= htmlspecialchars($donation['campaign_title']) ?></strong> is greatly appreciated.</p>
        
        <div class="donation-details">
            <div class="detail-row">
                <span class="detail-label">Amount:</span>
                <span class="detail-value">$<?= number_format($donation['amount'], 2) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?= ucfirst($donation['payment_method']) ?></span>
            </div>
            <?php if ($donation['is_monthly']): ?>
            <div class="detail-row">
                <span class="detail-label">Recurring:</span>
                <span class="detail-value">Monthly</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($donation['note'])): ?>
            <div class="detail-row">
                <span class="detail-label">Your Note:</span>
                <span class="detail-value">"<?= htmlspecialchars($donation['note']) ?>"</span>
            </div>
            <?php endif; ?>
        </div>
        
        <p class="receipt-message">A receipt has been sent to <strong><?= htmlspecialchars($donation['email']) ?></strong></p>
        
        <div class="action-buttons">
            <a href="index.php" class="btn btn-secondary">Return Home</a>
            <a href="campaign.php?id=<?= $donation['campaign_id'] ?>" class="btn btn-primary">Back to Campaign</a>
        </div>
    </div>
    
    <div class="social-share">
        <p>Share your donation and inspire others:</p>
        <div class="share-buttons">
            <a href="#" class="share-button facebook">Share on Facebook</a>
            <a href="#" class="share-button twitter">Share on Twitter</a>
            <a href="#" class="share-button linkedin">Share on LinkedIn</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>