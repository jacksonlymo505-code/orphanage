<?php
session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get currency
$currency = get_currency();

// Fetch dashboard statistics
$stats = [
    'children' => 0,
    'donors' => 0,
    'sponsors' => 0,
    'guardians' => 0,
    'pending_donor_requests' => 0,
    'pending_adoptions' => 0,
    'completed_adoptions' => 0,
    'children_in_school' => 0,
    'medical_checkups' => 0,
    'meals_served' => 0,
    'volunteer_hours' => 0
];

try {
    // Get total children
    $result = $conn->query("SELECT COUNT(*) as count FROM children");
    if ($result) {
        $stats['children'] = $result->fetch_assoc()['count'];
    }

    // Get total donors
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'donor'");
    if ($result) {
        $stats['donors'] = $result->fetch_assoc()['count'];
    }

    // Get guest/public contributions count
    $result = $conn->query("SELECT COUNT(*) as count FROM donations WHERE project_id IS NULL OR notes LIKE '%Anonymous public%'");
    if ($result) {
        $stats['guest_contributions'] = $result->fetch_assoc()['count'];
    }

    // Get total sponsors
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'sponsor'");
    if ($result) {
        $stats['sponsors'] = $result->fetch_assoc()['count'];
    }

    // Get pending donor requests
    $result = $conn->query("SHOW TABLES LIKE 'donor_applications'");
    if ($result && $result->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM donor_applications WHERE status = 'pending'");
        if ($result) {
            $stats['pending_donor_requests'] = $result->fetch_assoc()['count'];
        }
    }

    // Get total guardians
    $result = $conn->query("SELECT COUNT(*) as count FROM guardians");
    if ($result) {
        $stats['guardians'] = $result->fetch_assoc()['count'];
    }

    // Get adoption statistics
    $result = $conn->query("SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM adoptions");
    if ($result) {
        $adoption_stats = $result->fetch_assoc();
        $stats['pending_adoptions'] = $adoption_stats['pending'];
        $stats['completed_adoptions'] = $adoption_stats['completed'];
    }

    // Get children in school count
    $result = $conn->query("SELECT COUNT(*) as count FROM children WHERE education_status = 'enrolled'");
    if ($result) {
        $stats['children_in_school'] = $result->fetch_assoc()['count'];
    }

    // Get medical checkups count
    $result = $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE checkup_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($result) {
        $stats['medical_checkups'] = $result->fetch_assoc()['count'];
    }

    // Get meals served count
    $result = $conn->query("SELECT COUNT(*) as count FROM meal_records WHERE meal_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($result) {
        $stats['meals_served'] = $result->fetch_assoc()['count'];
    }

    // Get volunteer hours
    $result = $conn->query("SELECT SUM(hours) as total FROM volunteer_hours WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stats['volunteer_hours'] = isset($row['total']) ? $row['total'] : 0;
    } else {
        $stats['volunteer_hours'] = 0;
    }

} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Orphanage Management System</title>
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
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        /* Top Bar Styles */
        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: #f5f6fa;
            padding: 8px 15px;
            border-radius: 20px;
            width: 300px;
        }

        .search-bar input {
            border: none;
            background: none;
            outline: none;
            margin-left: 10px;
            width: 100%;
        }

        .notifications {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-icon {
            position: relative;
            margin-left: 20px;
            cursor: pointer;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f5f6fa;
            border-radius: 50%;
            color: var(--secondary-color);
            font-size: 20px;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(15,23,42,0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 16px;
            color: var(--dark-color);
            font-weight: 600;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .card-footer {
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }

        .card-link {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 14px;
            background: var(--secondary-color);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .card-link:hover {
            background: #2566b4;
        }

        /* Recent Activity Section */
        .recent-activity {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .activity-list {
            margin-top: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f6fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--dark-color);
        }

        .activity-time {
            font-size: 12px;
            color: #666;
        }

        /* Quick Access Buttons */
        .quick-access {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .quick-btn {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
        }

        .quick-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .quick-btn i {
            font-size: 24px;
            margin-right: 10px;
            color: var(--secondary-color);
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .date-filter select {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-card.primary { border-left: 4px solid #2196f3; }
        .stat-card.success { border-left: 4px solid #4caf50; }
        .stat-card.warning { border-left: 4px solid #ff9800; }
        .stat-card.info { border-left: 4px solid #9c27b0; }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-card.primary .stat-icon { background: #e3f2fd; color: #2196f3; }
        .stat-card.success .stat-icon { background: #e8f5e9; color: #4caf50; }
        .stat-card.warning .stat-icon { background: #fff3e0; color: #ff9800; }
        .stat-card.info .stat-icon { background: #f3e5f5; color: #9c27b0; }

        .trend {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 8px;
            display: inline-block;
        }

        .trend.up { background: #e8f5e9; color: #4caf50; }
        .trend.down { background: #ffebee; color: #f44336; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
        }

        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 24px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .view-all {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 14px;
        }

        .adoption-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .adoption-stat {
            text-align: center;
            padding: 16px;
            border-radius: 8px;
        }

        .adoption-stat.pending { background: #fff3e0; }
        .adoption-stat.completed { background: #e8f5e9; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px;
            background: #f5f6fa;
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            background: var(--secondary-color);
            color: white;
        }

        .action-btn i {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .recent-activity {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f6fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 4px;
        }

        .activity-info {
            font-size: 13px;
            color: #666;
        }

        .activity-time {
            font-size: 12px;
            color: #999;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .events-list, .opportunities-list {
            margin-top: 15px;
        }

        .event-item, .opportunity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .event-icon, .opportunity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f6fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .event-icon i, .opportunity-icon i {
            color: var(--secondary-color);
        }

        .event-details, .opportunity-details {
            flex: 1;
        }

        .event-title, .opportunity-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 4px;
        }

        .event-info, .opportunity-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }

        .event-time, .opportunity-time {
            font-size: 12px;
            color: #999;
        }

        .stats-grid-secondary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 15px;
        }

        .stat-card.secondary {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
        }

        .stat-card.secondary .stat-icon {
            background: #e9ecef;
            color: #6c757d;
        }

        .stat-card.secondary:nth-child(2) {
            border-left-color: #dc3545;
        }

        .stat-card.secondary:nth-child(2) .stat-icon {
            background: #f8d7da;
            color: #dc3545;
        }

        .stat-card.secondary:nth-child(3) {
            border-left-color: #28a745;
        }

        .stat-card.secondary:nth-child(3) .stat-icon {
            background: #d4edda;
            color: #28a745;
        }

        .stat-card.secondary:nth-child(4) {
            border-left-color: #17a2b8;
        }

        .stat-card.secondary:nth-child(4) .stat-icon {
            background: #d1ecf1;
            color: #17a2b8;
        }

        @media (max-width: 768px) {
            .stats-grid-secondary {
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
            <a href="manage_donors.php" class="menu-item">
                <i class="fas fa-user-check"></i> Donor Requests
            </a>
            <a href="adoptions.php" class="menu-item">
                <i class="fas fa-baby"></i> Adoptions
            </a>
            <a href="donations_history.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i> Donations
            </a>
            <a href="public_contributions.php" class="menu-item">
                <i class="fas fa-users"></i> Guest Contributions
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>
            <div class="top-bar-right">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="admin-profile">
                    <span class="admin-avatar"><i class="fas fa-user-circle"></i></span>
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Dashboard Overview</h1>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['children']; ?></h3>
                        <p>Total Children</p>
                        <span class="trend up">+5% this month</span>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['donors']; ?></h3>
                        <p>Total Donors</p>
                        <span class="trend up">+12% this month</span>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['guest_contributions'] ?? 0; ?></h3>
                        <p>Guest Contributions</p>
                        <span class="trend up">Unregistered donations</span>
                        <a href="public_contributions.php" class="card-link">View Guest Gifts</a>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_donor_requests']; ?></h3>
                        <p>Pending Donor Requests</p>
                        <span class="trend up">Review new applications</span>
                        <a href="manage_donors.php" class="card-link">View Donor Requests</a>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['sponsors']; ?></h3>
                        <p>Total Sponsors</p>
                        <span class="trend up">+8% this month</span>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['guardians']; ?></h3>
                        <p>Total Guardians</p>
                        <span class="trend up">+3% this month</span>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="dashboard-column">
                    <!-- Adoption Status -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>Adoption Status</h2>
                            <a href="adoptions.php" class="view-all">View All</a>
                        </div>
                        <div class="adoption-stats">
                            <div class="adoption-stat pending">
                                <h3><?php echo $stats['pending_adoptions']; ?></h3>
                                <p>Pending</p>
                            </div>
                            <div class="adoption-stat completed">
                                <h3><?php echo $stats['completed_adoptions']; ?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dashboard Charts -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>Performance Charts</h2>
                        </div>
                        <div class="chart-card">
                            <h3 class="chart-title">Monthly Donations</h3>
                            <div class="chart-container">
                                <canvas id="donationsChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card" style="margin-top: 20px;">
                            <h3 class="chart-title">Adoption Applications</h3>
                            <div class="chart-container">
                                <canvas id="adoptionsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>Quick Actions</h2>
                        </div>
                        <div class="quick-actions">
                            <a href="children.php" class="action-btn">
                                <i class="fas fa-plus"></i>
                                <span>Add Child</span>
                            </a>
                            <a href="donations_history.php" class="action-btn">
                                <i class="fas fa-upload"></i>
                                <span>Donations</span>
                            </a>
                            <a href="manage_opportunities.php" class="action-btn">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Opportunities</span>
                            </a>
                            <a href="messages.php" class="action-btn">
                                <i class="fas fa-envelope"></i>
                                <span>Messages</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="dashboard-column">
                    <!-- Additional Statistics -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>Additional Statistics</h2>
                        </div>
                        <div class="stats-grid-secondary">
                            <div class="stat-card secondary">
                                <div class="stat-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $stats['children_in_school']; ?></h3>
                                    <p>Children in School</p>
                                    <span class="trend up">+15% this year</span>
                                </div>
                            </div>

                            <div class="stat-card secondary">
                                <div class="stat-icon">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $stats['medical_checkups']; ?></h3>
                                    <p>Medical Checkups</p>
                                    <span class="trend up">+8% this month</span>
                                </div>
                            </div>

                            <div class="stat-card secondary">
                                <div class="stat-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $stats['meals_served']; ?></h3>
                                    <p>Meals Served</p>
                                    <span class="trend up">+25% this week</span>
                                </div>
                            </div>

                            <div class="stat-card secondary">
                                <div class="stat-icon">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $stats['volunteer_hours']; ?></h3>
                                    <p>Volunteer Hours</p>
                                    <span class="trend up">+20% this month</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Opportunities -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>Recent Opportunities</h2>
                            <a href="manage_opportunities.php" class="view-all">View All</a>
                        </div>
                        <div class="opportunities-list">
                            <?php
                            // Fetch recent opportunities
                            $sql = "SELECT o.* 
                                   FROM opportunities o 
                                   ORDER BY o.created_at DESC LIMIT 5";
                            $result = $conn->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($opportunity = $result->fetch_assoc()) {
                                    echo '<div class="opportunity-item">';
                                    echo '<div class="opportunity-icon">';
                                    echo '<i class="fas fa-handshake"></i>';
                                    echo '</div>';
                                    echo '<div class="opportunity-details">';
                                    echo '<div class="opportunity-title">' . htmlspecialchars($opportunity['title']) . '</div>';
                                    echo '<div class="opportunity-info">Category: ' . htmlspecialchars($opportunity['category']) . '</div>';
                                    echo '<div class="opportunity-time">' . date('M d, Y', strtotime($opportunity['created_at'])) . '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="opportunity-item">No opportunities found</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Chart.js -->
    <script src="../assets/chart.js"></script>
    <script>
        // Toggle sidebar on mobile
        const menuToggle = document.querySelector('.menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
        }

        const donationsCanvas = document.getElementById('donationsChart');
        const adoptionsCanvas = document.getElementById('adoptionsChart');

        if (donationsCanvas) {
            new Chart(donationsCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Monthly Donations',
                        data: [1200, 1900, 1500, 2100, 1800, 2400],
                        borderColor: '#2196f3',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        if (adoptionsCanvas) {
            new Chart(adoptionsCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Adoption Applications',
                        data: [3, 5, 2, 4, 6, 3],
                        backgroundColor: '#4caf50'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        const notifications = document.querySelector('.notifications');
        if (notifications) {
            notifications.addEventListener('click', function() {
                // Add notification dropdown functionality
            });
        }
    </script>
</body>
</html> 