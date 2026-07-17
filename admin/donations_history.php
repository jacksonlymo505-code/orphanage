<?php
session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get currency
$currency = get_currency();

// Function to update donation status
function updateDonationStatus($donation_id, $new_status) {
    global $conn;
    
    $sql = "UPDATE donations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $donation_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => "Donation status updated to " . ucfirst($new_status)
        ];
    } else {
        return [
            'success' => false,
            'message' => "Error updating donation status: " . $conn->error
        ];
    }
}

// Verify tables exist
$tables = ['donations', 'donors', 'opportunities']; // Changed 'users' to 'donors'
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        die("Table '$table' does not exist in the database");
    }
}

// Enable error reporting at the top of the file
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle status update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_status"])) {
    $donation_id = $_POST['donation_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    if (in_array($new_status, ['pending', 'completed', 'failed'])) {
        $result = updateDonationStatus($donation_id, $new_status);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = "Invalid status value";
    }
    
    // Redirect to prevent form resubmission
    header("Location: donations_history.php");
    exit();
}

// Handle filters
$where_conditions = [];
$params = [];
$types = "";

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $where_conditions[] = "d.donation_date >= ?";
    $params[] = $_GET['start_date'];
    $types .= "s";
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $where_conditions[] = "d.donation_date <= ?";
    $params[] = $_GET['end_date'];
    $types .= "s";
}

if (isset($_GET['donor_id']) && !empty($_GET['donor_id'])) {
    $where_conditions[] = "d.donor_id = ?";
    $params[] = $_GET['donor_id'];
    $types .= "i";
}

if (isset($_GET['payment_method']) && !empty($_GET['payment_method'])) {
    $where_conditions[] = "d.payment_method = ?";
    $params[] = $_GET['payment_method'];
    $types .= "s";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "d.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Build the WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total donations amount
$total_query = "SELECT 
    SUM(amount) as total_tsh,
    COUNT(*) as total_donations
    FROM donations d
    $where_clause";

$stmt = $conn->prepare($total_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// Main donations query
$sql = "SELECT 
    d.id,
    d.donor_id,
    d.amount,
    d.payment_method,
    d.status,
    d.donation_date,
    d.notes,
    d.created_at,
    CONCAT(dr.first_name, ' ', dr.last_name) as donor_name,
    dr.email as donor_email,
    dr.phone as donor_phone,
    o.title as opportunity_title,
    o.id as opportunity_id,
    o.category as opportunity_category
    FROM donations d
    LEFT JOIN donors dr ON d.donor_id = dr.id
    LEFT JOIN opportunities o ON d.project_id = o.id
    $where_clause
    ORDER BY d.donation_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error . 
        "<br>Query: " . $sql . 
        "<br>Error number: " . $conn->errno);
}

// If there are parameters, bind them
if (!empty($params)) {
    if (!$stmt->bind_param($types, ...$params)) {
        die("Error binding parameters: " . $stmt->error);
    }
}

// Execute the query
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
$donations = $result->fetch_all(MYSQLI_ASSOC);

// Get all donors for filter dropdown
$donors_query = "SELECT id, first_name, last_name, email, phone 
                 FROM donors 
                 WHERE type = 'donor' AND status = 'active' 
                 ORDER BY first_name";
$donors = $conn->query($donors_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations History - Orphanage Management System</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
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

        .container {
            flex: 1;
            padding: 20px;
            margin-left: 250px; /* Add margin to account for sidebar */
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            color: var(--dark-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #34495e;
        }

        .donations-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .donations-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .donations-table th,
        .donations-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .donations-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .donations-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .summary-card h3 {
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 1.1em;
            font-weight: 500;
        }

        .summary-card p {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }

        /* Alert styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Status update modal styles */
        .status-update-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            max-width: 500px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .close-btn {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        #status-confirmation-message {
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                padding: 10px;
            }

            .filters form {
                grid-template-columns: 1fr;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>OMS Admin</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="children.php" class="menu-item">
                <i class="fas fa-child"></i> Children
            </a>
            <a href="guardians.php" class="menu-item">
                <i class="fas fa-user-shield"></i> Guardians
            </a>
            <a href="donors.php" class="menu-item">
                <i class="fas fa-hand-holding-heart"></i> Donors
            </a>
            <a href="adoptions.php" class="menu-item">
                <i class="fas fa-baby"></i> Adoptions
            </a>
            <a href="donations_history.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i> Donations
            </a>
            <a href="manage_opportunities.php" class="menu-item">
                <i class="fas fa-handshake"></i> Opportunities
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    <div class="container">
        <div class="header">
            <h1>Donations History</h1>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Donations</h3>
                <p><?php echo number_format($totals['total_donations']); ?></p>
            </div>
            <div class="summary-card">
                                        <h3>Total Amount (TSh)</h3>
                        <p>TSh <?php echo number_format($totals['total_tsh'], 2); ?></p>
            </div>
        </div>

        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="donor">Donor</label>
                    <select id="donor" name="donor_id">
                        <option value="">Select a donor</option>
                        <?php foreach ($donors as $donor): ?>
                            <option value="<?php echo $donor['id']; ?>" <?php echo isset($_GET['donor_id']) && $_GET['donor_id'] == $donor['id'] ? 'selected' : ''; ?>>
                                <?php echo $donor['first_name'] . ' ' . $donor['last_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method">
                        <option value="">Select a payment method</option>
                        <option value="Cash" <?php echo isset($_GET['payment_method']) && $_GET['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="Online" <?php echo isset($_GET['payment_method']) && $_GET['payment_method'] == 'Online' ? 'selected' : ''; ?>>Online</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Select a status</option>
                        <option value="Pending" <?php echo isset($_GET['status']) && $_GET['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Failed" <?php echo isset($_GET['status']) && $_GET['status'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>

        <div class="donations-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Donor</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Opportunity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                            <td><?php echo htmlspecialchars(isset($donation['donor_name']) ? $donation['donor_name'] : 'N/A'); ?></td>
                                                            <td>TSh <?php echo number_format($donation['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($donation['payment_method']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($donation['status']); ?>">
                                    <?php echo htmlspecialchars($donation['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(isset($donation['opportunity_title']) ? $donation['opportunity_title'] : 'N/A'); ?></td>
                            <td>
                                <?php if ($donation['status'] === 'pending'): ?>
                                    <button 
                                        class="btn btn-success" 
                                        style="padding: 5px 10px; font-size: 12px; margin-right: 5px;"
                                        onclick="openStatusModal(<?php echo $donation['id']; ?>, 'completed')"
                                    >
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button 
                                        class="btn btn-danger" 
                                        style="padding: 5px 10px; font-size: 12px;"
                                        onclick="openStatusModal(<?php echo $donation['id']; ?>, 'failed')"
                                    >
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php elseif ($donation['status'] === 'completed' || $donation['status'] === 'failed'): ?>
                                    <button 
                                        class="btn btn-secondary" 
                                        style="padding: 5px 10px; font-size: 12px;"
                                        onclick="openStatusModal(<?php echo $donation['id']; ?>, 'pending')"
                                    >
                                        <i class="fas fa-undo"></i> Reset to Pending
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusUpdateModal" class="status-update-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Donation Status</h3>
                <span class="close-btn" onclick="closeStatusModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="donation_id" id="donation_id_input">
                <input type="hidden" name="new_status" id="new_status_input">
                
                <div class="form-group">
                    <p id="status-confirmation-message"></p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_status" class="btn btn-primary">Confirm</button>
                    <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Status update modal functions
        function openStatusModal(donationId, newStatus) {
            document.getElementById('donation_id_input').value = donationId;
            document.getElementById('new_status_input').value = newStatus;
            
            // Set confirmation message based on status
            let message = '';
            if (newStatus === 'completed') {
                message = 'Are you sure you want to approve this donation?';
            } else if (newStatus === 'failed') {
                message = 'Are you sure you want to reject this donation?';
            } else {
                message = 'Are you sure you want to reset this donation to pending?';
            }
            
            document.getElementById('status-confirmation-message').textContent = message;
            document.getElementById('statusUpdateModal').style.display = 'block';
        }
        
        function closeStatusModal() {
            document.getElementById('statusUpdateModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusUpdateModal');
            if (event.target == modal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>