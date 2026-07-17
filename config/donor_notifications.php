<?php
// Email notification helper functions

function send_donor_approval_email($donor_email, $donor_name, $temp_password = null) {
    $subject = "Your Donor Application Approved";
    $body = "Dear $donor_name,\n\n";
    $body .= "Congratulations! Your donor/sponsor application has been approved.\n\n";
    if ($temp_password) {
        $body .= "We have created a secure donor account for you. Use the credentials below to sign in and set a new password:\n\n";
        $body .= "Username: $donor_email\n";
        $body .= "Temporary Password: $temp_password\n\n";
        $body .= "For security, please log in and change your password immediately.\n\n";
    } else {
        $body .= "You can now log in to your donor dashboard using your email and create a password.\n\n";
    }
    $body .= "Dashboard URL: " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . "/donor/donor_dashboard.php\n\n";
    $body .= "Thank you for supporting our children!\n\n";
    $body .= "Best regards,\nOrphanage Management Team";
    
    $headers = "From: admin@orphanage.com\r\nReply-To: admin@orphanage.com";
    $ok = @mail($donor_email, $subject, $body, $headers);
    if (!$ok) {
        error_log("[donor_notifications] Failed to send approval email to $donor_email");
    }
}

/**
 * Send donor approval notification via both EMAIL and SMS
 */
function send_donor_approval_email_and_sms($donor_email, $donor_phone, $donor_name, $temp_password = null) {
    // Send email
    send_donor_approval_email($donor_email, $donor_name, $temp_password);
    
    // Send SMS with credentials (concise format)
    if ($temp_password && $donor_phone) {
        include_once __DIR__ . '/notifications.php';
        $sms_text = "Donor application approved! Email: $donor_email | Password: $temp_password | Login at: donor/donor_dashboard.php";
        send_sms_message($donor_phone, $sms_text);
    }
}

function send_donor_rejection_email($donor_email, $donor_name, $reason = "") {
    $subject = "Your Donor Application Status";
    $body = "Dear $donor_name,\n\n";
    $body .= "Thank you for your interest in supporting our children.\n\n";
    $body .= "Unfortunately, your application has not been approved at this time.\n\n";
    if ($reason) {
        $body .= "Reason: $reason\n\n";
    }
    $body .= "Please contact us for more information or to reapply.\n\n";
    $body .= "Best regards,\nOrphanage Management Team";
    
    $headers = "From: admin@orphanage.com\r\nReply-To: admin@orphanage.com";
    $ok = @mail($donor_email, $subject, $body, $headers);
    if (!$ok) {
        error_log("[donor_notifications] Failed to send rejection email to $donor_email");
    }
}

/**
 * Send donor rejection notification via both EMAIL and SMS (optional)
 */
function send_donor_rejection_email_and_sms($donor_email, $donor_phone, $donor_name, $reason = "") {
    // Send email
    send_donor_rejection_email($donor_email, $donor_name, $reason);
    
    // Send SMS notification (optional - for donors who prefer phone contact)
    if ($donor_phone) {
        include_once __DIR__ . '/notifications.php';
        $sms_reason = $reason ? " Reason: " . substr($reason, 0, 50) : '';
        $sms_text = "Your donor application has been reviewed.{$sms_reason} Please check your email for details.";
        send_sms_message($donor_phone, $sms_text);
    }
}

function send_contribution_receipt($donor_email, $donor_name, $amount, $date, $transaction_id = "") {
    $subject = "Donation Receipt - Thank You";
    $body = "Dear $donor_name,\n\n";
    $body .= "Thank you for your generous donation!\n\n";
    $body .= "Donation Details:\n";
    $body .= "Amount: \$$amount\n";
    $body .= "Date: $date\n";
    if ($transaction_id) {
        $body .= "Transaction ID: $transaction_id\n";
    }
    $body .= "\nYour contribution will make a significant impact on the lives of our children.\n\n";
    $body .= "You can view your contribution history in your donor dashboard.\n\n";
    $body .= "Best regards,\nOrphanage Management Team";
    
    $headers = "From: admin@orphanage.com\r\nReply-To: admin@orphanage.com";
    $ok = @mail($donor_email, $subject, $body, $headers);
    if (!$ok) {
        error_log("[donor_notifications] Failed to send contribution receipt to $donor_email");
    }
}

function send_admin_new_donor_notification($admin_email, $donor_name, $donor_email, $support_type) {
    $subject = "New Donor Application: $donor_name";
    $body = "A new donor/sponsor application has been submitted.\n\n";
    $body .= "Name: $donor_name\n";
    $body .= "Email: $donor_email\n";
    $body .= "Support Type: " . ucfirst(str_replace('_', ' ', $support_type)) . "\n\n";
    $body .= "Please log in to the admin panel to review and approve/reject this application.\n";
    $body .= "Admin URL: " . $_SERVER['HTTP_HOST'] . "/admin/manage_donors.php";
    
    $headers = "From: admin@orphanage.com\r\nReply-To: admin@orphanage.com";
    $ok = @mail($admin_email, $subject, $body, $headers);
    if (!$ok) {
        error_log("[donor_notifications] Failed to send admin notification to $admin_email");
    }
}

function send_monthly_contribution_reminder($donor_email, $donor_name, $amount) {
    $subject = "Monthly Contribution Reminder";
    $body = "Dear $donor_name,\n\n";
    $body .= "This is a friendly reminder that your monthly contribution of \$$amount is due.\n\n";
    $body .= "Thank you for your continued support!\n\n";
    $body .= "Best regards,\nOrphanage Management Team";
    
    $headers = "From: admin@orphanage.com\r\nReply-To: admin@orphanage.com";
    $ok = @mail($donor_email, $subject, $body, $headers);
    if (!$ok) {
        error_log("[donor_notifications] Failed to send monthly reminder to $donor_email");
    }
}

function send_impact_report($donor_email, $donor_name, $report_data) {
    $subject = "Your Impact Report";
    $body = "Dear $donor_name,\n\n";
    $body .= "Thank you for your continued support. Here's your impact report:\n\n";
    $body .= "Total Contributions: \$" . number_format($report_data['total'], 2) . "\n";
    $body .= "Number of Contributions: " . $report_data['count'] . "\n";
    $body .= "Children Supported: " . $report_data['children_supported'] . "\n";
    $body .= "Last Contribution: " . $report_data['last_contribution'] . "\n\n";
    $body .= "Thank you for making a difference!\n\n";
    $body .= "Best regards,\nOrphanage Management Team";
    
    $headers = "From: admin@orphanage.com\r\nReply-To: admin@orphanage.com";
    $ok = @mail($donor_email, $subject, $body, $headers);
    if (!$ok) {
        error_log("[donor_notifications] Failed to send impact report to $donor_email");
    }
}
?>
