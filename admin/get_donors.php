<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['opportunity_id'])) {
    echo json_encode(['error' => 'Opportunity ID is required']);
    exit();
}

$opportunity_id = $_GET['opportunity_id'];

// Get all donors (both those who have donated and potential donors)
$query = "SELECT 
            u.id,
            CONCAT(u.first_name, ' ', u.last_name) as name,
            u.email,
            c.amount,
            DATE_FORMAT(c.created_at, '%M %d, %Y') as date
          FROM users u
          LEFT JOIN contributions c ON u.id = c.donor_id AND c.opportunity_id = ?
          WHERE u.role = 'donor' AND u.status = 'active'
          ORDER BY c.amount DESC, u.first_name, u.last_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $opportunity_id);
$stmt->execute();
$result = $stmt->get_result();

$donors = [];
while ($row = $result->fetch_assoc()) {
    $donors[] = $row;
}

echo json_encode($donors);
$stmt->close();
$conn->close();
?>
