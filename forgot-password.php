<?php
// Start session
session_start();

// Include database connection
require_once 'config/database.php';

// Initialize variables
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (isset($_SESSION['reset_verified']) && $_SESSION['reset_verified'] === true && isset($_SESSION['reset_user_id'])) {
        // Handle password reset
        if (empty($new_password) || empty($confirm_password)) {
            $message = 'Please enter and confirm your new password.';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Passwords do not match!';
        } elseif (strlen($new_password) < 8) {
            $message = 'Password must be at least 8 characters long.';
        } else {
            // Use same encryption as register.php
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param('si', $hashed_password, $_SESSION['reset_user_id']);
            if ($update->execute()) {
                unset($_SESSION['reset_verified']);
                unset($_SESSION['reset_user_id']);
                $_SESSION['success'] = 'Password reset successful! Please login.';
                header('Location: index.php');
                exit();
            } else {
                $message = 'Failed to reset password. Please try again.';
            }
        }
    } else {
        // Handle verification by email and phone
        if (empty($email) || empty($phone)) {
            $message = 'Please enter both your email and phone number.';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND phone = ? LIMIT 1");
            $stmt->bind_param('ss', $email, $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_verified'] = true;
            } else {
                $message = 'No user found with that email and phone number.';
            }
        }
    }
}

// Handle password reset form if user is verified
if (isset($_SESSION['reset_verified']) && $_SESSION['reset_verified'] === true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-icon {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-icon i {
            font-size: 60px;
            color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        h2 {
            color: #2d3748;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }

        label i {
            margin-right: 8px;
            color: #667eea;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #fff;
        }

        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: #667eea;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #764ba2;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        button i {
            margin-right: 8px;
        }

        .error-messages {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .error {
            color: #c53030;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .success-message {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #2f855a;
        }

        p {
            text-align: center;
            margin-top: 25px;
            color: #4a5568;
            font-size: 14px;
        }

        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #764ba2;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            .login-icon i {
                font-size: 50px;
            }

            h2 {
                font-size: 24px;
            }

            input, button {
                padding: 12px;
            }
        }

        /* Loading Animation */
        .loading {
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: button-loading-spinner 1s linear infinite;
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }
            to {
                transform: rotate(1turn);
            }
        }

        .error-messages i, .success-message i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-icon">
            <i class="fas fa-key"></i>
        </div>
        <h2>Reset Your Password</h2>
        <?php if ($message) { echo '<div class="error-messages">' . $message . '</div>'; } ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="new_password"><i class="fas fa-lock"></i> New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit"><i class="fas fa-sync-alt"></i> Reset Password</button>
        </form>
        <p><a href="index.php"><i class="fas fa-sign-in-alt"></i> Back to Login</a></p>
    </div>
    <script>
        // Add password visibility toggle for both fields
        document.addEventListener('DOMContentLoaded', function() {
            ['new_password', 'confirm_password'].forEach(function(id) {
                const input = document.getElementById(id);
                const toggle = document.createElement('i');
                toggle.className = 'fas fa-eye password-toggle';
                toggle.style.position = 'absolute';
                toggle.style.right = '15px';
                toggle.style.top = '50%';
                toggle.style.transform = 'translateY(-50%)';
                toggle.style.cursor = 'pointer';
                toggle.style.color = '#a0aec0';
                const container = input.parentElement;
                container.style.position = 'relative';
                container.appendChild(toggle);
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.className = `fas fa-${type === 'password' ? 'eye' : 'eye-slash'} password-toggle`;
                });
            });
        });
    </script>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-icon {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-icon i {
            font-size: 60px;
            color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        h2 {
            color: #2d3748;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }

        label i {
            margin-right: 8px;
            color: #667eea;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #fff;
        }

        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: #667eea;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #764ba2;
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        button i {
            margin-right: 8px;
        }

        .error-messages {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .error {
            color: #c53030;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .success-message {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #2f855a;
        }

        p {
            text-align: center;
            margin-top: 25px;
            color: #4a5568;
            font-size: 14px;
        }

        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #764ba2;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            .login-icon i {
                font-size: 50px;
            }

            h2 {
                font-size: 24px;
            }

            input, button {
                padding: 12px;
            }
        }

        /* Loading Animation */
        .loading {
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: button-loading-spinner 1s linear infinite;
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }
            to {
                transform: rotate(1turn);
            }
        }

        .error-messages i, .success-message i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-icon">
            <i class="fas fa-key"></i>
        </div>
        <h2>Forgot Password</h2>
        <?php if ($message) { echo '<div class="error-messages"><i class="fas fa-exclamation-circle"></i> ' . $message . '</div>'; } ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i> Phone Number:</label>
                <input type="text" id="phone" name="phone" required>
            </div>
            <button type="submit"><i class="fas fa-search"></i> Verify</button>
        </form>
        <p><a href="index.php"><i class="fas fa-sign-in-alt"></i> Back to Login</a></p>
    </div>
</body>
</html> 