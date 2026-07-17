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

$stats = [
    'children' => 0,
    'donors' => 0,
    'sponsors' => 0,
    'guardians' => 0,
    'pending_adoptions' => 0,
    'completed_adoptions' => 0,
    'upcoming_activities' => 0,
    'total_donations' => 0,
    'supported_children' => 0,
    'recent_donations' => [],
    'upcoming_opportunities' => [],
    'reports' => [],
    'monthly_donations' => 0,
    'education_funded' => 0
];

try {
    // Total children
    $result = $conn->query("SELECT COUNT(*) as count FROM children");
    if ($result) $stats['children'] = (int)$result->fetch_assoc()['count'];

    // Total donors (type = 'donor')
    $result = $conn->query("SELECT COUNT(*) as count FROM donors WHERE type = 'donor'");
    if ($result) $stats['donors'] = (int)$result->fetch_assoc()['count'];

    // Total sponsors (type = 'adoptive')
    $result = $conn->query("SELECT COUNT(*) as count FROM donors WHERE type = 'adoptive'");
    if ($result) $stats['sponsors'] = (int)$result->fetch_assoc()['count'];

    // Total guardians
    $result = $conn->query("SELECT COUNT(*) as count FROM guardians");
    if ($result) $stats['guardians'] = (int)$result->fetch_assoc()['count'];

    // Adoption stats
    $result = $conn->query("SELECT 
        SUM(status = 'pending') as pending,
        SUM(status = 'completed') as completed
        FROM adoptions");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_adoptions'] = (int)$row['pending'];
        $stats['completed_adoptions'] = (int)$row['completed'];
    }

    // Upcoming activities count (use start_date)
    $result = $conn->query("SELECT COUNT(*) as count FROM activities WHERE start_date > NOW()");
    if ($result) $stats['upcoming_activities'] = (int)$result->fetch_assoc()['count'];

    $user_id = (int)$_SESSION['user_id'];
    $user_email = '';

    $result = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $user_email = $row['email'];
    }

    // Collect all donor IDs that match this user's email (some donations reference donors.id),
    // and also include the users.id because some donations use users.id as donor_id.
    $donor_ids_arr = array();
    if (!empty($user_email)) {
        $email_esc = $conn->real_escape_string($user_email);
        $res = $conn->query("SELECT id FROM donors WHERE email = '$email_esc'");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $donor_ids_arr[] = (int)$r['id'];
            }
        }
    }
    // Always include user_id as fallback (many records use users.id)
    $donor_ids_arr[] = $user_id;
    // Remove duplicates and build comma-separated list for SQL IN()
    $donor_ids_arr = array_values(array_unique($donor_ids_arr));
    $donor_ids = implode(',', $donor_ids_arr);

    // Total donations by donor (completed only)
    $result = $conn->query("SELECT SUM(amount) as total FROM donations WHERE donor_id IN ($donor_ids) AND status = 'completed'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_donations'] = $row['total'] !== null ? (float)$row['total'] : 0;
    }

    // Monthly donations for current month (completed only)
    $result = $conn->query("SELECT SUM(amount) as total FROM donations WHERE donor_id IN ($donor_ids) AND status = 'completed' AND MONTH(donation_date) = MONTH(CURRENT_DATE()) AND YEAR(donation_date) = YEAR(CURRENT_DATE())");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['monthly_donations'] = $row['total'] !== null ? (float)$row['total'] : 0;
    }

    // Supported children count (distinct child_id in donations)
    $result = $conn->query("SELECT COUNT(DISTINCT child_id) as count FROM donations WHERE donor_id IN ($donor_ids)");
    if ($result) $stats['supported_children'] = (int)$result->fetch_assoc()['count'];

    // Education funded sum (notes LIKE '%education%')
    $result = $conn->query("SELECT SUM(amount) as total FROM donations WHERE donor_id IN ($donor_ids) AND LOWER(notes) LIKE '%education%' AND status = 'completed'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['education_funded'] = $row['total'] !== null ? (float)$row['total'] : 0;
    }

    // Recent donations (limit 5) with child name
    $result = $conn->query("SELECT d.*, CONCAT(c.first_name, ' ', c.last_name) as child_name 
                               FROM donations d 
                               LEFT JOIN children c ON d.child_id = c.id 
                               WHERE d.donor_id IN ($donor_ids) 
                               ORDER BY d.created_at DESC LIMIT 5");
    if ($result) {
        $stats['recent_donations'] = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Upcoming opportunities (from opportunities table, active only)
    $result = $conn->query("SELECT *, 
            target_amount AS goal_amount, 
            COALESCE(deadline, DATE_ADD(NOW(), INTERVAL 30 DAY)) AS end_date 
        FROM opportunities WHERE status = 'active' ORDER BY deadline ASC LIMIT 3");
    if ($result) {
        $stats['upcoming_opportunities'] = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Log or handle error if needed
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Orphanage Management System</title>
    
    <link rel="stylesheet" href="../assets/css/all.min.css">
 
</head>
<body>
<?php include 'sidebar.php'; ?>
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
                    <img src="../assets/images/donor-avatar.png" alt="Donor">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1><i class="fas fa-chart-line"></i> Dashboard Overview</h1>
                <div class="date-filter">
                    <select id="dateRange" class="form-select">
                        <option value="today"><i class="fas fa-calendar-day"></i> Today</option>
                        <option value="week"><i class="fas fa-calendar-week"></i> This Week</option>
                        <option value="month" selected><i class="fas fa-calendar-alt"></i> This Month</option>
                        <option value="year"><i class="fas fa-calendar"></i> This Year</option>
                    </select>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding-heart fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3>TSh <?php echo number_format($stats['total_donations'], 2); ?></h3>
                        <p>Total Donations Received</p>
                        <span class="trend up"><i class="fas fa-arrow-up"></i> Based on completed donations</span>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-child fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['supported_children']; ?></h3>
                        <p>Supported Children</p>
                        <span class="trend up"><i class="fas fa-arrow-up"></i> 5% this month</span>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['sponsors']; ?></h3>
                        <p>Total Sponsors</p>
                        <span class="trend up"><i class="fas fa-arrow-up"></i> 8% this month</span>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['guardians']; ?></h3>
                        <p>Total Guardians</p>
                        <span class="trend up"><i class="fas fa-arrow-up"></i> 3% this month</span>
                    </div>
                </div>
            </div>

            <!-- Add Recent Donations Section -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Donations</h2>
                    <a href="donations.php" class="view-all">View All</a>
                </div>
                <div class="donations-list">
                    <?php foreach ($stats['recent_donations'] as $donation): ?>
                        <div class="donation-item">
                            <div class="donation-info">
                                <h4><?php echo htmlspecialchars(isset($donation['child_name']) ? $donation['child_name'] : 'General Donation'); ?></h4>
                                <p>Amount: TSh <?php echo number_format($donation['amount'], 2); ?></p>
                                <p>Purpose: <?php echo htmlspecialchars($donation['notes']); ?></p>
                                <span class="status-badge <?php echo $donation['status']; ?>">
                                    <?php echo ucfirst($donation['status']); ?>
                                </span>
                            </div>
                            <div class="donation-date">
                                <?php echo date('M d, Y', strtotime($donation['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Add Upcoming Opportunities Section -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Upcoming Opportunities</h2>
                    <a href="opportunities.php" class="view-all">View All</a>
                </div>
                <div class="opportunities-list">
                    <?php foreach ($stats['upcoming_opportunities'] as $opportunity): ?>
                        <div class="opportunity-item">
                            <h4><?php echo htmlspecialchars($opportunity['title'] ?? 'Untitled Campaign'); ?></h4>
                            <p><?php echo htmlspecialchars($opportunity['description'] ?? 'No description available'); ?></p>
                            <div class="opportunity-meta">
                                <span>Goal: TSh <?php echo number_format($opportunity['goal_amount'] ?? 0, 2); ?></span>
                                <span>Ends: <?php echo !empty($opportunity['end_date']) ? date('M d, Y', strtotime($opportunity['end_date'])) : 'TBA'; ?></span>
                            </div>
                            <a href="donate.php?campaign=<?php echo htmlspecialchars($opportunity['id']); ?>" class="btn-donate">Donate Now</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- Main Content Grid -->
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="dashboard-column">
                    <!-- Adoption Status -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2><i class="fas fa-heart"></i> Adoption Status</h2>
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

                    <!-- Donation Summary -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-pie"></i> Donation Summary</h2>
                        </div>
                        <div class="donation-summary">
                            <div class="summary-item">
                                <i class="fas fa-hand-holding-heart"></i>
                                <div class="summary-info">
                                    <h4>Total Donations</h4>
                                    <p>TSh <?php echo number_format($stats['total_donations'], 2); ?></p>
                                </div>
                            </div>
                            <div class="summary-item">
                                <i class="fas fa-calendar-check"></i>
                                <div class="summary-info">
                                    <h4>This Month</h4>
                                    <p>TSh <?php echo number_format($stats['monthly_donations'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="dashboard-column">
                    <!-- Donation Stats -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-bar"></i> Donation Statistics</h2>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-hand-holding-heart"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Total Donations</h3>
                                    <p>TSh <?php echo number_format($stats['total_donations'], 2); ?></p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>This Month</h3>
                                    <p>TSh <?php echo number_format($stats['monthly_donations'], 2); ?></p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-child"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Children Helped</h3>
                                    <p><?php echo $stats['supported_children']; ?></p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-info">
                                    <h3>Education Funded</h3>
                                    <p>TSh <?php echo number_format($stats['education_funded'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bars -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2><i class="fas fa-tasks"></i> Donation Progress</h2>
                        </div>
                        <div class="progress-container">
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Education</span>
                                    <span>35%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 35%"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Healthcare</span>
                                    <span>25%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 25%"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Food & Nutrition</span>
                                    <span>20%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 20%"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <div class="progress-label">
                                    <span>Clothing</span>
                                    <span>15%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: 15%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Initialize the chart
        let donationsChart;

        function initChart() {
            const ctx = document.getElementById('donationsChart').getContext('2d');
            donationsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Donations',
                        data: [],
                        borderColor: '#2196f3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `TSh ${context.raw.toFixed(2)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'TSh ' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateChart(period) {
            // Show loading state
            document.querySelector('.chart-container').innerHTML = '<div class="loading">Loading...</div>';
            
            // Fetch data from server
            fetch(`get_donation_stats.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    // Remove loading state
                    document.querySelector('.chart-container').innerHTML = '<canvas id="donationsChart"></canvas>';
                    
                    // Initialize chart
                    initChart();
                    
                    // Update chart data
                    donationsChart.data.labels = data.labels;
                    donationsChart.data.datasets[0].data = data.values;
                    donationsChart.update();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.querySelector('.chart-container').innerHTML = '<div class="error">Error loading chart data</div>';
                });
        }

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('donationsChart').getContext('2d');
            
            // Fetch donation data
            fetch('get_donation_stats.php')
                .then(response => response.json())
                .then(data => {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Donations',
                                data: data.values,
                                borderColor: '#2196f3',
                                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `TSh ${context.raw.toFixed(2)}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'TSh ' + value;
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
        });

        // Notification dropdown
        document.querySelector('.notifications').addEventListener('click', function() {
            // Add notification dropdown functionality
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Donation Trends Chart
            const trendsCtx = document.getElementById('donationTrendsChart').getContext('2d');
            new Chart(trendsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Monthly Donations',
                        data: [1200, 1900, 1500, 2100, 1800, 2400],
                        backgroundColor: 'rgba(33, 150, 243, 0.7)',
                        borderColor: 'rgba(33, 150, 243, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `TSh ${context.raw.toFixed(2)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'TSh ' + value;
                                }
                            }
                        }
                    }
                }
            });

            // Donation Distribution Chart
            const distributionCtx = document.getElementById('donationDistributionChart').getContext('2d');
            new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Education', 'Healthcare', 'Food', 'Clothing', 'Other'],
                    datasets: [{
                        data: [35, 25, 20, 15, 5],
                        backgroundColor: [
                            'rgba(33, 150, 243, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(241, 196, 15, 0.7)',
                            'rgba(231, 76, 60, 0.7)',
                            'rgba(155, 89, 182, 0.7)'
                        ],
                        borderColor: [
                            'rgba(33, 150, 243, 1)',
                            'rgba(46, 204, 113, 1)',
                            'rgba(241, 196, 15, 1)',
                            'rgba(231, 76, 60, 1)',
                            'rgba(155, 89, 182, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw}%`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 