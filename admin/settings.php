<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'update_profile':
            $user_id = $_SESSION['user_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            
            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png'];
                $filename = $_FILES['profile_image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($filetype), $allowed)) {
                    $new_filename = 'profile_' . $user_id . '.' . $filetype;
                    $upload_path = '../uploads/profiles/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $profile_image = $new_filename;
                    }
                }
            }
            
            $sql = "UPDATE users SET first_name=?, last_name=?, email=?, phone=?";
            $params = [$first_name, $last_name, $email, $phone];
            $types = "ssss";
            
            if (isset($profile_image)) {
                $sql .= ", profile_image=?";
                $params[] = $profile_image;
                $types .= "s";
            }
            
            $sql .= " WHERE id=?";
            $params[] = $user_id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Profile updated successfully";
            } else {
                $_SESSION['error'] = "Error updating profile: " . $conn->error;
            }
            break;
            
        case 'change_password':
            $user_id = $_SESSION['user_id'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Verify current password
            $sql = "SELECT password FROM users WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $sql = "UPDATE users SET password=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Password changed successfully";
                    } else {
                        $_SESSION['error'] = "Error changing password: " . $conn->error;
                    }
                } else {
                    $_SESSION['error'] = "New passwords do not match";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect";
            }
            break;
            
        case 'update_settings':
            $orphanage_name = $_POST['orphanage_name'];
            $address = $_POST['address'];
            $phone = $_POST['phone'];
            $email = $_POST['email'];
            $website = $_POST['website'];
            $currency = $_POST['currency'];
            $timezone = $_POST['timezone'];
            
            $sql = "UPDATE settings SET 
                    orphanage_name=?, 
                    address=?, 
                    phone=?, 
                    email=?, 
                    website=?, 
                    currency=?, 
                    timezone=? 
                    WHERE id=1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $orphanage_name, $address, $phone, $email, $website, $currency, $timezone);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Settings updated successfully";
            } else {
                $_SESSION['error'] = "Error updating settings: " . $conn->error;
            }
            break;
    }
    
    header("Location: settings.php");
    exit();
}

// Fetch current user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch system settings
$sql = "SELECT * FROM settings WHERE id=1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Orphanage Management System</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            display: flex;
            min-height: 100vh;
        }

        .container {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e1e1;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin: 0;
        }

        .settings-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .settings-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .settings-card h2 {
            color: var(--primary-color);
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e1e1e1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }

        .profile-image-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-image-container input[type="file"] {
            display: none;
        }

        .profile-image-container label {
            display: inline-block;
            padding: 8px 16px;
            background: var(--secondary-color);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .profile-image-container label:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
 <!-- Sidebar -->
 <div class="sidebar">
        <div class="sidebar-header">
            <h2>OMS Admin</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="children.php" class="menu-item">
                <i class="fas fa-child"></i> Children
            </a>
            <a href="guardians.php" class="menu-item">
                <i class="fas fa-user-shield"></i> Guardians
            </a>
            <a href="donors.php" class="menu-item">
                <i class="fas fa-hand-holding-heart"></i> Donors
            </a>
            <a href="adoptions.php" class="menu-item">
                <i class="fas fa-baby"></i> Adoptions
            </a>
            <a href="donations_history.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i> Donations
            </a>
            <a href="manage_opportunities.php" class="menu-item">
                <i class="fas fa-handshake"></i> Opportunities
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1>Settings</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Profile Settings -->
            <div class="settings-card">
                <h2>Profile Settings</h2>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="profile-image-container">
                        <img src="<?php echo !empty($user['profile_image']) ? '../uploads/profiles/' . $user['profile_image'] : '../assets/images/default-profile.png'; ?>" 
                             alt="Profile Image" 
                             class="profile-image">
                        <label for="profile_image">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="settings-card">
                <h2>Change Password</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>

            <!-- System Settings -->
            <div class="settings-card">
                <h2>System Settings</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="form-group">
                        <label for="orphanage_name">Orphanage Name</label>
                        <input type="text" id="orphanage_name" name="orphanage_name" value="<?php echo htmlspecialchars($settings['orphanage_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($settings['address']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($settings['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($settings['website']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency" required>
                            <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD</option>
                            <option value="Tsh" <?php echo $settings['currency'] === 'Tsh' ? 'selected' : ''; ?>>Tsh</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone" required>
                            <?php
                            $timezones = DateTimeZone::listIdentifiers();
                            foreach ($timezones as $timezone) {
                                $selected = $settings['timezone'] === $timezone ? 'selected' : '';
                                echo "<option value=\"$timezone\" $selected>$timezone</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Preview profile image before upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 