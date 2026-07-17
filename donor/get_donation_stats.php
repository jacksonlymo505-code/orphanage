<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not authenticated']));
}

$donor_id = $_SESSION['user_id'];

// Get last 7 days of donations
$sql = "SELECT DATE_FORMAT(donation_date, '%M %d') as date_label, 
        SUM(amount) as total_amount
        FROM donations 
        WHERE donor_id = ? 
        AND donation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(donation_date)
        ORDER BY donation_date";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $donor_id);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['date_label'];
    $values[] = floatval($row['total_amount']);
}

echo json_encode([
    'labels' => $labels,
    'values' => $values
]); 