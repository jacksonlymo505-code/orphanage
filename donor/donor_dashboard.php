<?php
session_start();
include '../config/database.php';

// Check if user is logged in as donor
if (!isset($_SESSION['donor_id'])) {
    // Try to log in with email if POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];
        
        $donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE email='$email' AND status='approved'"));
        
        if ($donor && password_verify($password, $donor['password'] ?? '')) {
            $_SESSION['donor_id'] = $donor['id'];
            header('Location: donor_dashboard.php');
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        header('Location: ../login.php');
        exit();
    }
}

$donor_id = $_SESSION['donor_id'];

// Get donor info
$donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE id='$donor_id' AND status='approved'"));

if (!$donor) {
    header('Location: ../login.php');
    exit();
}

// Get contributions
$contributions = mysqli_query($conn, "SELECT * FROM contributions WHERE donor_id='$donor_id' ORDER BY contribution_date DESC LIMIT 10");

// Get total contributions
$total_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM contributions WHERE donor_id='$donor_id' AND contribution_type='financial'"));
$total_contributed = $total_result['total'] ?? 0;

// Get contribution count
$count_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM contributions WHERE donor_id='$donor_id'"));
$contribution_count = $count_result['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root{--primary:#0f4c81;--secondary:#1e88e5;--dark:#0f172a;--light:#f8fafc;--text:#475569;}
        *{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI, sans-serif;}
        body{background:var(--light);color:var(--dark);}
        .container{width:95%;max-width:1200px;margin:auto;}
        header{background:#fff;padding:20px 0;box-shadow:0 2px 10px rgba(0,0,0,.1);}
        .header-content{display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:22px;font-weight:700;color:var(--primary);}
        nav{display:flex;gap:20px;align-items:center;}
        nav a{color:var(--text);text-decoration:none;font-weight:600;}
        nav a:hover{color:var(--secondary);}
        .main{padding:40px 0;}
        .welcome{background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;padding:40px;border-radius:10px;margin-bottom:30px;}
        .welcome h1{margin-bottom:10px;font-size:28px;}
        .welcome p{opacity:.95;}
        .stats{display:grid;grid-template-columns:repeat(3, 1fr);gap:20px;margin-bottom:30px;}
        .stat-card{background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);text-align:center;border-top:4px solid var(--secondary);}
        .stat-card .value{font-size:32px;font-weight:700;color:var(--secondary);margin:10px 0;}
        .stat-card .label{color:var(--text);font-size:14px;}
        .stat-card i{font-size:28px;color:var(--secondary);}
        .card{background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px;}
        .card h2{color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:20px;}
        table{width:100%;border-collapse:collapse;}
        th{background:#f5f5f5;padding:12px;text-align:left;font-weight:600;border-bottom:2px solid var(--secondary);}
        td{padding:12px;border-bottom:1px solid #eee;}
        tr:hover{background:#f9f9f9;}
        .btn{display:inline-block;padding:10px 20px;background:var(--secondary);color:#fff;text-decoration:none;border-radius:6px;border:none;cursor:pointer;font-weight:600;}
        .btn:hover{background:#1565c0;}
        .empty{text-align:center;padding:40px;color:var(--text);}
        .action-btn{display:inline-block;padding:8px 16px;background:var(--primary);color:#fff;text-decoration:none;border-radius:6px;font-size:13px;margin-right:5px;}
        footer{background:var(--dark);color:#cbd5e1;padding:30px 0;text-align:center;margin-top:50px;}
        @media(max-width:768px){.stats{grid-template-columns:1fr;}.header-content{flex-direction:column;align-items:flex-start;gap:15px;}}
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo"><i class="fas fa-hand-holding-heart"></i> Donor Portal</div>
            <nav>
                <span style="color:var(--secondary);font-weight:700;">Welcome, <?php echo htmlspecialchars($donor['full_name']); ?></span>
                <a href="update_profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </div>
</header>

<section class="main">
    <div class="container">
        <div class="welcome">
            <h1>Welcome to Your Donor Dashboard</h1>
            <p>Track your contributions and impact in the lives of the children we serve.</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-dollar-sign"></i>
                <div class="value">$<?php echo number_format($total_contributed, 2); ?></div>
                <div class="label">Total Contributed</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-gift"></i>
                <div class="value"><?php echo $contribution_count; ?></div>
                <div class="label">Total Contributions</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <div class="value"><?php echo ucfirst(str_replace('_', ' ', $donor['support_type'])); ?></div>
                <div class="label">Support Type</div>
            </div>
        </div>

        <div class="card">
            <h2><i class="fas fa-history"></i> Recent Contributions</h2>
            <?php if (mysqli_num_rows($contributions) > 0): ?>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                    <?php while ($contrib = mysqli_fetch_assoc($contributions)): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($contrib['contribution_date'])); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $contrib['contribution_type'])); ?></td>
                        <td><?php echo $contrib['contribution_type'] === 'financial' ? '$' . number_format($contrib['amount'], 2) : htmlspecialchars($contrib['amount']); ?></td>
                        <td><?php echo htmlspecialchars($contrib['description'] ?? '-'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div class="empty">
                    <i class="fas fa-inbox"></i>
                    <p>No contributions yet. Make your first contribution today!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2><i class="fas fa-info-circle"></i> Your Profile Information</h2>
            <table>
                <tr><th>Field</th><th>Information</th></tr>
                <tr><td><strong>Full Name</strong></td><td><?php echo htmlspecialchars($donor['full_name']); ?></td></tr>
                <tr><td><strong>Email</strong></td><td><?php echo htmlspecialchars($donor['email']); ?></td></tr>
                <tr><td><strong>Phone</strong></td><td><?php echo htmlspecialchars($donor['phone']); ?></td></tr>
                <?php if ($donor['organization_name']): ?>
                <tr><td><strong>Organization</strong></td><td><?php echo htmlspecialchars($donor['organization_name']); ?></td></tr>
                <?php endif; ?>
                <tr><td><strong>Preferred Contact</strong></td><td><?php echo ucfirst($donor['preferred_contact']); ?></td></tr>
                <tr><td><strong>Member Since</strong></td><td><?php echo date('M d, Y', strtotime($donor['date_approved'])); ?></td></tr>
            </table>
            <a href="update_profile.php" class="action-btn"><i class="fas fa-edit"></i> Update Profile</a>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <p>© 2026 Orphanage Management System. All Rights Reserved.</p>
    </div>
</footer>
</body>
</html>
