<?php
session_start();
require_once 'config/database.php';
require_once 'config/notifications.php';
require_once 'config/helpers.php';

// Ensure database schema is updated
ensure_public_contributions_table_exists();
migrate_public_contributions_table();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: public_contribute.php');
    exit();
}

$payment = $_SESSION['public_payment'] ?? null;
if (!$payment) {
    $_SESSION['public_contribute_error'] = 'No payment in progress.';
    header('Location: public_contribute.php');
    exit();
}

$entered = trim($_POST['otp'] ?? '');
// Re-check expiry server-side
$expires = ($payment['created_at'] + 15*60);
if (time() > $expires) {
    unset($_SESSION['public_payment']);
    $_SESSION['public_contribute_error'] = 'This confirmation code has expired. Please start again.';
    header('Location: public_contribute.php');
    exit();
}

// Initialize attempts if missing
if (!isset($_SESSION['public_payment']['attempts'])) {
    $_SESSION['public_payment']['attempts'] = 0;
}

$entered = preg_replace('/[^0-9]/', '', $entered);
if ($entered === '' || strlen($entered) !== 6) {
    $_SESSION['public_payment']['attempts']++;
    $_SESSION['public_contribute_error'] = 'Invalid confirmation code format.';
    header('Location: public_contribute_verify.php');
    exit();
}

// Disallow common test/placeholder codes to prevent accidental acceptance
$banned = ['123456','000000','111111','222222','333333','444444','555555'];
if (in_array($entered, $banned, true)) {
    $_SESSION['public_payment']['attempts']++;
    $_SESSION['public_contribute_error'] = 'Invalid confirmation code.';
    header('Location: public_contribute_verify.php');
    exit();
}

// block after too many attempts
if ($_SESSION['public_payment']['attempts'] >= 5) {
    unset($_SESSION['public_payment']);
    $_SESSION['public_contribute_error'] = 'Too many invalid attempts. Please try again later.';
    header('Location: public_contribute.php');
    exit();
}

if ($entered !== $payment['otp']) {
    $_SESSION['public_payment']['attempts']++;
    $remaining = max(0, 5 - $_SESSION['public_payment']['attempts']);
    $_SESSION['public_contribute_error'] = 'Invalid confirmation code. Attempts remaining: ' . $remaining;
    header('Location: public_contribute_verify.php');
    exit();
}

// Insert donation as completed
$donor_id = (int)$payment['donor_id'];
$amount = (float)$payment['amount'];
$method = $conn->real_escape_string($payment['method']);
$currency = $conn->real_escape_string($payment['currency']);
$notes = 'Anonymous public contribution';

$stmt = $conn->prepare('INSERT INTO donations (donor_id, project_id, amount, donation_date, payment_method, status, notes, created_at, currency) VALUES (?, NULL, ?, NOW(), ?, "completed", ?, NOW(), ?)');
if (!$stmt) {
    $_SESSION['public_contribute_error'] = 'Database error: ' . $conn->error;
    header('Location: public_contribute_verify.php');
    exit();
}
$stmt->bind_param('idsss', $donor_id, $amount, $method, $notes, $currency);
if (!$stmt->execute()) {
    $_SESSION['public_contribute_error'] = 'Unable to record contribution: ' . $stmt->error;
    $stmt->close();
    header('Location: public_contribute_verify.php');
    exit();
}
$donation_id = $conn->insert_id;
$stmt->close();

// Create public contribution record with tracking info
require_once 'config/helpers.php';
create_public_contribution_record([
    'donor_id' => $donor_id,
    'phone' => $payment['phone'],
    'amount' => $amount,
    'currency' => $currency,
    'payment_method' => $method,
    'status' => 'completed',
    'transaction_id' => generate_public_contribution_ref(),
    'source' => 'public',
    'notes' => 'Public contribution via OTP verification - ' . $notes,
    'completed_at' => date('Y-m-d H:i:s'),
    'otp_sent_at' => isset($payment['otp_sent_at']) ? $payment['otp_sent_at'] : null,
    'otp_verified_at' => date('Y-m-d H:i:s'),
    'payment_started_at' => isset($payment['created_at']) ? date('Y-m-d H:i:s', $payment['created_at']) : null,
    'payment_attempts' => (isset($payment['attempts']) ? $payment['attempts'] : 0) + 1,
    'device_type' => detect_device_type(),
]);

// Send thank-you SMS
$phone = $payment['phone'];
$thank = "Thank you for your contribution of $currency $amount to our orphanage. Your support makes a real difference. We appreciate your generosity and hope to see you again soon.";
send_sms_message($phone, $thank);

// Optionally notify admin via SMS
$admin_notice = "New public contribution: $currency $amount received from $phone.";
send_sms_message(ADMIN_PHONE, $admin_notice);

// Clear session payment
unset($_SESSION['public_payment']);

// Show simple confirmation page
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Contribution Complete</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:#f8fafc} .container{max-width:720px;margin:36px auto;padding:18px} .card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Thank you</h2>
        <p>Your contribution of <?php echo htmlspecialchars($currency); ?> <?php echo number_format($amount,2); ?> has been received. A confirmation SMS has been sent to <?php echo htmlspecialchars($phone); ?>. We appreciate your generosity.</p>
        <p><a href="index.php">Return to Home</a></p>
    </div>
</div>
</body>
</html>
