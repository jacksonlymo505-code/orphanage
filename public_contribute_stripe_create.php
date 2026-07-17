<?php
session_start();
require_once 'config/payments.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: public_contribute.php');
    exit();
}

$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$currency = $_POST['currency'] ?? 'TSh';
$phone = trim($_POST['phone'] ?? '');
$method = $_POST['method'] ?? 'card';

if ($amount <= 0) {
    $_SESSION['public_contribute_error'] = 'Invalid amount.';
    header('Location: public_contribute.php');
    exit();
}

// Convert to smallest currency unit for Stripe (assume TSh no decimals, USD cents)
if (strtoupper($currency) === 'USD') {
    $amount_int = (int) round($amount * 100);
} else {
    // keep as integer (e.g., TSh)
    $amount_int = (int) round($amount);
}

// Create a pending public_payment in session so we can match after success
$_SESSION['public_payment'] = [
    'donor_id' => null, // will create guest donor after success if needed
    'amount' => $amount,
    'currency' => $currency,
    'method' => $method,
    'phone' => $phone,
    'created_at' => time(),
];

// Create Stripe Checkout Session via direct API call (no library required)
$secret = STRIPE_SECRET;
if (strpos($secret, 'sk_test_') === false && strpos($secret, 'sk_live_') === false) {
    $_SESSION['public_contribute_error'] = 'Stripe not configured. Set STRIPE_SECRET in config/payments.php';
    header('Location: public_contribute.php');
    exit();
}

$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$success_url = $domain . '/public_contribute_stripe_success.php?session_id={CHECKOUT_SESSION_ID}';
$cancel_url = $domain . '/public_contribute.php';

$post = http_build_query([
    'mode' => 'payment',
    'payment_method_types[]' => 'card',
    'line_items[0][price_data][currency]' => strtolower($currency === 'USD' ? 'usd' : 'tzs'),
    'line_items[0][price_data][product_data][name]' => 'Orphanage contribution',
    'line_items[0][price_data][unit_amount]' => $amount_int,
    'line_items[0][quantity]' => 1,
    'success_url' => $success_url,
    'cancel_url' => $cancel_url,
]);

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 && $code !== 201) {
    $_SESSION['public_contribute_error'] = 'Stripe API error. Response code: ' . $code . '. ' . $resp;
    header('Location: public_contribute.php');
    exit();
}

$data = json_decode($resp, true);
if (!isset($data['url'])) {
    $_SESSION['public_contribute_error'] = 'Unable to create Stripe checkout session.';
    header('Location: public_contribute.php');
    exit();
}

// Redirect to Stripe Checkout
header('Location: ' . $data['url']);
exit();
?>