-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 06:43 PM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 7.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `orphanage_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_type` enum('health','education','recreation','counseling','other') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `title`, `description`, `activity_type`, `start_date`, `end_date`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'General Health Check', 'Routine health checkup for all children at the orphanage.', 'health', '2025-06-01 09:00:00', '2025-06-01 12:00:00', 'Clinic Room 1', 'upcoming', '2025-05-06 14:12:44', '2025-05-06 14:12:44'),
(2, 'Back to School Program', 'Distributing school supplies and uniforms.', 'education', '2025-05-15 10:00:00', '2025-05-15 13:00:00', 'Main Hall', 'completed', '2025-05-06 14:12:44', '2025-05-06 14:12:44'),
(3, 'Fun Day Games', 'Outdoor games and music to promote social bonding.', 'recreation', '2025-06-10 14:00:00', '2025-06-10 17:00:00', 'Playground', 'upcoming', '2025-05-06 14:12:44', '2025-05-06 14:12:44'),
(4, 'Counseling Session', 'Psychological support for children who’ve faced trauma.', 'counseling', '2025-04-28 11:00:00', '2025-04-28 13:00:00', 'Counseling Room', 'completed', '2025-05-06 14:12:44', '2025-05-06 14:12:44'),
(5, 'Computer Literacy Workshop', 'Teaching basic computer skills to older children.', 'education', '2025-06-20 09:30:00', '2025-06-20 11:30:00', 'ICT Lab', 'upcoming', '2025-05-06 14:12:44', '2025-05-06 14:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `adoptions`
--

CREATE TABLE `adoptions` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `adopter_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL,
  `application_date` date NOT NULL,
  `approval_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `adoptions`
--

INSERT INTO `adoptions` (`id`, `child_id`, `adopter_id`, `status`, `application_date`, `approval_date`, `created_at`, `notes`) VALUES
(1, 1, 2, 'approved', '2025-04-01', '2025-04-10', '2025-05-06 14:19:07', NULL),
(2, 3, 4, 'approved', '2025-05-01', NULL, '2025-05-06 14:19:07', ''),
(3, 2, 1, 'completed', '2025-01-15', '2025-01-30', '2025-05-06 14:19:07', NULL),
(4, 4, 5, 'rejected', '2025-02-10', '2025-02-20', '2025-05-06 14:19:07', NULL),
(5, 5, 2, 'approved', '2025-05-05', NULL, '2025-05-06 14:19:07', ''),
(6, 3, 4, 'approved', '2025-06-11', NULL, '2025-06-11 02:46:46', 'nots'),
(7, 18, 6, 'approved', '2026-06-20', NULL, '2026-06-20 08:55:16', 'Child profile submitted by adoptive parent.'),
(8, 19, 9, 'rejected', '2026-06-20', NULL, '2026-06-20 15:09:41', 'Child profile submitted by adoptive parent.');

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `health_status` text DEFAULT NULL,
  `guardian_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `health_status`, `guardian_id`, `created_at`) VALUES
(1, 'Aisha', 'Mwita', '2015-06-12', 'female', 'Healthy', 1, '2025-05-06 08:49:31'),
(2, 'James', 'Musa', '2012-11-03', 'male', 'Needs regular medication for asthma', 2, '2025-05-06 08:49:31'),
(3, 'Neema', 'Joseph', '2017-09-25', 'female', 'Healthy', NULL, '2025-05-06 08:49:31'),
(4, 'Peter', 'John', '2013-04-19', 'male', 'Recovering from malnutrition', 3, '2025-05-06 08:49:31'),
(5, 'Fatuma', 'Ali', '2016-01-10', 'female', 'Minor physical disability', NULL, '2025-05-06 08:49:31'),
(6, 'Aisha', 'Mwita', '2015-06-12', 'female', 'Healthy', 1, '2025-05-06 13:08:34'),
(7, 'James', 'Musa', '2012-11-03', 'male', 'Asthmatic, needs inhaler', 2, '2025-05-06 13:08:34'),
(8, 'Neema', 'Joseph', '2012-02-09', 'female', 'Healthy', 3, '2025-05-06 13:08:34'),
(9, 'Peter', 'John', '2013-04-19', 'male', 'Recovering from malnutrition', 3, '2025-05-06 13:08:34'),
(10, 'Fatuma', 'Ali', '2016-01-10', 'female', 'Minor disability (limping)', NULL, '2025-05-06 13:08:34'),
(11, 'Baraka', 'Shayo', '2014-03-22', 'male', 'Healthy', 4, '2025-05-06 13:13:50'),
(12, 'Zawadi', 'Khalid', '2016-07-15', 'female', 'Healthy', 5, '2025-05-06 13:13:50'),
(13, 'Jabir', 'Abdul', '2013-12-01', 'male', 'Hearing impairment', 6, '2025-05-06 13:13:50'),
(14, 'Salma', 'Omari', '2018-10-18', 'female', 'Healthy', 7, '2025-05-06 13:13:50'),
(15, 'Khalfan', 'Yusuph', '2015-01-09', 'male', 'On medication for sickle cell', 8, '2025-05-06 13:13:50'),
(18, 'given', 'aloyce', '2023-01-05', 'female', 'eye damage', 2, '2026-06-20 08:55:16'),
(19, 'hassan mwinyi ', 'nabi', '2018-06-06', 'male', 'anemia', 1, '2026-06-20 15:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `contributions`
--

CREATE TABLE `contributions` (
  `id` int(11) NOT NULL,
  `opportunity_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donation_date` datetime DEFAULT current_timestamp(),
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `currency` varchar(3) DEFAULT 'USD'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_id`, `project_id`, `amount`, `donation_date`, `payment_method`, `status`, `notes`, `created_at`, `currency`) VALUES
(1, 2, NULL, '473200.00', '2025-05-06 16:10:41', 'bank_transfer', 'completed', 'bsnj', '2025-05-06 16:10:41', 'USD'),
(2, 2, NULL, '473204.00', '2025-05-06 20:15:06', 'paypal', 'completed', NULL, '2025-05-06 17:15:06', 'USD'),
(3, 2, NULL, '30000.00', '2025-05-07 10:46:20', 'bank_transfer', 'completed', 'i help', '2025-05-07 10:46:20', 'USD'),
(4, 2, NULL, '473200.00', '2025-05-07 18:08:17', 'paypal', 'completed', NULL, '2025-05-07 15:08:17', 'USD'),
(5, 2, NULL, '473.00', '2025-05-07 15:10:10', 'bank_transfer', 'completed', 'yyuinhi', '2025-05-07 15:10:10', 'USD'),
(6, 2, 1, '554.00', '2025-05-17 18:46:53', 'bank_transfer', 'completed', 'dfdsfg', '2025-05-17 18:46:53', 'USD'),
(7, 2, NULL, '2343.00', '2025-05-17 21:47:09', 'credit_card', 'completed', NULL, '2025-05-17 18:47:09', 'USD'),
(8, 2, 1, '1000.00', '2025-06-10 08:23:45', 'bank_transfer', 'completed', '239dskmr lrds', '2025-06-10 08:23:45', 'USD'),
(9, 2, 3, '12100.00', '2025-06-26 07:58:10', 'bank_transfer', 'completed', '123232o', '2025-06-26 07:58:10', 'USD'),
(10, 11, NULL, '1000000.00', '2026-06-22 13:51:48', 'bank_transfer', 'completed', NULL, '2026-06-22 10:51:48', 'TSh'),
(11, 11, 8, '100000.00', '2026-06-22 12:52:43', 'bank_transfer', 'completed', 'coplete', '2026-06-22 10:52:43', 'USD'),
(12, 11, 8, '1000000.00', '2026-06-22 13:34:00', 'bank_transfer', 'completed', 'complete', '2026-06-22 11:34:00', 'USD'),
(13, 14, 3, '100000.00', '2026-06-29 11:17:01', 'bank_transfer', 'completed', 'complete', '2026-06-29 09:17:01', 'USD'),
(14, 6, NULL, '100000.00', '2026-06-29 20:36:28', 'mobile_money', 'completed', 'Anonymous public contribution (auto-complete)', '2026-06-29 17:36:28', 'TSh'),
(15, 6, NULL, '100000.00', '2026-06-30 14:46:09', 'mobile_money', 'completed', 'Anonymous public contribution (auto-complete)', '2026-06-30 11:46:09', 'TSh');

-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE `donors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `type` enum('donor','adoptive') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(255) DEFAULT NULL,
  `support_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `organization_name` varchar(255) DEFAULT NULL,
  `preferred_contact` enum('email','phone','both') DEFAULT NULL,
  `donor_username` varchar(100) DEFAULT NULL,
  `date_applied` datetime DEFAULT NULL,
  `date_approved` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `donors`
--

INSERT INTO `donors` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `type`, `status`, `approval_status`, `is_active`, `created_at`, `full_name`, `support_type`, `description`, `organization_name`, `preferred_contact`, `donor_username`, `date_applied`, `date_approved`, `approved_by`, `reviewed_by`, `notes`) VALUES
(1, 'Ali', 'Mussa', 'ali@gmail.com', '0711223344', NULL, 'donor', 'active', 'pending', 0, '2025-05-06 14:12:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'bilali', 'Mwinyi', 'bilali@gmail.com', '0755667788', NULL, 'donor', 'active', 'pending', 0, '2025-05-06 14:12:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Peter', 'Maina', 'peter@gmail.com', '0788112233', NULL, 'donor', 'inactive', 'pending', 0, '2025-05-06 14:12:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Zainabu', 'Othman', 'zainabu@gmail.com', '0733445566', NULL, 'adoptive', 'active', 'pending', 0, '2025-05-06 14:12:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Richard', 'Kimaro', 'richard@gmail.com', '0766778899', NULL, 'donor', 'active', 'pending', 0, '2025-05-06 14:12:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'dama', 'dama', 'd@gmail.com', '0620449020', NULL, 'adoptive', 'active', 'pending', 0, '2026-06-19 13:23:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'dama', 'dama', 'd@gmail.com', '0620449020', NULL, 'adoptive', 'active', 'pending', 0, '2026-06-19 13:46:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'dama', 'dama', 'da@gmail.com', '0620449020', NULL, 'adoptive', 'active', 'pending', 0, '2026-06-19 14:25:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'juma', 'joseph', 'ju@gmail.com', '0620449020', NULL, 'adoptive', 'active', 'pending', 0, '2026-06-20 14:15:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'amani', 'hassan', 'a@gmail.com', '0752162198', NULL, 'adoptive', 'active', 'pending', 0, '2026-06-22 10:26:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'given', 'joseph', 'g@gmail.com', '0652162194', NULL, 'donor', 'active', 'pending', 0, '2026-06-22 10:50:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'maige', '', 'm@gmail.com', '0620449020', NULL, '', 'active', 'pending', 0, '2026-06-27 11:29:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'amani', 'swalehe hassan', 'am@gmail.com', '0620449020', NULL, 'donor', 'active', 'pending', 0, '2026-06-28 13:11:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'amani', 'swalehe hassan', 'opl@gmail.com', '0620449020', NULL, 'donor', 'active', 'pending', 0, '2026-06-28 13:34:42', 'amani swalehe hassan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Automated', 'Test Donor', 'test+bot@example.com', '+255700000000', NULL, 'donor', 'active', 'pending', 0, '2026-06-28 14:31:26', 'Automated Test Donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'juma', 'john joseph', 'j@gmail.com', '0620449020', '$2y$10$Cjc43IUhGTN8RZVFuuq5YeyPGr/mZRMSYb3C7W/YKCD2L99f7oMuu', 'donor', 'active', 'pending', 1, '2026-06-28 22:08:33', 'juma john joseph', 'one_time', 'child', 'TLS', 'phone', 'j@gmail.com', '2026-06-28 12:16:42', '2026-06-29 01:08:33', 1, NULL, 'Test approval'),
(17, 'juma', 'john joseph', 'j@gmail.com', '0620449020', '$2y$10$vAK/7ujnIUP/H8o6VPMHUONyxx/vc9tstA4m6644gBq8nqSBSWhfG', 'donor', '', 'pending', 1, '2026-06-28 22:13:42', 'juma john joseph', 'one_time', 'child', 'TLS', 'phone', 'j@gmail.com', '2026-06-28 12:16:42', '2026-06-29 01:13:42', 1, NULL, 'Test approval'),
(18, 'juma', 'john josr', 'jo@gmail.com', '0620449026', '$2y$10$OvamBnyejK2OmAHNDocu7.zS4JRBTbdY40FsWUy5A3lb.6UMKrXga', 'donor', 'active', 'approved', 1, '2026-06-28 22:14:09', 'juma john josr', 'one_time', 'all', 'TLS', 'phone', 'jo@gmail.com', '2026-06-28 12:21:13', '2026-06-29 01:14:09', 1, NULL, 'Test approval'),
(19, 'amani', 'swalehe hassan', 'am@gmail.com', '0620449020', '$2y$10$zDiKF/46yiRSobVcbhSgMOWdjU8qURQsAHQkJBRs.u9fobMGGXMSi', 'donor', 'active', 'approved', 1, '2026-06-28 22:27:36', 'amani swalehe hassan', 'monthly', 'all', 'TLS', 'phone', 'am@gmail.com', '2026-06-28 15:12:41', '2026-06-29 01:27:36', 1, NULL, 'Test approval'),
(20, 'hamiss', 'jamili juma', 'ha@gmail.com', '0620449020', '$2y$10$tJ8tT1jASApHlvQ/bI3eUufvEwq4y20pxdTQzcNnBVYaYX1PVmTeK', 'donor', 'active', 'approved', 1, '2026-06-28 22:55:25', 'hamiss jamili juma', 'sponsorship', 'children', 'FOOD COMPANY Ltd', 'phone', 'ha@gmail.com', '2026-06-29 01:36:20', '2026-06-29 00:55:25', 6, NULL, ''),
(21, 'daudi', 'homi', 'daud@gmail.com', '877666555', NULL, 'adoptive', 'active', 'pending', 0, '2026-06-29 09:52:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'anna', 'hamis jumanne', 'anna@gmail.com', '0620449020', '$2y$10$SE7klG24Qv1MUPYyNS.j1egCRvAFU6cSZAXHE9AhWAe4WLTydvI2y', 'donor', '', 'approved', 1, '2026-06-29 16:33:10', 'anna hamis jumanne', 'one_time', 'children', 'WATER COMPANY Ltd', 'phone', 'anna@gmail.com', '2026-06-29 18:49:33', '2026-06-29 18:33:10', 6, NULL, ''),
(23, 'Guest', '449020', 'guest+17827521389164@example.com', '620449020', NULL, 'donor', 'active', 'pending', 0, '2026-06-29 16:55:38', 'Guest Donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `donor_applications`
--

CREATE TABLE `donor_applications` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `support_type` enum('one_time','monthly','sponsorship','in_kind','other') NOT NULL,
  `description` text DEFAULT NULL,
  `organization_name` varchar(255) DEFAULT NULL,
  `preferred_contact` enum('email','phone','both') NOT NULL DEFAULT 'both',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `date_applied` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_reviewed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `donor_applications`
--

INSERT INTO `donor_applications` (`id`, `full_name`, `email`, `phone`, `support_type`, `description`, `organization_name`, `preferred_contact`, `status`, `notes`, `reviewed_by`, `date_applied`, `date_reviewed`) VALUES
(6, 'hamiss jamili juma', 'ha@gmail.com', '0620449020', 'sponsorship', 'children', 'FOOD COMPANY Ltd', 'phone', 'approved', '', 6, '2026-06-28 22:36:20', '2026-06-29 00:55:25'),
(7, 'Test Rejecter', 'test.reject@gmail.com', '0700123456', 'one_time', 'Testing rejection flow', 'Test Org', 'email', 'rejected', 'Organization does not meet our criteria at this time.', 6, '2026-06-28 22:58:45', '2026-06-29 00:59:06'),
(8, 'anna hamis jumanne', 'anna@gmail.com', '0620449020', 'one_time', 'children', 'WATER COMPANY Ltd', 'phone', 'approved', '', 6, '2026-06-29 15:49:33', '2026-06-29 18:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `guardians`
--

CREATE TABLE `guardians` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `relationship` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `guardians`
--

INSERT INTO `guardians` (`id`, `first_name`, `last_name`, `phone`, `email`, `address`, `relationship`, `created_at`) VALUES
(1, 'Zainab', 'Omar', '0712345678', 'zainab.omar@example.com', '', 'Legal Guardian', '2025-05-06 13:08:34'),
(2, 'Joseph', 'Mwakalinga', '0789123456', 'joseph.m@example.com', '', 'Parent', '2025-05-06 13:08:34'),
(3, 'Salma', 'Abdallah', '0744332211', 'salma.abdallah@example.com', NULL, 'Caretaker', '2025-05-06 13:08:34'),
(4, 'Mariam', 'Kassim', '0712123456', 'mariam.kassim@example.com', NULL, 'Mother', '2025-05-06 13:13:50'),
(5, 'David', 'Makori', '0788223344', 'david.makori@example.com', NULL, 'Family Friend', '2025-05-06 13:13:50'),
(6, 'Rehema', 'Juma', '0744556677', 'rehema.juma@example.com', NULL, 'Sister', '2025-05-06 13:13:50'),
(7, 'Juma', 'Hamisi', '0733661122', 'juma.hamisi@example.com', NULL, 'Uncle', '2025-05-06 13:13:50'),
(8, 'Amina', 'Yahya', '0700223344', 'amina.yahya@example.com', NULL, 'Neighbor', '2025-05-06 13:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `subject`, `content`, `read_status`, `created_at`) VALUES
(11, 2, 1, 'message', 'nnr', 0, '2025-06-26 07:42:52'),
(12, 9, 6, 'Reply from adoptive parent', 'hellow', 0, '2026-06-20 15:31:46'),
(13, 6, 9, 'ghhhj', 'hellow to', 0, '2026-06-20 15:34:17'),
(14, 9, 6, 'Reply from adoptive parent', 'how are you', 0, '2026-06-20 15:41:37'),
(15, 21, 6, 'Reply from adoptive parent', 'hellow', 0, '2026-06-29 10:17:47'),
(16, 14, 6, 'taarifa', 'please fika bila kukosa', 0, '2026-06-29 13:35:35'),
(17, 6, 19, 'taarifa', 'nimepokea', 0, '2026-06-29 15:43:26'),
(18, 14, 6, 'taarifa', 'habari', 0, '2026-06-29 15:44:15'),
(19, 6, 14, 'taarifa', 'nzuri', 1, '2026-06-29 15:45:05');

-- --------------------------------------------------------

--
-- Table structure for table `opportunities`
--

CREATE TABLE `opportunities` (
  `id` int(11) NOT NULL,
  `orphanage_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `deadline` date NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `opportunities`
--

INSERT INTO `opportunities` (`id`, `orphanage_id`, `title`, `description`, `image_url`, `category`, `target_amount`, `current_amount`, `deadline`, `status`, `created_at`) VALUES
(1, 1, 'School Supplies for New Term', 'Providing essential school supplies for 50 children for the upcoming term.', '../images/6848fe21e9d33.jpg', 'Education', '500000.00', '150000.00', '2025-06-15', 'active', '2025-05-07 10:09:14'),
(2, 1, 'Medical Checkup Camp', 'Organizing a health checkup camp for all children in the orphanage.', '../images/6848fce2d685f.jpg', 'Healthcare', '300000.00', '300000.00', '2025-05-30', 'completed', '2025-05-07 10:09:14'),
(3, 2, 'Playground Equipment Upgrade', 'Upgrading the playground with new swings and slides for better recreation.', '../images/6848fcbb64433.jpg', 'Other', '450000.00', '100000.00', '2025-07-01', 'active', '2025-05-07 10:09:14'),
(4, 2, 'Library Renovation', 'Renovating the library to include new books and reading materials.', '../images/6848fd5cdd4cb.jpg', 'Education', '600000.00', '600000.00', '2025-05-20', 'completed', '2025-05-07 10:09:14'),
(5, 3, 'Winter Clothing Drive', 'Collecting funds to purchase winter clothing for the children.', '../images/6848fd19d2715.jpg', 'Healthcare', '350000.00', '50000.00', '2025-08-15', 'active', '2025-05-07 10:09:14'),
(8, 1, 'football training', 'football training center', '../images/6a3911553ff1f.jpg', 'Other', '1000000.00', '0.00', '2027-05-06', 'active', '2026-06-22 10:41:25');

-- --------------------------------------------------------

--
-- Table structure for table `public_contributions`
--

CREATE TABLE `public_contributions` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'TSh',
  `payment_method` varchar(100) NOT NULL,
  `status` enum('pending','otp_sent','otp_verified','processing','completed','failed','cancelled') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `source` varchar(50) DEFAULT 'public',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `donor_email` varchar(255) DEFAULT NULL,
  `otp_sent_at` datetime DEFAULT NULL,
  `otp_verified_at` datetime DEFAULT NULL,
  `payment_started_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payment_attempts` int(11) DEFAULT 0,
  `device_type` varchar(50) DEFAULT NULL,
  `referrer_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `public_contributions`
--

INSERT INTO `public_contributions` (`id`, `donor_id`, `phone`, `amount`, `currency`, `payment_method`, `status`, `transaction_id`, `source`, `notes`, `created_at`, `completed_at`, `ip_address`, `updated_at`, `donor_email`, `otp_sent_at`, `otp_verified_at`, `payment_started_at`, `failed_at`, `failure_reason`, `user_agent`, `payment_attempts`, `device_type`, `referrer_url`) VALUES
(1, 6, '0620449020', '100000.00', 'TSh', 'mobile_money', 'completed', 'LEGACY-15', 'legacy', 'Migrated from donations table - Anonymous public contribution (auto-complete)', '2026-06-30 11:53:54', '2026-06-30 14:46:09', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(2, 6, '0620449020', '100000.00', 'TSh', 'mobile_money', 'completed', 'LEGACY-14', 'legacy', 'Migrated from donations table - Anonymous public contribution (auto-complete)', '2026-06-30 11:53:54', '2026-06-29 20:36:28', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(3, 11, '0652162194', '1000000.00', 'TSh', 'bank_transfer', 'completed', 'LEGACY-10', 'legacy', 'Migrated from donations table - N/A', '2026-06-30 11:53:54', '2026-06-22 13:51:48', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(4, 2, '0755667788', '473.00', 'USD', 'bank_transfer', 'completed', 'LEGACY-5', 'legacy', 'Migrated from donations table - yyuinhi', '2026-06-30 11:53:54', '2025-05-07 18:10:10', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(5, 2, '0755667788', '473200.00', 'USD', 'paypal', 'completed', 'LEGACY-4', 'legacy', 'Migrated from donations table - N/A', '2026-06-30 11:53:54', '2025-05-07 18:08:17', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(6, 2, '0755667788', '30000.00', 'USD', 'bank_transfer', 'completed', 'LEGACY-3', 'legacy', 'Migrated from donations table - i help', '2026-06-30 11:53:54', '2025-05-07 13:46:20', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(7, 2, '0755667788', '473204.00', 'USD', 'paypal', 'completed', 'LEGACY-2', 'legacy', 'Migrated from donations table - N/A', '2026-06-30 11:53:54', '2025-05-06 20:15:06', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(8, 2, '0755667788', '473200.00', 'USD', 'bank_transfer', 'completed', 'LEGACY-1', 'legacy', 'Migrated from donations table - bsnj', '2026-06-30 11:53:54', '2025-05-06 19:10:41', NULL, '2026-06-30 11:53:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('newsletter','progress','financial') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `orphanage_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `orphanage_name`, `address`, `phone`, `email`, `website`, `currency`, `timezone`, `created_at`, `updated_at`) VALUES
(1, 'Orphanage Name', 'Address', 'Phone', 'email@example.com', 'https://example.com', 'TSH', 'UTC', '2025-05-06 14:12:13', '2025-06-11 03:28:22');

-- --------------------------------------------------------

--
-- Table structure for table `updates`
--

CREATE TABLE `updates` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `organization` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','donor','adoptive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `organization`, `password`, `role`, `created_at`) VALUES
(6, 'jackson', 'lymo', 'j@gmail.com', 'j@gmail.com', '', '$2y$10$kQ/TX5f1MaZ.CzPizOqKZ.8phsppQvL2F3rWifjBA3TFWLsZf4VUS', 'admin', '2026-06-17 12:40:40'),
(2, 'bilali', 'mwinyi', 'bilali@gmail.com', '0744500776', 'Kind Hearts Org', '$2y$10$cIplcrr5KIlamfrHpWWrn.wNz9.bes3oVHlELhPhPYLBneU5gIpte', 'donor', '2025-05-06 08:19:23'),
(3, 'Admin', 'Main', 'admin@oms.com', '0700000000', NULL, '$2y$10$hashedpassword1', 'admin', '2025-05-06 13:08:34'),
(4, 'Fatma', 'Ali', 'fatma.ali@hope.org', '0755332211', 'Hope for Children', '$2y$10$hashedpassword2', 'donor', '2025-05-06 13:08:34'),
(5, 'John', 'Doe', 'john.doe@givers.org', '0788112244', 'Givers International', '$2y$10$hashedpassword3', 'donor', '2025-05-06 13:08:34'),
(9, 'juma', 'joseph', 'ju@gmail.com', '0620449020', NULL, '$2y$10$UJ8fz1AJvSvRDQ49EVomseU.9NL/mqHkETV.wJfciA/ynzckJeYCe', '', '2026-06-20 14:15:30'),
(10, 'amani', 'hassan', 'a@gmail.com', '0752162198', NULL, '$2y$10$rkrobOQfC4xvWDz9DXCh/.x7kBIWJhW4EtmuIl.BqgiEacFfc.bxq', '', '2026-06-22 10:26:28'),
(11, 'given', 'joseph', 'g@gmail.com', '0652162194', 'food company Ltd', '$2y$10$PubUsKaC3pNfC9eYFlFlOec3gJYWus0H4QDCMD2hlIbcQwpZI3EXO', 'donor', '2026-06-22 10:50:51'),
(12, 'juma', 'john josr', 'jo@gmail.com', '0620449026', 'TLS', '$2y$10$OvamBnyejK2OmAHNDocu7.zS4JRBTbdY40FsWUy5A3lb.6UMKrXga', 'donor', '2026-06-29 07:04:39'),
(13, 'amani', 'swalehe hassan', 'am@gmail.com', '0620449020', 'TLS', '$2y$10$zDiKF/46yiRSobVcbhSgMOWdjU8qURQsAHQkJBRs.u9fobMGGXMSi', 'donor', '2026-06-29 07:04:39'),
(14, 'hamiss', 'jamili juma', 'ha@gmail.com', '0620449020', 'FOOD COMPANY Ltd', '$2y$10$tJ8tT1jASApHlvQ/bI3eUufvEwq4y20pxdTQzcNnBVYaYX1PVmTeK', 'donor', '2026-06-29 07:04:39'),
(15, 'daudi', 'homi', 'daud@gmail.com', '877666555', NULL, '$2y$10$sBVOwASvFSliuevSzX/R8eAmHGOO0/k7/6kxzgomCMSaC8MBA..K2', 'adoptive', '2026-06-29 09:52:13'),
(16, 'anna', 'hamis jumanne', 'anna@gmail.com', '0620449020', 'WATER COMPANY Ltd', '$2y$10$SE7klG24Qv1MUPYyNS.j1egCRvAFU6cSZAXHE9AhWAe4WLTydvI2y', 'donor', '2026-06-29 16:33:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `adoptions`
--
ALTER TABLE `adoptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `adopter_id` (`adopter_id`);

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contributions`
--
ALTER TABLE `contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opportunity_id` (`opportunity_id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `donors`
--
ALTER TABLE `donors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donor_applications`
--
ALTER TABLE `donor_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `guardians`
--
ALTER TABLE `guardians`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `opportunities`
--
ALTER TABLE `opportunities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orphanage_id` (`orphanage_id`);

--
-- Indexes for table `public_contributions`
--
ALTER TABLE `public_contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `updates`
--
ALTER TABLE `updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `adoptions`
--
ALTER TABLE `adoptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `contributions`
--
ALTER TABLE `contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `donors`
--
ALTER TABLE `donors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `donor_applications`
--
ALTER TABLE `donor_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `guardians`
--
ALTER TABLE `guardians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `opportunities`
--
ALTER TABLE `opportunities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `public_contributions`
--
ALTER TABLE `public_contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
