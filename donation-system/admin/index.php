<?php
// Set the correct path to include files from the root
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php'; // Add this line

// Check admin authentication
adminAuthGuard();

// Get admin dashboard data with proper NULL handling
$campaigns = (int)($pdo->query("SELECT COUNT(*) FROM campaigns")->fetchColumn() ?? 0);
$donations = (int)($pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn() ?? 0);
$total_amount = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'completed'")->fetchColumn());
$recent_donations = $pdo->query("SELECT d.*, c.title as campaign_title FROM donations d JOIN campaigns c ON d.campaign_id = c.id ORDER BY d.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-dashboard">
    <?php include __DIR__ . '/includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include __DIR__ . '/includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <h1>Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $campaigns ?></div>
                    <div class="stat-label">Active Campaigns</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $donations ?></div>
                    <div class="stat-label">Total Donations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">$<?= formatMoney($total_amount) ?></div>
                    <div class="stat-label">Total Raised</div>
                </div>
            </div>
            
            <?php if (!empty($recent_donations)): ?>
            <div class="recent-section">
                <h2>Recent Donations</h2>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Campaign</th>
                                <th>Donor</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_donations as $donation): ?>
                            <tr>
                                <td><?= (int)$donation['id'] ?></td>
                                <td><?= htmlspecialchars($donation['campaign_title'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($donation['donor_name'] ?? 'Anonymous') ?></td>
                                <td>$<?= formatMoney($donation['amount'] ?? 0) ?></td>
                                <td><?= formatDate($donation['created_at'] ?? '') ?></td>
                                <td>
                                    <span class="status-badge <?= htmlspecialchars($donation['status'] ?? 'pending') ?>">
                                        <?= ucfirst(htmlspecialchars($donation['status'] ?? 'Pending')) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-info">No recent donations found</div>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>