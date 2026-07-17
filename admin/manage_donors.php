<?php
session_start();
include '../config/database.php';
include '../config/donor_notifications.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $post_action = $_POST['action'];
    // application actions: approve_application, reject_application
    if ($post_action === 'approve_application' || $post_action === 'reject_application') {
        $app_id = intval($_POST['application_id']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $reviewed_by = $_SESSION['user_id'];
        $date_reviewed = date('Y-m-d H:i:s');

        $app = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donor_applications WHERE id='$app_id'"));
        if ($app) {
            if ($post_action === 'approve_application') {
                // create donor record with approved status and credentials
                $full_name = mysqli_real_escape_string($conn, $app['full_name']);
                // split name
                $parts = preg_split('/\s+/', $full_name, 2);
                $first_name = mysqli_real_escape_string($conn, $parts[0]);
                $last_name = isset($parts[1]) ? mysqli_real_escape_string($conn, $parts[1]) : '';
                $email = mysqli_real_escape_string($conn, $app['email']);
                $phone = mysqli_real_escape_string($conn, $app['phone']);
                $support_type = mysqli_real_escape_string($conn, $app['support_type']);
                $description = mysqli_real_escape_string($conn, $app['description']);
                $org = mysqli_real_escape_string($conn, $app['organization_name']);
                $preferred = mysqli_real_escape_string($conn, $app['preferred_contact']);

                // generate temp password
                $temp_password = bin2hex(random_bytes(5));
                $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

                // insert into donors table; mark as approved so donor dashboard and approved donor queries work
                $insert = mysqli_query($conn, "INSERT INTO donors (full_name, first_name, last_name, email, phone, support_type, description, organization_name, preferred_contact, status, approval_status, is_active, donor_username, password_hash, date_applied, date_approved, approved_by, notes) VALUES ('".$full_name."', '".$first_name."', '".$last_name."', '".$email."', '".$phone."', '".$support_type."', '".$description."', '".$org."', '".$preferred."', 'approved', 'approved', 1, '".$email."', '".$password_hash."', '".$app['date_applied']."', '".$date_reviewed."', '".$reviewed_by."', '".$notes."')");

                if ($insert) {
                    // ensure a users account exists so approved donors appear in the admin donors list and can login
                    $user_check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1");
                    if ($user_check && mysqli_num_rows($user_check) > 0) {
                        $existing_user = mysqli_fetch_assoc($user_check);
                        mysqli_query($conn, "UPDATE users SET first_name='$first_name', last_name='$last_name', phone='$phone', organization='$org', password='$password_hash', role='donor' WHERE id='".$existing_user['id']."'");
                    } else {
                        mysqli_query($conn, "INSERT INTO users (first_name, last_name, email, phone, organization, password, role) VALUES ('$first_name', '$last_name', '$email', '$phone', '$org', '$password_hash', 'donor')");
                    }

                    // mark application approved
                    mysqli_query($conn, "UPDATE donor_applications SET status='approved', reviewed_by='$reviewed_by', date_reviewed='$date_reviewed', notes='$notes' WHERE id='$app_id'");
                    // send approval email AND SMS with credentials
                    send_donor_approval_email_and_sms($email, $phone, $full_name, $temp_password);
                    $_SESSION['success'] = 'Application approved and donor credentials sent via email and SMS.';
                } else {
                    $_SESSION['error'] = 'Failed to create donor record: ' . mysqli_error($conn);
                }
            } else {
                // reject application
                mysqli_query($conn, "UPDATE donor_applications SET status='rejected', reviewed_by='$reviewed_by', date_reviewed='$date_reviewed', notes='$notes' WHERE id='$app_id'");
                send_donor_rejection_email($app['email'], $app['full_name'], $notes);
                $_SESSION['success'] = 'Application rejected and applicant notified.';
            }
        } else {
            $_SESSION['error'] = 'Application not found.';
        }

        header('Location: manage_donors.php');
        exit();
    }
}

// Determine which date columns exist and choose safe ORDER BY fallbacks
$has_date_applied = false;
$has_date_approved = false;
$has_updated_at = false;
$cols = mysqli_query($conn, "SHOW COLUMNS FROM donors LIKE 'date_applied'");
if ($cols && mysqli_num_rows($cols) > 0) { $has_date_applied = true; }
$cols = mysqli_query($conn, "SHOW COLUMNS FROM donors LIKE 'date_approved'");
if ($cols && mysqli_num_rows($cols) > 0) { $has_date_approved = true; }
$cols = mysqli_query($conn, "SHOW COLUMNS FROM donors LIKE 'updated_at'");
if ($cols && mysqli_num_rows($cols) > 0) { $has_updated_at = true; }

$pending_order = $has_date_applied ? 'date_applied DESC' : ($has_updated_at ? 'updated_at DESC' : 'created_at DESC');
$approved_order = $has_date_approved ? 'date_approved DESC' : ($has_updated_at ? 'updated_at DESC' : 'created_at DESC');
$rejected_order = $approved_order;

// Get approved/rejected applications (from donor_applications table - real form submissions)
$approved = mysqli_query($conn, "SELECT * FROM donor_applications WHERE status='approved' ORDER BY date_reviewed DESC");
$rejected = mysqli_query($conn, "SELECT * FROM donor_applications WHERE status='rejected' ORDER BY date_reviewed DESC");

// Fetch donor applications (if table exists)
$apps_check = mysqli_query($conn, "SHOW TABLES LIKE 'donor_applications'");
$applications = false;
$applications_count = 0;
if ($apps_check && mysqli_num_rows($apps_check) > 0) {
    $applications = mysqli_query($conn, "SELECT * FROM donor_applications WHERE status='pending' ORDER BY date_applied DESC");
    $applications_count = $applications ? mysqli_num_rows($applications) : 0;
}

// Total pending = only form applications (not donor table pending records)
$total_pending = $applications_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donors - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root{--primary:#0f4c81;--secondary:#1e88e5;--dark:#0f172a;--light:#f8fafc;--text:#475569;}
        *{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI, sans-serif;}
        body{background:var(--light);color:var(--dark);}
        .container{width:95%;max-width:1200px;margin:auto;}
        header{background:#fff;padding:20px 0;box-shadow:0 2px 10px rgba(0,0,0,.1);}
        .header-content{display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:22px;font-weight:700;color:var(--primary);}
        nav a{margin-left:20px;color:var(--text);text-decoration:none;}
        nav a:hover{color:var(--secondary);}
        .main{padding:30px 0;}
        .tabs{display:flex;gap:10px;margin-bottom:30px;border-bottom:2px solid #ddd;}
        .tab-btn{padding:12px 20px;background:none;border:none;cursor:pointer;font-size:16px;font-weight:600;color:var(--text);border-bottom:3px solid transparent;transition:.2s;}
        .tab-btn.active{color:var(--secondary);border-color:var(--secondary);}
        .tab-content{display:none;}
        .tab-content.active{display:block;}
        table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.08);}
        th{background:var(--primary);color:#fff;padding:15px;text-align:left;font-weight:600;}
        td{padding:15px;border-bottom:1px solid #eee;}
        tr:hover{background:#f9f9f9;}
        .btn{padding:8px 16px;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;}
        .btn-view{background:var(--secondary);color:#fff;}
        .btn-approve{background:#43a047;color:#fff;}
        .btn-reject{background:#d32f2f;color:#fff;}
        .status-badge{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;}
        .status-pending{background:#fff3cd;color:#856404;}
        .status-approved{background:#d4edda;color:#155724;}
        .status-rejected{background:#f8d7da;color:#721c24;}
        .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;}
        .modal-content{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:30px;border-radius:10px;max-width:500px;width:90%;}
        .modal.active{display:flex;align-items:center;justify-content:center;}
        .close-btn{float:right;font-size:24px;cursor:pointer;color:#aaa;}
        .close-btn:hover{color:var(--dark);}
        .form-group{margin-bottom:15px;}
        .form-group label{display:block;margin-bottom:5px;font-weight:600;}
        .form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-family:inherit;}
        .form-group textarea{resize:vertical;min-height:80px;}
        .success-msg{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px;border-radius:6px;margin-bottom:15px;}
        .empty{text-align:center;padding:30px;color:var(--text);}
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo"><i class="fas fa-hand-holding-heart"></i> Donor Management</div>
            <nav>
                <a href="../admin/dashboard.php">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </div>
</header>

<section class="main">
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab(0)">Pending Applications <span>(<?php echo $applications_count; ?>)</span></button>
            <button class="tab-btn" onclick="switchTab(1)">Approved <span>(<?php echo mysqli_num_rows($approved); ?>)</span></button>
            <button class="tab-btn" onclick="switchTab(2)">Rejected <span>(<?php echo mysqli_num_rows($rejected); ?>)</span></button>
        </div>

        <p style="margin-bottom:20px;color:var(--text);">Review incoming donor/sponsor applications. For approved applications the system will generate a secure temporary password and email credentials to the donor. Use <strong>Resend Credentials</strong> to regenerate and email credentials again if needed.</p>

        <!-- PENDING TAB -->
        <div class="tab-content active">
            <?php if ($applications && mysqli_num_rows($applications) > 0): ?>
                <h4 style="margin-bottom:12px;">📋 Incoming Applications (Form Submissions)</h4>
                <table style="margin-bottom:20px;">
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Support Type</th><th>Applied</th><th>Action</th></tr>
                    <?php while ($app = mysqli_fetch_assoc($applications)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['email']); ?></td>
                        <td><?php echo htmlspecialchars($app['phone']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $app['support_type'])); ?></td>
                        <td><?php echo isset($app['date_applied']) && $app['date_applied'] ? date('M d, Y', strtotime($app['date_applied'])) : 'N/A'; ?></td>
                        <td>
                            <button class="btn btn-view" onclick="viewApplication(<?php echo $app['id']; ?>)">View</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div class="empty"><i class="fas fa-inbox"></i> No pending applications - all applicants have been reviewed!</div>
            <?php endif; ?>
        </div>

        <!-- APPROVED TAB -->
        <div class="tab-content">
            <?php if (mysqli_num_rows($approved) > 0): ?>
                <table>
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Support Type</th><th>Approved Date</th><th>Action</th></tr>
                    <?php while ($row = mysqli_fetch_assoc($approved)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $row['support_type'])); ?></td>
                        <td><?php echo $row['date_reviewed'] ? date('M d, Y', strtotime($row['date_reviewed'])) : 'N/A'; ?></td>
                        <td>
                            <button class="btn btn-view" onclick="viewApplication(<?php echo $row['id']; ?>)">View</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div class="empty"><i class="fas fa-check-circle"></i> No approved applications yet</div>
            <?php endif; ?>
        </div>

        <!-- REJECTED TAB -->
        <div class="tab-content">
            <?php if (mysqli_num_rows($rejected) > 0): ?>
                <table>
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Support Type</th><th>Rejected Date</th><th>Notes</th></tr>
                    <?php while ($row = mysqli_fetch_assoc($rejected)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $row['support_type'])); ?></td>
                        <td><?php echo $row['date_reviewed'] ? date('M d, Y', strtotime($row['date_reviewed'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($row['notes'] ?? '-'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div class="empty"><i class="fas fa-times-circle"></i> No rejected applications</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- MODAL FOR VIEWING/APPROVING DONOR -->
<div class="modal" id="donorModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Donor Application</h2>
        <div id="modalBody"></div>
    </div>
</div>

<script>
function switchTab(index) {
    document.querySelectorAll('.tab-btn').forEach((btn, i) => btn.classList.toggle('active', i === index));
    document.querySelectorAll('.tab-content').forEach((content, i) => content.classList.toggle('active', i === index));
}

function viewDonor(donorId) {
    fetch('get_donor.php?id=' + donorId)
        .then(r => r.text())
        .then(data => {
            document.getElementById('modalBody').innerHTML = data;
            document.getElementById('donorModal').classList.add('active');
        });
}

function viewApplication(appId) {
    fetch('get_application.php?id=' + appId)
        .then(r => r.text())
        .then(data => {
            document.getElementById('modalBody').innerHTML = data;
            document.getElementById('donorModal').classList.add('active');
        });
}

function closeModal() {
    document.getElementById('donorModal').classList.remove('active');
}

function approveDonor(donorId) {
    const notes = document.getElementById('notes-' + donorId).value;
    const form = document.getElementById('donor-form-' + donorId);
    form.querySelector('input[name="action"]').value = 'approve';
    form.querySelector('input[name="notes"]').value = notes;
    form.submit();
}

function rejectDonor(donorId) {
    const notes = document.getElementById('notes-' + donorId).value;
    if (!notes) {
        alert('Please provide a reason for rejection.');
        return;
    }
    const form = document.getElementById('donor-form-' + donorId);
    form.querySelector('input[name="action"]').value = 'reject';
    form.querySelector('input[name="notes"]').value = notes;
    form.submit();
}

// Handle application approval from modal
function approveApplicationFromModal(appId, appEmail, appPhone) {
    if (!confirm('Approve this application?\n\n✓ Donor account will be created\n✓ Credentials sent via email: ' + appEmail + '\n✓ Password sent via SMS: ' + appPhone)) {
        return;
    }
    
    const form = document.getElementById('application-form-' + appId);
    if (!form) {
        alert('Error: Form not found');
        return;
    }
    
    const notes = document.getElementById('notes-app-' + appId).value;
    form.querySelector('input[name="action"]').value = 'approve_application';
    form.querySelector('input[name="notes"]').value = notes;
    form.submit();
}

// Handle application rejection from modal
function rejectApplicationFromModal(appId) {
    const notes = document.getElementById('notes-app-' + appId).value;
    if (!notes) { 
        alert('⚠️ Please provide a reason for rejection.');
        return; 
    }
    
    if (!confirm('Reject this application?\n\n✗ Applicant will be notified via email with reason:\n' + notes)) {
        return;
    }
    
    const form = document.getElementById('application-form-' + appId);
    if (!form) {
        alert('Error: Form not found');
        return;
    }
    
    form.querySelector('input[name="action"]').value = 'reject_application';
    form.querySelector('input[name="notes"]').value = notes;
    form.submit();
}

window.onclick = function(event) {
    const modal = document.getElementById('donorModal');
    if (event.target === modal) closeModal();
}
</script>
</body>
</html>
