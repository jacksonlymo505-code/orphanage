<?php
header('Location: ../register_child.php');
exit();
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Registration Disabled - Orphanage Management System</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 28px 90px rgba(0, 0, 0, 0.18);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 32px 30px;
            text-align: center;
            color: white;
        }

        .card-header .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .card-header h1 {
            font-size: 26px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .card-header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }

        .card-body {
            padding: 30px;
        }

        .alert {
            background: #fff5f5;
            border: 1px solid #f8d7da;
            color: #842029;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .card-body p {
            color: #4a5568;
            font-size: 15px;
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 14px 18px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(102, 126, 234, 0.24);
        }

        .card-footer {
            text-align: center;
            padding: 18px 30px 32px;
            color: #718096;
            font-size: 13px;
        }

        .card-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .card {
                border-radius: 18px;
            }

            .card-header {
                padding: 26px 22px;
            }

            .card-body {
                padding: 24px 22px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="icon"><i class="fas fa-user-lock"></i></div>
            <h1>Donor Registration Disabled</h1>
            <p>Self-registration is currently closed for this system.</p>
        </div>
        <div class="card-body">
            <div class="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
            <p>For security reasons, new donor accounts must be created by an administrator. Please contact the orphanage system administrator for access.</p>
            <a class="button" href="../login.php"><i class="fas fa-sign-in-alt"></i>&nbsp;Back to Login</a>
        </div>
        <div class="card-footer">Already have an account? <a href="../login.php">Login here</a></div>
    </div>
</body>
</html>
