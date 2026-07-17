<?php
require_once '../config/database.php';
require_once '../config/helpers.php';

// Simple admin view for public/guest contributions
// Only accessible to admins - minimal check (assumes admin session exists)
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

ensure_public_contributions_table_exists();
migrate_public_contributions_table();

// Fetch live public contribution records with detailed tracking, excluding legacy migrated rows
$stmt = $conn->prepare('SELECT pc.id, pc.phone, pc.donor_email, pc.amount, pc.currency, pc.payment_method, pc.status, pc.notes, pc.transaction_id, pc.source, pc.created_at, pc.completed_at, pc.otp_sent_at, pc.otp_verified_at, pc.payment_started_at, pc.failure_reason, pc.payment_attempts, pc.device_type, d.email, d.full_name FROM public_contributions pc LEFT JOIN donors d ON d.id = pc.donor_id WHERE pc.source <> "legacy" ORDER BY pc.created_at DESC LIMIT 500');

if (!$stmt) {
    // Fallback to basic query if new columns don't exist yet
    $stmt = $conn->prepare('SELECT pc.id, pc.phone, pc.amount, pc.currency, pc.payment_method, pc.status, pc.notes, pc.transaction_id, pc.source, pc.created_at, d.email, d.full_name FROM public_contributions pc LEFT JOIN donors d ON d.id = pc.donor_id WHERE pc.source <> "legacy" ORDER BY pc.created_at DESC LIMIT 500');
    if (!$stmt) {
        die('Database query error: ' . $conn->error);
    }
}

$stmt->execute();
$res = $stmt->get_result();
$public_rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// No need to fetch legacy donations - we track everything in public_contributions now
$legacy_rows = [];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Public Contributions</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        :root {
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: #f8fafc;
        }
        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
        }
        .page-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        .header-panel {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 24px;
        }
        .header-panel h2 {
            margin: 0;
            font-size: clamp(1.8rem, 2.4vw, 2.4rem);
        }
        .header-panel .meta {
            margin: 12px 0 0;
            color: #64748b;
            max-width: 72ch;
            line-height: 1.7;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 1rem;
            color: #1d4ed8;
            background: #dbeafe;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .control-panel {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-box {
            flex: 1 1 320px;
            min-width: 280px;
        }
        .search-box input {
            width: 100%;
            min-height: 44px;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .search-box input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .filter-pill {
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid transparent;
            background: #f8fafc;
            color: #334155;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
        .filter-pill:hover {
            background: #e2e8f0;
        }
        .filter-pill.active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            box-shadow: 0 18px 60px rgba(15, 23, 42, 0.06);
        }
        .table-card {
            overflow-x: auto;
        }
        .styled-table {
            width: 100%;
            min-width: 980px;
            border-collapse: separate;
            border-spacing: 0;
        }
        .styled-table th,
        .styled-table td {
            padding: 15px 18px;
            white-space: nowrap;
        }
        .styled-table thead th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            color: #334155;
            font-size: 0.78rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .styled-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .styled-table tbody tr:hover {
            background: #eef2ff;
            transform: translateY(-1px);
        }
        .styled-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .styled-table tbody tr:last-child td {
            border-bottom: none;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .status-pill.completed {
            background: #dcfce7;
            color: #14532d;
        }
        .status-pill.failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-pill.pending,
        .status-pill.otp_sent,
        .status-pill.processing,
        .status-pill.otp_verified {
            background: #fef3c7;
            color: #78350f;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .summary-card {
            padding: 18px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }
        .summary-card .metric-label {
            font-size: 0.78rem;
            color: #64748b;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .summary-card strong {
            display: block;
            margin-top: 0;
            font-size: 1.9rem;
            color: #0f172a;
        }
        .method-chip {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #334155;
            font-size: 0.86rem;
            margin: 6px 8px 6px 0;
        }
        .flow-text,
        .small-text {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.6;
        }
        .overview-section {
            padding: 26px;
        }
        .overview-section h3 {
            margin-top: 0;
            margin-bottom: 14px;
            font-size: 1.1rem;
            color: #0f172a;
        }
        @media (max-width: 900px) {
            .header-panel { flex-direction: column; }
            .styled-table th,
            .styled-table td { padding: 12px 10px; }
        }
    </style>
</head>
<body>
<div class="page-shell">
    <div class="header-panel">
        <div>
            <h2>Guest Contributions (Without Login)</h2>
            <p class="meta">All public contributions from unregistered users are tracked here with complete payment flow details, transaction status, and device metadata.</p>
        </div>
        <div class="badge">Total records: <?php echo count($public_rows); ?></div>
    </div>
    <div class="control-panel">
        <div class="search-box">
            <input id="contributionSearch" type="search" placeholder="Search ID, email, phone, transaction, method, device..." aria-label="Search contributions">
        </div>
        <div class="filters">
            <button type="button" class="filter-pill active" data-filter="all">All</button>
            <button type="button" class="filter-pill" data-filter="completed">Completed</button>
            <button type="button" class="filter-pill" data-filter="failed">Failed</button>
            <button type="button" class="filter-pill" data-filter="pending">Pending</button>
            <button type="button" class="filter-pill" data-filter="otp_sent">OTP Sent</button>
        </div>
    </div>
    <div class="card table-card">
        <table class="styled-table">
        <thead><tr>
            <th>ID</th>
            <th>Submitted</th>
            <th>Contact</th>
            <th>Phone</th>
            <th>Amount</th>
            <th>Currency</th>
            <th>Method</th>
            <th>Status</th>
            <th>Payment Flow</th>
            <th>Device</th>
            <th>Attempts</th>
            <th>Transaction ID</th>
            <th>Notes</th>
        </tr></thead>
        <tbody>
        <?php 
        if (empty($public_rows)) {
            echo '<tr><td colspan="13" style="text-align:center;padding:20px;color:#999;">No contributions yet</td></tr>';
        } else {
            foreach ($public_rows as $r): 
                $flow = '';
                if ($r['payment_method'] === 'card') {
                    $flow = 'Stripe';
                    if ($r['payment_started_at']) $flow .= ' (' . $r['payment_started_at'] . ')';
                } else {
                    $flow = $r['source'] ?? 'public';
                    if ($r['otp_sent_at']) $flow .= '<br/>OTP: ' . $r['otp_sent_at'];
                    if ($r['otp_verified_at']) $flow .= '<br/>Verified: ' . $r['otp_verified_at'];
                }
                if ($r['payment_started_at'] && !$r['otp_sent_at']) {
                    $flow .= '<br/>Started: ' . $r['payment_started_at'];
                }
                if ($r['failure_reason']) {
                    $flow .= '<br/><span style="color:#b91c1c;">Failed: ' . htmlspecialchars($r['failure_reason']) . '</span>';
                }
                if ($r['completed_at']) {
                    $flow .= '<br/><span style="color:#15803d;font-weight:600;">✓ Completed: ' . htmlspecialchars($r['completed_at']) . '</span>';
                }
                $statusClass = htmlspecialchars(str_replace('_', '-', $r['status']));
        ?>
            <tr>
                <td><?php echo htmlspecialchars($r['id']); ?></td>
                <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                <td><?php echo htmlspecialchars($r['donor_email'] ?: $r['email'] ?: 'Guest'); ?></td>
                <td><?php echo htmlspecialchars($r['phone']); ?></td>
                <td style="text-align:right;font-variant-numeric:tabular-nums;"><?php echo number_format($r['amount'],2); ?></td>
                <td style="text-align:center"><?php echo htmlspecialchars($r['currency']); ?></td>
                <td><?php echo htmlspecialchars($r['payment_method']); ?></td>
                <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                <td class="flow-text"><?php echo $flow; ?></td>
                <td><?php echo htmlspecialchars($r['device_type'] ?: '—'); ?></td>
                <td style="text-align:center"><?php echo $r['payment_attempts']; ?></td>
                <td class="small-text"><?php echo htmlspecialchars($r['transaction_id'] ?: '—'); ?></td>
                <td class="small-text"><?php echo htmlspecialchars(substr($r['notes'] ?? '', 0, 50)); ?></td>
            </tr>
        <?php 
            endforeach;
        }
        ?>
        </tbody>
    </table>
    </div>

    <div class="card overview-section">
        <h3>Summary Statistics</h3>
        <?php 
        if (!empty($public_rows)) {
            $total_amount = 0;
            $completed_count = 0;
            $failed_count = 0;
            $methods = [];
            
            foreach ($public_rows as $r) {
                $total_amount += $r['amount'];
                if ($r['status'] === 'completed') $completed_count++;
                if ($r['status'] === 'failed') $failed_count++;
                $m = htmlspecialchars($r['payment_method'] ?: 'unknown');
                $methods[$m] = ($methods[$m] ?? 0) + 1;
            }
        ?>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="metric-label">Total Contributions</div>
                <strong><?php echo count($public_rows); ?></strong>
            </div>
            <div class="summary-card">
                <div class="metric-label">Total Amount</div>
                <strong><?php echo number_format($total_amount, 2); ?></strong>
            </div>
            <div class="summary-card">
                <div class="metric-label">Completed</div>
                <strong><?php echo $completed_count; ?></strong>
            </div>
            <div class="summary-card">
                <div class="metric-label">Failed</div>
                <strong><?php echo $failed_count; ?></strong>
            </div>
        </div>
        
        <div style="margin-top:20px;">
            <div class="metric-label" style="margin-bottom:10px;">By Payment Method</div>
            <?php foreach ($methods as $method => $count): ?>
                <span class="method-chip"><?php echo $method; ?>: <strong><?php echo $count; ?></strong></span>
            <?php endforeach; ?>
        </div>
        <?php } else { ?>
        <p style="color:#64748b;">No contributions to display</p>
        <?php } ?>
    </div>
</div>
<script>
    (function () {
        var searchInput = document.getElementById('contributionSearch');
        var filterButtons = Array.prototype.slice.call(document.querySelectorAll('.filter-pill'));
        var tableRows = Array.prototype.slice.call(document.querySelectorAll('.styled-table tbody tr'));

        function updateTable() {
            var query = (searchInput.value || '').trim().toLowerCase();
            var active = document.querySelector('.filter-pill.active');
            var filter = active ? active.getAttribute('data-filter') : 'all';

            tableRows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                var status = (row.querySelector('.status-pill') || {}).textContent || '';
                status = status.toLowerCase();
                var matchesQuery = !query || text.indexOf(query) !== -1;
                var matchesFilter = filter === 'all' || status === filter;
                row.style.display = matchesQuery && matchesFilter ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', updateTable);
        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                filterButtons.forEach(function (btn) { btn.classList.remove('active'); });
                button.classList.add('active');
                updateTable();
            });
        });
    })();
</script>
</body>
</html>