<?php
session_start();
require_once 'config/database.php';
require_once 'config/notifications.php';

$payment = $_SESSION['public_payment'] ?? null;
if (!$payment) {
    header('Location: public_contribute.php');
    exit();
}

$expires = ($payment['created_at'] + 15*60);
$expired = time() > $expires;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verify Contribution</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:#f8fafc}
        .container{max-width:640px;margin:36px auto;padding:18px}
        .card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
        .form-control{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:12px}
        .btn{display:inline-block;padding:10px 16px;border-radius:8px;border:none;background:#4f46e5;color:#fff;font-weight:700}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Confirm Contribution</h2>
        <p>Amount: <?php echo htmlspecialchars($payment['currency']); ?> <?php echo number_format($payment['amount'],2); ?></p>
        <p>Phone: <?php echo htmlspecialchars($payment['phone']); ?></p>
        <?php if (isset($_SESSION['public_contribute_warning'])): ?>
            <div style="background:#fff5e6;padding:10px;border-radius:6px;margin-bottom:12px;color:#92400e"><?php echo htmlspecialchars($_SESSION['public_contribute_warning']); unset($_SESSION['public_contribute_warning']); ?></div>
        <?php endif; ?>

        <?php if ($expired): ?>
            <div style="background:#fee2e2;padding:10px;border-radius:6px;margin-bottom:12px;color:#991b1b">This confirmation code has expired. Please start again.</div>
            <a href="public_contribute.php" class="btn">Start Over</a>
        <?php else: ?>
            <form method="POST" action="public_contribute_confirm.php">
                <label for="otp">Enter confirmation code (sent to your phone)</label>
                <input name="otp" id="otp" class="form-control" required placeholder="Enter 6-digit code">
                <div style="margin-top:8px">
                    <button class="btn" type="submit">Confirm and Pay</button>
                    <a href="public_contribute_resend.php" style="margin-left:12px">Resend code</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>