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
    echo json_encode(['error' => 'Missing guardian ID']);
    exit();
}

$id = (int)$_GET['id'];
$sql = "SELECT * FROM guardians WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$guardian = $result->fetch_assoc();

if (!$guardian) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Guardian not found']);
    exit();
}

// Return guardian data as JSON
header('Content-Type: application/json');
echo json_encode($guardian); 