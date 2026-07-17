<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
// Try to find donor id by email, fallback to users.id
$donor_id = null;
$res = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
if ($res && $r = $res->fetch_assoc()) {
    $email = $r['email'];
    $email_esc = $conn->real_escape_string($email);
    $res2 = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' LIMIT 1");
    if ($res2 && $row = $res2->fetch_assoc()) {
        $identity = (int)$row['id'];
    } else {
        $identity = $user_id;
    }
} else {
    $identity = $user_id;
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response'])) {
    $recipient_id = (int)($_POST['recipient_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if ($recipient_id > 0 && $content !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content, read_status, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $subject = 'Reply from adoptive parent';
        if ($stmt) {
            $stmt->bind_param('iiss', $identity, $recipient_id, $subject, $content);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Message sent.';
            } else {
                $_SESSION['error'] = 'Unable to send message.';
            }
            $stmt->close();
        }
    }
}

// Fetch messages involving this identity
$stmt = $conn->prepare("SELECT m.*, DATE_FORMAT(m.created_at, '%M %d, %Y %h:%i %p') as formatted_date FROM messages m WHERE m.recipient_id = ? OR m.sender_id = ? ORDER BY m.created_at DESC");
$stmt->bind_param('ii', $identity, $identity);
$stmt->execute();
$res = $stmt->get_result();
$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Your Messages</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background:#f8fafc}
        .container{max-width:980px;margin:28px auto;padding:20px}
        .panel{background:#fff;padding:18px;border-radius:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
        .thread{margin-top:12px}
        .msg{padding:12px;border-radius:8px;margin-bottom:8px}
        .sent{background:#e6f4ea;margin-left:auto;max-width:80%}
        .received{background:#f3f4f6;max-width:80%}
        form.reply{margin-top:16px}
    </style>
</head>
<body>
<div class="container">
    <div class="panel">
        <h2>Your Messages</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <div style="background:#e6f4ea;padding:10px;border-radius:6px;color:#065f46;margin-bottom:12px"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background:#fff1f2;padding:10px;border-radius:6px;color:#991b1b;margin-bottom:12px"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
            <p>No messages yet. Use "Message Admin" on the dashboard to contact the team.</p>
        <?php else: ?>
            <div class="thread">
                <?php foreach ($messages as $m): ?>
                    <?php $is_sent = ($m['sender_id'] == $identity); ?>
                    <div class="msg <?php echo $is_sent? 'sent':'received'; ?>">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px"><?php echo $m['formatted_date']; ?></div>
                        <div style="white-space:pre-wrap"><?php echo htmlspecialchars($m['content']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="reply">
            <h4>Send a message</h4>
            <input type="hidden" name="recipient_id" value="<?php
                // find admin id for the reply target
                $r = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
                $aid = ($r && $row = $r->fetch_assoc()) ? (int)$row['id'] : 0; echo $aid;
            ?>">
            <textarea name="content" rows="4" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px" required></textarea>
            <div style="margin-top:8px;display:flex;gap:8px">
                <button type="submit" name="send_response" class="btn-primary">Send</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
