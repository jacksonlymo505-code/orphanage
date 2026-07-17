<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized access');
}

// Check if donation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Donation ID is required');
}

$donation_id = $_GET['id'];

// Fetch donation details with donor information
$query = "SELECT d.*, 
          CONCAT(dr.first_name, ' ', dr.last_name) as donor_name,
          dr.email as donor_email,
          o.title as opportunity_title,
          o.id as opportunity_id
          FROM donations d
          LEFT JOIN donors dr ON d.donor_id = dr.id
          LEFT JOIN opportunities o ON d.opportunity_id = o.id
          WHERE d.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $donation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Donation not found');
}

$donation = $result->fetch_assoc();
?>

<div class="donation-details">
    <div class="detail-section">
        <h3><i class="fas fa-user"></i> Donor Information</h3>
        <p><strong>Donor Name:</strong> 
            <?php 
            $donorName = '';
            if (isset($donation['first_name']) && isset($donation['last_name'])) {
                $donorName = htmlspecialchars($donation['first_name'] . ' ' . $donation['last_name']);
            } else {
                $donorName = 'Anonymous';
            }
            echo $donorName;
            ?>
        </p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($donation['donor_email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars(isset($donation['phone']) ? $donation['phone'] : 'N/A'); ?></p>
    </div>
    
    <div class="detail-section">
        <h3><i class="fas fa-money-bill-wave"></i> Payment Information</h3>
        <p><strong>Amount:</strong> <?php echo htmlspecialchars($donation['currency']) . ' ' . number_format($donation['amount'], 2); ?></p>
        <?php if ($donation['currency'] !== 'USD'): ?>
            <p><strong>USD Equivalent:</strong> $<?php echo number_format($donation['amount'] / 2300, 2); ?></p>
        <?php endif; ?>
        <p><strong>Payment Method:</strong> <?php echo strtoupper(str_replace('_', ' ', $donation['payment_method'])); ?></p>
        <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars(isset($donation['transaction_id']) ? $donation['transaction_id'] : 'N/A'); ?></p>
    </div>

    <div class="detail-section">
        <h3><i class="fas fa-info-circle"></i> Status Information</h3>
        <p><strong>Status:</strong> <span class="status-badge <?php echo strtolower($donation['status']); ?>"><?php echo ucfirst($donation['status']); ?></span></p>
        <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($donation['donation_date'])); ?></p>
        <p><strong>Last Updated:</strong> 
            <?php echo isset($donation['updated_at']) ? date('M d, Y H:i', strtotime($donation['updated_at'])) : 'N/A'; ?>
        </p>
    </div>

    <?php if (!empty($donation['notes'])): ?>
    <div class="detail-section">
        <h3><i class="fas fa-sticky-note"></i> Additional Notes</h3>
        <p><?php echo htmlspecialchars($donation['notes']); ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($donation['opportunity_id'])): ?>
        <button class="btn btn-primary" onclick="viewOpportunityDonors(<?php echo $donation['opportunity_id']; ?>)">
            <i class="fas fa-users"></i> View Donors
        </button>
    <?php endif; ?>
</div>
