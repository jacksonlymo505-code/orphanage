<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
        header("Location: ../login.php");
        exit();
    }

    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $donor_id = $_SESSION['user_id'];

    if (empty($subject) || empty($message)) {
        $_SESSION['error'] = "Subject and message are required.";
        header("Location: messages.php");
        exit();
    }

    // Get admin ID (assuming there's a default admin or you want to send to all admins)
    $query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
    $result = $conn->query($query);
    $admin = $result->fetch_assoc();

    if ($admin) {
        $query = "INSERT INTO messages (sender_id, recipient_id, subject, content) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $donor_id, $admin['id'], $subject, $message);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Message sent successfully!";
        } else {
            $_SESSION['error'] = "Failed to send message. Please try again.";
        }
    } else {
        $_SESSION['error'] = "No admin found to receive the message.";
    }

    header("Location: messages.php");
    exit();
}
