<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$docs = [];

try {
    $stmt = $conn->prepare("SELECT id, filename, original_name, status, uploaded_at, notes FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $docs = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Documents</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        body{font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:#f7fbff;margin:0;padding:24px}
        .panel{max-width:960px;margin:24px auto;background:#fff;padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,0.08)}
        .doc-row{display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:10px;border:1px solid #eef2ff;margin-bottom:10px}
        .meta{display:flex;gap:12px;align-items:center}
        .badge{background:#eef2ff;color:#1e3a8a;padding:8px 10px;border-radius:8px;font-weight:700}
        .muted{color:#6b7280}
        .actions form{display:inline}
        .btn{background:#1e3a8a;color:#fff;padding:8px 12px;border-radius:8px;border:0;margin-left:8px}
        .link{color:#1e3a8a;text-decoration:none}
    </style>
</head>
<body>
    <div class="panel">
        <h2>Uploaded Documents</h2>
        <p class="muted">Files you've uploaded and their review status.</p>

        <?php if (empty($docs)): ?>
            <div style="padding:18px;border-radius:10px;background:#fff6f3;border:1px solid #ffe4d6">No documents uploaded yet. <a class="link" href="upload_documents.php">Upload now</a></div>
        <?php else: ?>
            <?php foreach ($docs as $d): ?>
                <div class="doc-row">
                    <div class="meta">
                        <div class="badge"><i class="fas fa-file"></i></div>
                        <div>
                            <div style="font-weight:700"><?php echo htmlspecialchars($d['original_name']); ?></div>
                            <div class="muted">Uploaded: <?php echo date('M j, Y H:i', strtotime($d['uploaded_at'])); ?></div>
                        </div>
                    </div>
                    <div>
                        <span style="margin-right:12px" class="muted">Status: <strong><?php echo htmlspecialchars(ucfirst($d['status'])); ?></strong></span>
                        <a class="link" href="../uploads/documents/<?php echo rawurlencode($d['filename']); ?>" download>Download</a>
                        <form method="post" action="document_action.php" style="display:inline">
                            <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                            <?php if ($d['status'] !== 'submitted'): ?>
                                <button class="btn" name="action" value="submit">Mark Submitted</button>
                            <?php endif; ?>
                            <?php if ($d['status'] === 'submitted' || $d['status'] === 'received'): ?>
                                <button class="btn" name="action" value="request_review">Request Review</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
