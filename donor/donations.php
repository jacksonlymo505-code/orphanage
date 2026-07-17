<?php
session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

// Get currency
$currency = get_currency();

// Get user's donor record
$user_id = (int)$_SESSION['user_id'];
$user_email = '';
$donor_id = null;

$result = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $user_email = $row['email'];
}

$completed_total = 0;
$pending_total = 0;

if ($user_email) {
    $email_esc = $conn->real_escape_string($user_email);
    $result = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $donor_id = (int)$row['id'];
    }
}

if ($donor_id === null) {
    $donor_id = $user_id;
}

$donor_ids = $donor_id === $user_id ? "$donor_id" : "$donor_id, $user_id";

$query = "SELECT id, donor_id, amount, currency, payment_method, status, donation_date, created_at 
          FROM donations 
          WHERE donor_id IN ($donor_ids) 
          ORDER BY created_at DESC";

$result = $conn->query($query);
if ($result === false) {
    die("Error fetching donations: " . $conn->error);
}
$donations = $result->fetch_all(MYSQLI_ASSOC);

$total_query = "SELECT 
    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS total_completed,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) AS total_pending
    FROM donations 
    WHERE donor_id IN ($donor_ids)";

$total_result = $conn->query($total_query);
if ($total_result && $total_row = $total_result->fetch_assoc()) {
    $completed_total = (float)($total_row['total_completed'] ?? 0);
    $pending_total = (float)($total_row['total_pending'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History - Donor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            margin-top: 20px;
        }

        .menu-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .menu-item.active {
            background: var(--secondary-color);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .donations-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #4b5563;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.successful {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .floating-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .btn-submit {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .btn-submit:hover {
            background: #2980b9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .amount-input-group {
            display: flex;
            gap: 10px;
        }

        .amount-input-group select {
            width: 100px;
        }

        .amount-input-group input {
            flex: 1;
        }
    </style>
 
</head>
<body>
    <!-- Sidebar -->
   <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Donation History</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="donation-summary-cards" style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
            <div style="flex:1; min-width:220px; background:white; border-radius:12px; padding:1rem; box-shadow:0 2px 6px rgba(0,0,0,0.08);">
                <h3 style="margin:0 0 0.5rem; color:#2c3e50;">Confirmed Total</h3>
                <p style="font-size:1.75rem; font-weight:700; margin:0;">TSh <?php echo number_format($completed_total, 2); ?></p>
                <small style="color:#6b7280;">Sum of confirmed donations</small>
            </div>
            <div style="flex:1; min-width:220px; background:white; border-radius:12px; padding:1rem; box-shadow:0 2px 6px rgba(0,0,0,0.08);">
                <h3 style="margin:0 0 0.5rem; color:#2c3e50;">Pending Total</h3>
                <p style="font-size:1.75rem; font-weight:700; margin:0;">TSh <?php echo number_format($pending_total, 2); ?></p>
                <small style="color:#6b7280;">Pending donations not yet confirmed</small>
            </div>
        </div>

        <div class="donations-table">
            <?php if (empty($donations)): ?>
                <div class="empty-state">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>No Donations Yet</h3>
                    <p>Start making a difference by making your first donation.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Project</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                                <td>TSh <?php echo number_format($donation['amount'], 2); ?></td>
                                <td>General Donation</td>
                                <td><?php echo htmlspecialchars($donation['payment_method']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($donation['status']); ?>">
                                        <?php echo ucfirst($donation['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add this before closing body tag -->
    <div id="donationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Make a Donation</h2>
            <form id="donationForm" action="process_donation.php" method="POST">
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <div class="amount-input-group">
                        <select id="currency" name="currency" required>
                            <option value="TSh" selected>TSh</option>
                        </select>
                        <input type="number" id="amount" name="amount" min="1" step="0.01" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="credit_card">Credit Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Donate Now</button>
            </form>
        </div>
    </div>

    <button id="refreshBtn" class="floating-btn" style="right: 180px; background:#10b981;">Refresh Totals</button>
    <button id="donateBtn" class="floating-btn">Make Another Donation</button>

    <script>
        // Add this before closing body tag
        const modal = document.getElementById('donationModal');
        const btn = document.getElementById('donateBtn');
        const refreshBtn = document.getElementById('refreshBtn');
        const span = document.getElementsByClassName('close')[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        refreshBtn.onclick = function() {
            location.reload();
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html> 