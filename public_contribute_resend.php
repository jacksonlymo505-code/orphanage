<?php
session_start();
require_once 'config/database.php';
require_once 'config/notifications.php';

$payment = $_SESSION['public_payment'] ?? null;
if (!$payment) {
    header('Location: public_contribute.php');
    exit();
}

// regenerate otp
$otp = random_int(100000, 999999);
$_SESSION['public_payment']['otp'] = (string)$otp;
$_SESSION['public_payment']['created_at'] = time();
// reset attempts on resend
$_SESSION['public_payment']['attempts'] = 0;

$sms_text = "Your Orphanage payment confirmation code is: $otp. Enter this code to complete your contribution.";
$sms_res = send_sms_message($payment['phone'], $sms_text);
if (!$sms_res['success']) {
    $_SESSION['public_contribute_warning'] = 'Could not deliver SMS: ' . $sms_res['message'];
}

header('Location: public_contribute_verify.php');
exit();
?>