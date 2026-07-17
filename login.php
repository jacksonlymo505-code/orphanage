<?php
// Suppress session already started notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        try {
            // Try users table first
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $effectiveRole = $user['role'];
                // compatibility: set both session keys used across app
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $effectiveRole;
                $_SESSION['user_role'] = $effectiveRole;
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

                if ($effectiveRole === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($effectiveRole === 'adoptive') {
                    header("Location: adoptive/dashboard.php");
                } else {
                    header("Location: donor/dashboard.php");
                }
                exit();
            }

            // If not a user, check donors table for approved donor credentials
            $donorCheck = $conn->prepare("SELECT id, full_name, password_hash, status, approval_status FROM donors WHERE email = ? LIMIT 1");
            if ($donorCheck) {
                $donorCheck->bind_param('s', $email);
                $donorCheck->execute();
                $donorResult = $donorCheck->get_result();
                $donor = $donorResult->fetch_assoc();
                // Check if donor has active status and approved/active approval status, and password matches
                if ($donor && ($donor['status'] === 'active' || $donor['approval_status'] === 'approved') && password_verify($password, $donor['password_hash'])) {
                    $_SESSION['donor_id'] = $donor['id'];
                    $_SESSION['user_id'] = $donor['id'];
                    $_SESSION['role'] = 'donor';
                    $_SESSION['user_role'] = 'donor';
                    $_SESSION['user_name'] = $donor['full_name'];
                    header('Location: donor/donor_dashboard.php');
                    exit();
                }
            }

            $errors[] = "Invalid email or password";
        } catch (Exception $e) {
            $errors[] = "Login failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .site-header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 18px 28px; 
            background: rgba(255, 255, 255, 0.98); 
            border-bottom: 1px solid rgba(15, 23, 42, 0.04);
        }
        .brand { 
            font-weight: 700; 
            display: flex; 
            gap: 8px; 
            align-items: center;
            color: #0f172a;
        }
        .brand i { color: #667eea; font-size: 20px; }
        .site-nav { 
            display: flex; 
            gap: 24px; 
            align-items: center;
        }
        .site-nav a { 
            color: #0f172a; 
            text-decoration: none; 
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .site-nav a:hover { color: #667eea; }
        .site-actions { 
            display: flex; 
            gap: 12px;
        }
        .site-actions a { 
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .site-actions a.primary { 
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .login-wrap { 
            flex: 1;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 40px 20px;
        }
        .card { 
            width: 100%; 
            max-width: 420px; 
            background: white;
            border-radius: 12px; 
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); 
            padding: 40px;
        }
        .card-header { text-align: center; margin-bottom: 30px; }
        .card-icon { font-size: 48px; color: #667eea; margin-bottom: 12px; }
        .card h2 { 
            margin: 0 0 8px; 
            font-size: 24px;
            color: #0f172a;
        }
        .card p { 
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .alert { 
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            border-left: 3px solid #dc2626;
        }
        .form-group { 
            margin-bottom: 18px;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        input { 
            width: 100%; 
            padding: 12px 14px; 
            border-radius: 8px; 
            border: 1px solid #e6edf3;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .forgot-link {
            text-align: right;
            margin-top: -12px;
            margin-bottom: 20px;
        }
        .forgot-link a {
            color: #667eea;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .forgot-link a:hover { color: #764ba2; }
        button.btn-primary { 
            width: 100%; 
            padding: 12px 14px; 
            border-radius: 10px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            border: none; 
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }
        button.btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .register-section {
            text-align: center;
            padding-top: 16px;
            border-top: 1px solid #e6edf3;
        }
        .register-section p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .register-btn { 
            display: inline-block;
            padding: 10px 20px;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .register-btn:hover {
            background: #bfdbfe;
            transform: translateY(-2px);
        }
        .footer {
            background: rgba(255, 255, 255, 0.95);
            border-top: 1px solid rgba(15, 23, 42, 0.04);
            padding: 24px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }
        @media (max-width: 480px) {
            .site-header { 
                flex-direction: column; 
                gap: 12px;
                text-align: center;
            }
            .site-nav {
                gap: 16px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .card { 
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="brand"><i class="fas fa-shield"></i> <span>Orphanage Management</span></div>
        <nav class="site-nav">
            <?php if (basename($_SERVER['PHP_SELF']) === 'index.html'): ?>
            <a href="index.php">Home</a>
            <a href="index.php#features">Features</a>
            <?php endif; ?>
        </nav>
        <div class="site-actions">
            <a href="login.php" class="primary">Login</a>
        </div>
    </header>

    <div class="login-wrap">
        <div class="card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                <h2>Welcome Back!</h2>
                <p>Sign in to your account</p>
            </div>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address:</label>
                    <input type="email" id="email" name="email" placeholder="j@gmail.com" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password:</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="forgot-link">
                    <a href="forgot-password.php"><i class="fas fa-question-circle"></i> Forgot Password?</a>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="register-section">
                <p>If you are an adoptive parent,</p>
                <a href="register_child.php" class="register-btn">
                    <i class="fas fa-plus-circle"></i> Register as an adoptive parent
                </a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2026 Orphanage Management System. All rights reserved.</p>
    </footer>
</body>
</html>
