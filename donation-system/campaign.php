<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$campaign = getCampaign($campaign_id);

if (!$campaign) {
    header('Location: index.php');
    exit;
}

$amounts = getDonationAmounts($campaign_id);
$csrfToken = generateCsrfToken();
?>

<div class="container campaign-page">
    <div class="campaign-header">
        <h1><?= htmlspecialchars($campaign['title']) ?></h1>
        <p class="campaign-description"><?= htmlspecialchars($campaign['description']) ?></p>
        
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress" style="width: <?= min(100, ($campaign['goal_amount'] > 0 ? ($campaign['current_amount'] / $campaign['goal_amount']) * 100 : 0)) ?>%"></div>
            </div>
            <div class="progress-stats">
                <span>$<?= number_format($campaign['current_amount'], 2) ?> raised</span>
                <span>$<?= number_format($campaign['goal_amount'], 2) ?> goal</span>
                <span><?= $campaign['goal_amount'] > 0 ? round(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 2) : 0 ?>% funded</span>
            </div>
        </div>
    </div>
    
    <div class="donation-section">
        <h2>Make a Donation</h2>
        
        <form action="donate.php" method="POST" class="donation-form">
            <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="form-group">
                <label>Donation Amount</label>
                <div class="amount-options">
                    <?php foreach ($amounts as $amount): ?>
                        <button type="button" class="amount-option" data-amount="<?= $amount ?>">$<?= $amount ?></button>
                    <?php endforeach; ?>
                    <input type="number" name="custom_amount" id="custom_amount" placeholder="Other amount" min="1" step="0.01">
                </div>
                <input type="hidden" name="amount" id="selected_amount" required>
            </div>
            
            <div class="form-group">
                <label>Payment Method</label>
                <div class="payment-methods">
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="card" checked>
                        <img src="assets/images/card-icon.png" alt="Credit Card">
                        <span>Credit Card</span>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="paypal">
                        <img src="assets/images/paypal-icon.png" alt="PayPal">
                        <span>PayPal</span>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="crypto">
                        <img src="assets/images/crypto-icon.png" alt="Crypto">
                        <span>Crypto</span>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="monthly-toggle">
                    <input type="checkbox" name="is_monthly">
                    <span class="toggle-slider"></span>
                    <span class="toggle-label">Make this a monthly donation</span>
                </label>
                <p class="help-text">Monthly donations help nonprofits focus on their mission. Cancel anytime.</p>
            </div>
            
            <div class="form-group">
                <label for="donor_name">Your Name</label>
                <input type="text" id="donor_name" name="donor_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="note">Add a note (optional)</label>
                <textarea id="note" name="note" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label class="public-toggle">
                    <input type="checkbox" name="is_public">
                    <span>Show my name and note publicly</span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-donate">Donate Now</button>
        </form>
    </div>
    
    <div class="donor-testimonials">
        <h3>What donors are saying</h3>
        <?php
        $stmt = $pdo->prepare("SELECT donor_name, note, created_at FROM donations WHERE campaign_id = ? AND is_public = 1 AND note IS NOT NULL ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$campaign_id]);
        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($testimonials as $testimonial): ?>
            <div class="testimonial">
                <p class="testimonial-text">"<?= htmlspecialchars($testimonial['note']) ?>"</p>
                <p class="testimonial-author">- <?= htmlspecialchars($testimonial['donor_name']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle amount selection
    const amountOptions = document.querySelectorAll('.amount-option');
    const customAmount = document.getElementById('custom_amount');
    const selectedAmount = document.getElementById('selected_amount');
    
    amountOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all options
            amountOptions.forEach(opt => opt.classList.remove('active'));
            // Add active class to clicked option
            this.classList.add('active');
            // Set the selected amount
            selectedAmount.value = this.dataset.amount;
            // Clear custom amount
            customAmount.value = '';
        });
    });
    
    customAmount.addEventListener('input', function() {
        // Remove active class from all options when custom amount is entered
        amountOptions.forEach(opt => opt.classList.remove('active'));
        // Set the selected amount
        selectedAmount.value = this.value;
    });
    
    // Set first amount as default
    if (amountOptions.length > 0) {
        amountOptions[0].click();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>