-- Create Donors/Sponsors Table
CREATE TABLE IF NOT EXISTS `donors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `phone` varchar(20) NOT NULL,
  `support_type` enum('one_time','monthly','sponsorship','in_kind','other') NOT NULL,
  `amount` decimal(10,2),
  `description` text,
  `organization_name` varchar(255),
  `status` enum('pending','approved','rejected','inactive') NOT NULL DEFAULT 'pending',
  `donor_username` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `preferred_contact` enum('email','phone','both') DEFAULT 'both',
  `date_applied` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_approved` datetime,
  `approved_by` int,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
)
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- Create Donations/Contributions Table
CREATE TABLE IF NOT EXISTS `contributions` (
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
)
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- Create Donor Messages Table
CREATE TABLE IF NOT EXISTS `donor_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `donor_id` int NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('update','receipt','thank_you','request','other') NOT NULL,
  `sent_date` datetime NOT NULL,
  `read_status` boolean DEFAULT FALSE,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`donor_id`) REFERENCES `donors`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
