<?php
session_start();
include 'config/database.php';
include 'config/donor_notifications.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name_raw = trim($_POST['full_name']);
    $full_name = $full_name_raw;
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $support_type = mysqli_real_escape_string($conn, $_POST['support_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $organization_name = mysqli_real_escape_string($conn, $_POST['organization_name']);
    $preferred_contact = mysqli_real_escape_string($conn, $_POST['preferred_contact']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error = 'Please enter a valid email address.';
    } else {
        // check email in donors and existing applications
        $check_donor = mysqli_query($conn, "SELECT id FROM donors WHERE email='$email'");
        $check_app = mysqli_query($conn, "SELECT id FROM donor_applications WHERE email='$email' AND status='pending'");
        if (mysqli_num_rows($check_donor) > 0) {
            $form_error = 'This email is already registered. Please log in or contact admin.';
        } elseif ($check_app && mysqli_num_rows($check_app) > 0) {
            $form_error = 'An application with this email is already pending. Please wait for admin review.';
        } else {
            // ensure donor_applications table exists
            $create_apps = "CREATE TABLE IF NOT EXISTS donor_applications (
                id INT NOT NULL AUTO_INCREMENT,
                full_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                support_type ENUM('one_time','monthly','sponsorship','in_kind','other') DEFAULT 'one_time',
                description TEXT,
                organization_name VARCHAR(255),
                preferred_contact ENUM('email','phone','both') DEFAULT 'both',
                status ENUM('pending','approved','rejected') DEFAULT 'pending',
                notes TEXT,
                reviewed_by INT DEFAULT NULL,
                date_applied TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_reviewed DATETIME DEFAULT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            @mysqli_query($conn, $create_apps);

            $full_name_esc = mysqli_real_escape_string($conn, $full_name);
            $description_esc = mysqli_real_escape_string($conn, $description);
            $org_esc = mysqli_real_escape_string($conn, $organization_name);
            $preferred_esc = mysqli_real_escape_string($conn, $preferred_contact);

            // Inspect donors table columns to build a compatible INSERT
            $cols_res = mysqli_query($conn, "SHOW COLUMNS FROM donors");
            $cols = [];
            while ($c = mysqli_fetch_assoc($cols_res)) {
                $cols[] = $c['Field'];
            }

            // Ensure both naming conventions exist so other pages work reliably
            if (!in_array('full_name', $cols)) {
                @mysqli_query($conn, "ALTER TABLE donors ADD COLUMN full_name VARCHAR(255) DEFAULT NULL");
                $cols[] = 'full_name';
            }
            if (!in_array('first_name', $cols)) {
                @mysqli_query($conn, "ALTER TABLE donors ADD COLUMN first_name VARCHAR(128) DEFAULT NULL");
                $cols[] = 'first_name';
            }
            if (!in_array('last_name', $cols)) {
                @mysqli_query($conn, "ALTER TABLE donors ADD COLUMN last_name VARCHAR(128) DEFAULT NULL");
                $cols[] = 'last_name';
            }

            $fields = [];
            $values = [];

            // Populate both full_name and first_name/last_name where possible
            $parts = preg_split('/\s+/', $full_name_esc, 2);
            $first_name = $parts[0];
            $last_name = isset($parts[1]) ? $parts[1] : '';

            if (in_array('first_name', $cols)) { $fields[] = 'first_name'; $values[] = "'" . mysqli_real_escape_string($conn, $first_name) . "'"; }
            if (in_array('last_name', $cols)) { $fields[] = 'last_name'; $values[] = "'" . mysqli_real_escape_string($conn, $last_name) . "'"; }
            if (in_array('full_name', $cols)) { $fields[] = 'full_name'; $values[] = "'{$full_name_esc}'"; }

            // Add other common fields if they exist
            if (in_array('email', $cols)) { $fields[]='email'; $values[] = "'{$email}'"; }
            if (in_array('phone', $cols)) { $fields[]='phone'; $values[] = "'{$phone}'"; }
            if (in_array('support_type', $cols)) { $fields[]='support_type'; $values[] = "'{$support_type}'"; }
            if (in_array('description', $cols)) { $fields[]='description'; $values[] = "'{$description_esc}'"; }
            if (in_array('organization_name', $cols)) { $fields[]='organization_name'; $values[] = "'{$org_esc}'"; }
            if (in_array('preferred_contact', $cols)) { $fields[]='preferred_contact'; $values[] = "'{$preferred_esc}'"; }
            if (in_array('status', $cols)) { $fields[]='status'; $values[] = "'pending'"; }
            if (in_array('is_active', $cols)) { $fields[]='is_active'; $values[] = "0"; }

            // Insert application into donor_applications table
            $app_insert = mysqli_query($conn, "INSERT INTO donor_applications (full_name, email, phone, support_type, description, organization_name, preferred_contact, status) VALUES ('".mysqli_real_escape_string($conn, $full_name)."', '".$email."', '".$phone."', '".$support_type."', '".$description_esc."', '".$org_esc."', '".$preferred_esc."', 'pending')");

            if ($app_insert) {
                $form_success = 'Application submitted successfully! Your application has been received and will be reviewed by our admin team within 24-48 hours. You will receive an email confirmation shortly.';
                send_admin_new_donor_notification('admin@orphanage.com', $full_name, $email, $support_type);
            } else {
                $form_error = 'An error occurred. Please try again later.';
                error_log('[donor_sponsor] Application insert failed: ' . mysqli_error($conn));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Donor or Sponsor</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
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

        *{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI, sans-serif;}
        body{background:var(--light);color:var(--dark);line-height:1.7;}
        .container{width:90%;max-width:1100px;margin:auto;}
        header{background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.08);padding:18px 0;position:sticky;top:0;z-index:100;}
        nav{display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:24px;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:10px;}
        .nav-links{display:flex;gap:20px;flex-wrap:wrap;}
        .nav-links a{text-decoration:none;color:#12263f;font-weight:600;}
        .nav-links a:hover{color:var(--secondary);} 
        .page{padding:70px 0 90px;}
        .card{background:var(--white);padding:40px;border-radius:20px;box-shadow:0 18px 50px rgba(15,23,42,.08);margin-bottom:30px;border:1px solid rgba(15,23,42,.04);}
        .card.form-card{max-width:680px;margin:36px auto;padding:36px 40px;}
        .card:not(.form-card){max-width:1000px;margin:24px auto;padding:44px 48px;}
        .card h1{font-size:1.95rem;margin-bottom:18px;color:var(--primary);letter-spacing:-0.5px;} 
        .card h3{font-size:1.25rem;color:var(--primary);margin-top:24px;margin-bottom:12px;font-weight:700;}
        .card p{color:var(--text);margin-bottom:16px;font-size:0.98rem;}
        .steps{padding-left:20px;margin:20px 0;}
        .steps li{margin-bottom:14px;color:var(--text);} 
        .highlight{background:#f0f7ff;border-left:4px solid var(--secondary);padding:15px 18px;border-radius:8px;margin:20px 0;}
        .btn{display:inline-block;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:700;margin-top:10px;border:none;cursor:pointer;}
        .btn-primary{background:linear-gradient(90deg,var(--secondary),#1565c0);color:#fff;box-shadow:0 10px 30px rgba(30,136,229,.18);padding:12px 26px;}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 18px 45px rgba(30,136,229,.24);}        
        .btn-secondary{background:#fff;border:2px solid var(--secondary);color:var(--secondary);margin-left:12px;padding:10px 20px;}
        .btn-secondary:hover{background:#f7fbff;}
        
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;margin-bottom:8px;font-weight:600;color:var(--dark);}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:14px 16px;border:1px solid #e6eefc;border-radius:12px;font-size:15px;font-family:inherit;background:#fbfdff;box-shadow:inset 0 1px 2px rgba(15,23,42,.02);transition:all .18s ease;}
        .form-group input::placeholder,.form-group textarea::placeholder{color:#94a3b8;font-size:0.95rem}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--secondary);box-shadow:0 6px 30px rgba(14,114,206,.08);}
        .form-row{display:grid;grid-template-columns:1fr;gap:20px;}
        .form-wrapper{max-width:680px;margin:auto;}
        .form-group textarea{resize:vertical;min-height:140px;}
        .required{color:#d32f2f;}
        footer{background:var(--dark);color:#cbd5e1;padding:30px 0;text-align:center;}
        .success-message{background:#c8e6c9;border-left:4px solid #43a047;padding:15px;border-radius:8px;margin-bottom:20px;color:#1b5e20;}
        .error-message{background:#ffcdd2;border-left:4px solid #d32f2f;padding:15px;border-radius:8px;margin-bottom:20px;color:#b71c1c;}
        @media(max-width:992px){.card:not(.form-card){padding:28px;margin:18px;}.card.form-card{margin:24px 16px;padding:24px;} }
        @media(max-width:768px){nav{flex-direction:column;align-items:flex-start;gap:12px;}.btn-secondary{margin-left:0;margin-top:10px;}.form-row{grid-template-columns:1fr;} .card h1{font-size:1.6rem;} }
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="logo"><i class="fas fa-hand-holding-heart"></i> Become a Donor/Sponsor</div>
            <div class="nav-links">
                <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                <a href="index.php">Home</a>
                <?php endif; ?>
                <a href="contact.php">Contact</a>
                <a href="login.php">Login</a>
            </div>
        </nav>
    </div>
</header>

<section class="page">
    <div class="container">
        <div class="card">
            <h1>How to Become a Donor or Sponsor</h1>
            <p>Thank you for your interest in supporting our children and programs. Your contribution can help provide education, food, medical care, shelter, and emotional support.</p>

            <div class="highlight">
                <strong>Who can become a donor or sponsor?</strong><br>
                Anyone who wishes to support the orphanage through financial help, in-kind donations, or long-term sponsorship can join.
            </div>

            <h3>Step-by-step process</h3>
            <ol class="steps">
                <li>Fill out the application form below with your details and the type of support you want to offer.</li>
                <li>Submit the form and wait for admin approval (typically within 24-48 hours).</li>
                <li>Once approved, you will receive an email with your donor credentials and login instructions.</li>
                <li>Log in to your donor dashboard to track your contributions and impact.</li>
                <li>You can update your profile, view impact reports, and manage your support anytime.</li>
            </ol>
        </div>

        <div class="card form-card">
            <h1>Donor/Sponsor Application Form</h1>
            
            <?php if (!empty($form_error)): ?>
                <div class="error-message"><i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($form_error); ?></div>
            <?php endif; ?>
            <?php if (!empty($form_success)): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($form_success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="organization_name">Organization Name (Optional)</label>
                        <input type="text" id="organization_name" name="organization_name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="support_type">Type of Support <span class="required">*</span></label>
                        <select id="support_type" name="support_type" required>
                            <option value="">Select a support type</option>
                            <option value="one_time">One-time Donation</option>
                            <option value="monthly">Monthly Contribution</option>
                            <option value="sponsorship">Child Sponsorship</option>
                            <option value="in_kind">In-kind Donation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description of Support <span class="required">*</span></label>
                    <textarea id="description" name="description" placeholder="Please describe the type of support you wish to offer, any specific focus areas, or special requests." required></textarea>
                </div>

                <div class="form-group">
                    <label for="preferred_contact">Preferred Contact Method <span class="required">*</span></label>
                    <select id="preferred_contact" name="preferred_contact" required>
                        <option value="both">Both Email and Phone</option>
                        <option value="email">Email only</option>
                        <option value="phone">Phone only</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Submit Application</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>

            <div class="highlight" style="margin-top:30px;">
                <strong>What happens next?</strong><br>
                After submitting, our admin team will review your application and contact you within 24-48 hours. Once approved, you'll receive login credentials to access your donor dashboard where you can track your contributions and impact.
            </div>
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
