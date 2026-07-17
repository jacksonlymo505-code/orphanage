<?php
session_start();
require_once 'config/database.php';

function ensureAdoptiveRoleInUsersEnum($conn) {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row) {
            $type = $row['Type'];
            // Parse enum definition without using preg_match to avoid delimiter issues
            if (stripos($type, 'enum(') === 0 && substr($type, -1) === ')') {
                $inside = substr($type, 5, strlen($type) - 6); // content between enum( and )
                $rawValues = explode("','", $inside);
                $values = array_map(function($v) {
                    return trim($v, "' \t\n\r\0\x0B");
                }, $rawValues);

                if (!in_array('adoptive', $values, true)) {
                    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','donor','adoptive') NOT NULL";
                    $conn->query($sql);
                }
            }
        }
    }
}

ensureAdoptiveRoleInUsersEnum($conn);

$errors = [];
$success = '';
$full_name = $parent_email = $parent_phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $parent_email = trim($_POST['parent_email'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate full name (must have at least 3 parts)
    $name_parts = array_values(array_filter(preg_split('/\s+/', $full_name)));
    if (count($name_parts) !== 3) {
        $errors[] = 'Full name must contain exactly three names.';
    }
    if (empty($parent_email)) {
        $errors[] = 'Email is required.';
    }
    if (empty($parent_phone)) {
        $errors[] = 'Phone is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }
    if ($confirm_password === '') {
        $errors[] = 'Confirm your password.';
    }
    if ($password !== '' && $confirm_password !== '' && $password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param('s', $parent_email);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $errors[] = 'Email address is already registered. Please login or use a different email.';
            }
            $check_stmt->close();
        } else {
            $errors[] = 'Unable to validate email address.';
        }
    }

    if (empty($errors)) {
        // Extract first, middle, last from full name
        $name_parts = array_values(array_filter(preg_split('/\s+/', $full_name)));
        $first_name = $name_parts[0] ?? '';
        $middle_name = $name_parts[1] ?? '';
        $last_name = $name_parts[2] ?? '';
        
        $conn->begin_transaction();
        try {
            $donor_sql = "INSERT INTO donors (first_name, last_name, email, phone, type, status) VALUES (?, ?, ?, ?, 'adoptive', 'active')";
            $donor_stmt = $conn->prepare($donor_sql);
            if (!$donor_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $donor_stmt->bind_param('ssss', $first_name, $last_name, $parent_email, $parent_phone);
            $donor_stmt->execute();
            $adopter_id = $conn->insert_id;
            if ($adopter_id <= 0) {
                throw new Exception('Unable to register adoptive parent.');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_sql = "INSERT INTO users (first_name, last_name, email, phone, organization, password, role) VALUES (?, ?, ?, ?, NULL, ?, 'adoptive')";
            $user_stmt = $conn->prepare($user_sql);
            if (!$user_stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $user_stmt->bind_param('sssss', $first_name, $last_name, $parent_email, $parent_phone, $hashed_password);
            $user_stmt->execute();
            $user_id = $conn->insert_id;
            if ($user_id <= 0) {
                throw new Exception('Unable to create login account.');
            }

            $conn->commit();
            $success = 'Account created. Please login.';
            $full_name = $parent_email = $parent_phone = '';
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Submission failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoptive Parent Registration</title>
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
        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border-left-color: #22c55e;
        }
        .alert ul { list-style: none; padding: 0; margin: 8px 0 0 0; }
        .alert li { margin: 4px 0; }
        .form-group { 
            margin-bottom: 18px;
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
        .button-group { 
            display: grid; 
            gap: 12px; 
            margin-top: 24px;
        }
        .btn-submit {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .btn-reset {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e6edf3;
            border-radius: 8px;
            background: #f9fafb;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            border-color: #667eea;
            background: #f0f1ff;
        }
        .card-footer { 
            text-align: center; 
            margin-top: 20px; 
            padding-top: 20px; 
            border-top: 1px solid #e6edf3;
        }
        .card-footer p { 
            color: #6b7280; 
            font-size: 13px; 
            margin: 0 0 8px;
        }
        .card-footer a { 
            color: #667eea; 
            text-decoration: none; 
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .card-footer a:hover { color: #764ba2; }
        @media (max-width: 640px) { .card { padding: 24px; } }
    </style>
</head>
<body>
    <div class="site-header">
        <div class="brand">
            <i class="fas fa-shield-alt"></i>
            <span>Orphanage Management</span>
        </div>
        <div class="site-nav">
            <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
            <a href="index.php">Home</a>
            <a href="index.php#features">Features</a>
            <?php endif; ?>
        </div>
        <div class="site-actions">
            <a href="login.php" class="primary">Login</a>
        </div>
    </div>
    <div class="login-wrap">
        <div class="card">
                <div class="card-header">
                <div class="card-icon"><i class="fas fa-hand-holding-heart"></i></div>
                <h2>Register as Adoptive Parent</h2>
            </div>
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> Please fix the following issues:
                <ul>
                    <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" placeholder="First Middle Last" required>
                </div>
                <div class="form-group">
                    <label for="parent_email">Email *</label>
                    <input type="email" id="parent_email" name="parent_email" value="<?php echo htmlspecialchars($parent_email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="parent_phone">Phone *</label>
                    <input type="tel" id="parent_phone" name="parent_phone" value="<?php echo htmlspecialchars($parent_phone); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Register</button>
                    <button type="reset" class="btn-reset"><i class="fas fa-redo"></i> Clear</button>
                </div>
            </form>
            <div class="card-footer">
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        </div>
    </div>
    <script>
        (function(){
            var form = document.getElementById('registerForm');
            if (!form) return;
            form.addEventListener('submit', function(e){
                var full = document.getElementById('full_name').value.trim();
                if (!full) return; // required attribute will handle empty
                var parts = full.split(/\s+/).filter(function(s){return s.length>0});
                if (parts.length !== 3) {
                    e.preventDefault();
                    alert('Please enter your full name with exactly three names (First Middle Last).');
                    document.getElementById('full_name').focus();
                    return false;
                }
            });
        })();
    </script>
</body>
</html>
