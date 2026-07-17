<?php
require_once 'config/database.php';

$before = [];
$res = $conn->query("SELECT source, COUNT(*) AS cnt, SUM(amount) AS total FROM public_contributions GROUP BY source");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $before[] = $row;
    }
}

$deleted = $conn->query("DELETE FROM public_contributions WHERE source = 'legacy'");
$deletedCount = $conn->affected_rows;

$after = [];
$res2 = $conn->query("SELECT source, COUNT(*) AS cnt, SUM(amount) AS total FROM public_contributions GROUP BY source");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $after[] = $row;
    }
}

header('Content-Type: text/plain');
echo "Before:\n";
foreach ($before as $row) {
    echo $row['source'] . ': ' . $row['cnt'] . ' -> ' . $row['total'] . "\n";
}
echo "\nDeleted legacy rows: $deletedCount\n\n";
echo "After:\n";
foreach ($after as $row) {
    echo $row['source'] . ': ' . $row['cnt'] . ' -> ' . $row['total'] . "\n";
}
