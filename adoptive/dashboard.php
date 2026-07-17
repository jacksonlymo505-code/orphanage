<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$stats = [
    'my_applications' => 0,
    'pending_requests' => 0,
    'completed_adoptions' => 0,
    'upcoming_activities' => 0,
    'active_children' => 0,
    'recent_activities' => []
];

try {
    $user_email = '';
    $result = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $user_email = $row['email'];
    }

    if ($user_email) {
        $email_esc = $conn->real_escape_string($user_email);
        $result = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' LIMIT 1");
        $adopter_id = null;
        if ($result && $row = $result->fetch_assoc()) {
            $adopter_id = (int)$row['id'];
        }

        if ($adopter_id) {
            $result = $conn->query(
                "SELECT COUNT(*) AS total, 
                        SUM(status = 'pending') AS pending, 
                        SUM(status = 'completed') AS completed 
                 FROM adoptions 
                 WHERE adopter_id = $adopter_id"
            );
            if ($result && $row = $result->fetch_assoc()) {
                $stats['my_applications'] = (int)$row['total'];
                $stats['pending_requests'] = (int)$row['pending'];
                $stats['completed_adoptions'] = (int)$row['completed'];
            }

            $result = $conn->query("SELECT COUNT(*) AS count FROM adoptions WHERE adopter_id = $adopter_id AND status = 'completed'");
            if ($result) {
                $stats['active_children'] = (int)$result->fetch_assoc()['count'];
            }
        }
    }

    $result = $conn->query("SELECT COUNT(*) AS count FROM activities WHERE start_date > NOW()");
    if ($result) {
        $stats['upcoming_activities'] = (int)$result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT title, start_date, location, status FROM activities WHERE start_date > NOW() ORDER BY start_date ASC LIMIT 4");
    if ($result) {
        $stats['recent_activities'] = $result->fetch_all(MYSQLI_ASSOC);
    }

    $unread_messages = 0;
    $new_approvals = 0;
    $notification_count = 0;
    if (!empty($adopter_id)) {
        $result = $conn->query("SELECT COUNT(*) AS cnt FROM messages WHERE recipient_id = $adopter_id AND read_status = 0");
        if ($result && $row = $result->fetch_assoc()) {
            $unread_messages = (int)$row['cnt'];
        }
        $result = $conn->query("SELECT COUNT(*) AS cnt FROM adoptions WHERE adopter_id = $adopter_id AND status = 'approved'");
        if ($result && $row = $result->fetch_assoc()) {
            $new_approvals = (int)$row['cnt'];
        }
        $notification_count = $unread_messages + $new_approvals;
    }
} catch (Exception $e) {
    // ignore display errors for now
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoptive Parent Dashboard</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        :root {
            --bg: #dbeafe;
            --surface: #ffffff;
            --surface-soft: #eff6ff;
            --text: #0f172a;
            --muted: #475569;
            --primary: #1e3a8a;
            --primary-strong: #122169;
            --primary-soft: #c7d2fe;
            --border: #a5b4fc;
            --shadow: 0 20px 50px rgba(18, 33, 105, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: var(--text);
        }

        .layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 18px;
            min-height: 100vh;
            padding: 18px;
        }

        .sidebar {
            background: linear-gradient(180deg, #122169 0%, #1e3a8a 100%);
            color: #ffffff;
            border-radius: 26px;
            box-shadow: var(--shadow);
            padding: 22px 18px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            min-height: calc(100vh - 36px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #1e40af, #1e3a8a);
            color: #ffffff;
            font-size: 20px;
        }

        .brand-title {
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.02em;
            color: #f8fafc;
        }

        .brand-subtitle {
            margin: 6px 0 0;
            color: #dbeafe;
            font-size: 14px;
            line-height: 1.6;
        }

        .sidebar-menu {
            display: grid;
            gap: 12px;
        }

        .menu-item {
            padding: 14px 15px;
            display: flex;
            align-items: center;
            color: #f8fafc;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 12px;
            gap: 14px;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.12);
        }

        .menu-item.active {
            background: rgba(59, 130, 246, 0.16);
            color: #ffffff;
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            color: #bfdbfe;
        }

        .sidebar-footer {
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.12);
        }

        .sidebar-footer p {
            margin: 0;
            color: #c7d2fe;
            font-size: 14px;
            line-height: 1.7;
        }

        .main {
            display: flex;
            flex-direction: column;
            gap: 18px;
            background: var(--bg);
            padding: 20px;
            border-radius: 28px;
        }

        .top-bar {
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(59, 130, 246, 0.12);
            padding: 14px 18px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }

        .notifications {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
            color: var(--muted);
            font-size: 18px;
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: #ef4444;
            color: #ffffff;
            width: 18px;
            height: 18px;
            border-radius: 999px;
            font-size: 11px;
            display: grid;
            place-items: center;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text);
            font-size: 14px;
        }

        .profile-initials {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #dbeafe;
            color: #1d4ed8;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 16px;
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
            line-height: 1.2;
        }

        .profile-details strong {
            color: var(--text);
            font-size: 14px;
        }

        .profile-details span {
            color: var(--muted);
            font-size: 12px;
        }

        .dashboard-header,
        .panel,
        .stats-grid .stat-card {
            background: var(--surface);
            border-radius: 24px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(59, 130, 246, 0.12);
        }

        .dashboard-header {
            padding: 18px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }

        .dashboard-header .header-copy {
            max-width: 680px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 26px;
            color: var(--primary-strong);
            line-height: 1.1;
        }

        .dashboard-header p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.5;
            font-size: 13px;
            max-width: 560px;
        }


        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 14px;
        }

        .stat-card {
            padding: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: grid;
            gap: 12px;
            border: 1px solid rgba(59, 130, 246, 0.10);
            background: var(--surface);
        }

        .stat-card .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #ffffff;
            background: linear-gradient(135deg, #4338ca, #2563eb);
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.14);
        }

        .stat-card h3 {
            margin: 0;
            color: var(--primary-strong);
            font-size: 14px;
            font-weight: 700;
        }

        .stat-card .stat-value {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
        }

        .stat-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 13px;
        }

        .action-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 14px;
        }

        .action-card {
            background: var(--surface);
            border-radius: 22px;
            padding: 16px;
            border: 1px solid rgba(148, 163, 184, 0.14);
            display: grid;
            gap: 10px;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(18, 33, 105, 0.08);
        }

        .action-card .action-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: #ffffff;
        }

        .action-card h3 {
            margin: 0;
            font-size: 15px;
            color: var(--primary-strong);
        }

        .action-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
            font-size: 13px;
        }

        .action-card a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .stats-grid,
        .action-row {
            margin-bottom: 10px;
        }

        .date-filter select {
            border: none;
            background: transparent;
            font-size: 14px;
            color: var(--text);
            outline: none;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #c7d2fe;
            color: var(--primary-strong);
            padding: 14px 18px;
            border-radius: 999px;
            font-weight: 700;
            box-shadow: inset 0 0 0 1px rgba(18, 33, 105, 0.15);
        }


        .profile-details strong,
        .hero-badge,
        .notification-icon {
            color: var(--primary-strong);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .stat-card {
            padding: 28px;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
            border-radius: 28px;
            display: grid;
            gap: 18px;
            border: 1px solid rgba(59, 130, 246, 0.12);
            background: var(--surface);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px rgba(18, 33, 105, 0.08);
        }

        .stat-card .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: #ffffff;
            background: linear-gradient(135deg, #4338ca, #2563eb);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.14);
        }

        .stat-card h3 {
            margin: 0;
            color: var(--primary-strong);
            font-size: 15px;
            font-weight: 700;
        }

        .stat-card .stat-value {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            color: var(--text);
        }

        .stat-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.75;
            font-size: 14px;
        }

        .grid-two,
        .section-grid {
            display: grid;
            grid-template-columns: 1.4fr 0.86fr;
            gap: 20px;
        }

        .panel {
            padding: 22px;
            background: var(--surface);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 28px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 20px;
            color: var(--primary-strong);
        }

        .panel-header a {
            color: var(--primary-strong);
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .activity-list {
            display: grid;
            gap: 14px;
        }

        .activity-item {
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: var(--surface-soft);
        }

        .activity-item h4 {
            margin: 0 0 10px;
            font-size: 16px;
            color: var(--primary-strong);
        }

        .activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            color: var(--muted);
            font-size: 13px;
        }

        .activity-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-pill {
            padding: 7px 12px;
            border-radius: 999px;
            background: #eef2ff;
            color: #1e40af;
            font-size: 12px;
            font-weight: 700;
        }

        .action-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .action-card {
            background: var(--surface);
            border-radius: 24px;
            padding: 18px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            display: grid;
            gap: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .action-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(18, 33, 105, 0.06);
        }

        .action-card .action-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: #ffffff;
        }

        .action-card.profile .action-icon { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .action-card.child .action-icon { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .action-card.documents .action-icon { background: linear-gradient(135deg, #ea580c, #c2410c); }
        .action-card.messages .action-icon { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }

        .action-card h3 {
            margin: 0;
            font-size: 16px;
            color: var(--primary-strong);
        }

        .action-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 13px;
        }

        .action-card a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .section-grid {
            margin-top: 0;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .status-item {
            padding: 18px;
            border-radius: 22px;
            background: #f8fbff;
            border: 1px solid rgba(148, 163, 184, 0.16);
            display: grid;
            gap: 8px;
        }

        .status-item h3 {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .status-item strong {
            font-size: 26px;
            color: var(--text);
            line-height: 1;
        }

        .panel.compact {
            padding: 22px;
        }

        @media (max-width: 1120px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .stats-grid,
            .action-row {
                grid-template-columns: 1fr;
            }

            .action-card.child {
                grid-column: span 1;
            }
        }

        @media (max-width: 780px) {
            .dashboard-header {
                align-items: flex-start;
            }
        }

        @media (max-width: 560px) {
            .stat-card,
            .action-card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon"><i class="fas fa-heart"></i></div>
                <div>
                    <h2 class="brand-title">OMS Adoptive</h2>
                    <p class="brand-subtitle">Your adoption journey in one place.</p>
                </div>
            </div>

            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="register_child.php" class="menu-item"><i class="fas fa-child"></i> Register Child</a>
                <a href="applications.php" class="menu-item"><i class="fas fa-file-alt"></i> My Applications</a>
                <a href="activities.php" class="menu-item"><i class="fas fa-calendar-alt"></i> Activities</a>
                <a href="profile.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>

            <div class="sidebar-footer">
                <p>Monitor application status and access center support.</p>
            </div>
        </aside>

        <main class="main">
            <div class="top-bar">
                <div class="notifications">
                    <a href="messages.php" class="btn-primary" style="padding:8px 12px;border-radius:10px;position:relative;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-envelope"></i>
                        <?php if (!empty($notification_count)): ?>
                            <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if (!empty($notification_count)): ?>
                            <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-profile">
                        <div class="profile-initials"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                        <div class="profile-details">
                            <strong><?php echo htmlspecialchars($user_name); ?></strong>
                            <span>Adoptive Parent</span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="dashboard-header">
                <div class="header-copy">
                    <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>.</h1>
                    <p>Start by registering a child, then track your adoption status and admin messages.</p>
                </div>
            </section>

            <section class="action-row">
                <div class="action-card child">
                    <div class="action-icon"><i class="fas fa-child"></i></div>
                    <h3>Register Child</h3>
                    <p>Start a new child profile.</p>
                    <a href="register_child.php">Register</a>
                </div>
                <div class="action-card profile">
                    <div class="action-icon"><i class="fas fa-user-edit"></i></div>
                    <h3>Profile</h3>
                    <p>Update your account details.</p>
                    <a href="profile.php">Edit</a>
                </div>
                <div class="action-card documents">
                    <div class="action-icon"><i class="fas fa-file-upload"></i></div>
                    <h3>Documents</h3>
                    <p>Submit supporting paperwork.</p>
                    <a href="upload_documents.php">Upload</a>
                </div>
                <div class="action-card activities">
                    <div class="action-icon"><i class="fas fa-calendar-alt"></i></div>
                    <h3>Activities</h3>
                    <p>View upcoming events and tasks.</p>
                    <a href="activities.php">Open</a>
                </div>
            </section>

            <section class="stats-grid">
                <div class="stat-card applications">
                    <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                    <h3>Submitted</h3>
                        <p id="stat-applications" class="stat-value"><?php echo $stats['my_applications']; ?></p>
                    <p>Applications submitted</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <h3>Pending</h3>
                        <p id="stat-pending" class="stat-value"><?php echo $stats['pending_requests']; ?></p>
                    <p>Under review</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Completed</h3>
                        <p id="stat-completed" class="stat-value"><?php echo $stats['completed_adoptions']; ?></p>
                    <p>Approved</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-child"></i></div>
                    <h3>Linked Children</h3>
                        <p id="stat-children" class="stat-value"><?php echo $stats['active_children']; ?></p>
                    <p>Current cases</p>
                </div>
            </section>
        </main>
    </div>

</body>
</html>

<script>
function refreshCounts(){
    fetch('get_dashboard_counts.php', {credentials: 'same-origin'})
        .then(r=>r.json())
        .then(d=>{
            if (d && !d.error) {
                document.getElementById('stat-applications').textContent = d.my_applications || 0;
                document.getElementById('stat-pending').textContent = d.pending_requests || 0;
                document.getElementById('stat-completed').textContent = d.completed_adoptions || 0;
                document.getElementById('stat-children').textContent = d.active_children || 0;
            }
        }).catch(()=>{});
}

document.addEventListener('DOMContentLoaded', function(){
    refreshCounts();
    setInterval(refreshCounts, 15000);
});
</script>
