<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

// Initialize data array
$data = [
    'messages' => [],
    'unread_count' => 0,
    'pagination' => [
        'current_page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
        'total_pages' => 1,
        'has_previous' => false,
        'has_next' => false
    ]
];

// Fetch all messages with admin information
$query = "SELECT m.*, 
          u.first_name as admin_first_name, u.last_name as admin_last_name,
          DATE_FORMAT(m.created_at, '%M %d, %Y %h:%i %p') as formatted_date
          FROM messages m
          LEFT JOIN users u ON m.sender_id = u.id
          WHERE (m.recipient_id = ? OR m.sender_id = ?)
          ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$donor_id = $_SESSION['user_id'];
if (!$stmt->bind_param("ii", $donor_id, $donor_id)) {
    die("Error binding parameters: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result === false) {
    die("Error getting result: " . $stmt->error);
}

// Process messages
while ($message = $result->fetch_assoc()) {
    $data['messages'][] = $message;
    if (!$message['read_status'] && $message['recipient_id'] == $donor_id) {
        $data['unread_count']++;
    }
}

// Calculate pagination
$items_per_page = 10;
$total_messages = count($data['messages']);
$data['pagination']['total_pages'] = ceil($total_messages / $items_per_page);
$data['pagination']['has_previous'] = $data['pagination']['current_page'] > 1;
$data['pagination']['has_next'] = $data['pagination']['current_page'] < $data['pagination']['total_pages'];

// Slice messages for current page
$start = ($data['pagination']['current_page'] - 1) * $items_per_page;
$data['messages'] = array_slice($data['messages'], $start, $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Donor Dashboard</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            margin-top: 20px;
        }

        .menu-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .menu-item.active {
            background: var(--secondary-color);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Updated Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Card Styles */
        .card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0 !important;
        }

        .card-header h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }

        /* Message List Styles */
        .message-list {
            max-height: 650px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--secondary-color) #f0f0f0;
        }

        .message-list::-webkit-scrollbar {
            width: 6px;
        }

        .message-list::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        .message-list::-webkit-scrollbar-thumb {
            background-color: var(--secondary-color);
            border-radius: 3px;
        }

        .message-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .message-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .message-item.unread {
            background: #f0f7ff;
            border-left: 4px solid var(--secondary-color);
        }

        .message-item h6 {
            color: var(--primary-color);
            font-weight: 600;
        }

        .message-preview {
            color: #666;
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 5px;
        }

        /* Button Styles */
        .btn-primary {
            background: var(--secondary-color);
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.2);
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .alert-danger {
            background: #fde8e8;
            color: #c81e1e;
        }

        /* Page Title */
        h1.mt-4 {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .container-fluid {
                padding: 10px;
            }

            .card {
                margin-bottom: 15px;
            }
        }

        /* Add these styles to your existing CSS */
        .message-content {
            min-height: 400px;
        }

        .message-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .message-body {
            padding: 20px 0;
            white-space: pre-wrap;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 20px 25px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            border-color: var(--secondary-color);
        }

        .message-item.active {
            background: #f0f7ff;
            border-left: 4px solid var(--secondary-color);
        }

        .message-thread {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .thread-message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            max-width: 80%;
        }

        .thread-message.sent {
            background: #e3f2fd;
            margin-left: auto;
        }

        .thread-message.received {
            background: #f5f5f5;
        }

        .thread-message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .thread-message-content {
            white-space: pre-wrap;
        }

        .message-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
     <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mt-4">Messages</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                    <i class="fas fa-pen"></i> Compose
                </button>
            </div>

            <div class="row">
                <!-- Message List -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Inbox (<?php echo intval($data['unread_count']); ?> unread)</h5>
                        </div>
                        <div class="card-body p-0 message-list">
                            <div class="list-group list-group-flush">
                                <?php if (!empty($data['messages'])): ?>
                                    <?php foreach ($data['messages'] as $message): ?>
                                        <div class="list-group-item message-item <?php echo !$message['read_status'] ? 'unread' : ''; ?>" 
                                             data-id="<?php echo $message['id']; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-1">
                                                    <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 
                                                        'To: Admin' :
                                                        'From: Admin'; ?>
                                                </h6>
                                                <small><?php echo date('M d', strtotime($message['created_at'])); ?></small>
                                            </div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                            <p class="mb-1 message-preview"><?php echo htmlspecialchars($message['content']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="list-group-item">
                                        <p class="mb-0 text-center">No messages found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Content -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Message Content</h5>
                        </div>
                        <div class="card-body">
                            <div id="messageContent" class="message-content">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-envelope-open-text fa-3x mb-3"></i>
                                    <p>Select a message to view its content</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compose Message Modal -->
            <div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="composeModalLabel">Compose Message</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="send_message.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" id="recipient_id" name="recipient_id" value="">
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this before closing body tag -->
    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle message item clicks
        const messageItems = document.querySelectorAll('.message-item');
        messageItems.forEach(item => {
            item.addEventListener('click', function() {
                const messageId = this.dataset.id;
                // Remove active class from all items
                messageItems.forEach(i => i.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');
                // Mark as read
                fetch('mark_as_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message_id=' + messageId
                });
                // Load message content
                loadMessageContent(messageId);
            });
        });

        function loadMessageContent(messageId) {
            fetch('get_message.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    const contentDiv = document.getElementById('messageContent');
                    contentDiv.innerHTML = `
                        <div class="message-header mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4>${data.subject}</h4>
                                <div class="message-actions">
                                    <button class="btn btn-outline-primary btn-sm" onclick="showReplyModal('${data.subject}', '${data.sender_id}')">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm ms-2" onclick="deleteMessage(${messageId})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div>
                                    <strong>${data.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'To: Admin' : 'From: Admin'}</strong>
                                </div>
                                <small class="text-muted">${data.formatted_date}</small>
                            </div>
                        </div>
                        <div class="message-body">
                            ${data.content}
                        </div>
                        <div class="message-thread mt-4">
                            <h6 class="mb-3">Conversation History</h6>
                            <div class="thread-messages">
                                ${data.thread ? data.thread.map(msg => `
                                    <div class="thread-message ${msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}">
                                        <div class="thread-message-header">
                                            <strong>${msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'You' : 'Admin'}</strong>
                                            <small class="text-muted">${msg.formatted_date}</small>
                                        </div>
                                        <div class="thread-message-content">
                                            ${msg.content}
                                        </div>
                                    </div>
                                `).join('') : '<p class="text-muted">No previous messages in this conversation</p>'}
                            </div>
                        </div>
                    `;
                })
                .catch(error => console.error('Error:', error));
        }

        // Add these functions for reply and delete functionality
        function showReplyModal(subject, recipientId) {
            const modal = new bootstrap.Modal(document.getElementById('composeModal'));
            document.getElementById('subject').value = `Re: ${subject.replace(/^Re:\s*/i, '')}`;
            document.getElementById('recipient_id').value = recipientId;
            document.getElementById('message').value = '';
            modal.show();
        }

        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                fetch('delete_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message_id=' + messageId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Error deleting message');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting message');
                });
            }
        }
    });
    </script>
</body>
</html> 