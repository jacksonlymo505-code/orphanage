<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    echo json_encode(['error' => 'unauthenticated']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$result = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
$user_email = '';
if ($result && $row = $result->fetch_assoc()) $user_email = $row['email'];

$adopter_id = null;
if ($user_email) {
    $email_esc = $conn->real_escape_string($user_email);
    $r = $conn->query("SELECT id FROM donors WHERE email = '$email_esc' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) $adopter_id = (int)$row['id'];
}

$counts = ['my_applications' => 0, 'pending_requests' => 0, 'completed_adoptions' => 0, 'active_children' => 0, 'documents' => []];
if (!empty($adopter_id)) {
    $r = $conn->query("SELECT COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status='completed') AS completed FROM adoptions WHERE adopter_id = $adopter_id");
    if ($r && $row = $r->fetch_assoc()) {
        $counts['my_applications'] = (int)$row['total'];
        $counts['pending_requests'] = (int)$row['pending'];
        $counts['completed_adoptions'] = (int)$row['completed'];
    }
    $r = $conn->query("SELECT COUNT(*) AS count FROM adoptions WHERE adopter_id = $adopter_id AND status = 'completed'");
    if ($r) $counts['active_children'] = (int)$r->fetch_assoc()['count'];
}

// document status counts for this user
$stmt = $conn->prepare("SELECT status, COUNT(*) AS cnt FROM documents WHERE user_id = ? GROUP BY status");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $counts['documents'][$row['status']] = (int)$row['cnt'];
    }
    $stmt->close();
}

echo json_encode($counts);

?>
