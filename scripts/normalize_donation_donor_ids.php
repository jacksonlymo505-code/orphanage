<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Forbidden: admin only";
    exit();
}

// Normalize donation donor_ids by matching user emails to donor records.
$sql = "UPDATE donations d
JOIN users u ON d.donor_id = u.id
JOIN donors r ON u.email = r.email
SET d.donor_id = r.id
WHERE d.donor_id <> r.id";

if ($conn->query($sql)) {
    echo "Donation donor_id normalization completed. Rows updated: " . $conn->affected_rows;
} else {
    echo "Error normalizing donation donor IDs: " . htmlspecialchars($conn->error);
}
