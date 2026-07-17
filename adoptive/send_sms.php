<?php
session_start();
require_once '../config/database.php';
require_once '../config/notifications.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'adoptive') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = $_POST;
$message = trim($input['message'] ?? '');
$template = trim($input['template'] ?? '');
$user_id = (int)$_SESSION['user_id'];

if ($message === '' && $template === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is required.']);
    exit();
}

// Compose final message: template selected overrides empty
if ($template !== '') {
    // For now templates are simple shortcuts; you can expand this lookup
    $templates = [
        'introduce' => "Hello, I am an adoptive parent and I'd like to check on my application status.",
        'request_meeting' => "Hello, I'd like to request a meeting with the admin regarding my application.",
        'update_contact' => "Please update my contact details on file. Thank you."
    ];
    if (isset($templates[$template])) {
        $message = $templates[$template];
    }
}

// Basic length check
if (mb_strlen($message) > 320) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message exceeds 320 characters.']);
    exit();
}

// Rate limit
$rate = sms_rate_check_and_record($user_id, 5, 3600);
if (!$rate['allowed']) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit reached. Try again later.']);
    exit();
}

// Determine donor id for the sender (prefer donors table by email)
$sender_id = $user_id; // fallback to users.id
$user_email = '';
$res = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
if ($res && $r = $res->fetch_assoc()) {
    $user_email = $r['email'];
}
if ($user_email) {
    $email_esc = $conn->real_escape_string($user_email);
    $res = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $sender_id = (int)$row['id'];
    }
}

// Find an admin user id to receive messages
$admin_user_id = null;
$res = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $admin_user_id = (int)$row['id'];
}

// Insert message into messages table for admin UI
if ($admin_user_id !== null) {
    $subject = 'SMS to admin';
    $ins = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content, read_status, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    if ($ins) {
        $ins->bind_param('iiss', $sender_id, $admin_user_id, $subject, $message);
        $ins->execute();
        $ins->close();
    }
}

// Send to admin phone
$adminPhone = ADMIN_PHONE;
$fullMessage = "[Adoptive Parent #" . $user_id . "] " . $message;

$result = send_sms_message($adminPhone, $fullMessage);
if ($result['success']) {
    echo json_encode(['success' => true, 'message' => $result['message'], 'remaining' => $rate['remaining']]);
    exit();
}

// If SMS provider failed, return error but message still stored
http_response_code(500);
echo json_encode(['success' => false, 'message' => $result['message']]);

