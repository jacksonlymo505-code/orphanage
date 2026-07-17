<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orphanage Management System</title>

<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/all.min.css">

<meta name="description"
      content="A secure digital platform for managing child welfare, donations, adoption processes, and orphanage operations.">

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
        min-height:100vh;
        scroll-behavior:smooth;
    }

    .container{
        width:90%;
        max-width:1200px;
        margin:auto;
    }

    header{
        position:sticky;
        top:0;
        z-index:1000;
        background:var(--dark);
        box-shadow:0 10px 30px rgba(15,23,42,.12);
    }

    nav{
        display:flex;
        justify-content:space-between;
        align-items:center;
        padding:18px 0;
    }

    .logo{
        color:var(--white);
        font-size:24px;
        font-weight:700;
        display:flex;
        align-items:center;
        gap:10px;
    }

    .logo i{
        font-size:26px;
    }

    .nav-links{
        display:flex;
        gap:30px;
    }

    .nav-links a{
        color:#cbd5e1;
        text-decoration:none;
        font-weight:600;
        transition:color .2s ease;
    }

    .nav-links a:hover,
    .nav-links a.active{
        color:var(--white);
        border-bottom:none;
        padding-bottom:0;
    }

    .login-btn{
        background:#1d4ed8;
        color:var(--white);
        padding:12px 26px;
        border-radius:999px;
        text-decoration:none;
        font-weight:700;
        box-shadow:0 16px 30px rgba(29,78,216,.18);
    }

    .nav-actions{
        display:flex;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
    }

    .nav-cta{
        background:transparent;
        color:var(--white);
        padding:10px 20px;
        border:2px solid rgba(255,255,255,.85);
        border-radius:999px;
        text-decoration:none;
        font-weight:700;
        transition:all .2s ease;
    }

    .nav-cta:hover{
        background:rgba(255,255,255,.08);
    }
       
    .hero{
        position:relative;
        background: url('images/home1.png') center/cover no-repeat;
        color:var(--dark);
        padding:100px 0 120px;
        overflow:hidden;
    }

    .hero::before{
        content:'';
        position:absolute;
        inset:0;
        background:linear-gradient(90deg,rgba(255, 251, 17, 0.97) 35%,rgba(255,255,255,0) 100%);
        pointer-events:none;
    }

    .hero-content{
        display:grid;
        grid-template-columns:minmax(0, 580px) 1fr;
        gap:40px;
        align-items:center;
        position:relative;
        z-index:1;
    }

    .hero h1{
        font-size:4.25rem;
        line-height:1.1;
        margin-bottom:20px;
        color:#0f172a;
        font-weight:900;
        text-shadow:0 2px 4px rgba(0,0,0,.05);
    }

    .hero p{
        font-size:1.05rem;
        max-width:520px;
        opacity:.9;
        margin-bottom:30px;
        color:#334155;
        line-height:1.8;
    }

    .hero-text-box{
        background:linear-gradient(135deg,rgba(29,78,216,.08),rgba(29,78,216,.04));
        padding:50px 40px;
        border-radius:20px;
        backdrop-filter:blur(10px);
        border:1px solid rgba(29,78,216,.1);
    }

    .hero-buttons{
        display:flex;
        gap:16px;
        flex-wrap:wrap;
    }

    .btn{
        padding:14px 30px;
        border-radius:999px;
        text-decoration:none;
        font-weight:700;
        transition:all .3s cubic-bezier(.4,0,.2,1);
        cursor:pointer;
        display:inline-block;
    }

    .btn-primary{
        background:#1d4ed8;
        color:var(--white);
        box-shadow:0 10px 25px rgba(29,78,216,.3);
    }

    .btn-primary:hover{
        background:#1e40af;
        box-shadow:0 20px 40px rgba(29,78,216,.4),0 0 40px rgba(29,78,216,.2);
        transform:translateY(-2px);
    }

    .btn-secondary{
        border:2.5px solid #1d4ed8;
        color:#1d4ed8;
        background:#fff;
        box-shadow:0 4px 15px rgba(29,78,216,.15);
    }

    .btn-secondary:hover{
        background:#f0f7ff;
        border-color:#1e40af;
        color:#1e40af;
        box-shadow:0 12px 30px rgba(29,78,216,.25);
        transform:translateY(-2px);
    }

    section{
        padding:80px 0;
    }

    .section-title{
        text-align:center;
        margin-bottom:50px;
    }

    .section-title h2{
        font-size:40px;
        margin-bottom:15px;
    }

    .section-title p{
        color:var(--text);
        max-width:700px;
        margin:auto;
    }

    .grid-3{
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:25px;
    }

    .card{
        background:var(--white);
        padding:30px;
        border-radius:20px;
        box-shadow:0 10px 30px rgba(0,0,0,.08);
    }

    .card i{
        font-size:40px;
        color:var(--secondary);
        margin-bottom:20px;
    }

    .card h3{
        margin-bottom:15px;
    }

    .card p{
        color:var(--text);
    }

    .stats{
        background:var(--primary);
        color:var(--white);
    }

    .stats-grid{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:30px;
        text-align:center;
    }

    .stats-grid h3{
        font-size:42px;
    }

    .steps{
        counter-reset:step;
        padding-left:20px;
    }

    .steps li{
        margin-bottom:20px;
        color:var(--text);
    }

    .donor-section{
        padding:80px 0 100px;
        background:linear-gradient(180deg,#f8fbff 0%,#eef6ff 100%);
    }

    .donor-card{
        background:var(--white);
        padding:40px;
        border-radius:24px;
        box-shadow:0 18px 45px rgba(15,23,42,.08);
        border:1px solid rgba(29,78,216,.08);
    }

    .donor-card h3{
        margin-bottom:20px;
        color:var(--primary);
    }

    .contact-box{
        background:var(--white);
        padding:40px;
        border-radius:20px;
        box-shadow:0 10px 30px rgba(0,0,0,.08);
    }

    footer{
        background:var(--dark);
        color:#cbd5e1;
        padding:60px 0 20px;
    }

    .footer-grid{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:30px;
    }

    footer h4{
        color:var(--white);
        margin-bottom:20px;
    }

    footer a{
        color:#cbd5e1;
        text-decoration:none;
        display:block;
        margin-bottom:10px;
    }

    .copyright{
        text-align:center;
        margin-top:40px;
        border-top:1px solid rgba(255,255,255,.1);
        padding-top:20px;
    }

    @media(max-width:768px){

        .hero-content,
        .grid-3,
        .stats-grid,
        .footer-grid{
            grid-template-columns:1fr;
        }

        nav{
            align-items:flex-start;
        }

        .hero h1{
            font-size:38px;
        }
    }
</style>

<body>

<header>
    <div class="container">
        <nav>

        <div class="logo">
            <i class="fas fa-home"></i>
            Orphanage Management System
        </div>

        <div class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="about.php">About</a>
            <a href="features.php">Features</a>
             <a href="services.php">Services</a>
            <a href="users.php">Users</a>
            <a href="process.php">Process</a>
            <a href="contact.php">Contact</a>
        </div>

        <div class="nav-actions">
           
            <a href="login.php" class="login-btn">
                Login
            </a>
        </div>

    </nav>
</div>

</header>

<section class="hero">

<div class="container">

    <div class="hero-content">

        <div class="hero-text-box">

            <h1>Empowering Child Care Through Digital Innovation</h1>

            <p>
                A secure and comprehensive platform for managing
                child welfare services, donations, adoption workflows,
                records, reports, and organizational operations.
            </p>

            <div class="hero-buttons">

                <a href="features.php" class="btn btn-primary">
                    Explore Features
                </a>

                <a href="donor_sponsor.php" class="btn btn-secondary">
                    Become a Donor or Sponsor
                </a>

                <a href="public_contribute.php" class="btn btn-accent" style="background:#10b981;color:#fff;border-radius:8px;padding:12px 18px; margin-left:8px;">
                    Contribute without login
                </a>

            </div>

        </div>

    </div>

</div>

</section>

<footer>

<div class="container">

    <div class="footer-grid">

        <div>

            <h4>About Us</h4>

            <p>
                A modern digital platform that improves child care,
                donor engagement, adoption services, and operational efficiency.
            </p>

        </div>

        <div>

            <h4>Quick Links</h4>

            <a href="about.php">About</a>
            <a href="features.php">Features</a>
            <a href="process.php">How It Works</a>
            <a href="login.php">Staff Login</a>

        </div>

        <div>

            <h4>Services</h4>

            <a href="services.php#child-welfare">Child Welfare</a>
            <a href="services.php#adoption-services">Adoption Services</a>
            <a href="services.php#donor-management">Donor Management</a>
            <a href="services.php#reports-analytics">Reports & Analytics</a>

        </div>

        <div>

            <h4>Contact</h4>

            <p>+255 752162194</p>
            <p>jacksonlymo505@gmail.com</p>
            <p>Arusha, Tanzania</p>

        </div>

    </div>

    <div class="copyright">

        <p>
            © 2026 Orphanage Management System.
            All Rights Reserved.
        </p>

    </div>

</div>

</body>
</html>
