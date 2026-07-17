<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['donor_id'])) {
    header('Location: ../login.php');
    exit();
}

$donor_id = $_SESSION['donor_id'];
$donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE id='$donor_id'"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $organization_name = mysqli_real_escape_string($conn, $_POST['organization_name']);
    $preferred_contact = mysqli_real_escape_string($conn, $_POST['preferred_contact']);
    
    $update = mysqli_query($conn, "UPDATE donors SET full_name='$full_name', phone='$phone', organization_name='$organization_name', preferred_contact='$preferred_contact' WHERE id='$donor_id'");
    
    if ($update) {
        $success = "Profile updated successfully!";
        $donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE id='$donor_id'"));
    } else {
        $error = "Error updating profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root{--primary:#0f4c81;--secondary:#1e88e5;--dark:#0f172a;--light:#f8fafc;--text:#475569;}
        *{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI, sans-serif;}
        body{background:var(--light);color:var(--dark);}
        .container{width:95%;max-width:800px;margin:auto;}
        header{background:#fff;padding:20px 0;box-shadow:0 2px 10px rgba(0,0,0,.1);margin-bottom:40px;}
        .header-content{display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:22px;font-weight:700;color:var(--primary);}
        nav a{margin-left:20px;color:var(--text);text-decoration:none;font-weight:600;}
        nav a:hover{color:var(--secondary);}
        .card{background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);}
        .card h1{color:var(--primary);margin-bottom:25px;}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;margin-bottom:8px;font-weight:600;color:var(--dark);}
        .form-group input,.form-group select{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit;}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:var(--secondary);box-shadow:0 0 0 3px rgba(30,136,229,.1);}
        .form-group textarea{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;resize:vertical;min-height:100px;}
        .btn{padding:12px 24px;background:var(--secondary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:14px;}
        .btn:hover{background:#1565c0;}
        .btn-secondary{background:#ccc;color:#333;margin-left:10px;}
        .btn-secondary:hover{background:#aaa;}
        .success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px;border-radius:6px;margin-bottom:20px;}
        .error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;border-radius:6px;margin-bottom:20px;}
        footer{background:var(--dark);color:#cbd5e1;padding:30px 0;text-align:center;margin-top:50px;}
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo"><i class="fas fa-hand-holding-heart"></i> Update Profile</div>
            <nav>
                <a href="donor_dashboard.php">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </div>
</header>

<section style="padding:40px 0;">
    <div class="container">
        <div class="card">
            <h1>Update Your Profile</h1>
            
            <?php if (isset($success)): ?>
                <div class="success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><i class="fas fa-times-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email (Cannot be changed)</label>
                    <input type="email" value="<?php echo htmlspecialchars($donor['email']); ?>" disabled style="background:#f5f5f5;">
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($donor['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($donor['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="organization_name">Organization Name (Optional)</label>
                    <input type="text" id="organization_name" name="organization_name" value="<?php echo htmlspecialchars($donor['organization_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="preferred_contact">Preferred Contact Method</label>
                    <select id="preferred_contact" name="preferred_contact">
                        <option value="both" <?php echo $donor['preferred_contact'] === 'both' ? 'selected' : ''; ?>>Both Email and Phone</option>
                        <option value="email" <?php echo $donor['preferred_contact'] === 'email' ? 'selected' : ''; ?>>Email only</option>
                        <option value="phone" <?php echo $donor['preferred_contact'] === 'phone' ? 'selected' : ''; ?>>Phone only</option>
                    </select>
                </div>

                <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
                <a href="donor_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </form>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <p>© 2026 Orphanage Management System. All Rights Reserved.</p>
    </div>
</footer>
</body>
</html>
