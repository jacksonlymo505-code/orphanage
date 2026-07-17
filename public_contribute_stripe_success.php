<?php
session_start();
require_once 'config/payments.php';
require_once 'config/database.php';
require_once 'config/helpers.php';

// Ensure database schema is updated
ensure_public_contributions_table_exists();
migrate_public_contributions_table();

// After successful Stripe checkout, Stripe redirects here with session_id
$session_id = $_GET['session_id'] ?? null;
if (!$session_id) {
    $_SESSION['public_contribute_error'] = 'Missing session id.';
    header('Location: public_contribute.php');
    exit();
}

$secret = STRIPE_SECRET;
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($session_id));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($code !== 200) {
    $_SESSION['public_contribute_error'] = 'Unable to verify payment session.';
    header('Location: public_contribute.php');
    exit();
}
$data = json_decode($resp, true);
if (!isset($data['payment_status']) || $data['payment_status'] !== 'paid') {
    $_SESSION['public_contribute_error'] = 'Payment not completed.';
    header('Location: public_contribute.php');
    exit();
}

// Create donor if needed
$phone = $_SESSION['public_payment']['phone'] ?? '';
$amount = $_SESSION['public_payment']['amount'] ?? 0;
$currency = $_SESSION['public_payment']['currency'] ?? 'TSh';

$donor_id = null;
if ($phone) {
    $stmt = $conn->prepare('SELECT id FROM donors WHERE phone = ? LIMIT 1');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $donor_id = (int)$row['id'];
    $stmt->close();
}
if (!$donor_id) {
    $guest_email = 'guest+' . time() . rand(1000,9999) . '@example.com';
    $first = 'Guest'; $last = substr($phone ?: '0', -6);
    $full = 'Guest Donor';
    $stmt = $conn->prepare('INSERT INTO donors (first_name,last_name,email,phone,type,status,created_at,full_name) VALUES (?, ?, ?, ?, "donor", "active", NOW(), ?)');
    $stmt->bind_param('sssss', $first, $last, $guest_email, $phone, $full);
    $stmt->execute();
    $donor_id = $conn->insert_id;
    $stmt->close();
}

// record donation
$stmt = $conn->prepare('INSERT INTO donations (donor_id, project_id, amount, donation_date, payment_method, status, notes, created_at, currency) VALUES (?, NULL, ?, NOW(), ?, "completed", ?, NOW(), ?)');
$notes = 'Public contribution via Stripe';
$method = 'card';
$stmt->bind_param('idsss', $donor_id, $amount, $method, $notes, $currency);
$stmt->execute();
$donation_id = $conn->insert_id;
$stmt->close();

// Also create public contribution record for tracking
create_public_contribution_record([
    'donor_id' => $donor_id,
    'phone' => $phone,
    'amount' => $amount,
    'currency' => $currency,
    'payment_method' => 'card',
    'status' => 'completed',
    'transaction_id' => $session_id,
    'source' => 'stripe',
    'notes' => 'Public contribution via Stripe card payment',
    'completed_at' => date('Y-m-d H:i:s'),
    'payment_started_at' => isset($_SESSION['public_payment']['created_at']) ? date('Y-m-d H:i:s', $_SESSION['public_payment']['created_at']) : date('Y-m-d H:i:s'),
    'payment_attempts' => 1,
    'device_type' => detect_device_type(),
]);

// send thank you
require_once 'config/notifications.php';
$thank = "Thank you for your contribution of $currency $amount to our orphanage via card. We appreciate your support.";
send_sms_message($phone, $thank);
send_sms_message(ADMIN_PHONE, "New public Stripe contribution: $currency $amount from $phone.");

unset($_SESSION['public_payment']);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Payment Complete</title></head><body>
<h2>Thank you</h2>
<p>Your card payment was successful. A confirmation SMS has been sent.</p>
<p><a href="index.php">Return to home</a></p>
</body></html>