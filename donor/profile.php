<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize user data with default values if not found
if (!$user) {
    $user = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'organization' => ''
    ];
    $error_message = "User data not found. Please contact support.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $organization = trim($_POST['organization'] ?? '');

    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = "First name, last name, and email are required fields.";
    } else {
        // Check if email is already taken by another user
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "Email is already taken by another user.";
        } else {
            // Update profile
            $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, organization = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $organization, $user_id);

            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "New password must be at least 8 characters long.";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Update password using same encryption as register/forgot
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password. Please try again.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Donor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            margin-top: 20px;
        }

        .menu-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .menu-item.active {
            background: var(--secondary-color);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        .profile-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-header h1 {
            color: var(--primary-color);
            font-size: 2em;
            font-weight: 600;
        }

        .profile-section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .profile-section h2 {
            color: var(--primary-color);
            font-size: 1.5em;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #495057;
            font-weight: 500;
            font-size: 0.95em;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 0.95em;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.9em;
            font-weight: 500;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .profile-container {
                padding: 20px;
            }

            .profile-section {
                padding: 20px;
            }

            .form-group input {
                padding: 10px 12px;
            }
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
   

    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <h1>Profile Settings</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="profile-section">
                <h2>Personal Information</h2>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name"><i class="fas fa-user"></i> First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars(isset($user['first_name']) ? $user['first_name'] : ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name"><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars(isset($user['last_name']) ? $user['last_name'] : ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars(isset($user['email']) ? $user['email'] : ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars(isset($user['phone']) ? $user['phone'] : ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="organization"><i class="fas fa-building"></i> Organization</label>
                            <input type="text" id="organization" name="organization" value="<?php echo htmlspecialchars(isset($user['organization']) ? $user['organization'] : ''); ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-submit"><i class="fas fa-save"></i> Update Profile</button>
                </form>
            </div>

            <div class="profile-section">
                <h2>Change Password</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-key"></i> New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-submit"><i class="fas fa-key"></i> Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add password strength validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strength = {
                length: password.length >= 8,
                hasUpper: /[A-Z]/.test(password),
                hasLower: /[a-z]/.test(password),
                hasNumber: /[0-9]/.test(password),
                hasSpecial: /[!@#$%^&*]/.test(password)
            };
            
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            
            if (Object.values(strength).every(Boolean)) {
                strengthIndicator.textContent = 'Strong password';
                strengthIndicator.style.color = 'var(--success-color)';
            } else if (Object.values(strength).filter(Boolean).length >= 3) {
                strengthIndicator.textContent = 'Medium password';
                strengthIndicator.style.color = 'var(--warning-color)';
            } else {
                strengthIndicator.textContent = 'Weak password';
                strengthIndicator.style.color = 'var(--danger-color)';
            }
            
            const existingIndicator = this.parentElement.querySelector('.password-strength');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            this.parentElement.appendChild(strengthIndicator);
        });
    </script>
</body>
</html> 