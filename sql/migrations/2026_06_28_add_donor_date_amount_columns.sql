-- Migration: Add missing donor columns for application workflow
ALTER TABLE `donors`
  ADD COLUMN IF NOT EXISTS `date_applied` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `date_approved` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `amount` DECIMAL(10,2) NULL,
  ADD COLUMN IF NOT EXISTS `preferred_contact` ENUM('email','phone','both') DEFAULT 'both';

-- Note: Some MySQL versions do not support IF NOT EXISTS for ADD COLUMN.
-- If your MySQL rejects the above, run these commands conditionally after checking SHOW COLUMNS.
