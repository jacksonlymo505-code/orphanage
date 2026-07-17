<?php
session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';

// Check if user is logged in and is donor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'donor') {
    header('Location: ../login.php');
    exit();
}

// Fetch all adoptive parents
$query = "SELECT id, first_name, last_name, email, phone, organization_name AS organization, status, created_at FROM donors WHERE type = 'adoptive' ORDER BY first_name, last_name";
$result = $conn->query($query);
$adoptive_parents = [];
if ($result) {
    $adoptive_parents = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoptive Parents - Donor Portal</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-header h1 {
            font-size: 1.75rem;
            color: var(--primary-color);
        }

        .page-header span {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .adoptive-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .adoptive-list table {
            width: 100%;
            border-collapse: collapse;
        }

        .adoptive-list th,
        .adoptive-list td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .adoptive-list th {
            background: #f9fafb;
            color: #374151;
            font-weight: 700;
        }

        .adoptive-list tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #ecfdf5;
            color: #166534;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge.inactive {
            background: #fef3c7;
            color: #92400e;
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Adoptive Parents</h1>
            <span>All adoptive parents available in the donor portal</span>
        </div>
        <div class="badge"><?php echo count($adoptive_parents); ?> listed</div>
    </div>

    <div class="adoptive-list">
        <?php if (count($adoptive_parents) === 0): ?>
            <div class="empty-state">
                No adoptive parents are registered yet.
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($adoptive_parents as $parent): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($parent['email']); ?></td>
                            <td><?php echo htmlspecialchars($parent['phone']); ?></td>
                            <td><?php echo htmlspecialchars($parent['organization'] ?: '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $parent['status'] === 'active' ? '' : 'inactive'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($parent['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($parent['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
