<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = $_POST['action'] ?? '';
    if ($action === 'save' && $id) {
        if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            header('Location: documents.php'); exit();
        }
        $status = $_POST['status'] ?? 'reviewed';
        $notes = $_POST['notes'] ?? '';
        $stmt = $conn->prepare("UPDATE documents SET status = ?, notes = ? WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ssi', $status, $notes, $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: documents.php'); exit();
    }

    if ($action === 'review' && $id) {
        // show review form
        $stmt = $conn->prepare("SELECT * FROM documents WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $doc = $res->fetch_assoc();
            $stmt->close();
        }
    }
}

if (empty($doc)) { header('Location: documents.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Review Document</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>body{font-family:Arial,Helvetica,sans-serif;background:#f7fbff;margin:0;padding:24px}.panel{max-width:800px;margin:24px auto;background:#fff;padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,0.08)}.btn{background:#1e3a8a;color:#fff;padding:8px 12px;border-radius:8px;border:0;margin-left:8px}textarea{width:100%;height:120px}</style>
</head>
<body>
    <div class="panel">
        <h2>Review: <?php echo htmlspecialchars($doc['original_name']); ?></h2>
        <p><a href="../uploads/documents/<?php echo rawurlencode($doc['filename']); ?>" download>Download file</a></p>
        <form method="post">
            <input type="hidden" name="id" value="<?php echo (int)$doc['id']; ?>">
            <input type="hidden" name="action" value="save">
            <div style="margin-bottom:12px">
                <label>Status</label>
                <select name="status">
                    <option value="reviewed" <?php echo $doc['status']=='reviewed'?'selected':'';?>>Reviewed</option>
                    <option value="accepted" <?php echo $doc['status']=='accepted'?'selected':'';?>>Accept</option>
                    <option value="rejected" <?php echo $doc['status']=='rejected'?'selected':'';?>>Reject</option>
                </select>
            </div>
            <div style="margin-bottom:12px">
                <label>Notes</label>
                <textarea name="notes"><?php echo htmlspecialchars($doc['notes']); ?></textarea>
            </div>
            <div>
                <button class="btn" type="submit">Save</button>
                <a href="documents.php" style="margin-left:12px">Back</a>
            </div>
        </form>
    </div>
</body>
</html>
