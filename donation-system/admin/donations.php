<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

adminAuthGuard();

$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$campaign = getCampaign($campaign_id);

if (!$campaign) {
    header('Location: campaigns.php');
    exit;
}

$donations = getCampaignDonations($campaign_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations for <?= htmlspecialchars($campaign['title']) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-dashboard">
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Donations for <?= htmlspecialchars($campaign['title']) ?></h1>
                <a href="campaigns.php" class="btn btn-secondary">Back to Campaigns</a>
            </div>
            
            <div class="card">
                <div class="campaign-stats">
                    <div class="stat-item">
                        <span class="stat-label">Goal:</span>
                        <span class="stat-value">$<?= number_format($campaign['goal_amount'], 2) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Raised:</span>
                        <span class="stat-value">$<?= number_format($campaign['current_amount'], 2) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Progress:</span>
                        <span class="stat-value"><?= round(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 2) ?>%</span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Donor</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Recurring</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donations as $donation): ?>
                            <tr>
                                <td><?= $donation['id'] ?></td>
                                <td><?= htmlspecialchars($donation['donor_name']) ?></td>
                                <td><?= htmlspecialchars($donation['email']) ?></td>
                                <td>$<?= number_format($donation['amount'], 2) ?></td>
                                <td><?= ucfirst($donation['payment_method']) ?></td>
                                <td><?= $donation['is_monthly'] ? 'Yes' : 'No' ?></td>
                                <td><?= date('M j, Y', strtotime($donation['created_at'])) ?></td>
                                <td><span class="status-badge <?= $donation['status'] ?>"><?= ucfirst($donation['status']) ?></span></td>
                                <td><?= !empty($donation['note']) ? htmlspecialchars(substr($donation['note'], 0, 50)) . '...' : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>