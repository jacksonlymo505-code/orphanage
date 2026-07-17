<?php
session_start();
include '../config/database.php';

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    SYSTEM VERIFICATION & TESTING GUIDE                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "DATABASE VERIFICATION:\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";

// Check donors table structure
$result = mysqli_query($conn, "DESCRIBE donors");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[$row['Field']] = $row['Type'];
}
echo "✓ Donors table columns:\n";
echo "  - status: " . $columns['status'] . "\n";
echo "  - approval_status: " . ($columns['approval_status'] ?? 'MISSING') . "\n\n";

// Check pending applications
$pending = mysqli_query($conn, "SELECT COUNT(*) as count FROM donor_applications WHERE status='pending'");
$p = mysqli_fetch_assoc($pending);
echo "✓ Pending applications: " . $p['count'] . "\n\n";

// Show pending applications
echo "Pending Applications Ready to Approve:\n";
echo "─────────────────────────────────────────────────────────────────────────\n";
$apps = mysqli_query($conn, "SELECT id, full_name, email, phone FROM donor_applications WHERE status='pending' LIMIT 3");
$count = 0;
while ($app = mysqli_fetch_assoc($apps)) {
    $count++;
    echo "[$count] Application ID: {$app['id']}\n";
    echo "    Name: {$app['full_name']}\n";
    echo "    Email: {$app['email']}\n";
    echo "    Phone: {$app['phone']}\n\n";
}

echo "\nAPPROVED DONORS (for reference):\n";
echo "─────────────────────────────────────────────────────────────────────────\n";
$approved = mysqli_query($conn, "SELECT id, full_name, email, approval_status, status FROM donors WHERE approval_status='approved' LIMIT 3");
$app_count = mysqli_num_rows($approved);
if ($app_count > 0) {
    $i = 0;
    while ($donor = mysqli_fetch_assoc($approved)) {
        $i++;
        echo "[$i] Donor ID: {$donor['id']}\n";
        echo "    Name: {$donor['full_name']}\n";
        echo "    Email: {$donor['email']}\n";
        echo "    Status: {$donor['status']}\n";
        echo "    Approval: {$donor['approval_status']}\n\n";
    }
} else {
    echo "(No approved donors yet)\n\n";
}

echo "\nSMS LOG STATUS:\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
$sms_log = '../logs/sms.log';
if (file_exists($sms_log)) {
    $content = file_get_contents($sms_log);
    $records = array_filter(explode("\n", trim($content)));
    echo "✓ SMS Log exists at: /logs/sms.log\n";
    echo "✓ Total SMS records: " . count($records) . "\n";
    echo "\nLast SMS record:\n";
    if (count($records) > 0) {
        $last = end($records);
        $data = json_decode($last, true);
        echo "  To: " . $data['to'] . "\n";
        echo "  Body: " . substr($data['body'], 0, 80) . "...\n";
    }
} else {
    echo "✗ SMS Log NOT found\n";
}

echo "\n\nWEB TESTING INSTRUCTIONS:\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "Step 1: Open Admin Panel\n";
echo "  URL: http://localhost/Orphanage/admin/manage_donors.php\n\n";

echo "Step 2: Login\n";
echo "  Email: j@gmail.com\n";
echo "  Password: j300\n\n";

echo "Step 3: Find Pending Application\n";
echo "  Look for pending donor applications in the list\n\n";

echo "Step 4: View Application\n";
echo "  Click 'View' button on a pending application\n";
echo "  Modal opens with application details\n\n";

echo "Step 5: Approve Application\n";
echo "  Click 'Approve & Create Donor' button\n";
echo "  Confirm the popup\n\n";

echo "Step 6: Verify Results\n";
echo "  Modal should close\n";
echo "  Page refreshes\n";
echo "  Success message appears\n";
echo "  Application now shows: ✓ APPROVED\n\n";

echo "Step 7: Verify Database\n";
echo "  Run: SELECT * FROM donors WHERE email='[applicant email]' ORDER BY id DESC LIMIT 1\n";
echo "  Check:\n";
echo "    - approval_status = 'approved'\n";
echo "    - status = 'active'\n";
echo "    - is_active = 1\n";
echo "    - password_hash = [bcrypt hash]\n";
echo "    - date_approved = [current datetime]\n\n";

echo "Step 8: Verify SMS Sent\n";
echo "  Check /logs/sms.log for new entry with:\n";
echo "    - Phone number of applicant\n";
echo "    - Message containing: 'Email: [applicant email]' and 'Password: [10 char hex]'\n\n";

echo "Step 9: Donor Login Test\n";
echo "  URL: http://localhost/Orphanage/donor/donor_dashboard.php\n";
echo "  Username: [applicant email from SMS]\n";
echo "  Password: [temporary password from SMS]\n";
echo "  Expected: Donor dashboard loads successfully\n\n";

echo "TROUBLESHOOTING:\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "Problem: Modal doesn't close after clicking Approve\n";
echo "  Solution: Check browser console for JavaScript errors\n";
echo "  Try: Press F12 to open Developer Tools → Console tab\n\n";

echo "Problem: Success message doesn't appear\n";
echo "  Solution: Check if form was submitted\n";
echo "  Try: Check manage_donors.php for POST errors\n";
echo "  Run: Check browser Network tab to see POST request\n\n";

echo "Problem: SMS doesn't appear in log\n";
echo "  Solution: SMS is in testing mode (logs to file)\n";
echo "  Try: Check /logs/sms.log for new entries\n";
echo "  Check: Verify applicant phone number is correct\n\n";

echo "Problem: Email says \"Failed to send\"\n";
echo "  Solution: XAMPP doesn't have SMTP configured\n";
echo "  Expected: Email function logs error\n";
echo "  To Fix: Configure SMTP in php.ini or use external provider\n\n";

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "Ready to test? Go to: http://localhost/Orphanage/admin/\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";

$conn->close();
?>
