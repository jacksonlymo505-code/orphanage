<?php
session_start();
include '../config/database.php';
include '../config/donor_notifications.php';

if (!isset($_SESSION['donor_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../donor/donor_dashboard.php');
    exit();
}

$donor_id = $_SESSION['donor_id'];
$amount = floatval($_POST['amount']);
$contribution_type = mysqli_real_escape_string($conn, $_POST['contribution_type']);
$description = mysqli_real_escape_string($conn, $_POST['description']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'manual');
$transaction_id = mysqli_real_escape_string($conn, $_POST['transaction_id'] ?? '');

// Validate amount
if ($amount <= 0) {
    $_SESSION['error'] = "Invalid amount.";
    header('Location: ../donor/donor_dashboard.php');
    exit();
}

// Insert contribution
$contribution_date = date('Y-m-d H:i:s');
$insert = mysqli_query($conn, "INSERT INTO contributions (donor_id, amount, contribution_date, contribution_type, description, payment_method, transaction_id) 
                              VALUES ('$donor_id', $amount, '$contribution_date', '$contribution_type', '$description', '$payment_method', '$transaction_id')");

if ($insert) {
    // Get donor info for email
    $donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE id='$donor_id'"));
    
    // Send receipt email
    send_contribution_receipt($donor['email'], $donor['full_name'], number_format($amount, 2), date('M d, Y', strtotime($contribution_date)), $transaction_id);
    
    // Send notification to admin
    $admin_email = "admin@orphanage.com";
    $admin_subject = "New Contribution from {$donor['full_name']}";
    $admin_body = "A new contribution has been received.\n\n";
    $admin_body .= "Donor: {$donor['full_name']}\n";
    $admin_body .= "Amount: \$$amount\n";
    $admin_body .= "Type: " . ucfirst(str_replace('_', ' ', $contribution_type)) . "\n";
    $admin_body .= "Date: " . date('M d, Y H:i', strtotime($contribution_date)) . "\n";
    mail($admin_email, $admin_subject, $admin_body);
    
    $_SESSION['success'] = "Contribution recorded successfully! A receipt has been sent to your email.";
} else {
    $_SESSION['error'] = "Error recording contribution. Please try again.";
}

header('Location: ../donor/donor_dashboard.php');
exit();
?>
