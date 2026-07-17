<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <meta name="description" content="Learn how the Orphanage Management System works, from registration to reporting and approvals.">
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
        .steps{counter-reset:step;margin-top:30px;}
        .steps li{margin-bottom:20px;font-size:18px;color:var(--text);}
        .steps li strong{display:inline-block;width:32px;height:32px;background:var(--primary);color:var(--white);border-radius:50%;text-align:center;margin-right:14px;}
        .steps li{display:flex;align-items:flex-start;}
        footer{background:var(--dark);color:#cbd5e1;padding:40px 0 30px;}
        @media(max-width:900px){.nav-links{display:none;}.steps li{flex-direction:column;align-items:flex-start;}}        
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="logo"><i class="fas fa-home"></i> Orphanage Management System</div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="features.php">Features</a>
                <a href="users.php">Users</a>
                <a href="contact.php">Contact</a>
            </div>
            <a href="login.php" class="login-btn">Login</a>
        </nav>
    </div>
</header>
<div class="container">
    <section class="hero">
        <div class="section-title">
            <h1>How It Works</h1>
            <p>Follow the main process for using the Orphanage Management System from registration to reporting and approval.</p>
        </div>
    </section>
    <section>
        <div class="section-title">
            <h2>System Process</h2>
            <p>Designed to make orphanage workflows clear, efficient, and secure for every user.</p>
        </div>
        <ul class="steps">
            <li><strong>1</strong> Register or log in with your role-based credentials.</li>
            <li><strong>2</strong> Complete child, donor, or adoption information using the dashboard tools.</li>
            <li><strong>3</strong> Upload and review documents, track application progress, and communicate as needed.</li>
            <li><strong>4</strong> Generate reports, receipts, and approvals to support transparency and follow-through.</li>
        </ul>
    </section>
</div>
<footer>
    <div class="container">
        <p>© 2026 Orphanage Management System. All Rights Reserved.</p>
    </div>
</footer>
</body>
</html>