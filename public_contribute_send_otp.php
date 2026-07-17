<?php
session_start();
require_once 'config/database.php';
require_once 'config/notifications.php';
require_once 'config/helpers.php';
require_once 'config/rate_limiter.php';

// Ensure database schema is updated
ensure_public_contributions_table_exists();
migrate_public_contributions_table();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: public_contribute.php');
    exit();
}

$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$currency = $_POST['currency'] ?? 'TSh';
$method = $_POST['method'] ?? 'mobile_money';
$phone = trim($_POST['phone'] ?? '');

if ($amount <= 0 || $phone === '') {
    $_SESSION['public_contribute_error'] = 'Please provide a valid amount and phone number.';
    header('Location: public_contribute.php');
    exit();
}

// Basic phone normalization
$phone = preg_replace('/\s+/', '', $phone);

// Find or create guest donor record using phone
$donor_id = null;
$stmt = $conn->prepare('SELECT id FROM donors WHERE phone = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $donor_id = (int)$row['id'];
    }
    $stmt->close();
}

if (!$donor_id) {
    $guest_email = 'guest+' . time() . rand(1000,9999) . '@example.com';
    $first = 'Guest';
    $last = substr($phone, -6);
    $stmt = $conn->prepare('INSERT INTO donors (first_name,last_name,email,phone,type,status,created_at,full_name) VALUES (?, ?, ?, ?, "donor", "active", NOW(), ?)');
    if ($stmt) {
        $full_name = 'Guest Donor';
        $stmt->bind_param('sssss', $first, $last, $guest_email, $phone, $full_name);
        $stmt->execute();
        $donor_id = $conn->insert_id;
        $stmt->close();
    }
}

if (!$donor_id) {
    $_SESSION['public_contribute_error'] = 'Unable to create donation record. Please try again later.';
    header('Location: public_contribute.php');
    exit();
}

// Rate limiting: allow max 3 sends per 15 minutes per phone, and 10 per IP per hour
$phone_key = 'phone_' . $phone;
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip_key = 'ip_' . $ip;
if (!rl_allow($phone_key, 15*60, 3)) {
    $_SESSION['public_contribute_error'] = 'Too many OTP requests for this phone. Try again later.';
    header('Location: public_contribute.php');
    exit();
}
if (!rl_allow($ip_key, 60*60, 10)) {
    $_SESSION['public_contribute_error'] = 'Too many OTP requests from this IP. Try again later.';
    header('Location: public_contribute.php');
    exit();
}

// If payment method is not card, auto-complete the donation (mark as completed)
if ($method !== 'card') {
    $donor_id_i = (int)$donor_id;
    $amount_i = (float)$amount;
    $method_i = $conn->real_escape_string($method);
    $currency_i = $conn->real_escape_string($currency);
    $notes_i = 'Anonymous public contribution (auto-complete)';

    $stmt = $conn->prepare('INSERT INTO donations (donor_id, project_id, amount, donation_date, payment_method, status, notes, created_at, currency) VALUES (?, NULL, ?, NOW(), ?, "completed", ?, NOW(), ?)');
    if ($stmt) {
        $stmt->bind_param('idsss', $donor_id_i, $amount_i, $method_i, $notes_i, $currency_i);
        $stmt->execute();
        $donation_id = $conn->insert_id;
        $stmt->close();

        create_public_contribution_record([
            'donor_id' => $donor_id_i,
            'phone' => $phone,
            'amount' => $amount_i,
            'currency' => $currency_i,
            'payment_method' => $method_i,
            'status' => 'completed',
            'transaction_id' => generate_public_contribution_ref(),
            'source' => 'public',
            'notes' => $notes_i,
            'completed_at' => date('Y-m-d H:i:s'),
            'payment_started_at' => date('Y-m-d H:i:s'),
            'payment_attempts' => 1,
            'device_type' => detect_device_type(),
        ]);

        // Send thank-you SMS
        $thank = "Thank you for your contribution of $currency_i $amount_i to our orphanage. We have received your payment and appreciate your support.";
        send_sms_message($phone, $thank);
        send_sms_message(ADMIN_PHONE, "New public contribution auto-completed: $currency_i $amount_i from $phone.");

        $_SESSION['public_contribute_success'] = 'Contribution recorded successfully.';
        header('Location: public_contribute_done.php');
        exit();
    } else {
        $_SESSION['public_contribute_error'] = 'Database error recording contribution: ' . $conn->error;
        header('Location: public_contribute.php');
        exit();
    }
}

$payment = $_SESSION['public_payment'] ?? null;
if (!$payment) {
    header('Location: public_contribute.php');
    exit();
}

// Ensure database schema is updated
ensure_public_contributions_table_exists();
migrate_public_contributions_table();

$expires = ($payment['created_at'] + 15*60);

$otp = random_int(100000, 999999);

// Rate limit by phone and IP: max 3 sends per 15 minutes per phone, 10 per hour per IP
$phone_key = 'phone_' . $phone;
$ip_key = 'ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!rl_allow($phone_key, 15*60, 3)) {
    $_SESSION['public_contribute_error'] = 'Too many OTP requests for this phone. Try again later.';
    header('Location: public_contribute.php');
    exit();
}
if (!rl_allow($ip_key, 60*60, 10)) {
    $_SESSION['public_contribute_error'] = 'Too many OTP requests from your network. Try again later.';
    header('Location: public_contribute.php');
    exit();
}

// Store pending payment in session (short lived)
$_SESSION['public_payment'] = [
    'donor_id' => $donor_id,
    'amount' => $amount,
    'currency' => $currency,
    'method' => $method,
    'phone' => $phone,
    'otp' => (string)$otp,
    'created_at' => time(),
    'attempts' => 0
];

// Send SMS with OTP
$sms_text = "Your Orphanage payment confirmation code is: $otp. Enter this code to complete your contribution of $currency $amount. This code expires in 15 minutes.";
$sms_res = send_sms_message($phone, $sms_text);

// Log or show error for local testing
if (!$sms_res['success']) {
    // allow flow to continue but set a warning
    $_SESSION['public_contribute_warning'] = 'Could not deliver SMS: ' . $sms_res['message'];
}

header('Location: public_contribute_verify.php');
exit();
?>