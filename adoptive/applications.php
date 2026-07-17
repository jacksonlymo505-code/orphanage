<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$applications = [];

try {
    $user_email = '';
    $result = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $user_email = $row['email'];
    }

    if ($user_email) {
        $email_esc = $conn->real_escape_string($user_email);
        $result = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $adopter_id = (int)$row['id'];
            $query = "SELECT a.*, CONCAT(c.first_name, ' ', c.last_name) AS child_name FROM adoptions a LEFT JOIN children c ON a.child_id = c.id WHERE a.adopter_id = $adopter_id ORDER BY a.created_at DESC";
            $applications_result = $conn->query($query);
            if ($applications_result) {
                $applications = $applications_result->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
} catch (Exception $e) {
    // ignore errors
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoption Applications</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root { --bg: #f5f7ff; --surface: #ffffff; --text: #111827; --muted: #475569; --primary: #4f46e5; --border: #dbeafe; --shadow: 0 18px 50px rgba(15, 23, 42, 0.08); }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(180deg, #f4f6ff 0%, #eef2ff 100%); color: var(--text); }
        .layout { display: grid; grid-template-columns: 280px 1fr; gap: 24px; min-height: 100vh; padding: 24px; }
        .sidebar { background: var(--surface); border-radius: 28px; box-shadow: var(--shadow); padding: 28px 24px; display: flex; flex-direction: column; gap: 28px; }
        .brand { display: flex; align-items: center; gap: 16px; }
        .brand-icon { width: 52px; height: 52px; border-radius: 18px; display: grid; place-items: center; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; font-size: 20px; }
        .nav { display: grid; gap: 12px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 18px; color: var(--text); text-decoration: none; transition: background 0.2s ease, transform 0.2s ease; }
        .nav-link:hover, .nav-link.active { background: #eef2ff; transform: translateX(2px); }
        .panel { background: var(--surface); border-radius: 28px; box-shadow: var(--shadow); border: 1px solid rgba(79, 70, 229, 0.08); padding: 28px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-header h2 { margin: 0; font-size: 22px; }
        .panel-header a { color: var(--primary); text-decoration: none; font-weight: 700; }
        .application-list { display: grid; gap: 18px; }
        .application-card { padding: 22px; border-radius: 24px; border: 1px solid var(--border); background: #f8fafc; box-shadow: inset 0 0 0 1px rgba(15,23,42,0.03); }
        .application-card h3 { margin: 0 0 10px; font-size: 18px; }
        .application-details { display: grid; gap: 10px; color: var(--muted); font-size: 14px; }
        .status-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; background: #eef2ff; color: #4338ca; font-weight: 700; font-size: 13px; }
        @media (max-width: 1120px) { .layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon"><i class="fas fa-heart"></i></div>
                <div>
                    <h2 style="margin:0;">OMS Adoptive</h2>
                    <p style="margin:6px 0 0; color:var(--muted);">My applications.</p>
                </div>
            </div>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="applications.php" class="nav-link active"><i class="fas fa-file-alt"></i> My Applications</a>
                <a href="activities.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Activities</a>
                <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        <main class="main">
            <section class="panel">
                <div class="panel-header">
                    <h2>My Adoption Applications</h2>
                    <a href="dashboard.php">Back to Dashboard</a>
                </div>
                <div class="application-list">
                    <?php if (!empty($applications)): ?>
                        <?php foreach ($applications as $application): ?>
                            <div class="application-card">
                                <h3><?php echo htmlspecialchars($application['child_name'] ?: 'Child Requested'); ?></h3>
                                <div class="application-details">
                                    <div><strong>Status:</strong> <span class="status-pill"><?php echo htmlspecialchars(ucfirst($application['status'])); ?></span></div>
                                    <div><strong>Date:</strong> <?php echo date('M j, Y', strtotime($application['created_at'])); ?></div>
                                    <div><strong>Child ID:</strong> <?php echo htmlspecialchars($application['child_id']); ?></div>
                                    <div><strong>Notes:</strong> <?php echo htmlspecialchars($application['notes'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="application-card">
                            <h3>No applications found</h3>
                            <p>Please contact the orphanage team for help submitting your adoption application.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
