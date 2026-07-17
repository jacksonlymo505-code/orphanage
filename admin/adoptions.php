<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'add':
            $child_id = $_POST['child_id'];
            $adopter_id = $_POST['adoptive_parent_id'];
            $status = $_POST['status'];
            $application_date = $_POST['application_date'];
            $notes = $_POST['notes'];
            
            $sql = "INSERT INTO adoptions (child_id, adopter_id, status, application_date) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $child_id, $adopter_id, $status, $application_date);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Adoption application added successfully";
            } else {
                $_SESSION['error'] = "Error adding application: " . $conn->error;
            }
            break;
            
        case 'edit':
            $id = $_POST['id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'];
            
            $sql = "UPDATE adoptions SET status=?, notes=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $status, $notes, $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Adoption status updated successfully";
            } else {
                $_SESSION['error'] = "Error updating status: " . $conn->error;
            }
            break;
            
        case 'delete':
            $id = $_POST['id'];
            
            $sql = "DELETE FROM adoptions WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Adoption record deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting record: " . $conn->error;
            }
            break;
    }
    
    header("Location: adoptions.php");
    exit();
}

// Fetch all adoption applications with related information
$sql = "SELECT a.*, 
        c.first_name as child_first_name, c.last_name as child_last_name,
        d.first_name as parent_first_name, d.last_name as parent_last_name,
        a.notes
        FROM adoptions a
        LEFT JOIN children c ON a.child_id = c.id
        LEFT JOIN donors d ON a.adopter_id = d.id
        ORDER BY a.application_date DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Fetch available children for adoption
$children_sql = "SELECT id, first_name, last_name FROM children";
$children_result = $conn->query($children_sql);

if (!$children_result) {
    die("Query failed: " . $conn->error);
}

// Fetch adoptive parents
$parents_sql = "SELECT id, first_name, last_name FROM donors WHERE type = 'adoptive'";
$parents_result = $conn->query($parents_sql);

if (!$parents_result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoption Management - Orphanage Management System</title>
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

        /* Main Content Styles */
        .container {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            background: #f5f6fa;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e1e1;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin: 0;
        }

        /* Table Styles */
        .table-responsive {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            color: var(--primary-color);
            font-weight: 600;
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid #e1e1e1;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e1e1e1;
            color: #2c3e50;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            color: #6c5ce7;
            border-left: 4px solid #6c5ce7;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(108, 92, 231, 0.2);
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #cce5ff;
            color: #004085;
        }

        .badge-adoptive-parent {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pending-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .pending-indicator::before {
            content: '●';
            color: #6c5ce7;
            font-size: 10px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e1e1e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--primary-color);
            font-size: 20px;
        }

        .close {
            font-size: 24px;
            color: #666;
            cursor: pointer;
        }

        .close:hover {
            color: var(--danger-color);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
            padding: 0 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
        }

        .form-actions {
            padding: 20px;
            border-top: 1px solid #e1e1e1;
            text-align: right;
        }

        .form-actions .btn {
            margin-left: 10px;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        #adoptionForm {
            overflow-y: auto;
            padding: 20px 0;
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
        <div class="page-header">
            <h1>Adoption Management</h1>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i> New Adoption Application
            </button>
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

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-child"></i> Child Name</th>
                        <th><i class="fas fa-user-check"></i> Adoptive Parent</th>
                        <th><i class="fas fa-calendar"></i> Application Date</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-sticky-note"></i> Notes</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['child_first_name'] . ' ' . $row['child_last_name']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($row['parent_first_name'] . ' ' . $row['parent_last_name']); ?>
                                <span class="badge-adoptive-parent"><i class="fas fa-heart"></i> Adoptive Parent</span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['application_date'])); ?></td>
                            <td>
                                <?php if (strtolower($row['status']) === 'pending'): ?>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <span class="pending-indicator">PENDING REQUEST</span>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick='editAdoption(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>) ' title="Edit adoption record">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteAdoption(<?php echo $row['id']; ?>)" title="Delete adoption record">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Adoption Modal -->
    <div id="adoptionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">New Adoption Application</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="adoptionForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="adoptionId">
                
                <div class="form-group">
                    <label for="child_id">Child *</label>
                    <select id="child_id" name="child_id" required>
                        <option value="">Select Child</option>
                        <?php while ($child = $children_result->fetch_assoc()): ?>
                            <option value="<?php echo $child['id']; ?>">
                                <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="adoptive_parent_id">Adoptive Parent *</label>
                    <select id="adoptive_parent_id" name="adoptive_parent_id" required>
                        <option value="">Select Adoptive Parent</option>
                        <?php while ($parent = $parents_result->fetch_assoc()): ?>
                            <option value="<?php echo $parent['id']; ?>">
                                <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="application_date">Application Date *</label>
                    <input type="date" id="application_date" name="application_date" required>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('adoptionModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'New Adoption Application';
            document.getElementById('formAction').value = 'add';
            document.getElementById('adoptionForm').reset();
            document.getElementById('application_date').valueAsDate = new Date();
        }

        function closeModal() {
            document.getElementById('adoptionModal').style.display = 'none';
        }

        function editAdoption(adoption) {
            document.getElementById('adoptionModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Adoption Application';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('adoptionId').value = adoption.id;
            document.getElementById('child_id').value = adoption.child_id;
            document.getElementById('adoptive_parent_id').value = adoption.adopter_id;
            document.getElementById('status').value = adoption.status;
            document.getElementById('application_date').value = adoption.application_date;
            document.getElementById('notes').value = adoption.notes || '';
        }

        function deleteAdoption(id) {
            if (confirm('Are you sure you want to delete this adoption record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('adoptionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 