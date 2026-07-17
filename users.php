<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <meta name="description" content="Learn about the different user roles in the Orphanage Management System and how each role benefits from the platform.">
    <style>
        :root{
            --primary:#0f4c81;
            --secondary:#1e88e5;
            --accent:#f59e0b;
            --dark:#0f172a;
            --light:#f8fafc;
            --text:#475569;
            --white:#ffffff;
        }
        *{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI, sans-serif;}
        body{background:var(--light);color:var(--dark);line-height:1.7;}
        .container{width:90%;max-width:1100px;margin:auto;padding:40px 0;}
        header{background:rgba(15,23,42,.95);color:var(--white);position:sticky;top:0;z-index:1000;backdrop-filter:blur(10px);}
        nav{display:flex;justify-content:space-between;align-items:center;padding:20px 0;}
        .logo{font-size:24px;font-weight:bold;}
        .nav-links{display:flex;gap:25px;}
        .nav-links a,.login-btn{color:var(--white);text-decoration:none;font-weight:600;}
        .login-btn{background:var(--accent);padding:12px 24px;border-radius:30px;}
        .hero{padding:80px 0;}
        .hero h1{font-size:42px;margin-bottom:20px;color:var(--primary);}
        .hero p{color:var(--text);max-width:760px;margin-bottom:30px;}
        section{padding:40px 0;}
        .section-title h2{font-size:34px;margin-bottom:15px;}
        .section-title p{max-width:760px;color:var(--text);}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:30px;}
        .card{background:var(--white);border-radius:20px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,.08);}
        .card h3{margin-bottom:12px;}
        .card p{color:var(--text);}
        footer{background:var(--dark);color:#cbd5e1;padding:40px 0 30px;}
        @media(max-width:900px){.stats-grid{grid-template-columns:1fr;}.nav-links{display:none;}}
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="logo"><i class="fas fa-home"></i> Orphanage Management System</div>
            <div class="nav-links">
                <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                <a href="index.php">Home</a>
                <a href="index.php#features">Features</a>
                <?php endif; ?>
                <a href="about.php">About</a>
                <a href="process.php">How It Works</a>
                <a href="contact.php">Contact</a>
            </div>
            <a href="login.php" class="login-btn">Login</a>
        </nav>
    </div>
</header>
<div class="container">
    <section class="hero">
        <div class="section-title">
            <h1>User Roles</h1>
            <p>Understand how each user type interacts with the platform and which tools are available to them.</p>
        </div>
    </section>
    <section>
        <div class="section-title">
            <h2>Platform Users</h2>
            <p>Users access tailored dashboards and workflows based on their responsibilities in the orphanage system.</p>
        </div>
        <div class="stats-grid">
            <div class="card">
                <h3>Admin</h3>
                <p>Administrators manage children, guardians, donors, system settings, and oversee all records.</p>
            </div>
            <div class="card">
                <h3>Adoptive</h3>
                <p>Adoptive users upload documents, view adoption progress, and communicate with orphanage staff.</p>
            </div>
            <div class="card">
                <h3>Donor</h3>
                <p>Donors give support, view donation history, and receive receipts for their contributions.</p>
            </div>
            <div class="card">
                <h3>Guardian</h3>
                <p>Guardians support child care, monitor needs, and coordinate with the orphanage team.</p>
            </div>
        </div>
    </section>
</div>
<footer>
    <div class="container">
        <p>© 2026 Orphanage Management System. All Rights Reserved.</p>
    </div>
</footer>
</body>
</html>