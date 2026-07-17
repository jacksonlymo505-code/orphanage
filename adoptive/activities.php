<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_name = htmlspecialchars($_SESSION['user_name']);
$activities = [];
$result = $conn->query("SELECT title, start_date, location, status, description FROM activities WHERE start_date > NOW() ORDER BY start_date ASC LIMIT 10");
if ($result) {
    $activities = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Activities</title>
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
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 18px; color: var(--text); text-decoration: none; transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; }
        .nav-link:hover, .nav-link.active { background: #eef2ff; transform: translateX(2px); box-shadow: inset 0 0 0 1px rgba(79,70,229,0.12); }
        .panel { background: var(--surface); border-radius: 32px; box-shadow: var(--shadow); border: 1px solid rgba(79, 70, 229, 0.12); padding: 32px; }
        .panel-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 18px; }
        .panel-header h2 { margin: 0; font-size: 28px; line-height: 1.1; }
        .panel-header a { color: var(--primary); text-decoration: none; font-weight: 700; font-size: 14px; }
        .eyebrow { margin: 0 0 10px; text-transform: uppercase; letter-spacing: 0.18em; font-size: 12px; color: #4338ca; font-weight: 700; }
        .panel-subtitle { margin: 0; color: #475569; max-width: 720px; font-size: 15px; line-height: 1.75; }
        .summary { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
        .summary-card { background: #eef2ff; border-radius: 22px; border: 1px solid rgba(79,70,229,0.12); padding: 22px 24px; min-width: 220px; flex: 1 1 220px; }
        .summary-card strong { display: block; font-size: 16px; color: #111827; margin-bottom: 6px; }
        .summary-card span { color: #475569; font-size: 14px; }
        .activity-list { display: grid; grid-template-columns: repeat( auto-fit, minmax(320px, 1fr) ); gap: 22px; }
        .activity-card { position: relative; padding: 28px; border-radius: 28px; border: 1px solid rgba(15,23,42,0.06); background: #ffffff; box-shadow: 0 22px 48px rgba(15,23,42,0.08); display: flex; flex-direction: column; gap: 20px; }
        .activity-card::before { content: ''; position: absolute; left: 28px; top: 28px; width: 68px; height: 4px; border-radius: 999px; background: linear-gradient(90deg, #4f46e5, #818cf8); }
        .activity-card h3 { margin: 0; font-size: 20px; line-height: 1.2; }
        .activity-card p { margin: 0; color: #475569; font-size: 15px; line-height: 1.75; }
        .activity-meta { display: grid; gap: 12px; color: var(--muted); font-size: 14px; }
        .activity-meta div { display: flex; align-items: center; gap: 10px; }
        .activity-meta i { color: #6366f1; min-width: 18px; text-align: center; }
        .status-pill { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 999px; font-weight: 700; font-size: 13px; letter-spacing: 0.01em; }
        .status-pill.status-upcoming { background: #eef2ff; color: #4338ca; }
        .status-pill.status-planned { background: #def7ec; color: #0f766e; }
        .status-pill.status-active { background: #e0f2fe; color: #0369a1; }
        .status-pill.status-completed { background: #f3f4f6; color: #334155; }
        .status-pill.status-cancelled { background: #fee2e2; color: #b91c1c; }
        .empty-state { padding: 36px; border-radius: 28px; background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%); border: 1px dashed rgba(79,70,229,0.22); text-align: center; }
        .empty-state h3 { margin: 0 0 12px; font-size: 22px; }
        .empty-state p { margin: 0; color: #475569; }
        @media (max-width: 1120px) { .layout { grid-template-columns: 1fr; } }
        @media (max-width: 720px) { .panel-header { flex-direction: column; align-items: stretch; } .summary { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon"><i class="fas fa-heart"></i></div>
                <div>
                    <h2 style="margin:0;">OMS Adoptive</h2>
                    <p style="margin:6px 0 0; color:var(--muted);">Upcoming activities.</p>
                </div>
            </div>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="applications.php" class="nav-link"><i class="fas fa-file-alt"></i> My Applications</a>
                <a href="activities.php" class="nav-link active"><i class="fas fa-calendar-alt"></i> Activities</a>
                <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        <main class="main">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Adoptive Activities</p>
                        <h2>Upcoming Activities</h2>
                        <p class="panel-subtitle">Stay updated with upcoming adoption-related events, orientations, and community activities designed for adoptive families.</p>
                    </div>
                    <a href="dashboard.php">Back to Dashboard</a>
                </div>
                <div class="summary">
                    <div class="summary-card">
                        <strong>Next event</strong>
                        <span>Added automatically from scheduled activities.</span>
                    </div>
                    <div class="summary-card">
                        <strong>Activity feed</strong>
                        <span>Shows only events with future start dates for your convenience.</span>
                    </div>
                </div>
                <div class="activity-list">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-card">
                                <h3><?php echo htmlspecialchars($activity['title']); ?></h3>
                                <div class="activity-meta">
                                    <div><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($activity['start_date'])); ?></div>
                                    <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($activity['location']); ?></div>
                                    <div><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($activity['description'] ?? 'No description provided.'); ?></div>
                                    <div><strong>Status:</strong> <span class="status-pill status-<?php echo strtolower(htmlspecialchars($activity['status'])); ?>"><?php echo htmlspecialchars(ucfirst($activity['status'])); ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-card empty-state">
                            <h3>No upcoming activities found</h3>
                            <p>No new activities are available right now. Please check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
