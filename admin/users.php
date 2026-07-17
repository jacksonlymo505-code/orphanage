<?php
session_start();
require_once '../config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $id = $_POST['id'] ?? null;
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $organization = $_POST['organization'] ?? '';
    $role = $_POST['role'] ?? 'donor';

    if ($action === 'add') {
        $password = password_hash("12345678", PASSWORD_DEFAULT); // Default password
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, organization, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $organization, $password, $role);
        if ($stmt->execute()) {
            $_SESSION['success'] = "User added successfully.";
        } else {
            $_SESSION['error'] = "Error adding user: " . $conn->error;
        }
    }

    if ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, organization=?, role=? WHERE id=?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $organization, $role, $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "User updated successfully.";
        } else {
            $_SESSION['error'] = "Error updating user: " . $conn->error;
        }
    }

    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting user: " . $conn->error;
        }
    }

    header("Location: users.php");
    exit();
}

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
if (!$result) {
    $_SESSION['error'] = "Error fetching users: " . $conn->error;
    $result = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
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
        .donor-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .donor-type.donor {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .donor-type.adoptive {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        .status-active {
            color: #2e7d32;
        }
        .status-inactive {
            color: #c62828;
        }

        /* Main Content Styles */
        .container {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
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
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--primary-color);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-color);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        /* Alert Styles */
        .alert {
            padding: 12px 15px;
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
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-color);
        }

        .close {
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--dark-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
            <h1>Users (Admins & Donors)</h1>
            <button class="btn btn-primary" onclick="openModal()">Add New</button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Organization</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo ucfirst($row['role']); ?></td>
                        <td>
                            <div>Email: <?php echo htmlspecialchars($row['email']); ?></div>
                            <div>Phone: <?php echo htmlspecialchars($row['phone']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($row['organization']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick='editUser(<?php echo json_encode($row); ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $row['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="donorModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New User</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="donorForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="donorId">
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" id="first_name" required></div>
                <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" id="last_name" required></div>
                <div class="form-group"><label>Email *</label><input type="email" name="email" id="email" required></div>
                <div class="form-group"><label>Phone *</label><input type="tel" name="phone" id="phone" required></div>
                <div class="form-group"><label>Organization</label><input type="text" name="organization" id="organization"></div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="role" required>
                        <option value="donor">Donor</option>
                        <option value="admin">Admin</option>
                    </select>
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
            document.getElementById('donorModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('formAction').value = 'add';
            document.getElementById('donorForm').reset();
        }

        function closeModal() {
            document.getElementById('donorModal').style.display = 'none';
        }

        function editUser(user) {
            document.getElementById('donorModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('donorId').value = user.id;
            document.getElementById('first_name').value = user.first_name;
            document.getElementById('last_name').value = user.last_name;
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone;
            document.getElementById('organization').value = user.organization;
            document.getElementById('role').value = user.role;
        }

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                document.getElementById('formAction').value = 'delete';
                document.getElementById('donorId').value = id;
                document.getElementById('donorForm').submit();
            }
        }
    </script>
</body>
</html>
