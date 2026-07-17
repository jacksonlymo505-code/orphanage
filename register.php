<?php
session_start();

$errorMessage = $_SESSION['error'] ?? 'Self-registration is disabled. Please contact the system administrator to request an account.';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Disabled - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body { margin:0; font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif; background: linear-gradient(180deg,#eef5ff 0%,#f8fafc 100%); color:#0f172a; }
        .site-header { display:flex; align-items:center; justify-content:space-between; padding:18px 28px; background:rgba(255,255,255,0.98); border-bottom:1px solid rgba(15,23,42,0.04); }
        .brand { font-weight:700; display:flex; gap:8px; align-items:center; }
        .card { width:100%; max-width:640px; margin:48px auto; background:#fff; border-radius:12px; box-shadow:0 20px 50px rgba(15,23,42,0.06); overflow:hidden; }
        .card-header { padding:28px; border-bottom:1px solid #eef2ff; text-align:center; }
        .card-header .icon { width:64px; height:64px; border-radius:50%; background:#eef2ff; display:inline-grid; place-items:center; font-size:28px; color:#4338ca; }
        .card-body { padding:28px; }
        .alert { background:#fff5f5; border:1px solid #fed7d7; color:#c53030; border-radius:8px; padding:14px; margin-bottom:20px; }
        .button { display:inline-flex; align-items:center; justify-content:center; padding:12px 18px; border-radius:10px; background:linear-gradient(90deg,#4f46e5,#4338ca); color:#fff; font-weight:700; text-decoration:none; }
        .card-footer { padding:18px 28px; text-align:center; color:#6b7280; }
    </style>
    </head>
<body>
    <header class="site-header">
        <div class="brand"><i class="fas fa-home"></i> <span>Orphanage Management System</span></div>
        <nav class="site-nav">
            <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
            <a href="index.php">Home</a>
            <a href="index.php#features">Features</a>
            <?php endif; ?>
        </nav>
        <div class="site-actions"><a class="button" href="login.php">Login</a></div>
    </header>

    <div class="card">
        <div class="card-header">
            <div class="icon"><i class="fas fa-lock"></i></div>
            <h1>Registration Disabled</h1>
            <p class="muted">Self-registration is currently closed.</p>
        </div>
        <div class="card-body">
            <div class="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
            <p>For security reasons, all accounts must be created by an administrator. Please contact the system administrator for access.</p>
            <p style="margin-top:18px;"><a class="button" href="login.php"><i class="fas fa-sign-in-alt"></i>&nbsp;Back to Login</a></p>
        </div>
        <div class="card-footer">Already have an account? <a href="login.php">Login here</a></div>
    </div>

    <footer style="padding:28px; text-align:center; color:#64748b;">&copy; 2026 Orphanage Management System</footer>
</body>
</html>
