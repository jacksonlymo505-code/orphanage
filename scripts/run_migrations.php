<?php
// Simple migration runner for local use. Run as admin user in browser.
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "Forbidden: admin only";
    exit();
}

$sqlFile = __DIR__ . '/../sql/create_documents_table.sql';
if (!is_readable($sqlFile)) {
    echo "Migration file not found: $sqlFile";
    exit();
}

$sql = file_get_contents($sqlFile);
if (!$sql) {
    echo "Migration file empty or unreadable.";
    exit();
}

if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Migration completed.";
} else {
    echo "Migration failed: " . htmlspecialchars($conn->error);
}

?>
