<?php
/**
 * Helper functions for the Orphanage Management System
 */

/**
 * Get the default currency from settings
 * 
 * @return string The default currency code (e.g., 'TSh')
 */
function get_currency() {
    global $conn;
    
    // Check if connection is available
    if (!$conn) {
        return 'TSh'; // Default fallback
    }
    
    // Try to get from settings table
    $query = "SELECT currency FROM settings LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['currency'];
    }
    
    // Default to TSh if no setting found
    return 'TSh';
}

/**
 * Detect device type from user agent
 * 
 * @return string Device type: 'mobile', 'tablet', or 'desktop'
 */
function detect_device_type() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) {
        return 'mobile';
    } elseif (preg_match('/ipad|tablet|kindle|playbook/i', $ua)) {
        return 'tablet';
    } else {
        return 'desktop';
    }
}

function generate_public_contribution_ref() {
    return 'PUB-' . time() . '-' . random_int(1000, 9999);
}

function ensure_public_contributions_table_exists() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS `public_contributions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `donor_id` int(11) DEFAULT NULL,
        `phone` varchar(50) NOT NULL,
        `donor_email` varchar(255) DEFAULT NULL,
        `amount` decimal(12,2) NOT NULL,
        `currency` varchar(10) NOT NULL DEFAULT 'TSh',
        `payment_method` varchar(100) NOT NULL,
        `status` enum('pending','otp_sent','otp_verified','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
        `transaction_id` varchar(255) DEFAULT NULL,
        `source` varchar(50) DEFAULT 'public',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `otp_sent_at` datetime DEFAULT NULL,
        `otp_verified_at` datetime DEFAULT NULL,
        `payment_started_at` datetime DEFAULT NULL,
        `completed_at` datetime DEFAULT NULL,
        `failed_at` datetime DEFAULT NULL,
        `failure_reason` text DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `payment_attempts` int(11) DEFAULT 0,
        `device_type` varchar(50) DEFAULT NULL,
        `referrer_url` varchar(500) DEFAULT NULL,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `donor_id` (`donor_id`),
        KEY `phone` (`phone`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $conn->query($sql);
}

function migrate_public_contributions_table() {
    global $conn;
    
    // Check and add missing columns to public_contributions table
    $columns_to_add = [
        'donor_email' => "VARCHAR(255) DEFAULT NULL",
        'otp_sent_at' => "DATETIME DEFAULT NULL",
        'otp_verified_at' => "DATETIME DEFAULT NULL",
        'payment_started_at' => "DATETIME DEFAULT NULL",
        'failed_at' => "DATETIME DEFAULT NULL",
        'failure_reason' => "TEXT DEFAULT NULL",
        'user_agent' => "TEXT DEFAULT NULL",
        'payment_attempts' => "INT(11) DEFAULT 0",
        'device_type' => "VARCHAR(50) DEFAULT NULL",
        'referrer_url' => "VARCHAR(500) DEFAULT NULL",
    ];
    
    // Get existing columns
    $result = $conn->query("SHOW COLUMNS FROM public_contributions");
    $existing = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[$row['Field']] = true;
        }
    }
    
    // Add missing columns
    foreach ($columns_to_add as $col => $def) {
        if (!isset($existing[$col])) {
            $conn->query("ALTER TABLE public_contributions ADD COLUMN `$col` $def");
        }
    }
    
    // Update status enum if needed
    if (isset($existing['status'])) {
        // Check current enum values
        $result = $conn->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='public_contributions' AND COLUMN_NAME='status'");
        if ($result && $row = $result->fetch_assoc()) {
            $type = $row['COLUMN_TYPE'];
            // Only update if it doesn't have all the new status values
            if (strpos($type, 'otp_sent') === false) {
                $conn->query("ALTER TABLE public_contributions CHANGE COLUMN `status` `status` ENUM('pending','otp_sent','otp_verified','processing','completed','failed','cancelled') DEFAULT 'pending'");
            }
        }
    }
}

function create_public_contribution_record($data) {
    global $conn;
    ensure_public_contributions_table_exists();
    migrate_public_contributions_table();

    $stmt = $conn->prepare('INSERT INTO public_contributions (donor_id, phone, donor_email, amount, currency, payment_method, status, transaction_id, source, notes, completed_at, otp_sent_at, otp_verified_at, payment_started_at, failed_at, failure_reason, ip_address, user_agent, payment_attempts, device_type, referrer_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return 0;
    }

    $donor_id = isset($data['donor_id']) ? $data['donor_id'] : null;
    $phone = $data['phone'];
    $donor_email = $data['donor_email'] ?? null;
    $amount = $data['amount'];
    $currency = $data['currency'];
    $payment_method = $data['payment_method'];
    $status = $data['status'] ?? 'pending';
    $transaction_id = $data['transaction_id'] ?? null;
    $source = $data['source'] ?? 'public';
    $notes = $data['notes'] ?? null;
    $completed_at = $data['completed_at'] ?? null;
    $otp_sent_at = $data['otp_sent_at'] ?? null;
    $otp_verified_at = $data['otp_verified_at'] ?? null;
    $payment_started_at = $data['payment_started_at'] ?? null;
    $failed_at = $data['failed_at'] ?? null;
    $failure_reason = $data['failure_reason'] ?? null;
    $ip_address = $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    $user_agent = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
    $payment_attempts = isset($data['payment_attempts']) ? (int)$data['payment_attempts'] : 0;
    $device_type = $data['device_type'] ?? null;
    $referrer_url = $data['referrer_url'] ?? ($_SERVER['HTTP_REFERER'] ?? null);

    $stmt->bind_param('issdssssssssssssssiss', $donor_id, $phone, $donor_email, $amount, $currency, $payment_method, $status, $transaction_id, $source, $notes, $completed_at, $otp_sent_at, $otp_verified_at, $payment_started_at, $failed_at, $failure_reason, $ip_address, $user_agent, $payment_attempts, $device_type, $referrer_url);
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }
    $insert_id = $conn->insert_id;
    $stmt->close();
    return $insert_id;
}

function update_public_contribution_record($id, $data) {
    global $conn;
    ensure_public_contributions_table_exists();
    migrate_public_contributions_table();

    $fields = [];
    $params = [];
    $types = '';

    foreach ($data as $key => $value) {
        $fields[] = "`$key` = ?";
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_double($value) || is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        $params[] = $value;
    }

    if (empty($fields)) {
        return false;
    }

    $sql = 'UPDATE public_contributions SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $types .= 'i';
    $params[] = $id;
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
