<?php
session_start();
require_once 'config/database.php';
require_once 'config/rate_limiter.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip_key = 'ip_' . $ip;
$ip_remaining = rl_remaining($ip_key, 60*60, 10);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Contribute - Orphanage (No Login)</title>
    <link rel="stylesheet" href="assets/css/all.min.css">
    <style>
        body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:#f8fafc}
        .container{max-width:720px;margin:36px auto;padding:18px}
        .card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 8px 30px rgba(2,6,23,0.06)}
        label{display:block;margin-bottom:6px;font-weight:600}
        .form-control{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:12px}
        .btn{display:inline-block;padding:10px 16px;border-radius:8px;border:none;background:#4f46e5;color:#fff;font-weight:700}
        .muted{color:#6b7280;font-size:14px}
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Contribute without login</h2>
        <p class="muted">Support our children quickly—enter amount, choose payment method and your phone number. We'll send an SMS with a confirmation code to complete the payment. <strong>IP OTP quota remaining:</strong> <?php echo $ip_remaining; ?> per hour.</p>
        <form id="publicContributeForm" action="public_contribute_send_otp.php" method="POST">
            <label for="amount">Amount</label>
            <input type="number" step="0.01" min="0.5" name="amount" id="amount" class="form-control" required placeholder="Enter amount e.g. 5000">

            <label for="currency">Currency</label>
            <select name="currency" id="currency" class="form-control">
                <option value="TSh">TSh</option>
                <option value="USD">USD</option>
            </select>

            <label for="method">Payment method</label>
            <select name="method" id="method" class="form-control">
                <option value="mobile_money">Mobile Money</option>
                <option value="card">Card</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="paypal">PayPal</option>
            </select>

            <label for="phone">Phone number</label>
            <input type="tel" name="phone" id="phone" class="form-control" required placeholder="e.g. +2557XXXXXXXX">

            <div style="margin-top:12px">
                <button class="btn" type="submit">Pay Now</button>
                <a href="index.php" style="margin-left:12px; color:#374151">Cancel</a>
            </div>
        </form>
        <script>
            // If user picks card payment, send to Stripe create endpoint instead of OTP flow
            document.getElementById('publicContributeForm').addEventListener('submit', function(e){
                var method = document.getElementById('method').value;
                if (method === 'card') {
                    e.preventDefault();
                    // create a form and POST to stripe create endpoint
                    var f = document.createElement('form');
                    f.method = 'POST';
                    f.action = 'public_contribute_stripe_create.php';
                    ['amount','currency','method','phone'].forEach(function(name){
                        var el = document.querySelector('[name="'+name+'"]');
                        if (el) {
                            var inp = document.createElement('input'); inp.type='hidden'; inp.name=name; inp.value=el.value; f.appendChild(inp);
                        }
                    });
                    document.body.appendChild(f); f.submit();
                }
            });
        </script>
    </div>
</div>
</body>
</html>