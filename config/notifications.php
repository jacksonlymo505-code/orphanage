<?php
// SMS notifications configuration and helper

// Provider: 'log' (default) or 'twilio'
define('SMS_PROVIDER', getenv('SMS_PROVIDER') ?: 'log');

// Admin phone number to receive adoptive parent messages. Configure to real E.164 number when ready.
define('ADMIN_PHONE', getenv('ADMIN_PHONE') ?: '+255700000000');

// Twilio credentials (if using Twilio provider). Set via environment variables or replace below.
define('TWILIO_SID', getenv('TWILIO_SID') ?: '');
define('TWILIO_TOKEN', getenv('TWILIO_TOKEN') ?: '');
define('TWILIO_FROM', getenv('TWILIO_FROM') ?: '');

// Log file for 'log' provider
define('SMS_LOG_FILE', __DIR__ . '/../logs/sms.log');
// Rate-limit store
define('SMS_RATE_FILE', __DIR__ . '/../logs/sms_rates.json');

/**
 * Send an SMS message using configured provider.
 * Returns array: ['success' => bool, 'message' => string]
 */
function send_sms_message($to, $text) {
    $to = trim($to);
    $text = trim($text);

    if ($to === '' || $text === '') {
        return ['success' => false, 'message' => 'Missing destination or message.'];
    }

    if (SMS_PROVIDER === 'twilio') {
        if (TWILIO_SID === '' || TWILIO_TOKEN === '' || TWILIO_FROM === '') {
            return ['success' => false, 'message' => 'Twilio not configured.'];
        }
        $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
        $data = http_build_query([
            'From' => TWILIO_FROM,
            'To' => $to,
            'Body' => $text
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ':' . TWILIO_TOKEN);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => 'HTTP error: ' . $err];
        }
        if ($code >= 200 && $code < 300) {
            return ['success' => true, 'message' => 'Message sent via Twilio.'];
        }
        return ['success' => false, 'message' => 'Twilio error: HTTP ' . $code . ' - ' . substr($resp, 0, 200)];
    }

    // Default: write to log for local testing
    $logDir = dirname(SMS_LOG_FILE);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $entry = [
        'timestamp' => date('c'),
        'to' => $to,
        'body' => $text
    ];
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents(SMS_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    return ['success' => true, 'message' => 'Message written to log for local testing.'];
}

/**
 * Simple rate limiter: allow N messages per timeframe per user id.
 * Stores small JSON map in SMS_RATE_FILE.
 */
function sms_rate_check_and_record($user_id, $limit = 5, $period_seconds = 3600) {
    $file = SMS_RATE_FILE;
    $data = [];
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true) ?: [];
    }
    $now = time();
    $userKey = (string)$user_id;
    $timestamps = isset($data[$userKey]) ? array_filter($data[$userKey], function($t) use ($now, $period_seconds){ return ($now - $t) <= $period_seconds; }) : [];
    if (count($timestamps) >= $limit) {
        return ['allowed' => false, 'remaining' => 0, 'reset' => $period_seconds - ($now - min($timestamps))];
    }
    $timestamps[] = $now;
    $data[$userKey] = $timestamps;
    $logDir = dirname($file);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    file_put_contents($file, json_encode($data), LOCK_EX);
    return ['allowed' => true, 'remaining' => $limit - count($timestamps), 'reset' => $period_seconds];
}

return true;
