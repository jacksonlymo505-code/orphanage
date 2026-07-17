<?php
session_start();
include '../config/database.php';

$donor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE id='$donor_id'"));

if (!$donor) {
    echo "Donor not found.";
    exit();
}
?>
<div>
    <h3><?php echo htmlspecialchars($donor['full_name']); ?></h3>
    
    <div style="margin:15px 0;">
        <p><strong>Email:</strong> <?php echo htmlspecialchars($donor['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($donor['phone']); ?></p>
        <p><strong>Organization:</strong> <?php echo htmlspecialchars($donor['organization_name'] ?? 'N/A'); ?></p>
        <p><strong>Support Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $donor['support_type'])); ?></p>
        <p><strong>Amount:</strong> $<?php echo number_format($donor['amount'], 2); ?></p>
        <p><strong>Preferred Contact:</strong> <?php echo ucfirst($donor['preferred_contact']); ?></p>
    </div>

    <p><strong>Description:</strong></p>
    <p style="padding:10px;background:#f5f5f5;border-radius:6px;">
        <?php echo nl2br(htmlspecialchars($donor['description'])); ?>
    </p>

    <?php if ($donor['status'] === 'pending'): ?>
    <div style="margin:20px 0;padding:15px;background:#fff3cd;border-left:4px solid #ffc107;border-radius:6px;">
        <strong style="color:#856404;">⏳ Status: PENDING REVIEW</strong><br>
        <small style="color:#856404;">Admin review required. Choose to approve (creates account + sends credentials) or reject.</small>
    </div>
    
    <form id="donor-form-<?php echo $donor_id; ?>" method="POST" style="margin-top:20px;">
        <div style="margin-bottom:15px;">
            <label><strong>Add Notes (Optional):</strong></label>
            <textarea id="notes-<?php echo $donor_id; ?>" name="notes" placeholder="Add approval or rejection notes..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;min-height:80px;"></textarea>
        </div>
        
        <input type="hidden" name="donor_id" value="<?php echo $donor_id; ?>">
        <input type="hidden" name="action" value="">
        <input type="hidden" name="notes" value="">
        
        <button type="button" class="btn btn-approve" onclick="approveDonor(<?php echo $donor_id; ?>)" style="padding:10px 20px;margin-right:10px;">
            <i class="fas fa-check"></i> ✓ Approve & Create Account
        </button>
        <button type="button" class="btn btn-reject" onclick="rejectDonor(<?php echo $donor_id; ?>)" style="padding:10px 20px;">
            <i class="fas fa-times"></i> ✗ Reject
        </button>
    </form>
    <?php else: ?>
    <div style="margin:20px 0;padding:15px;background:<?php echo $donor['status'] === 'approved' ? '#d4edda' : '#f8d7da'; ?>;border-left:4px solid <?php echo $donor['status'] === 'approved' ? '#28a745' : '#dc3545'; ?>;border-radius:6px;">
        <strong style="color:<?php echo $donor['status'] === 'approved' ? '#155724' : '#721c24'; ?>;">
            <?php echo $donor['status'] === 'approved' ? '✓ APPROVED' : '✗ REJECTED'; ?>
        </strong>
        <p style="margin:10px 0 0 0;color:<?php echo $donor['status'] === 'approved' ? '#155724' : '#721c24'; ?>;">
            <?php if ($donor['status'] === 'approved'): ?>
                Account created. Credentials sent via email and SMS.
            <?php else: ?>
                Application rejected.
            <?php endif; ?>
        </p>
        <?php if ($donor['date_approved']): ?>
        <p style="margin:8px 0 0 0;font-size:0.9rem;">
            <strong>Status Updated:</strong> <?php echo date('M d, Y H:i', strtotime($donor['date_approved'])); ?>
        </p>
        <?php endif; ?>
        <?php if ($donor['notes']): ?>
        <p style="margin:8px 0 0 0;font-size:0.9rem;">
            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($donor['notes'])); ?>
        </p>
        <?php endif; ?>
        <?php if ($donor['status'] === 'approved'): ?>
            <form method="POST" action="resend_donor_credentials.php" style="margin-top:12px;">
                <input type="hidden" name="donor_id" value="<?php echo $donor_id; ?>">
                <button type="submit" class="btn" style="background:#1e88e5;padding:8px 14px;border-radius:6px;color:#fff;font-weight:700;"><i class="fas fa-redo"></i> Resend Credentials via Email & SMS</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .btn{padding:10px 16px;border:none;border-radius:6px;cursor:pointer;font-size:14px;font-weight:600;color:#fff;}
    .btn-approve{background:#43a047;}
    .btn-reject{background:#d32f2f;}
</style>
