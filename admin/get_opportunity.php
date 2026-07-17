<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing opportunity ID']);
    exit();
}

$id = (int)$_GET['id'];
$sql = "SELECT * FROM opportunities WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$opportunity = $result->fetch_assoc();

if (!$opportunity) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Opportunity not found']);
    exit();
}

// Format the deadline date for the form
$opportunity['deadline'] = date('Y-m-d', strtotime($opportunity['deadline']));

// Return opportunity data as JSON
header('Content-Type: application/json');
echo json_encode($opportunity);

$stmt->close();
$conn->close();
?>
