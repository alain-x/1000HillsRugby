<?php
/**
 * Helper functions for the donation system
 */

// Money formatting function
function formatMoney($amount, $decimals = 2) {
    return number_format((float)($amount ?? 0), $decimals);
}

// Number formatting function
function formatNumber($number, $decimals = 0) {
    return number_format((float)($number ?? 0), $decimals);
}

// Date formatting function
function formatDate($dateString, $format = 'M j, Y') {
    if (empty($dateString)) {
        return 'N/A';
    }
    try {
        $date = new DateTime($dateString);
        return $date->format($format);
    } catch (Exception $e) {
        return 'Invalid date';
    }
}

// Get all active campaigns
function getActiveCampaigns() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM campaigns WHERE is_active = TRUE ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getActiveCampaigns: " . $e->getMessage());
        return [];
    }
}

// Get campaign by ID with validation
function getCampaign($id) {
    global $pdo;
    if (!is_numeric($id)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getCampaign: " . $e->getMessage());
        return false;
    }
}

// Get donation amounts for campaign with validation
function getDonationAmounts($campaign_id) {
    global $pdo;
    if (!is_numeric($campaign_id)) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT amount FROM donation_amounts WHERE campaign_id = ? AND is_active = TRUE ORDER BY amount ASC");
        $stmt->execute([$campaign_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Database error in getDonationAmounts: " . $e->getMessage());
        return [];
    }
}

// Create donation with validation
function createDonation($data) {
    global $pdo;
    
    // Validate required fields
    $required = ['campaign_id', 'donor_name', 'email', 'amount', 'payment_method'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Validate amount is numeric
    if (!is_numeric($data['amount'])) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO donations (campaign_id, donor_name, email, amount, is_monthly, note, is_public, payment_method) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$data['campaign_id'],
            htmlspecialchars($data['donor_name']),
            $data['email'],
            (float)$data['amount'],
            !empty($data['is_monthly']) ? 1 : 0,
            !empty($data['note']) ? htmlspecialchars($data['note']) : null,
            !empty($data['is_public']) ? 1 : 0,
            htmlspecialchars($data['payment_method'])
        ]);
        
        // Update campaign total
        $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?")
            ->execute([(float)$data['amount'], (int)$data['campaign_id']]);
        
        $donation_id = $pdo->lastInsertId();
        $pdo->commit();
        
        return $donation_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error in createDonation: " . $e->getMessage());
        return false;
    }
}

// Get donation by ID with validation
function getDonation($id) {
    global $pdo;
    if (!is_numeric($id)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title FROM donations d JOIN campaigns c ON d.campaign_id = c.id WHERE d.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getDonation: " . $e->getMessage());
        return false;
    }
}

// Admin functions

// Get all campaigns with error handling
function getAllCampaigns() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM campaigns ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getAllCampaigns: " . $e->getMessage());
        return [];
    }
}

// Create campaign with validation
function createCampaign($data) {
    global $pdo;
    
    // Validate required fields
    $required = ['title', 'description', 'goal_amount', 'start_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    
    // Validate goal amount is numeric
    if (!is_numeric($data['goal_amount'])) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO campaigns (title, description, goal_amount, start_date, end_date) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            htmlspecialchars($data['title']),
            htmlspecialchars($data['description']),
            (float)$data['goal_amount'],
            $data['start_date'],
            !empty($data['end_date']) ? $data['end_date'] : null
        ]);
        
        $campaign_id = $pdo->lastInsertId();
        
        // Process donation amounts
        if (!empty($data['donation_amounts']) && is_array($data['donation_amounts'])) {
            foreach ($data['donation_amounts'] as $amount) {
                if (is_numeric($amount) && $amount > 0) {
                    $stmt = $pdo->prepare("INSERT INTO donation_amounts (campaign_id, amount) VALUES (?, ?)");
                    $stmt->execute([$campaign_id, (float)$amount]);
                }
            }
        }
        
        $pdo->commit();
        return $campaign_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error in createCampaign: " . $e->getMessage());
        return false;
    }
}

// Update campaign goal with validation
function updateCampaignGoal($campaign_id, $new_goal) {
    global $pdo;
    
    if (!is_numeric($campaign_id) || !is_numeric($new_goal)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE campaigns SET goal_amount = ? WHERE id = ?");
        return $stmt->execute([(float)$new_goal, (int)$campaign_id]);
    } catch (PDOException $e) {
        error_log("Database error in updateCampaignGoal: " . $e->getMessage());
        return false;
    }
}

// Get campaign donations with validation
function getCampaignDonations($campaign_id) {
    global $pdo;
    
    if (!is_numeric($campaign_id)) {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM donations WHERE campaign_id = ? ORDER BY created_at DESC");
        $stmt->execute([$campaign_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getCampaignDonations: " . $e->getMessage());
        return [];
    }
}
?>