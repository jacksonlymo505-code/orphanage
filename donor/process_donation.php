<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
    $donor_id = null;
    
    // Find donor record from logged in user's email
    $user_id = (int)$_SESSION['user_id'];
    $result = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $user_email = $row['email'];
        $email_esc = $conn->real_escape_string($user_email);
        $donor_result = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' LIMIT 1");
        if ($donor_result && $donor_row = $donor_result->fetch_assoc()) {
            $donor_id = (int)$donor_row['id'];
        }
    }

    if ($amount <= 0) {
        $_SESSION['error'] = "Please enter a valid amount.";
        header('Location: donations.php');
        exit();
    }

    if ($donor_id === null) {
        $_SESSION['error'] = "Unable to identify your donor profile. Please contact support.";
        header('Location: donations.php');
        exit();
    }

    try {
        // Insert donation into database
        $query = "INSERT INTO donations (donor_id, amount, currency, payment_method, status, created_at) 
                 VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("idss", $donor_id, $amount, $currency, $payment_method);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Donation submitted successfully!";
        } else {
            throw new Exception("Error processing donation");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error processing donation: " . $e->getMessage();
    }
    
    header('Location: donations.php');
    exit();
} else {
    // If not POST request, redirect to donations page
    header('Location: donations.php');
    exit();
}
?> 