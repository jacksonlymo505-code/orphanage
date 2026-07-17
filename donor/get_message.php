<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Message ID is required']);
    exit();
}

$message_id = $_GET['id'];
$donor_id = $_SESSION['user_id'];

// Get the current message
$query = "SELECT m.*, 
          u.first_name as admin_first_name, u.last_name as admin_last_name,
          DATE_FORMAT(m.created_at, '%M %d, %Y %h:%i %p') as formatted_date
          FROM messages m
          LEFT JOIN users u ON m.sender_id = u.id
          WHERE m.id = ? AND (m.recipient_id = ? OR m.sender_id = ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $message_id, $donor_id, $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();

if (!$message) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Message not found']);
    exit();
}

// Mark message as read if donor is the recipient
if ($message['recipient_id'] == $donor_id && !$message['read_status']) {
    $update_query = "UPDATE messages SET read_status = 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $message_id);
    $update_stmt->execute();
    $message['read_status'] = 1;
}

// Get conversation thread
$thread_query = "SELECT m.*, 
                u.first_name as admin_first_name, u.last_name as admin_last_name,
                DATE_FORMAT(m.created_at, '%M %d, %Y %h:%i %p') as formatted_date
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE ((m.sender_id = ? AND m.recipient_id = ?) 
                   OR (m.sender_id = ? AND m.recipient_id = ?))
                AND m.id != ?
                AND m.subject LIKE ?
                ORDER BY m.created_at ASC";

$subject_pattern = '%' . str_replace('Re: ', '', $message['subject']) . '%';
$stmt = $conn->prepare($thread_query);
$stmt->bind_param("iiiiis", 
    $donor_id, $message['sender_id'], 
    $message['sender_id'], $donor_id,
    $message_id, $subject_pattern
);
$stmt->execute();
$thread_result = $stmt->get_result();

$thread = [];
while ($thread_message = $thread_result->fetch_assoc()) {
    $thread[] = $thread_message;
}

// Add thread to message data
$message['thread'] = $thread;

header('Content-Type: application/json');
echo json_encode($message);
?>
