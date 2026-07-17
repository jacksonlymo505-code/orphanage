<?php
session_start();
require_once '../config/database.php';

// Ensure approved donors are present in the users table for admin list display
$sync_sql = "INSERT INTO users (first_name, last_name, email, phone, organization, password, role) 
    SELECT first_name, last_name, email, phone, organization_name, password_hash, 'donor'
    FROM donors
    WHERE (status = 'approved' OR approval_status = 'approved')
      AND email NOT IN (SELECT email FROM users)";
$conn->query($sync_sql);

$conn->query("UPDATE users u
    JOIN donors d ON u.email = d.email
    SET u.role = 'donor', u.organization = d.organization_name, u.password = d.password_hash
    WHERE (d.status = 'approved' OR d.approval_status = 'approved')");

// Build donor list from users plus any approved donors still missing a users record
$users = [];
$user_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
if ($user_result) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
}
$donor_result = $conn->query("SELECT first_name, last_name, email, phone, organization_name AS organization, password_hash AS password, 'donor' AS role, NULL AS id FROM donors WHERE (status = 'approved' OR approval_status = 'approved') AND email NOT IN (SELECT email FROM users)");
if ($donor_result) {
    while ($row = $donor_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'add':
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $organization = trim($_POST['organization']);
            $role = $_POST['role']; // 'donor' or 'admin'
            $plain_password = trim($_POST['password'] ?? '');

            if (empty($plain_password)) {
                $plain_password = substr(bin2hex(random_bytes(4)), 0, 8);
            }
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (first_name, last_name, email, phone, organization, password, role) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $organization, $hashed_password, $role);

            if ($stmt->execute()) {
                if ($role === 'donor') {
                    $donor_type = 'donor';
                    $status = 'active';
                    $donor_sql = "INSERT INTO donors (first_name, last_name, email, phone, type, status) VALUES (?, ?, ?, ?, ?, ?)";
                    $donor_stmt = $conn->prepare($donor_sql);
                    if ($donor_stmt) {
                        $donor_stmt->bind_param('ssssss', $first_name, $last_name, $email, $phone, $donor_type, $status);
                        $donor_stmt->execute();
                        $donor_stmt->close();
                    }
                }

                $_SESSION['success'] = "User added successfully.";
                $_SESSION['new_user_credentials'] = "Credentials for $email: Password = $plain_password";
            } else {
                $_SESSION['error'] = "Error adding user: " . $conn->error;
            }
            break;

        case 'edit':
            $id = $_POST['id'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $organization = trim($_POST['organization']);
            $role = $_POST['role'];
            $plain_password = trim($_POST['password'] ?? '');

            // Fetch old user data to sync donor records if email changes
            $old_email = '';
            $user_check = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
            if ($user_check) {
                $user_check->bind_param('i', $id);
                $user_check->execute();
                $user_result = $user_check->get_result();
                $user_row = $user_result->fetch_assoc();
                $old_email = $user_row['email'] ?? '';
            }

            if (!empty($plain_password)) {
                $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET first_name=?, last_name=?, email=?, phone=?, organization=?, role=?, password=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $first_name, $last_name, $email, $phone, $organization, $role, $hashed_password, $id);
            } else {
                $sql = "UPDATE users SET first_name=?, last_name=?, email=?, phone=?, organization=?, role=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $phone, $organization, $role, $id);
            }

            if ($stmt->execute()) {
                // If this is a donor record, keep the donors table in sync
                if ($role === 'donor' && !empty($old_email)) {
                    if (!empty($plain_password)) {
                        $donor_update = $conn->prepare("UPDATE donors SET first_name = ?, last_name = ?, email = ?, phone = ?, organization_name = ?, password_hash = ? WHERE email = ? LIMIT 1");
                        if ($donor_update) {
                            $donor_update->bind_param('sssssss', $first_name, $last_name, $email, $phone, $organization, $hashed_password, $old_email);
                            $donor_update->execute();
                        }
                    } else {
                        $donor_update = $conn->prepare("UPDATE donors SET first_name = ?, last_name = ?, email = ?, phone = ?, organization_name = ? WHERE email = ? LIMIT 1");
                        if ($donor_update) {
                            $donor_update->bind_param('ssssss', $first_name, $last_name, $email, $phone, $organization, $old_email);
                            $donor_update->execute();
                        }
                    }
                }
                $_SESSION['success'] = "User updated successfully";
            } else {
                $_SESSION['error'] = "Error updating user: " . $conn->error;
            }
            break;

        case 'delete':
            $id = $_POST['id'];

            $sql = "DELETE FROM users WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = "User deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting user: " . $conn->error;
            }
            break;
    }

    header("Location: donors.php");
    exit();
}

// Fetch all users
$result = $users;

if ($result === false) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    $result = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donors & Adoptive Parents Management</title>
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
            <a href="manage_donors.php" class="menu-item">
                <i class="fas fa-user-check"></i> Donor Requests
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
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['new_user_credentials'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['new_user_credentials']; unset($_SESSION['new_user_credentials']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
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
                        <?php if (!empty($row['id'])): ?>
                            <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $row['id']; ?>)">Delete</button>
                        <?php else: ?>
                            <span class="badge badge-secondary">Imported</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="donorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add New User</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="donorForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="donorId">

            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone *</label>
                <input type="tel" id="phone" name="phone" required>
            </div>

            <div class="form-group">
                <label for="organization">Organization</label>
                <input type="text" id="organization" name="organization">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to generate or keep current password">
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
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