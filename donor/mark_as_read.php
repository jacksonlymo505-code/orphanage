<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = (int)$_POST['message_id'];
    $donor_id = $_SESSION['user_id'];

    $query = "UPDATE messages SET read_status = 1 
              WHERE id = ? AND recipient_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $message_id, $donor_id);
    $stmt->execute();
}
