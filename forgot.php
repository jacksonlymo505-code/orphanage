<?php
// Start session
session_start();

// Include database connection
require_once 'config/database.php';

// Initialize variables
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['email_or_phone']);
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($input)) {
        $message = 'Please enter your email or phone number.';
    } else {
        // Check if user exists by email or phone
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->bind_param('ss', $input, $input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // If password reset form is submitted
            if (!empty($new_password) && !empty($confirm_password)) {
                if ($new_password !== $confirm_password) {
                    $message = 'Passwords do not match!';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->bind_param('si', $hashed_password, $user['id']);
                    if ($update->execute()) {
                        $message = 'Password reset successful! You can now <a href="login.php">login</a>.';
                    } else {
                        $message = 'Failed to reset password. Please try again.';
                    }
                }
            } else {
                // Show password reset form
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_verified'] = true;
            }
        } else {
            $message = 'No user found with that email or phone number.';
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
        <?php include 'assets/css/style.css'; ?>
    </style>
</head>
<body>
    <div class="container">
        <div class="login-icon">
            <i class="fas fa-key"></i>
        </div>
        <h2>Reset Your Password</h2>
        <?php if ($message) { echo '<div class="success-message">' . $message . '</div>'; } ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="new_password"><i class="fas fa-lock"></i> New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
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
                toggle.style.right = '10px';
                toggle.style.top = '50%';
                toggle.style.transform = 'translateY(-50%)';
                toggle.style.cursor = 'pointer';
                toggle.style.color = '#7f8c8d';
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
    // Unset session after showing form
    unset($_SESSION['reset_verified']);
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
        <?php include 'assets/css/style.css'; ?>
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
                <label for="email_or_phone"><i class="fas fa-envelope"></i> Email or <i class="fas fa-phone"></i> Phone Number:</label>
                <input type="text" class="form-control" id="email_or_phone" name="email_or_phone" required>
            </div>
            <button type="submit"><i class="fas fa-search"></i> Verify</button>
        </form>
        <p><a href="index.php"><i class="fas fa-sign-in-alt"></i> Back to Login</a></p>
    </div>
</body>
</html> 