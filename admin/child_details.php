<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Missing child ID");
}

$id = (int)$_GET['id'];
$sql = "SELECT c.*, CONCAT(g.first_name, ' ', g.last_name) as guardian_name 
        FROM children c 
        LEFT JOIN guardians g ON c.guardian_id = g.id 
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();

if (!$child) {
    die("Child not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Details - Orphanage Management System</title>
    
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

        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
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
            transition: all 0.3s ease;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .menu-item.active {
            background: var(--secondary-color);
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .child-details {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }

        .child-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .child-header h2 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
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

        .section-title {
            margin: 25px 0 15px;
            color: var(--primary-color);
            font-size: 1.3em;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .child-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .child-info:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .child-info label {
            font-weight: 600;
            color: var(--primary-color);
            display: block;
            margin-bottom: 8px;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .child-info span {
            color: #555;
            font-size: 1.1em;
            display: block;
            padding: 5px 0;
        }

        .health-status {
            white-space: pre-line;
            line-height: 1.6;
            color: #666;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 10px;
            }

            .sidebar-header h2,
            .menu-item span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
                padding: 15px;
            }

            .child-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .action-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
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
        <div class="child-details">
            <div class="child-header">
                <h2><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h2>
                <div class="action-buttons">
                    <a href="children.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="javascript:void(0)" onclick="openModal('edit', <?php echo $child['id']; ?>)" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="javascript:void(0)" onclick="deleteChild(<?php echo $child['id']; ?>)" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>

            <h3 class="section-title">Personal Information</h3>
            <div class="child-info">
                <label>Full Name:</label>
                <span><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></span>
            </div>

            <div class="child-info">
                <label>Date of Birth:</label>
                <span><?php echo date('F j, Y', strtotime($child['date_of_birth'])); ?></span>
            </div>

            <div class="child-info">
                <label>Age:</label>
                <span><?php 
                    $dob = new DateTime($child['date_of_birth']);
                    $today = new DateTime();
                    echo $dob->diff($today)->y . ' years old';
                ?></span>
            </div>

            <div class="child-info">
                <label>Gender:</label>
                <span><?php echo htmlspecialchars($child['gender']); ?></span>
            </div>

            <h3 class="section-title">Health Information</h3>
            <div class="child-info">
                <label>Health Status:</label>
                <span class="health-status"><?php echo htmlspecialchars($child['health_status'] ?: 'No health issues reported'); ?></span>
            </div>

            <h3 class="section-title">Guardian Information</h3>
            <div class="child-info">
                <label>Assigned Guardian:</label>
                <span><?php echo htmlspecialchars($child['guardian_name'] ?: 'No guardian assigned'); ?></span>
            </div>

            <div class="child-info">
                <label>Date Added:</label>
                <span><?php echo date('F j, Y', strtotime($child['created_at'])); ?></span>
            </div>
        </div>
    </div>

    <script>
        function openModal(action, id) {
            window.location.href = `children.php?action=${action}&id=${id}`;
        }

        function deleteChild(id) {
            if (confirm('Are you sure you want to delete this child? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'children.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 