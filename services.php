<?php
include __DIR__ . '/config/database.php';
$opportunities = [];
$res = $conn->query("SELECT id,title,description,target_amount,deadline FROM opportunities WHERE status='active' ORDER BY deadline ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $opportunities[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Orphanage Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        :root{--primary:#0f4c81;--text:#475569;--white:#fff}
        body{font-family:Segoe UI, sans-serif;background:#f8fafc;color:var(--text)}
        .container{width:90%;max-width:1100px;margin:30px auto}
        .services-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
        .service-box{background:#fff;border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
        .service-box h3{margin:0 0 8px}
        .muted{color:#64748b}
        .opps{margin-top:26px}
        .opp-item{background:#fff;padding:14px;border-radius:10px;margin-bottom:12px;box-shadow:0 6px 12px rgba(0,0,0,.04)}
        .btn-small{display:inline-block;padding:8px 12px;background:var(--primary);color:var(--white);border-radius:8px;text-decoration:none}
        @media(max-width:900px){.services-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<?php include 'services_nav.php'; ?>
<div class="container">
    <h1>Services</h1>
    <p class="muted">Huduma zinazopatikana kwa uchache: Utunzaji wa watoto; Huduma za uhamishaji; Usimamizi wa wafadhili; Usimamizi wa nyaraka; Mawasiliano; Ripoti na uchanganuzi.</p>

    <div class="services-grid" style="margin-top:18px">
        <div class="service-box">
            <h3>Child Welfare</h3>
            <p class="muted">Child records, care plans, medical tracking and case management.</p>
            <p><a class="btn-small" href="child_welfare.php">Learn more</a></p>
        </div>
        <div class="service-box">
            <h3>Adoption Services</h3>
            <p class="muted">Application handling, document review and family matching workflows.</p>
            <p><a class="btn-small" href="adoption_services.php">Learn more</a></p>
        </div>
        <div class="service-box">
            <h3>Donor Management</h3>
            <p class="muted">Donation recording, receipt generation and donor engagement tools.</p>
            <p><a class="btn-small" href="donor_management.php">Learn more</a></p>
        </div>
        <div class="service-box">
            <h3>Document Management</h3>
            <p class="muted">Secure upload, storage and review workflows for required documents.</p>
            <p><a class="btn-small" href="document_management.php">Learn more</a></p>
        </div>
        <div class="service-box">
            <h3>Communication</h3>
            <p class="muted">Messaging, notifications and coordination tools for stakeholders.</p>
            <p><a class="btn-small" href="communication.php">Learn more</a></p>
        </div>
        <div class="service-box">
            <h3>Reports & Analytics</h3>
            <p class="muted">Pre-built reports and analytics to support planning and accountability.</p>
            <p><a class="btn-small" href="reports_analytics.php">Learn more</a></p>
        </div>
    </div>

    <div class="opps">
        <h2>Active Opportunities</h2>
        <?php if (empty($opportunities)): ?>
            <p class="muted">Hakuna fursa za sasa. Tafadhali angalia baadaye.</p>
        <?php else: ?>
            <?php foreach ($opportunities as $o): ?>
                <div class="opp-item">
                    <strong><?php echo htmlspecialchars($o['title']); ?></strong>
                    <div class="muted" style="font-size:13px;margin-top:6px"><?php echo htmlspecialchars(substr($o['description'],0,160)); if (strlen($o['description'])>160) echo '...'; ?></div>
                    <div style="margin-top:8px;font-size:13px">Target: <?php echo htmlspecialchars($o['target_amount']); ?> &nbsp; | &nbsp; Deadline: <?php echo htmlspecialchars($o['deadline']); ?></div>
                    <div style="margin-top:10px"><a href="donor/opportunities.php" class="btn-small">View</a></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
