<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: documents.php');
    exit();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}
$user_id = (int)$_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($id <= 0 || !$action) {
    header('Location: documents.php');
    exit();
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Location: documents.php');
    exit();
}

try {
    if ($action === 'submit' || $action === 'request_review') {
        $stmt = $conn->prepare("UPDATE documents SET status = 'submitted' WHERE id = ? AND user_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
} catch (Exception $e) {
    // ignore
}

header('Location: documents.php');
exit();
