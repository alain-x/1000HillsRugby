<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

adminAuthGuard();

// Create CSRF token for forms
$csrfToken = generateCsrfToken();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        if (isset($_POST['create_campaign'])) {
            $data = [
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'goal_amount' => $_POST['goal_amount'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'donation_amounts' => $_POST['donation_amounts']
            ];
            
            if (createCampaign($data)) {
                $success = "Campaign created successfully!";
            } else {
                $error = "Failed to create campaign. Please try again.";
            }
        }
        
        if (isset($_POST['update_goal'])) {
            if (updateCampaignGoal($_POST['campaign_id'], $_POST['new_goal'])) {
                $success = "Campaign goal updated successfully!";
            } else {
                $error = "Failed to update campaign goal.";
            }
        }
    }
}

$campaigns = getAllCampaigns();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Campaigns | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-dashboard">
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <h1>Manage Campaigns</h1>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Create New Campaign</h2>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Campaign Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="goal_amount">Goal Amount ($)</label>
                            <input type="number" id="goal_amount" name="goal_amount" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date (optional)</label>
                            <input type="date" id="end_date" name="end_date">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Donation Amount Options</label>
                            <div class="amount-options">
                                <input type="number" name="donation_amounts[]" placeholder="40" step="1" min="1">
                                <input type="number" name="donation_amounts[]" placeholder="100" step="1" min="1">
                                <input type="number" name="donation_amounts[]" placeholder="250" step="1" min="1">
                                <input type="number" name="donation_amounts[]" placeholder="500" step="1" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="create_campaign" class="btn btn-primary">Create Campaign</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Current Campaigns</h2>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Goal</th>
                                <th>Raised</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><?= htmlspecialchars($campaign['title']) ?></td>
                                <td>$<?= number_format($campaign['goal_amount'], 2) ?></td>
                                <td>$<?= number_format($campaign['current_amount'], 2) ?></td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?= min(100, ($campaign['goal_amount'] > 0 ? ($campaign['current_amount'] / $campaign['goal_amount']) * 100 : 0)) ?>%"></div>
                                        </div>
                                        <span><?= $campaign['goal_amount'] > 0 ? round(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 2) : 0 ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($campaign['is_active']): ?>
                                        <span class="status-badge active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="campaign-donations.php?id=<?= $campaign['id'] ?>" class="btn btn-sm btn-info">View Donations</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                                            <input type="number" name="new_goal" placeholder="New goal" step="0.01" min="0" style="width: 100px;">
                                            <button type="submit" name="update_goal" class="btn btn-sm btn-secondary">Update Goal</button>
                                        </form>
                                    </div>
                                </td>
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