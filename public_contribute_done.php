<?php
session_start();
$success = $_SESSION['public_contribute_success'] ?? null;
unset($_SESSION['public_contribute_success']);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Contribution Complete</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:#f8fafc} .container{max-width:720px;margin:36px auto;padding:18px} .card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Thank you</h2>
        <?php if ($success): ?>
            <p><?php echo htmlspecialchars($success); ?></p>
        <?php else: ?>
            <p>Your contribution has been recorded.</p>
        <?php endif; ?>
        <p><a href="index.php">Return to Home</a></p>
    </div>
</div>
</body>
</html>