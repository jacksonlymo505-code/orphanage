<?php
session_start();
include '../config/database.php';
include '../config/donor_notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donor_id = intval($_POST['donor_id']);
    $donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, email, full_name FROM donors WHERE id='$donor_id' AND status='approved'"));
    if (!$donor) {
        $_SESSION['success'] = 'Donor not found or not approved.';
        header('Location: manage_donors.php');
        exit();
    }

    // generate new temporary password
    $temp_password = bin2hex(random_bytes(5));
    $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

    $update = mysqli_query($conn, "UPDATE donors SET password_hash='$password_hash' WHERE id='$donor_id'");
    if ($update) {
        send_donor_approval_email($donor['email'], $donor['full_name'], $temp_password);
        $_SESSION['success'] = 'Credentials regenerated and emailed to the donor.';
    } else {
        $_SESSION['success'] = 'Failed to regenerate credentials.';
    }
}

header('Location: manage_donors.php');
exit();
