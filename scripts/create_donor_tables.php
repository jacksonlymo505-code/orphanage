#!/usr/bin/env php
<?php
/**
 * Database Migration Script for Donor/Sponsor System
 * Run this script to set up the necessary database tables
 * Usage: php scripts/create_donor_tables.php
 */

include 'config/database.php';

$sql_queries = [
    // Create Donors Table (updated with credential fields)
    "CREATE TABLE IF NOT EXISTS `donors` (
      `id` int NOT NULL AUTO_INCREMENT,
      `full_name` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL UNIQUE,
      `phone` varchar(20) NOT NULL,
      `support_type` enum('one_time','monthly','sponsorship','in_kind','other') NOT NULL,
      `amount` decimal(10,2),
      `description` text,
      `organization_name` varchar(255),
      `status` enum('pending','approved','rejected','inactive') NOT NULL DEFAULT 'pending',
      `preferred_contact` enum('email','phone','both') DEFAULT 'both',
      `donor_username` varchar(255) DEFAULT NULL,
      `password_hash` varchar(255) DEFAULT NULL,
      `is_active` tinyint(1) DEFAULT 0,
      `date_applied` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `date_approved` datetime,
      `approved_by` int,
      `notes` text,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Create Contributions Table
    "CREATE TABLE IF NOT EXISTS `contributions` (
      `id` int NOT NULL AUTO_INCREMENT,
      `donor_id` int NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `contribution_date` datetime NOT NULL,
      `contribution_type` enum('financial','in_kind','volunteer_hours','other') NOT NULL,
      `description` text,
      `payment_method` varchar(100),
      `transaction_id` varchar(255) UNIQUE,
      `receipt_sent` boolean DEFAULT FALSE,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`donor_id`) REFERENCES `donors`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Create Donor Messages Table
    "CREATE TABLE IF NOT EXISTS `donor_messages` (
      `id` int NOT NULL AUTO_INCREMENT,
      `donor_id` int NOT NULL,
      `message` text NOT NULL,
      `message_type` enum('update','receipt','thank_you','request','other') NOT NULL,
      `sent_date` datetime NOT NULL,
      `read_status` boolean DEFAULT FALSE,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`donor_id`) REFERENCES `donors`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$success = 0;
$failed = 0;

echo "Starting database migration for Donor/Sponsor System...\n";
echo str_repeat("=", 50) . "\n\n";

foreach ($sql_queries as $index => $query) {
    if (mysqli_query($conn, $query)) {
        $success++;
        echo "✓ Query " . ($index + 1) . " executed successfully\n";
    } else {
        $failed++;
        echo "✗ Query " . ($index + 1) . " failed: " . mysqli_error($conn) . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Migration completed!\n";
echo "Successful: $success | Failed: $failed\n";

if ($failed === 0) {
    echo "\n✓ All tables created successfully!\n";
    echo "\nThe following tables have been created:\n";
    echo "  - donors\n";
    echo "  - contributions\n";
    echo "  - donor_messages\n";
} else {
    echo "\n✗ Some queries failed. Please check the errors above.\n";
}

mysqli_close($conn);
?>
