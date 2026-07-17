<?php
/**
 * Verification Script for Public Contribution System
 * Checks data integrity and payment flow completeness
 */
require_once '../config/database.php';
require_once '../config/helpers.php';

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

ensure_public_contributions_table_exists();
migrate_public_contributions_table();

// Get statistics
$stats = [];

// 1. Check total contributions
$res = $conn->query("SELECT COUNT(*) as cnt, SUM(amount) as total FROM public_contributions WHERE status='completed' AND source <> 'legacy'");
$row = $res->fetch_assoc();
$stats['total_contributions'] = $row['cnt'] ?? 0;
$stats['total_amount'] = $row['total'] ?? 0;

// 2. Check donation records created
$res = $conn->query("SELECT COUNT(*) as cnt FROM donations WHERE notes LIKE '%public contribution%' OR notes LIKE '%Anonymous%'");
$row = $res->fetch_assoc();
$stats['donation_records'] = $row['cnt'] ?? 0;

// 3. Check guest donors created
$res = $conn->query("SELECT COUNT(*) as cnt FROM donors WHERE email LIKE '%guest+%@example.com%'");
$row = $res->fetch_assoc();
$stats['guest_donors'] = $row['cnt'] ?? 0;

// 4. Check failed contributions
$res = $conn->query("SELECT COUNT(*) as cnt, SUM(amount) as total FROM public_contributions WHERE status='failed' AND source <> 'legacy'");
$row = $res->fetch_assoc();
$stats['failed_count'] = $row['cnt'] ?? 0;
$stats['failed_amount'] = $row['total'] ?? 0;

// 5. Check pending contributions
$res = $conn->query("SELECT COUNT(*) as cnt FROM public_contributions WHERE status IN ('pending','otp_sent','processing') AND source <> 'legacy'");
$row = $res->fetch_assoc();
$stats['pending_count'] = $row['cnt'] ?? 0;

// 6. Get data by payment method
$stmt = $conn->prepare("SELECT payment_method, COUNT(*) as cnt, SUM(amount) as total FROM public_contributions WHERE status='completed' AND source <> 'legacy' GROUP BY payment_method");
$stmt->execute();
$res = $stmt->get_result();
$stats['by_method'] = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 7. Get data by device type
$stmt = $conn->prepare("SELECT device_type, COUNT(*) as cnt, SUM(amount) as total FROM public_contributions WHERE status='completed' AND source <> 'legacy' GROUP BY device_type");
$stmt->execute();
$res = $stmt->get_result();
$stats['by_device'] = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 8. Check donor linkage issues (contributions without linked donors)
$res = $conn->query("SELECT COUNT(*) as cnt FROM public_contributions WHERE status='completed' AND donor_id IS NULL");
$row = $res->fetch_assoc();
$stats['unlinked_donors'] = $row['cnt'] ?? 0;

// 9. Get process completion stats
$stmt = $conn->prepare("
    SELECT 
        'All contributions' as type,
        COUNT(*) as cnt
    FROM public_contributions
    WHERE source <> 'legacy'
    UNION ALL
    SELECT 'With OTP sent', COUNT(*) FROM public_contributions WHERE source <> 'legacy' AND otp_sent_at IS NOT NULL
    UNION ALL
    SELECT 'With OTP verified', COUNT(*) FROM public_contributions WHERE source <> 'legacy' AND otp_verified_at IS NOT NULL
    UNION ALL
    SELECT 'With payment started', COUNT(*) FROM public_contributions WHERE source <> 'legacy' AND payment_started_at IS NOT NULL
    UNION ALL
    SELECT 'Completed', COUNT(*) FROM public_contributions WHERE source <> 'legacy' AND completed_at IS NOT NULL
");
$stmt->execute();
$res = $stmt->get_result();
$stats['process_flow'] = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent issues
$issues = [];

// Check for donations not in public_contributions table
$stmt = $conn->prepare("
    SELECT d.id, d.amount, d.donation_date, d.currency, d.payment_method
    FROM donations d
    WHERE (d.notes LIKE '%Anonymous%' OR d.notes LIKE '%public%')
    AND d.id NOT IN (SELECT transaction_id FROM public_contributions WHERE source='public')
    AND d.project_id IS NULL
    LIMIT 20
");
$stmt->execute();
$res = $stmt->get_result();
$issues['orphaned_donations'] = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check for contributions without donation records
$stmt = $conn->prepare("
    SELECT pc.id, pc.phone, pc.amount, pc.status
    FROM public_contributions pc
    WHERE status='completed'
    AND donor_id NOT IN (SELECT donor_id FROM donations WHERE project_id IS NULL)
    LIMIT 20
");
$stmt->execute();
$res = $stmt->get_result();
$issues['missing_donations'] = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check for failed contributions older than 7 days
$stmt = $conn->prepare("
    SELECT id, phone, amount, payment_method, failure_reason, created_at
    FROM public_contributions
    WHERE status='failed' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    LIMIT 20
");
$stmt->execute();
$res = $stmt->get_result();
$issues['old_failures'] = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Public Contributions Verification</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        body { font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { color: #1f2937; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        h3 { color: #374151; margin-top: 20px; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 15px 0; }
        .stat-box { background: #f3f4f6; padding: 15px; border-radius: 8px; border-left: 4px solid #4f46e5; }
        .stat-label { font-size: 12px; color: #6b7280; text-transform: uppercase; }
        .stat-value { font-size: 24px; font-weight: bold; color: #1f2937; }
        .stat-value.ok { color: #059669; }
        .stat-value.warning { color: #d97706; }
        .stat-value.error { color: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #f3f4f6; padding: 12px; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-ok { background: #dcfce7; color: #166534; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-error { background: #fee2e2; color: #991b1b; }
        .back-link { display: inline-block; margin-bottom: 15px; padding: 8px 16px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; }
        .back-link:hover { background: #4338ca; }
    </style>
</head>
<body>
<div class="container">
    <a href="public_contributions.php" class="back-link">← Back to Public Contributions</a>
    
    <div class="card">
        <h2>Public Contributions - Data Verification Report</h2>
        <p>Last updated: <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <h3>Summary Statistics</h3>
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-label">Total Completed Contributions</div>
                <div class="stat-value ok"><?php echo number_format($stats['total_contributions']); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Amount Collected</div>
                <div class="stat-value ok"><?php echo number_format($stats['total_amount'], 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Donation Records Created</div>
                <div class="stat-value <?php echo $stats['donation_records'] >= $stats['total_contributions'] ? 'ok' : 'error'; ?>"><?php echo number_format($stats['donation_records']); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Guest Donors Created</div>
                <div class="stat-value ok"><?php echo number_format($stats['guest_donors']); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Failed Contributions</div>
                <div class="stat-value <?php echo $stats['failed_count'] > 0 ? 'warning' : 'ok'; ?>"><?php echo number_format($stats['failed_count']); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Pending/Processing</div>
                <div class="stat-value <?php echo $stats['pending_count'] > 0 ? 'warning' : 'ok'; ?>"><?php echo number_format($stats['pending_count']); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Unlinked Donors</div>
                <div class="stat-value <?php echo $stats['unlinked_donors'] > 0 ? 'error' : 'ok'; ?>"><?php echo number_format($stats['unlinked_donors']); ?></div>
            </div>
        </div>

        <h3>Contributions by Payment Method</h3>
        <table>
            <thead><tr><th>Method</th><th>Count</th><th>Total Amount</th></tr></thead>
            <tbody>
            <?php foreach ($stats['by_method'] as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['payment_method']); ?></td>
                    <td><?php echo number_format($m['cnt']); ?></td>
                    <td><?php echo number_format($m['total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Contributions by Device Type</h3>
        <table>
            <thead><tr><th>Device</th><th>Count</th><th>Total Amount</th></tr></thead>
            <tbody>
            <?php foreach ($stats['by_device'] as $d): ?>
                <tr>
                    <td><?php echo htmlspecialchars($d['device_type'] ?: 'unknown'); ?></td>
                    <td><?php echo number_format($d['cnt']); ?></td>
                    <td><?php echo number_format($d['total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Payment Process Flow Tracking</h3>
        <table>
            <thead><tr><th>Process Step</th><th>Completed Count</th></tr></thead>
            <tbody>
            <?php foreach ($stats['process_flow'] as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['type']); ?></td>
                    <td><?php echo number_format($p['cnt']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Data Integrity Checks</h3>
        
        <?php if (empty($issues['orphaned_donations'])): ?>
            <p><span class="status-badge status-ok">✓ All donation records are properly tracked</span></p>
        <?php else: ?>
            <p><span class="status-badge status-error">⚠ Found <?php echo count($issues['orphaned_donations']); ?> donations without public contribution records</span></p>
            <table>
                <thead><tr><th>Donation ID</th><th>Amount</th><th>Date</th><th>Method</th></tr></thead>
                <tbody>
                <?php foreach ($issues['orphaned_donations'] as $d): ?>
                    <tr>
                        <td><?php echo $d['id']; ?></td>
                        <td><?php echo number_format($d['amount'], 2); ?></td>
                        <td><?php echo $d['donation_date']; ?></td>
                        <td><?php echo htmlspecialchars($d['payment_method']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (empty($issues['missing_donations'])): ?>
            <p><span class="status-badge status-ok">✓ All completed contributions have donation records</span></p>
        <?php else: ?>
            <p><span class="status-badge status-error">⚠ Found <?php echo count($issues['missing_donations']); ?> contributions without linked donations</span></p>
            <table>
                <thead><tr><th>Contribution ID</th><th>Phone</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($issues['missing_donations'] as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['phone']); ?></td>
                        <td><?php echo number_format($c['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($c['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($issues['old_failures'])): ?>
            <p><span class="status-badge status-warning">⚠ Found <?php echo count($issues['old_failures']); ?> failed contributions older than 7 days</span></p>
            <table>
                <thead><tr><th>ID</th><th>Phone</th><th>Amount</th><th>Method</th><th>Reason</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($issues['old_failures'] as $f): ?>
                    <tr>
                        <td><?php echo $f['id']; ?></td>
                        <td><?php echo htmlspecialchars($f['phone']); ?></td>
                        <td><?php echo number_format($f['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($f['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars(substr($f['failure_reason'] ?? '', 0, 60)); ?></td>
                        <td><?php echo $f['created_at']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><span class="status-badge status-ok">✓ No old failures found</span></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
