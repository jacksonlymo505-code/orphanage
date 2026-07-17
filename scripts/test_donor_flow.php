<?php
// Test script: inserts a sample donor application and runs approval flow.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/donor_notifications.php';

echo "Starting donor flow test...\n";

$email = 'test+bot@example.com';
$full_name = 'Automated Test Donor';
$phone = '+255700000000';
$support_type = 'one_time';
$description = 'Test application inserted by automated test script.';
$org = 'TestOrg';
$preferred = 'email';

// Ensure donor_applications table exists (same DDL used in donor_sponsor.php)
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
mysqli_query($conn, $create_apps) or die('Failed to ensure donor_applications: ' . mysqli_error($conn));

// Clean up any previous test rows with this email for idempotence
mysqli_query($conn, "DELETE FROM donor_applications WHERE email='".mysqli_real_escape_string($conn, $email)."'");
mysqli_query($conn, "DELETE FROM donors WHERE email='".mysqli_real_escape_string($conn, $email)."'");

// Insert application
$stmt = mysqli_prepare($conn, "INSERT INTO donor_applications (full_name, email, phone, support_type, description, organization_name, preferred_contact, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
mysqli_stmt_bind_param($stmt, 'sssssss', $full_name, $email, $phone, $support_type, $description, $org, $preferred);
if (!mysqli_stmt_execute($stmt)) {
    echo "Failed to insert application: " . mysqli_error($conn) . "\n";
    exit(1);
}
$app_id = mysqli_insert_id($conn);
echo "Inserted application id=$app_id for $email\n";

// Simulate admin approval: create donor record
$date_reviewed = date('Y-m-d H:i:s');
$reviewed_by = 1;
$temp_password = bin2hex(random_bytes(5));
$password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

$cols_res = mysqli_query($conn, "SHOW COLUMNS FROM donors");
$cols = [];
while ($c = mysqli_fetch_assoc($cols_res)) { $cols[] = $c['Field']; }

$fields = [];
$values = [];

// name
$parts = preg_split('/\s+/', $full_name, 2);
$first_name = $parts[0];
$last_name = isset($parts[1]) ? $parts[1] : '';
if (in_array('first_name', $cols)) { $fields[] = 'first_name'; $values[] = "'".mysqli_real_escape_string($conn, $first_name)."'"; }
if (in_array('last_name', $cols)) { $fields[] = 'last_name'; $values[] = "'".mysqli_real_escape_string($conn, $last_name)."'"; }
if (in_array('full_name', $cols)) { $fields[] = 'full_name'; $values[] = "'".mysqli_real_escape_string($conn, $full_name)."'"; }

// common fields
if (in_array('email', $cols)) { $fields[]='email'; $values[] = "'".mysqli_real_escape_string($conn, $email)."'"; }
if (in_array('phone', $cols)) { $fields[]='phone'; $values[] = "'".mysqli_real_escape_string($conn, $phone)."'"; }
if (in_array('support_type', $cols)) { $fields[]='support_type'; $values[] = "'".mysqli_real_escape_string($conn, $support_type)."'"; }
if (in_array('description', $cols)) { $fields[]='description'; $values[] = "'".mysqli_real_escape_string($conn, $description)."'"; }
if (in_array('organization_name', $cols)) { $fields[]='organization_name'; $values[] = "'".mysqli_real_escape_string($conn, $org)."'"; }
if (in_array('preferred_contact', $cols)) { $fields[]='preferred_contact'; $values[] = "'".mysqli_real_escape_string($conn, $preferred)."'"; }
if (in_array('status', $cols)) { $fields[]='status'; $values[] = "'approved'"; }
if (in_array('is_active', $cols)) { $fields[]='is_active'; $values[] = "1"; }
if (in_array('donor_username', $cols)) { $fields[]='donor_username'; $values[] = "'".mysqli_real_escape_string($conn, $email)."'"; }
if (in_array('password_hash', $cols)) { $fields[]='password_hash'; $values[] = "'".mysqli_real_escape_string($conn, $password_hash)."'"; }
if (in_array('date_applied', $cols)) { $fields[]='date_applied'; $values[] = "NOW()"; }
if (in_array('date_approved', $cols)) { $fields[]='date_approved'; $values[] = "'".$date_reviewed."'"; }
if (in_array('approved_by', $cols)) { $fields[]='approved_by'; $values[] = (int)$reviewed_by; }
if (in_array('notes', $cols)) { $fields[]='notes'; $values[] = "'Approved by automated test'"; }

if (count($fields) === 0) {
    echo "No compatible donor columns found to insert.\n";
    exit(2);
}

$insert_sql = "INSERT INTO donors (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
if (!mysqli_query($conn, $insert_sql)) {
    echo "Failed to insert donor: " . mysqli_error($conn) . "\n";
    exit(2);
}
$donor_id = mysqli_insert_id($conn);
echo "Created donor id=$donor_id with temp password $temp_password\n";

// Update application status
mysqli_query($conn, "UPDATE donor_applications SET status='approved', reviewed_by='$reviewed_by', date_reviewed='$date_reviewed', notes='Approved by automated test' WHERE id='$app_id'");

// Output summary
$app = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donor_applications WHERE id='$app_id'"));
$donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE id='$donor_id'"));

echo "\nApplication record:\n";
print_r($app);
echo "\nDonor record:\n";
print_r($donor);

echo "\nTest completed.\n";

?>