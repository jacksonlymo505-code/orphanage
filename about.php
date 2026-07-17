<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <meta name="description" content="About the Orphanage Management System, its mission, vision, and the services it provides to children, donors, and adoptive families.">
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

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Segoe UI, sans-serif;
        }

        body{
            background:var(--light);
            color:var(--dark);
            line-height:1.7;
        }

        .container{
            width:90%;
            max-width:1100px;
            margin:auto;
            padding:40px 0;
        }

        header{
            background:rgba(15,23,42,.95);
            color:var(--white);
            position:sticky;
            top:0;
            z-index:1000;
            backdrop-filter:blur(10px);
        }

        nav{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:20px 0;
        }

        .logo{
            font-size:24px;
            font-weight:bold;
        }

        .nav-links{
            display:flex;
            gap:25px;
        }

        .nav-links a,
        .login-btn{
            color:var(--white);
            text-decoration:none;
            font-weight:600;
        }

        .login-btn{
            background:var(--accent);
            padding:12px 24px;
            border-radius:30px;
        }

        .hero{
            padding:80px 0;
        }

        .hero h1{
            font-size:42px;
            margin-bottom:20px;
            color:var(--primary);
        }

        .hero p{
            color:var(--text);
            max-width:760px;
            margin-bottom:30px;
        }

        .hero .btn {
            display:inline-block;
            margin-top:10px;
            padding:14px 28px;
            border-radius:30px;
            text-decoration:none;
            font-weight:600;
            background:var(--primary);
            color:var(--white);
        }

        section{
            padding:40px 0;
        }

        .section-title h2{
            font-size:34px;
            margin-bottom:15px;
        }

        .section-title p{
            max-width:760px;
            color:var(--text);
        }

        .card-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:20px;
            margin-top:30px;
        }

        .card{
            background:var(--white);
            border-radius:20px;
            padding:30px;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
        }

        .card h3{
            margin-bottom:12px;
        }

        .card p{
            color:var(--text);
        }

        footer{
            background:var(--dark);
            color:#cbd5e1;
            padding:40px 0 30px;
        }

        footer p{
            margin-bottom:8px;
        }

        @media(max-width:900px){
            .card-grid{
                grid-template-columns:1fr;
            }
            .nav-links{
                display:none;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="logo">
                <i class="fas fa-home"></i> Orphanage Management System
            </div>
            <div class="nav-links">
                <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                <a href="index.php">Home</a>
                <a href="index.php#features">Features</a>
                <a href="index.php#roles">Users</a>
                <a href="index.php#process">How It Works</a>
                <a href="index.php#contact">Contact</a>
                <?php endif; ?>
            </div>
            <a href="login.php" class="login-btn">Login</a>
        </nav>
    </div>
</header>

<div class="container">
    <section class="hero">
        <div class="section-title">
            <h2>About Us</h2>
            <p>Orphanage Management System is designed to support child welfare, adoption services, donor engagement, and orphanage operations in a secure digital environment.</p>
            <a href="index.php#features" class="btn">See What We Offer</a>
        </div>
    </section>

    <section>
        <div class="section-title">
            <h2>Our Mission</h2>
            <p>To empower orphanage administrators, donors, adoptive families, and guardians with efficient tools for managing care, donations, documents, and communication.</p>
        </div>
        <p style="margin-top:20px; color:var(--text);">We focus on delivering transparency, accountability, and compassion through a platform built for all stakeholders in the child welfare ecosystem. The system streamlines records, donation tracking, adoption workflows, and reporting so care teams can concentrate on children’s needs.</p>
    </section>

    <section>
        <div class="section-title">
            <h2>Our Vision</h2>
            <p>To build a trusted digital community where every child receives care, every donor feels connected, and every adoption journey is managed with clarity and dignity.</p>
        </div>
        <div class="card-grid">
            <div class="card">
                <h3>Child-centered care</h3>
                <p>We believe every child deserves safe, consistent support and transparent tracking of their welfare journey.</p>
            </div>
            <div class="card">
                <h3>Donor trust</h3>
                <p>We ensure donors can see their contributions making a real impact through reliable records and communication.</p>
            </div>
            <div class="card">
                <h3>Adoption support</h3>
                <p>We support families and orphanage staff with document review, status tracking, and process guidance.</p>
            </div>
        </div>
    </section>
</div>

<footer>
    <div class="container">
        <p><strong>Contact</strong></p>
        <p>Phone: +255 752162194</p>
        <p>Email: jacksonlymo505@gmail.com</p>
        <p>Arusha, Tanzania</p>
    </div>
</footer>
</body>
</html>