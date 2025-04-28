<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$campaigns = getActiveCampaigns();
?>

<div class="container">
    <header class="hero">
        <h1>Support Causes You Care About</h1>
        <p>Join thousands of donors making a difference every day</p>
    </header>

    <div class="campaigns-grid">
        <?php foreach ($campaigns as $campaign): ?>
            <div class="campaign-card">
                <div class="campaign-image">
                    <img src="assets/images/campaign-placeholder.jpg" alt="<?= htmlspecialchars($campaign['title']) ?>">
                </div>
                <div class="campaign-content">
                    <h3><?= htmlspecialchars($campaign['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($campaign['description'], 0, 100)) ?>...</p>
                    
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress" style="width: <?= min(100, ($campaign['current_amount'] / $campaign['goal_amount']) * 100) ?>%"></div>
                        </div>
                        <div class="progress-stats">
                            <span>$<?= number_format($campaign['current_amount'], 2) ?> raised</span>
                            <span>$<?= number_format($campaign['goal_amount'], 2) ?> goal</span>
                        </div>
                    </div>
                    
                    <a href="campaign.php?id=<?= $campaign['id'] ?>" class="btn">Donate Now</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>