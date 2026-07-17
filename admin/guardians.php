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
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $phone = $_POST['phone'];
            $email = $_POST['email'];
            $address = $_POST['address'];
            $relationship = $_POST['relationship'];
            
            $sql = "INSERT INTO guardians (first_name, last_name, phone, email, address, relationship) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $first_name, $last_name, $phone, $email, $address, $relationship);
            $stmt->execute();
            break;
            
        case 'edit':
            $id = $_POST['id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $phone = $_POST['phone'];
            $email = $_POST['email'];
            $address = $_POST['address'];
            $relationship = $_POST['relationship'];
            
            $sql = "UPDATE guardians 
                    SET first_name=?, last_name=?, phone=?, email=?, address=?, relationship=? 
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $email, $address, $relationship, $id);
            $stmt->execute();
            break;
            
        case 'delete':
            $id = $_POST['id'];
            $sql = "DELETE FROM guardians WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
    }
    
    header("Location: guardians.php");
    exit();
}

// Fetch all guardians
$sql = "SELECT g.*, COUNT(c.id) as children_count 
        FROM guardians g 
        LEFT JOIN children c ON g.id = c.guardian_id 
        GROUP BY g.id, g.first_name, g.last_name, g.phone, g.email, g.address, g.relationship";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardians - Orphanage Management System</title>
  
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
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 24px;
            color: var(--primary-color);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .guardians-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .guardian-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .guardian-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .guardian-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .guardian-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .guardian-info {
            margin-bottom: 10px;
        }

        .guardian-info label {
            font-weight: 600;
            color: #666;
            font-size: 0.9em;
        }

        .guardian-info span {
            color: #333;
            display: block;
            margin-top: 5px;
        }

        .guardian-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
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
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
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
            border-bottom: 1px solid #eee;
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
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
                padding: 15px;
            }

            .guardians-grid {
                grid-template-columns: 1fr;
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
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Guardians</h1>
            <button class="btn btn-primary" onclick="openModal('add')">
                <i class="fas fa-plus"></i> Add New Guardian
            </button>
        </div>

        <div class="guardians-grid">
            <?php while ($guardian = $result->fetch_assoc()): ?>
                <div class="guardian-card">
                    <div class="guardian-header">
                        <h3 class="guardian-name"><?php echo htmlspecialchars($guardian['first_name'] . ' ' . $guardian['last_name']); ?></h3>
                        <div class="guardian-actions">
                            <button class="btn btn-primary" onclick="openModal('edit', <?php echo $guardian['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger" onclick="deleteGuardian(<?php echo $guardian['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="guardian-info">
                        <label>Phone:</label>
                        <span><?php echo htmlspecialchars($guardian['phone'] ?? ''); ?></span>
                    </div>
                    <div class="guardian-info">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($guardian['email'] ?? ''); ?></span>
                    </div>
                    <div class="guardian-info">
                        <label>Address:</label>
                        <span><?php echo htmlspecialchars($guardian['address'] ?? ''); ?></span>
                    </div>
                    <div class="guardian-info">
                        <label>Relationship:</label>
                        <span><?php echo htmlspecialchars($guardian['relationship'] ?? ''); ?></span>
                    </div>
                    <div class="guardian-info">
                        <label>Children Under Care:</label>
                        <span><?php echo $guardian['children_count']; ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Add/Edit Guardian Modal -->
    <div id="guardianModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Guardian</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="guardianForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="guardianId">
                
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" maxlength="50" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" maxlength="50" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" maxlength="20" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" maxlength="255"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="relationship">Relationship to Child *</label>
                    <select id="relationship" name="relationship" required>
                        <option value="">Select Relationship</option>
                        <option value="Parent">Parent</option>
                        <option value="Relative">Relative</option>
                        <option value="Foster Parent">Foster Parent</option>
                        <option value="Legal Guardian">Legal Guardian</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Guardian</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, id = null) {
            const modal = document.getElementById('guardianModal');
            const form = document.getElementById('guardianForm');
            const title = document.getElementById('modalTitle');
            
            document.getElementById('formAction').value = action;
            
            if (action === 'edit' && id) {
                title.textContent = 'Edit Guardian';
                document.getElementById('guardianId').value = id;
                
                // Fetch guardian data and populate form
                fetch(`get_guardian.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('first_name').value = data.first_name || '';
                        document.getElementById('last_name').value = data.last_name || '';
                        document.getElementById('phone').value = data.phone || '';
                        document.getElementById('email').value = data.email || '';
                        document.getElementById('address').value = data.address || '';
                        document.getElementById('relationship').value = data.relationship || '';
                    });
            } else {
                title.textContent = 'Add New Guardian';
                form.reset();
            }
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('guardianModal').style.display = 'none';
        }

        function deleteGuardian(id) {
            if (confirm('Are you sure you want to delete this guardian? This action cannot be undone.')) {
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
            const modal = document.getElementById('guardianModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 