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
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-select {
            padding: 0.5rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f8f9fa;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-select:hover {
            border-color: var(--secondary-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card.primary .stat-icon { 
            background: #e3f2fd; 
            color: #2196f3; 
        }

        .stat-card.success .stat-icon { 
            background: #e8f5e9; 
            color: #4caf50; 
        }

        .stat-card.warning .stat-icon { 
            background: #fff3e0; 
            color: #ff9800; 
        }

        .stat-card.info .stat-icon { 
            background: #f3e5f5; 
            color: #9c27b0; 
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-info p {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .trend {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .trend.up {
            background: #e8f5e9;
            color: #4caf50;
        }

        .trend.down {
            background: #ffebee;
            color: #f44336;
        }

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
            position: relative;
            height: 300px;
            margin: 20px 0;
        }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #666;
        }

        .error {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #f44336;
        }

        #chartPeriod {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
        }

        #chartPeriod:focus {
            outline: none;
            border-color: var(--primary-color);
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

        /* Add to your existing styles */
        .donations-list, .opportunities-list, .reports-list {
            margin-top: 15px;
        }

        .donation-item, .opportunity-item, .report-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-badge.successful { background: #e8f5e9; color: #4caf50; }
        .status-badge.pending { background: #fff3e0; color: #ff9800; }
        .status-badge.failed { background: #ffebee; color: #f44336; }

        .btn-donate {
            background: var(--secondary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-download {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .opportunity-meta {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: #666;
            margin: 10px 0;
        }

        .donation-summary, .impact-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 15px;
        }

        .summary-item, .impact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .summary-item:hover, .impact-item:hover {
            transform: translateY(-5px);
        }

        .summary-item i, .impact-item i {
            font-size: 24px;
            color: var(--primary-color);
            background: rgba(33, 150, 243, 0.1);
            padding: 15px;
            border-radius: 8px;
        }

        .summary-info, .impact-info {
            flex: 1;
        }

        .summary-info h4, .impact-info h4 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-info p, .impact-info p {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .card-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: var(--primary-color);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 15px;
        }

        .stat-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(33, 150, 243, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .stat-info h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        /* Progress Bars */
        .progress-container {
            padding: 20px;
        }

        .progress-item {
            margin-bottom: 20px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: #666;
        }

        .progress-bar {
            height: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: var(--primary-color);
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        /* Card Styles */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 {
            font-size: 1.2rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-item {
                padding: 15px;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
            }
            
            .stat-icon i {
                font-size: 20px;
            }
            
            .stat-info p {
                font-size: 18px;
            }
        }
    </style> 
 
 
 <!-- Sidebar -->
 <div class="sidebar">
        <div class="sidebar-header">
            <h2>Donor Portal</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="donations.php" class="menu-item">
                <i class="fas fa-hand-holding-heart"></i> My Donations
            </a>
            <a href="opportunities.php" class="menu-item">
                <i class="fas fa-handshake"></i> My Opportunities
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="profile.php" class="menu-item">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>