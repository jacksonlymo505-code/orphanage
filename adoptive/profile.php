<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);

$user = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'organization' => ''
];

$result = $conn->query("SELECT first_name, last_name, email, phone, organization FROM users WHERE id = $user_id LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $user = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoptive Parent Profile</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        :root {
            --bg: #f5f7ff;
            --surface: #ffffff;
            --surface-soft: #eef2ff;
            --text: #111827;
            --muted: #475569;
            --primary: #4f46e5;
            --border: #dbeafe;
            --shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(180deg, #f4f6ff 0%, #eef2ff 100%); color: var(--text); }
        .layout { display: grid; grid-template-columns: 280px 1fr; gap: 24px; min-height: 100vh; padding: 24px; }
        .sidebar { background: var(--surface); border-radius: 28px; box-shadow: var(--shadow); padding: 28px 24px; display: flex; flex-direction: column; gap: 28px; }
        .brand { display: flex; align-items: center; gap: 16px; }
        .brand-icon { width: 52px; height: 52px; border-radius: 18px; display: grid; place-items: center; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; font-size: 20px; }
        .brand-title { margin: 0; font-size: 20px; letter-spacing: 0.02em; }
        .brand-subtitle { margin: 6px 0 0; color: var(--muted); font-size: 14px; line-height: 1.6; }
        .nav { display: grid; gap: 12px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 18px; color: var(--text); text-decoration: none; transition: background 0.2s ease, transform 0.2s ease; }
        .nav-link:hover, .nav-link.active { background: var(--surface-soft); transform: translateX(2px); }
        .nav-link i { width: 24px; color: var(--primary); }
        .sidebar-footer { padding-top: 18px; border-top: 1px solid var(--border); }
        .sidebar-footer p { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.7; }
        .main { display: flex; flex-direction: column; gap: 24px; }
        .hero-card, .panel { background: var(--surface); border-radius: 28px; box-shadow: var(--shadow); border: 1px solid rgba(79, 70, 229, 0.08); }
        .hero-card { padding: 32px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
        .hero-card h1 { margin: 0; font-size: 32px; }
        .hero-card p { margin: 16px 0 0; color: var(--muted); line-height: 1.8; max-width: 700px; }
        .hero-badge { display: inline-flex; align-items: center; gap: 10px; background: var(--surface-soft); color: #4338ca; padding: 14px 18px; border-radius: 999px; font-weight: 700; box-shadow: inset 0 0 0 1px rgba(79, 70, 229, 0.08); }
        .panel { padding: 28px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
        .panel-header h2 { margin: 0; font-size: 22px; }
        .panel-header a { color: var(--primary); text-decoration: none; font-weight: 700; }
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .profile-card { background: var(--surface-soft); border-radius: 24px; padding: 24px; border: 1px solid var(--border); }
        .profile-card h3 { margin-top: 0; font-size: 18px; color: var(--muted); }
        .profile-row { margin: 16px 0; }
        .profile-label { font-size: 14px; color: var(--muted); margin-bottom: 6px; display: block; }
        .profile-value { font-size: 16px; color: var(--text); font-weight: 600; }
        @media (max-width: 1120px) { .layout { grid-template-columns: 1fr; } }
        @media (max-width: 780px) { .profile-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon"><i class="fas fa-heart"></i></div>
                <div>
                    <h2 class="brand-title">OMS Adoptive</h2>
                    <p class="brand-subtitle">Manage your adoption profile.</p>
                </div>
            </div>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="applications.php" class="nav-link"><i class="fas fa-file-alt"></i> My Applications</a>
                <a href="activities.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Activities</a>
                <a href="profile.php" class="nav-link active"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
            <div class="sidebar-footer">
                <p>Update your contact details and stay connected with the adoption team.</p>
            </div>
        </aside>
        <main class="main">
            <section class="hero-card">
                <div>
                    <h1>Profile Settings</h1>
                    <p>Hapa unaweza kuona na kusasisha taarifa yako kama mzazi wa uanahodha. Wasiliana na timu ikiwa unahitaji msaada zaidi.</p>
                </div>
                <div class="hero-badge"><i class="fas fa-user-shield"></i> Adoptive Parent</div>
            </section>
            <section class="panel">
                <div class="panel-header">
                    <h2>Your Information</h2>
                    <a href="dashboard.php">Back to Dashboard</a>
                </div>
                <div class="profile-grid">
                    <div class="profile-card">
                        <h3>Personal Details</h3>
                        <div class="profile-row"><span class="profile-label">First Name</span><span class="profile-value"><?php echo htmlspecialchars($user['first_name']); ?></span></div>
                        <div class="profile-row"><span class="profile-label">Last Name</span><span class="profile-value"><?php echo htmlspecialchars($user['last_name']); ?></span></div>
                        <div class="profile-row"><span class="profile-label">Email</span><span class="profile-value"><?php echo htmlspecialchars($user['email']); ?></span></div>
                    </div>
                    <div class="profile-card">
                        <h3>Contact</h3>
                        <div class="profile-row"><span class="profile-label">Phone</span><span class="profile-value"><?php echo htmlspecialchars($user['phone']); ?></span></div>
                        <div class="profile-row"><span class="profile-label">Organization</span><span class="profile-value"><?php echo htmlspecialchars($user['organization']); ?></span></div>
                        <div class="profile-row"><span class="profile-label">Role</span><span class="profile-value">Adoptive Parent</span></div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
