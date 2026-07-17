<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <meta name="description" content="Explore the features of the Orphanage Management System for child management, donations, adoption workflows, and communications.">
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
        .hero .btn{display:inline-block;margin-top:10px;padding:14px 28px;border-radius:30px;text-decoration:none;font-weight:600;background:var(--primary);color:var(--white);}
        section{padding:40px 0;}
        .section-title h2{font-size:34px;margin-bottom:15px;}
        .section-title p{max-width:760px;color:var(--text);}
        .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:30px;}
        .card{background:var(--white);border-radius:20px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,.08);}
        .card i{font-size:40px;color:var(--secondary);margin-bottom:20px;}
        .card h3{margin-bottom:12px;}
        .card p{color:var(--text);}
        footer{background:var(--dark);color:#cbd5e1;padding:40px 0 30px;}
        @media(max-width:900px){.grid-3{grid-template-columns:1fr;}.nav-links{display:none;}}
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
                <a href="users.php">Users</a>
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
            <h1>Platform Features</h1>
            <p>Learn how the Orphanage Management System streamlines child welfare, donations, adoption, and communication for everyone involved.</p>
        </div>
    </section>
    <section>
        <div class="section-title">
            <h2>Core Features</h2>
            <p>Each feature is built to support transparent, secure, and efficient orphanage operations.</p>
        </div>
        <div class="grid-3">
            <div class="card">
                <i class="fas fa-child"></i>
                <h3>Child Management</h3>
                <p>Register children, maintain records, monitor health and care needs, and review placement details.</p>
            </div>
            <div class="card">
                <i class="fas fa-hand-holding-heart"></i>
                <h3>Donation Tracking</h3>
                <p>Capture gift details, issue receipts, and track donor relationships with full transparency.</p>
            </div>
            <div class="card">
                <i class="fas fa-user-shield"></i>
                <h3>Adoption Workflow</h3>
                <p>Manage applications, upload documents, and follow each adoption step from submission to approval.</p>
            </div>
            <div class="card">
                <i class="fas fa-comments"></i>
                <h3>Communication</h3>
                <p>Send messages, review correspondence, and keep stakeholders connected through the system.</p>
            </div>
            <div class="card">
                <i class="fas fa-chart-line"></i>
                <h3>Reporting</h3>
                <p>Generate reports on children, donations, and adoption progress to support planning and accountability.</p>
            </div>
            <div class="card">
                <i class="fas fa-lock"></i>
                <h3>Secure Access</h3>
                <p>Role-based dashboards and login protection keep user data safe across the orphanage network.</p>
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