<?php
session_start();
require_once '../config/database.php';
require_once '../config/notifications.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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

// Handle message response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response'])) {
    $donor_id = (int)($_POST['donor_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['content'] ?? '');
    $admin_id = (int)$_SESSION['user_id'];
    
    if ($donor_id > 0 && $message !== '') {
        $query = "INSERT INTO messages (sender_id, recipient_id, subject, content, read_status, created_at) 
                 VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iiss", $admin_id, $donor_id, $subject, $message);
            if ($stmt->execute()) {
                // Try to fetch donor phone to send SMS
                $phone = null;
                $res = $conn->query("SELECT phone FROM donors WHERE id = $donor_id LIMIT 1");
                if ($res && $r = $res->fetch_assoc()) {
                    $phone = $r['phone'];
                }

                if ($phone) {
                    $smsRes = send_sms_message($phone, "[Admin Reply] " . $message);
                    if (!$smsRes['success']) {
                        // Log but still consider message saved
                        error_log("SMS send failed to $phone: " . $smsRes['message']);
                    }
                }

                $_SESSION['success'] = "Response sent successfully!";
            } else {
                $_SESSION['error'] = "Error sending response.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Unable to prepare message save.";
        }
    }
}

// Fetch all messages with donor information
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
          WHERE m.recipient_id = ? OR m.sender_id = ?
          ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
$admin_id = $_SESSION['user_id'];
$stmt->bind_param("iiii", $admin_id, $admin_id, $admin_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// Process messages
while ($message = $result->fetch_assoc()) {
    $data['messages'][] = $message;
    if (!$message['read_status'] && $message['recipient_id'] == $admin_id) {
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
    <title>Messages - Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
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
        .message-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .message-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .message-item:hover {
            background-color: #f8f9fa;
        }

        .message-item.unread {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }

        .message-preview {
            color: #666;
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-content {
            min-height: 400px;
        }

        .message-body {
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .btn-group {
            gap: 5px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .container-fluid {
            padding: 20px;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background: white;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }

        .card-header h5 {
            color: var(--primary-color);
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Message List Styles */
        .message-list {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            padding: 0;
        }

        .message-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .message-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .message-item.unread {
            background-color: #e3f2fd;
            border-left: 4px solid var(--secondary-color);
        }

        .message-preview {
            color: #666;
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 5px;
        }

        /* Message Content Styles */
        .message-content {
            min-height: calc(100vh - 300px);
            padding: 20px;
        }

        .message-body {
            white-space: pre-wrap;
            line-height: 1.6;
            color: #444;
            font-size: 1.1em;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }

        /* Button Styles */
        .btn-group {
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
        }

        /* Badge Styles */
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 20px;
        }

        .badge.bg-primary {
            background-color: var(--secondary-color) !important;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 10px;
            border: none;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .modal-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
        }

        /* Form Styles */
        .form-control {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        /* Scrollbar Styles */
        .message-list::-webkit-scrollbar {
            width: 6px;
        }

        .message-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .message-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .message-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .me-3 {
            margin-right: 1rem;
        }

        #recipientSelect {
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }

        #recipientSelect:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Add to the existing <style> section */
        .chat-history {
            height: calc(100vh - 300px);
            overflow-y: auto;
            padding: 20px;
        }

        .chat-message {
            margin-bottom: 20px;
            max-width: 80%;
        }

        .chat-message.sent {
            margin-left: auto;
        }

        .chat-message.received {
            margin-right: auto;
        }

        .message-bubble {
            padding: 12px 15px;
            border-radius: 15px;
            position: relative;
            margin-bottom: 5px;
        }

        .sent .message-bubble {
            background-color: #3498db;
            color: white;
            border-top-right-radius: 5px;
        }

        .received .message-bubble {
            background-color: #f1f1f1;
            color: #333;
            border-top-left-radius: 5px;
        }

        .message-info {
            font-size: 0.8em;
            color: #666;
            margin-bottom: 5px;
        }

        .sent .message-info {
            text-align: right;
        }

        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .chat-header h5 {
            margin: 0;
        }

        .message-time {
            font-size: 0.75em;
            opacity: 0.8;
        }

        .sent .message-time {
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>OMS Admin</h2>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="children.php" class="menu-item">
                <i class="fas fa-child"></i> Children
            </a>
            <a href="guardians.php" class="menu-item">
                <i class="fas fa-user-shield"></i> Guardians
            </a>
            <a href="donors.php" class="menu-item">
                <i class="fas fa-hand-holding-heart"></i> Donors
            </a>
            <a href="adoptions.php" class="menu-item">
                <i class="fas fa-baby"></i> Adoptions
            </a>
            <a href="donations_history.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i> Donations
            </a>
            <a href="manage_opportunities.php" class="menu-item">
                <i class="fas fa-handshake"></i> Opportunities
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
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

            <div class="row">
                <!-- Message List -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 me-3">Messages</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#composeModal">
                                    <i class="fas fa-pen"></i> Compose
                                </button>
                            </div>
                            <span class="badge bg-primary"><?php echo $data['unread_count']; ?> unread</span>
                        </div>
                        <div class="message-list">
                            <?php if (!empty($data['messages'])): ?>
                                <?php foreach ($data['messages'] as $message): ?>
                                    <div class="message-item <?php echo (!$message['read_status'] && $message['recipient_id'] == $admin_id) ? 'unread' : ''; ?>"
                                         data-id="<?php echo $message['id']; ?>"
                                         data-recipient-id="<?php echo $message['sender_id'] == $admin_id ? $message['recipient_id'] : $message['sender_id']; ?>"
                                         data-recipient-name="<?php echo $message['sender_id'] == $admin_id ? $message['recipient_name'] : $message['sender_name']; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1">
                                                <?php echo $message['sender_id'] == $admin_id ? 
                                                    'To: ' . $message['recipient_name'] :
                                                    'From: ' . $message['sender_name']; ?>
                                            </h6>
                                            <small><?php echo date('M d', strtotime($message['created_at'])); ?></small>
                                        </div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                        <p class="mb-1 message-preview"><?php echo htmlspecialchars($message['content']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <p class="mb-0">No messages found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Message Content -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div id="chatHeader">
                                <h5 class="mb-0">Select a conversation to view messages</h5>
                            </div>
                            <div id="chatActions" class="d-none">
                                <button class="btn btn-primary btn-sm reply-btn">
                                    <i class="fas fa-reply"></i> Reply
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="messageContent" class="message-content">
                                <div class="chat-history" id="chatHistory">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-comments fa-3x mb-3"></i>
                                        <p>Select a conversation to view message history</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="replyModalLabel">Reply to Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="replyForm">
                        <input type="hidden" id="replyRecipientId" name="donor_id">
                        <div class="mb-3">
                            <label for="replySubject" class="form-label">Subject:</label>
                            <input type="text" class="form-control" id="replySubject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="replyContent" class="form-label">Message:</label>
                            <textarea class="form-control" id="replyContent" name="content" rows="8" required 
                                    placeholder="Type your message here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Your message will be sent to the donor.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="sendReply">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Compose Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="composeModalLabel">Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Update the compose form -->
                    <form id="composeForm" method="POST">
                        <div class="mb-3">
                            <label for="recipientSelect" class="form-label">Recipient:</label>
                            <select class="form-select" id="recipientSelect" name="donor_id" required>
                                <option value="">Select recipient...</option>
                                <?php
                                // Fetch all donors
                                $donor_query = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM donors ORDER BY full_name";
                                $donor_result = $conn->query($donor_query);
                                while ($donor = $donor_result->fetch_assoc()) {
                                    echo "<option value='{$donor['id']}'>{$donor['full_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="composeSubject" class="form-label">Subject:</label>
                            <input type="text" class="form-control" id="composeSubject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="composeContent" class="form-label">Message:</label>
                            <textarea class="form-control" id="composeContent" name="content" rows="8" required 
                                    placeholder="Type your message here..."></textarea>
                        </div>
                        <input type="hidden" name="send_response" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="sendCompose">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get DOM elements
            const messageList = document.querySelector('.message-list');
            const chatHistory = document.getElementById('chatHistory');
            const replyForm = document.getElementById('replyForm');
            const composeForm = document.getElementById('composeForm');
            
            // Initialize Bootstrap modals
            let replyModal, composeModal;
            
            try {
                const replyModalEl = document.getElementById('replyModal');
                const composeModalEl = document.getElementById('composeModal');
                
                if (typeof bootstrap !== 'undefined') {
                    replyModal = new bootstrap.Modal(replyModalEl);
                    composeModal = new bootstrap.Modal(composeModalEl);
                    console.log('Bootstrap modals initialized successfully');
                } else {
                    console.error('Bootstrap is not defined. Make sure bootstrap.bundle.min.js is loaded correctly.');
                    alert('Error: Bootstrap library not loaded. Please refresh the page or contact support.');
                }
            } catch (error) {
                console.error('Error initializing Bootstrap modals:', error);
            }

            // Handle compose form submission
            document.getElementById('sendCompose').addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(composeForm);
                
                // Validate form
                const recipientSelect = document.getElementById('recipientSelect');
                const subject = document.getElementById('composeSubject');
                const content = document.getElementById('composeContent');
                
                if (!recipientSelect.value) {
                    alert('Please select a recipient');
                    recipientSelect.focus();
                    return;
                }
                
                if (!subject.value.trim()) {
                    alert('Please enter a subject');
                    subject.focus();
                    return;
                }
                
                if (!content.value.trim()) {
                    alert('Please enter a message');
                    content.focus();
                    return;
                }

                // Disable send button and show loading state
                const sendBtn = this;
                const originalBtnText = sendBtn.innerHTML;
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

                // Send the message using fetch
                fetch('message_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message and reload page
                        showAlert('success', 'Message sent successfully!');
                        composeModal.hide();
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        throw new Error(data.error || 'Error sending message');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', error.message);
                })
                .finally(() => {
                    // Reset button state
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = originalBtnText;
                });
            });

            // Helper function to show alerts
            function showAlert(type, message) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                alertDiv.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> 
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
                
                // Auto-dismiss alert after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }

            // Reset compose form when modal is closed
            composeModalEl.addEventListener('hidden.bs.modal', function () {
                composeForm.reset();
            });

            // Add form validation on input
            if (composeForm) {
                composeForm.querySelectorAll('input, select, textarea').forEach(input => {
                    input.addEventListener('input', function() {
                        if (this.value.trim()) {
                            this.classList.remove('is-invalid');
                        }
                    });
                });
            }

            // Handle message click
            if (messageList) {
                messageList.addEventListener('click', function(e) {
                    const messageItem = e.target.closest('.message-item');
                    if (messageItem) {
                        const messageId = messageItem.getAttribute('data-id');
                        const recipientId = messageItem.getAttribute('data-recipient-id');
                        const recipientName = messageItem.getAttribute('data-recipient-name');
                        
                        // Update chat header
                        document.getElementById('chatHeader').innerHTML = `
                            <h5 class="mb-0">Chat with ${recipientName}</h5>
                        `;
                        
                        // Show reply button and set data attributes
                        const chatActions = document.getElementById('chatActions');
                        chatActions.classList.remove('d-none');
                        const replyBtn = chatActions.querySelector('.reply-btn');
                        replyBtn.setAttribute('data-recipient', recipientId);
                        replyBtn.setAttribute('data-subject', 'Re: Chat');
                        
                        // Fetch chat history
                        fetchChatHistory(recipientId);
                    }
                });
            }

            // Handle reply button click
            document.addEventListener('click', function(e) {
                if (e.target.closest('.reply-btn')) {
                    const btn = e.target.closest('.reply-btn');
                    document.getElementById('replyRecipientId').value = btn.dataset.recipient;
                    document.getElementById('replySubject').value = 'Re: ' + (btn.dataset.subject || 'Message');
                    document.getElementById('replyContent').value = ''; // Clear previous content
                    replyModal.show();
                    setTimeout(() => {
                        document.getElementById('replyContent').focus(); // Focus on message field
                    }, 500);
                }
            });

            // Handle send reply
            document.getElementById('sendReply').addEventListener('click', function() {
                const formData = new FormData(replyForm);
                formData.append('send_response', '1');

                // Disable send button and show loading state
                const sendBtn = document.getElementById('sendReply');
                const originalBtnText = sendBtn.innerHTML;
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

                fetch('message_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        replyModal.hide();
                        showAlert('success', 'Message sent successfully!');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        alert(data.error || 'Error sending message');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending message');
                })
                .finally(() => {
                    // Reset button state
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = originalBtnText;
                });
            });

            // Function to fetch chat history
            function fetchChatHistory(recipientId) {
                // Show loading state
                chatHistory.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                        <p>Loading messages...</p>
                    </div>
                `;
                
                // Fetch chat history
                fetch(`message_actions.php?action=chat_history&recipient_id=${recipientId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }

                        chatHistory.innerHTML = '';

                        if (data.messages.length === 0) {
                            chatHistory.innerHTML = `
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-comments fa-3x mb-3"></i>
                                    <p>No messages in this conversation yet</p>
                                </div>
                            `;
                            return;
                        }

                        let currentDate = '';
                        
                        data.messages.forEach(message => {
                            const messageDate = new Date(message.created_at).toLocaleDateString();
                            
                            // Add date separator if it's a new date
                            if (messageDate !== currentDate) {
                                currentDate = messageDate;
                                chatHistory.innerHTML += `
                                    <div class="text-center mb-3">
                                        <small class="bg-light px-3 py-1 rounded-pill">${messageDate}</small>
                                    </div>
                                `;
                            }

                            const isAdmin = message.sender_id == <?php echo $_SESSION['user_id']; ?>;
                            chatHistory.innerHTML += `
                                <div class="chat-message ${isAdmin ? 'sent' : 'received'}">
                                    <div class="message-info">
                                        ${isAdmin ? 'You' : message.sender_name}
                                    </div>
                                    <div class="message-bubble">
                                        ${message.content.replace(/\n/g, '<br>')}
                                    </div>
                                    <div class="message-time">
                                        ${new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                    </div>
                                </div>
                            `;
                        });

                        // Scroll to bottom of chat history
                        chatHistory.scrollTop = chatHistory.scrollHeight;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        chatHistory.innerHTML = `
                            <div class="text-center text-danger py-5">
                                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                <p>Error loading messages: ${error.message}</p>
                            </div>
                        `;
                    });
            }

            // Add delete message function
            function deleteMessage(messageId) {
                if (confirm('Are you sure you want to delete this message?')) {
                    fetch(`message_actions.php?action=delete&message_id=${messageId}`)
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
    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script>
        // Fallback if bootstrap.bundle.min.js fails to load
        if (typeof bootstrap === 'undefined') {
            console.warn('Loading Bootstrap from CDN as fallback');
            const fallbackScript = document.createElement('script');
            fallbackScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
            fallbackScript.onload = function() {
                console.log('Bootstrap loaded from CDN');
                // Reinitialize the page
                location.reload();
            };
            document.body.appendChild(fallbackScript);
        }
    </script>
</body>
</html>
