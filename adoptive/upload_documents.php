<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adoptive') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request token.';
    }
    
    if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a file to upload.';
    } else {
        $file = $_FILES['document'];
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'File type not allowed. Allowed: pdf, jpg, png, doc.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'File too large. Max 10MB.';
        } else {
            $uploadsDir = __DIR__ . '/../uploads/documents';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $basename = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = $uploadsDir . '/' . $basename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // ensure documents table exists and insert a record
                try {
                    $userEmail = '';
                    $r = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1");
                    if ($r && $row = $r->fetch_assoc()) $userEmail = $row['email'];

                    // create table if it does not exist
                    $createSql = "CREATE TABLE IF NOT EXISTS documents (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        user_email VARCHAR(255),
                        filename VARCHAR(255),
                        original_name VARCHAR(255),
                        status ENUM('received','submitted','reviewed','accepted','rejected') DEFAULT 'received',
                        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        notes TEXT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $conn->query($createSql);

                    $stmt = $conn->prepare("INSERT INTO documents (user_id, user_email, filename, original_name, status, uploaded_at) VALUES (?, ?, ?, ?, 'received', NOW())");
                    if ($stmt) {
                        $stmt->bind_param('isss', $user_id, $userEmail, $basename, $file['name']);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $success = 'File uploaded successfully.';
                } catch (Exception $e) {
                    $errors[] = 'Upload saved but failed to record in database.';
                }
            } else {
                $errors[] = 'Failed to move uploaded file.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Upload Documents</title>
    <link rel="stylesheet" href="../assets/css/all.min.css">
    <style>
        body{font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:#f7fbff;margin:0;padding:24px}
        .panel{max-width:760px;margin:32px auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,0.08)}
        .field{margin-bottom:12px}
        .btn{background:#1e3a8a;color:#fff;padding:8px 14px;border-radius:8px;border:0}
        .muted{color:#677389;font-size:14px}
        .errors{background:#fff6f6;border:1px solid #ffe4e6;padding:10px;border-radius:8px;margin-bottom:12px;color:#7f1d1d}
        .success{background:#f0fdf4;border:1px solid #bbf7d0;padding:10px;border-radius:8px;margin-bottom:12px;color:#065f46}
    </style>
</head>
<body>
    <div class="panel">
        <h2>Upload Supporting Documents</h2>
        <p class="muted">Attach files like identity documents, medical records, or proof of residence (max 10MB).</p>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="field">
                <label for="document">Select file</label><br>
                <input type="file" name="document" id="document" required>
            </div>
            <div class="field">
                <button class="btn" type="submit">Upload</button>
                <a href="dashboard.php" style="margin-left:12px;color:#1e3a8a">Back to dashboard</a>
            </div>
        </form>
    </div>
</body>
</html>
