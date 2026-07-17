<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'forbidden']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'invalid_method']);
    exit();
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['error' => 'invalid_csrf']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$id || !$action) {
    echo json_encode(['error' => 'missing_parameters']);
    exit();
}

$map = [
    'accept' => 'accepted',
    'reject' => 'rejected',
    'review' => 'reviewed'
];

if (!isset($map[$action])) {
    echo json_encode(['error' => 'unknown_action']);
    exit();
}

$status = $map[$action];

try {
    $stmt = $conn->prepare("UPDATE documents SET status = ?, notes = ? WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ssi', $status, $notes, $id);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            echo json_encode(['success' => true, 'status' => $status]);
            exit();
        }
    }
} catch (Exception $e) {
    // ignore
}

echo json_encode(['error' => 'db_error']);
exit();
