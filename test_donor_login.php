<?php
require_once 'config/database.php';

$email = 'ha@gmail.com';
$password = '6582e265ad';

$donorCheck = $conn->prepare('SELECT id, full_name, password_hash, status, approval_status FROM donors WHERE email = ? LIMIT 1');
$donorCheck->bind_param('s', $email);
$donorCheck->execute();
$donorResult = $donorCheck->get_result();
$donor = $donorResult->fetch_assoc();

echo "TEST DONOR LOGIN\n";
echo "===============\n";

if ($donor) {
    echo "Donor found: " . $donor['full_name'] . "\n";
    echo "Status: " . $donor['status'] . "\n";
    echo "Approval Status: " . ($donor['approval_status'] ?? 'NULL') . "\n";
    echo "Password Hash: " . substr($donor['password_hash'], 0, 30) . "...\n";
    
    $verify = password_verify($password, $donor['password_hash']);
    echo "Password Verification: " . ($verify ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($donor && ($donor['status'] === 'active' || $donor['approval_status'] === 'approved') && $verify) {
        echo "\n✅ LOGIN SHOULD SUCCEED\n";
    } else {
        echo "\n❌ LOGIN SHOULD FAIL\n";
        echo "Reasons:\n";
        if (!($donor['status'] === 'active' || $donor['approval_status'] === 'approved')) {
            echo "- Status check failed (status='$donor[status]', approval_status='$donor[approval_status]')\n";
        }
        if (!$verify) {
            echo "- Password verification failed\n";
        }
    }
} else {
    echo "❌ Donor not found with email: $email\n";
}
?>
