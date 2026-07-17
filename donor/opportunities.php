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

// Get upcoming opportunities
$query = "SELECT o.* 
          FROM opportunities o 
          WHERE o.status = 'active' 
          ORDER BY o.deadline ASC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();
$opportunities = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opportunities - Donor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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

        /* Opportunity Card Styles */
        .opportunities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .opportunity-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .opportunity-card:hover {
            transform: translateY(-5px);
        }

        .opportunity-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .opportunity-content {
            padding: 1.5rem;
        }

        .opportunity-header {
            margin-bottom: 1rem;
        }

        .opportunity-title {
            font-size: 1.25rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .opportunity-orphanage {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .opportunity-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .opportunity-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--success-color);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
        }
        
        .current-amount {
            color: var(--secondary-color);
            font-weight: 700;
        }
        
        .target-amount {
            color: #666;
        }

        .opportunity-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-donate {
            flex: 1;
            padding: 0.75rem;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-donate:hover {
            background: #2980b9;
        }

        .btn-pledge {
            flex: 1;
            padding: 0.75rem;
            background: white;
            color: var(--secondary-color);
            border: 1px solid var(--secondary-color);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-pledge:hover {
            background: #f8f9fa;
        }

        .deadline-badge {
            background: #fff3cd;
            color: #856404;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .opportunities-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 250px;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e1e1e1;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-box {
            position: relative;
            min-width: 300px;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .filter-box select {
            padding: 0.75rem 2rem 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
        }

        .filter-box select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .empty-state i {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .header-actions {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
                min-width: unset;
            }

            .filter-box select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-handshake"></i> Upcoming Opportunities</h1>
            <div class="header-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchOpportunities" placeholder="Search opportunities...">
                </div>
                <div class="filter-box">
                    <select id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <option value="education">Education</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="food">Food & Nutrition</option>
                        <option value="clothing">Clothing</option>
                        <option value="shelter">Shelter</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="opportunities-container">
            <?php if (empty($opportunities)): ?>
                <div class="empty-state">
                    <i class="fas fa-handshake fa-3x"></i>
                    <h3>No Active Opportunities</h3>
                    <p>Check back later for new opportunities to make a difference.</p>
                </div>
            <?php else: ?>
                <div class="opportunities-grid">
                    <?php foreach ($opportunities as $opportunity): ?>
                        <div class="opportunity-card">
                            <img src="<?php echo htmlspecialchars($opportunity['image_url']); ?>" alt="Opportunity Image" class="opportunity-image">
                            <div class="opportunity-content">
                                <div class="opportunity-header">
                                    <h3 class="opportunity-title"><?php echo htmlspecialchars($opportunity['title']); ?></h3>
                                </div>
                                <p class="opportunity-description">
                                    <?php echo htmlspecialchars($opportunity['description']); ?>
                                </p>
                                <div class="opportunity-meta">
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo ($opportunity['current_amount'] / $opportunity['target_amount']) * 100; ?>%"></div>
                                        </div>
                                        <div class="progress-stats">
                                            <div style="text-align: center; font-weight: 600; margin: 8px 0;">
                                                <span class="current-amount">TSh <?php echo number_format($opportunity['current_amount'], 2); ?></span>
                                                <span class="target-amount"> of TSh <?php echo number_format($opportunity['target_amount'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="deadline-badge">
                                        <i class="fas fa-clock"></i>
                                        <?php 
                                        $days_left = ceil((strtotime($opportunity['deadline']) - time()) / (60 * 60 * 24));
                                        echo $days_left . ' days left';
                                        ?>
                                    </div>
                                </div>
                                <div class="opportunity-actions">
                                    <a href="donate.php?opportunity_id=<?php echo $opportunity['id']; ?>" class="btn-donate">
                                        <i class="fas fa-hand-holding-heart"></i>
                                        Donate Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchOpportunities').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.opportunity-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function(e) {
            const category = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.opportunity-card');
            
            cards.forEach(card => {
                const cardCategory = card.dataset.category;
                card.style.display = !category || cardCategory === category ? '' : 'none';
            });
        });
    </script>
</body>
</html> 