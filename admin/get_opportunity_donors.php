<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    exit('Unauthorized');
}

if (!isset($_GET['id'])) {
    exit('Opportunity ID is required');
}

$opportunity_id = $_GET['id'];

// Get opportunity details
$sql = "SELECT title FROM opportunities WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $opportunity_id);
$stmt->execute();
$opportunity = $stmt->get_result()->fetch_assoc();

if (!$opportunity) {
    exit('Opportunity not found');
}

// Get donors for this opportunity
$sql = "SELECT d.*, u.first_name, u.last_name, u.email 
        FROM donations d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.opportunity_id = ? 
        ORDER BY d.donation_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $opportunity_id);
$stmt->execute();
$result = $stmt->get_result();
$donors = $result->fetch_all(MYSQLI_ASSOC);

// Calculate total donations
$total = array_sum(array_column($donors, 'amount'));
?>

<div class="donors-list">
    <h3 style="margin-bottom: 20px;"><?php echo htmlspecialchars($opportunity['title']); ?></h3>
    
    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <strong>Total Donations:</strong> 
        <?php echo number_format($total, 2); ?> USD
        <br>
        <strong>Number of Donors:</strong> 
        <?php echo count($donors); ?>
    </div>

    <?php if (empty($donors)): ?>
        <p style="text-align: center; color: #666;">No donors yet for this opportunity.</p>
    <?php else: ?>
        <?php foreach ($donors as $donor): ?>
            <div class="donor-item">
                <div class="donor-info">
                    <div class="donor-name">
                        <?php echo htmlspecialchars($donor['first_name'] . ' ' . $donor['last_name']); ?>
                    </div>
                    <div class="donor-email">
                        <?php echo htmlspecialchars($donor['email']); ?>
                    </div>
                </div>
                <div class="donation-amount">
                    <?php echo htmlspecialchars($donor['currency']) . ' ' . number_format($donor['amount'], 2); ?>
                </div>
                <div class="donation-date">
                    <?php echo date('M d, Y', strtotime($donor['donation_date'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div> 