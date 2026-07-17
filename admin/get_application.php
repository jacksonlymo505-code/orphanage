<?php
session_start();
include '../config/database.php';

$app_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$app = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donor_applications WHERE id='$app_id'"));

if (!$app) {
    echo "Application not found.";
    exit();
}
?>
<div>
    <h3><?php echo htmlspecialchars($app['full_name']); ?></h3>
    <div style="margin:15px 0;">
        <p><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($app['phone']); ?></p>
        <p><strong>Organization:</strong> <?php echo htmlspecialchars($app['organization_name'] ?? 'N/A'); ?></p>
        <p><strong>Support Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $app['support_type'])); ?></p>
        <p><strong>Preferred Contact:</strong> <?php echo ucfirst($app['preferred_contact']); ?></p>
    </div>

    <p><strong>Description:</strong></p>
    <p style="padding:10px;background:#f5f5f5;border-radius:6px;">
        <?php echo nl2br(htmlspecialchars($app['description'])); ?>
    </p>

    <?php if ($app['status'] === 'pending'): ?>
    <div style="margin:20px 0;padding:15px;background:#fff3cd;border-left:4px solid #ffc107;border-radius:6px;">
        <strong style="color:#856404;">⏳ Status: PENDING REVIEW</strong><br>
        <small style="color:#856404;">Admin review required. Choose to approve (creates donor account + sends credentials) or reject.</small>
    </div>
    
    <form id="application-form-<?php echo $app_id; ?>" method="POST" action="manage_donors.php" style="margin-top:20px;">
        <div style="margin-bottom:15px;">
            <label><strong>Add Notes (Optional):</strong></label>
            <textarea id="notes-app-<?php echo $app_id; ?>" name="notes" placeholder="Add approval or rejection notes..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;min-height:80px;"></textarea>
        </div>

        <input type="hidden" name="application_id" value="<?php echo $app_id; ?>">
        <input type="hidden" name="action" value="">
        <input type="hidden" name="notes" value="">

        <button type="button" class="btn btn-approve" style="padding:10px 20px;margin-right:10px;" onclick="approveApplicationFromModal(<?php echo $app_id; ?>, '<?php echo htmlspecialchars($app['email']); ?>', '<?php echo htmlspecialchars($app['phone']); ?>')">
            <i class="fas fa-check"></i> ✓ Approve & Create Donor
        </button>
        <button type="button" class="btn btn-reject" style="padding:10px 20px;" onclick="rejectApplicationFromModal(<?php echo $app_id; ?>)">
            <i class="fas fa-times"></i> ✗ Reject
        </button>
    </form>
    <?php else: ?>
    <div style="margin:20px 0;padding:15px;background:<?php echo $app['status'] === 'approved' ? '#d4edda' : '#f8d7da'; ?>;border-left:4px solid <?php echo $app['status'] === 'approved' ? '#28a745' : '#dc3545'; ?>;border-radius:6px;">
        <strong style="color:<?php echo $app['status'] === 'approved' ? '#155724' : '#721c24'; ?>;">
            <?php echo $app['status'] === 'approved' ? '✓ APPROVED' : '✗ REJECTED'; ?>
        </strong>
        <p style="margin:10px 0 0 0;color:<?php echo $app['status'] === 'approved' ? '#155724' : '#721c24'; ?>;">
            <?php if ($app['status'] === 'approved'): ?>
                Donor account created. Credentials sent via email and SMS.
            <?php else: ?>
                Application rejected.
            <?php endif; ?>
        </p>
        <?php if ($app['date_reviewed']): ?>
        <p style="margin:8px 0 0 0;font-size:0.9rem;">
            <strong>Reviewed:</strong> <?php echo date('M d, Y H:i', strtotime($app['date_reviewed'])); ?>
        </p>
        <?php endif; ?>
        <?php if ($app['notes']): ?>
        <p style="margin:8px 0 0 0;font-size:0.9rem;">
            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($app['notes'])); ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .btn{padding:10px 16px;border:none;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600;color:#fff;}
    .btn-approve{background:#43a047;}
    .btn-reject{background:#d32f2f;}
</style>
