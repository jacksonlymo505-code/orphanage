<?php
session_start();
include '../config/database.php';
include '../config/donor_notifications.php';

echo "=== TESTING DONOR APPROVAL PROCESS ===\n\n";

// Get first pending application
$result = mysqli_query($conn, "SELECT * FROM donor_applications WHERE status='pending' LIMIT 1");
$app = mysqli_fetch_assoc($result);

if (!$app) {
    echo "✗ No pending applications to test\n";
    exit;
}

$app_id = $app['id'];
echo "Test Application:\n";
echo "  ID: $app_id\n";
echo "  Name: " . $app['full_name'] . "\n";
echo "  Email: " . $app['email'] . "\n";
echo "  Phone: " . $app['phone'] . "\n";
echo "  Current Status: " . $app['status'] . "\n\n";

// STEP 1: Parse name
echo "STEP 1: Parse Name\n";
$full_name = $app['full_name'];
$parts = preg_split('/\s+/', $full_name, 2);
$first_name = $parts[0];
$last_name = isset($parts[1]) ? $parts[1] : '';
echo "  First: $first_name\n";
echo "  Last: $last_name\n\n";

// STEP 2: Prepare data
echo "STEP 2: Prepare Data\n";
$full_name_esc = mysqli_real_escape_string($conn, $app['full_name']);
$first_name_esc = mysqli_real_escape_string($conn, $first_name);
$last_name_esc = mysqli_real_escape_string($conn, $last_name);
$email = mysqli_real_escape_string($conn, $app['email']);
$phone = mysqli_real_escape_string($conn, $app['phone']);
$support_type = mysqli_real_escape_string($conn, $app['support_type']);
$description = mysqli_real_escape_string($conn, $app['description']);
$org = mysqli_real_escape_string($conn, $app['organization_name']);
$preferred = mysqli_real_escape_string($conn, $app['preferred_contact']);
echo "  ✓ Data prepared and escaped\n\n";

// STEP 3: Generate credentials
echo "STEP 3: Generate Credentials\n";
$temp_password = bin2hex(random_bytes(5));
$password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
echo "  Temp Password: $temp_password\n";
echo "  Hash: " . substr($password_hash, 0, 30) . "...\n\n";

// STEP 4: Create donor record
echo "STEP 4: Insert into donors table\n";
$insert_sql = "INSERT INTO donors (full_name, first_name, last_name, email, phone, support_type, description, organization_name, preferred_contact, status, approval_status, is_active, donor_username, password_hash, date_applied, date_approved, approved_by, notes) VALUES ('$full_name_esc', '$first_name_esc', '$last_name_esc', '$email', '$phone', '$support_type', '$description', '$org', '$preferred', 'active', 'approved', 1, '$email', '$password_hash', '" . $app['date_applied'] . "', NOW(), 1, 'Test approval')";

echo "  SQL: $insert_sql\n\n";

$insert = mysqli_query($conn, $insert_sql);
if ($insert) {
    $new_donor_id = mysqli_insert_id($conn);
    echo "  ✓ Donor record created (ID: $new_donor_id)\n\n";
} else {
    echo "  ✗ FAILED: " . mysqli_error($conn) . "\n\n";
    exit;
}

// STEP 5: Update application status
echo "STEP 5: Update application status\n";
$update_sql = "UPDATE donor_applications SET status='approved', reviewed_by=1, date_reviewed=NOW(), notes='Test approval' WHERE id=$app_id";
echo "  SQL: $update_sql\n";

$update = mysqli_query($conn, $update_sql);
if ($update) {
    echo "  ✓ Application status updated to 'approved'\n\n";
    
    // Verify
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM donor_applications WHERE id=$app_id"));
    echo "  Verified status in DB: " . $check['status'] . "\n\n";
} else {
    echo "  ✗ FAILED: " . mysqli_error($conn) . "\n\n";
    exit;
}

// STEP 6: Send notifications
echo "STEP 6: Send Email & SMS Notifications\n";
echo "  Calling: send_donor_approval_email_and_sms()\n";
echo "  Email: $email\n";
echo "  Phone: $phone\n";
echo "  Name: " . $app['full_name'] . "\n";
echo "  Password: $temp_password\n\n";

send_donor_approval_email_and_sms($email, $phone, $app['full_name'], $temp_password);
echo "  ✓ Notifications sent\n\n";

// STEP 7: Verify SMS log
echo "STEP 7: Check SMS Log\n";
$sms_log = file_get_contents('../logs/sms.log');
$lines = array_filter(explode("\n", trim($sms_log)));
$last_sms = end($lines);
$sms_data = json_decode($last_sms, true);
echo "  Last SMS in log:\n";
echo "  To: " . $sms_data['to'] . "\n";
echo "  Message: " . $sms_data['body'] . "\n\n";

// STEP 8: Verify donor record
echo "STEP 8: Verify Donor Record Created\n";
$donor_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE email='$email' ORDER BY id DESC LIMIT 1"));
if ($donor_check) {
    echo "  ✓ Donor record exists:\n";
    echo "    ID: " . $donor_check['id'] . "\n";
    echo "    Name: " . $donor_check['full_name'] . "\n";
    echo "    Email: " . $donor_check['email'] . "\n";
    echo "    Phone: " . $donor_check['phone'] . "\n";
    echo "    Status: " . ($donor_check['status'] ?: 'NULL') . "\n";
    echo "    Approval Status: " . $donor_check['approval_status'] . "\n";
    echo "    Username: " . $donor_check['donor_username'] . "\n";
    echo "    Active: " . $donor_check['is_active'] . "\n";
    echo "    Date Approved: " . $donor_check['date_approved'] . "\n";
}

echo "\n╔═══════════════════════════════════════════════════╗\n";
echo "║  ✓ MANUAL APPROVAL PROCESS COMPLETED              ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\nSummary:\n";
echo "  Application $app_id: APPROVED\n";
echo "  Donor Account: CREATED\n";
echo "  Email: SENT\n";
echo "  SMS: SENT (logged to /logs/sms.log)\n";
echo "  Status in DB: " . $check['status'] . "\n";

$conn->close();
?>
