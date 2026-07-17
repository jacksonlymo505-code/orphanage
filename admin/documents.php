<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$docs = [];
try {
    $res = $conn->query("SELECT d.*, u.name AS uploader_name FROM documents d LEFT JOIN users u ON u.id = d.user_id ORDER BY d.uploaded_at DESC");
    if ($res) $docs = $res->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Documents</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f7fbff;margin:0;padding:24px}
        .panel{max-width:1100px;margin:24px auto;background:#fff;padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,0.08)}
        .row{display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:10px;border:1px solid #eef2ff;margin-bottom:10px}
        .meta{display:flex;gap:12px;align-items:center}
        .badge{background:#eef2ff;color:#1e3a8a;padding:8px 10px;border-radius:8px;font-weight:700}
        .muted{color:#6b7280}
        .btn{background:#1e3a8a;color:#fff;padding:8px 12px;border-radius:8px;border:0;margin-left:8px}
        textarea{width:100%;height:80px}
    </style>
</head>
<body>
    <div class="panel">
        <h2>Documents Review</h2>
        <p class="muted">Review uploaded documents and change status.</p>

        <?php if (empty($docs)): ?>
            <div style="padding:18px;border-radius:10px;background:#fff6f3;border:1px solid #ffe4d6">No documents found.</div>
        <?php else: ?>
            <?php foreach ($docs as $d): ?>
                <div class="row">
                    <div>
                        <div style="font-weight:700"><?php echo htmlspecialchars($d['original_name']); ?> <span class="muted">(by <?php echo htmlspecialchars($d['uploader_name'] ?? 'user'); ?>)</span></div>
                        <div class="muted">Uploaded: <?php echo date('M j, Y H:i', strtotime($d['uploaded_at'])); ?> — Status: <strong><?php echo htmlspecialchars($d['status']); ?></strong></div>
                    </div>
                    <div>
                        <a class="link" href="../uploads/documents/<?php echo rawurlencode($d['filename']); ?>" download>Download</a>
                        <div style="display:inline">
                            <button class="btn ajax-action" data-id="<?php echo (int)$d['id']; ?>" data-action="accept">Accept</button>
                            <button class="btn ajax-action" data-id="<?php echo (int)$d['id']; ?>" data-action="reject">Reject</button>
                            <button class="btn ajax-action" data-id="<?php echo (int)$d['id']; ?>" data-action="review">Mark Reviewed</button>
                            <a class="btn" href="document_review.php" onclick="event.preventDefault(); window.location='document_review.php';">Open Review</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function(){
    function handleAction(e){
        var btn = e.currentTarget;
        var id = btn.getAttribute('data-id');
        var action = btn.getAttribute('data-action');
        if (!id || !action) return;
        btn.disabled = true;
        fetch('document_action_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + encodeURIComponent(id) + '&action=' + encodeURIComponent(action) + '&csrf_token=' + encodeURIComponent('<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>')
        }).then(r=>r.json()).then(function(json){
            btn.disabled = false;
            if (json && json.success) {
                // update status text in the row
                var row = btn.closest('.row');
                if (row) {
                    var mut = row.querySelector('.muted');
                    if (mut) {
                        mut.innerHTML = 'Uploaded: ' + mut.innerHTML.split('—')[0].trim() + ' — Status: <strong>' + (json.status || '') + '</strong>';
                    }
                }
            } else {
                alert('Action failed: ' + (json.error || 'unknown'));
            }
        }).catch(function(){ btn.disabled = false; alert('Request failed'); });
    }

    var buttons = document.querySelectorAll('.ajax-action');
    buttons.forEach(function(b){ b.addEventListener('click', handleAction); });
});
</script>
</html>
