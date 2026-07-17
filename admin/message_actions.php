<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$response = ['success' => false, 'error' => null];

// Handle different actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'view':
        // View message content
        if (isset($_GET['message_id'])) {
            $message_id = (int)$_GET['message_id'];
            
            $query = "SELECT m.*, 
                     CASE 
                         WHEN m.sender_id = ? THEN 'You'
                         ELSE CONCAT(d_sender.first_name, ' ', d_sender.last_name)
                     END as sender_name,
                     CASE 
                         WHEN m.recipient_id = ? THEN 'You'
                         ELSE CONCAT(d_recipient.first_name, ' ', d_recipient.last_name)
                     END as recipient_name,
                     DATE_FORMAT(m.created_at, '%M %d, %Y %h:%i %p') as formatted_date
                     FROM messages m
                     LEFT JOIN donors d_sender ON m.sender_id = d_sender.id
                     LEFT JOIN donors d_recipient ON m.recipient_id = d_recipient.id
                     WHERE m.id = ? AND (m.recipient_id = ? OR m.sender_id = ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiiii", $admin_id, $admin_id, $message_id, $admin_id, $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($message = $result->fetch_assoc()) {
                // Mark message as read if admin is recipient
                if ($message['recipient_id'] == $admin_id && !$message['read_status']) {
                    $update_query = "UPDATE messages SET read_status = 1 WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("i", $message_id);
                    $update_stmt->execute();
                }
                
                echo json_encode($message);
            } else {
                echo json_encode(['error' => 'Message not found']);
            }
        }
        break;

    case 'delete':
        // Delete message
        if (isset($_GET['message_id'])) {
            $message_id = (int)$_GET['message_id'];
            
            // Verify message belongs to admin
            $query = "DELETE FROM messages 
                     WHERE id = ? AND (recipient_id = ? OR sender_id = ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $message_id, $admin_id, $admin_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Error deleting message']);
            }
        }
        break;

    case 'chat_history':
        if (!isset($_GET['recipient_id'])) {
            echo json_encode(['error' => 'Recipient ID is required']);
            exit();
        }
        
        $recipient_id = (int)$_GET['recipient_id'];
        
        try {
            // Mark all messages from this recipient as read
            $update_query = "UPDATE messages SET read_status = 1 
                           WHERE recipient_id = ? AND sender_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $admin_id, $recipient_id);
            $update_stmt->execute();
            
            // Get chat history
            $query = "SELECT m.*, 
                      CASE 
                          WHEN m.sender_id = ? THEN 'You'
                          ELSE CONCAT(IFNULL(d.first_name, ''), ' ', IFNULL(d.last_name, ''))
                      END as sender_name
                      FROM messages m
                      LEFT JOIN donors d ON m.sender_id = d.id
                      WHERE (m.sender_id = ? AND m.recipient_id = ?)
                         OR (m.sender_id = ? AND m.recipient_id = ?)
                      ORDER BY m.created_at ASC";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiiii", $admin_id, $admin_id, $recipient_id, $recipient_id, $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = [
                    'id' => $row['id'],
                    'sender_id' => $row['sender_id'],
                    'recipient_id' => $row['recipient_id'],
                    'sender_name' => $row['sender_name'] ?: 'Unknown',
                    'subject' => $row['subject'],
                    'content' => $row['content'],
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode(['success' => true, 'messages' => $messages]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error fetching chat history: ' . $e->getMessage()]);
        }
        break;

    default:
        // Handle message response submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response'])) {
            $donor_id = (int)$_POST['donor_id'];
            $subject = trim($_POST['subject']);
            $message = trim($_POST['content']);
            
            if (!empty($message)) {
                $query = "INSERT INTO messages (sender_id, recipient_id, subject, content, read_status) 
                         VALUES (?, ?, ?, ?, 0)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiss", $admin_id, $donor_id, $subject, $message);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['error' => 'Error sending message']);
                }
            } else {
                echo json_encode(['error' => 'Message content cannot be empty']);
            }
        } else {
            echo json_encode(['error' => 'Invalid request']);
        }
        break;
}

// Close database connection
$conn->close();
?>
