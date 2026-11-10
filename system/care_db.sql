-- phpMyAdmin SQL Dump
-- Database: `care_db`
-- Updated Schema with Fixed Column Sizes, Clean Indexes, and Admin Notes Support

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `care_applications`
--

CREATE TABLE IF NOT EXISTS `care_applications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `application_id` varchar(100) NOT NULL,
  `application_name` varchar(100) NOT NULL,
  `application_email` varchar(100) NOT NULL,
  `application_phone` varchar(20) NOT NULL,
  `application_experience` varchar(50) NOT NULL,
  `application_service_type` varchar(150) NOT NULL,
  `application_availability` varchar(50) NOT NULL,
  `application_address` varchar(200) DEFAULT NULL,
  `application_message` text DEFAULT NULL,
  `application_notes` text DEFAULT NULL,
  `application_status` enum('pending','reviewed','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_app_status` (`application_status`),
  KEY `idx_app_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_users`
--

CREATE TABLE IF NOT EXISTS `care_users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL,
  `user_fullname` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_phone` varchar(20) DEFAULT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_type` enum('client','caregiver','admin') NOT NULL,
  `user_status` enum('active','inactive','pending') DEFAULT 'pending',
  `user_profile_image` varchar(255) DEFAULT NULL,
  `user_address` text DEFAULT NULL,
  `user_city` varchar(50) DEFAULT NULL,
  `user_state` varchar(50) DEFAULT NULL,
  `user_zip_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_email` (`user_email`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping default admin user for table `care_users`
--

INSERT IGNORE INTO `care_users` (`id`, `user_id`, `user_fullname`, `user_email`, `user_phone`, `user_password`, `user_type`, `user_status`, `user_profile_image`, `user_address`, `user_city`, `user_state`, `user_zip_code`, `created_at`, `updated_at`) VALUES
(1, '901a72fc-er32-4770-a78d-6f33c6d6a55c', 'Alfa', 'alfa@admin.com', '0200000123', '$2y$10$kx18tB/FhMbjYuNf740gmeYuPUVrkkUTxFexQlr0f4PisQ.Dn2TXq', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, '2025-07-21 14:26:07', '2026-11-03 17:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `care_service_requests`
--

CREATE TABLE IF NOT EXISTS `care_service_requests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(100) NOT NULL,
  `request_client_id` varchar(100) DEFAULT NULL,
  `request_name` varchar(100) DEFAULT NULL,
  `request_email` varchar(100) DEFAULT NULL,
  `request_phone` varchar(20) DEFAULT NULL,
  `request_service_type` varchar(150) NOT NULL,
  `request_care_for` varchar(50) NOT NULL,
  `request_start_date` date NOT NULL,
  `request_frequency` varchar(50) NOT NULL,
  `request_hours` varchar(50) DEFAULT NULL,
  `request_status` enum('pending','approved','assigned','completed','cancelled') DEFAULT 'pending',
  `request_assigned_to` varchar(100) DEFAULT NULL,
  `request_special_requirements` text DEFAULT NULL,
  `request_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `request_client_id` (`request_client_id`),
  KEY `idx_req_status` (`request_status`),
  CONSTRAINT `care_service_requests_ibfk_1` FOREIGN KEY (`request_client_id`) REFERENCES `care_users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_assignments`
--

CREATE TABLE IF NOT EXISTS `care_assignments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `assignment_id` varchar(100) NOT NULL,
  `assignment_request_id` varchar(100) NOT NULL,
  `assignment_caregiver_id` varchar(100) NOT NULL,
  `assignment_start_date` date NOT NULL,
  `assignment_end_date` date DEFAULT NULL,
  `assignment_status` enum('active','completed','cancelled') DEFAULT 'active',
  `assignment_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `assignment_id` (`assignment_id`),
  KEY `assignment_request_id` (`assignment_request_id`),
  KEY `assignment_caregiver_id` (`assignment_caregiver_id`),
  CONSTRAINT `care_assignments_ibfk_1` FOREIGN KEY (`assignment_request_id`) REFERENCES `care_service_requests` (`request_id`) ON DELETE CASCADE,
  CONSTRAINT `care_assignments_ibfk_2` FOREIGN KEY (`assignment_caregiver_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_caregiver_profiles`
--

CREATE TABLE IF NOT EXISTS `care_caregiver_profiles` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `profile_id` varchar(100) NOT NULL,
  `profile_user_id` varchar(100) NOT NULL,
  `profile_experience` varchar(50) DEFAULT NULL,
  `profile_service_type` varchar(150) DEFAULT NULL,
  `profile_availability` varchar(50) DEFAULT NULL,
  `profile_bio` text DEFAULT NULL,
  `profile_hourly_rate` decimal(10,2) DEFAULT NULL,
  `profile_background_check_status` enum('pending','passed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `profile_user_id` (`profile_user_id`),
  CONSTRAINT `care_caregiver_profiles_ibfk_1` FOREIGN KEY (`profile_user_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_contact_messages`
--

CREATE TABLE IF NOT EXISTS `care_contact_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(100) NOT NULL,
  `message_name` varchar(100) NOT NULL,
  `message_email` varchar(100) NOT NULL,
  `message_phone` varchar(20) DEFAULT NULL,
  `message_subject` varchar(100) DEFAULT NULL,
  `message_message` text NOT NULL,
  `message_status` enum('unread','read','replied') DEFAULT 'unread',
  `is_important` tinyint(1) NOT NULL DEFAULT 0,
  `is_spam` tinyint(1) NOT NULL DEFAULT 0,
  `spam_reason` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_msg_status` (`message_status`),
  KEY `idx_msg_important` (`is_important`),
  KEY `idx_msg_spam` (`is_spam`),
  KEY `idx_msg_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_reviews`
--

CREATE TABLE IF NOT EXISTS `care_reviews` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `review_id` varchar(100) NOT NULL,
  `review_client_id` varchar(100) NOT NULL,
  `review_caregiver_id` varchar(100) NOT NULL,
  `review_assignment_id` varchar(100) DEFAULT NULL,
  `review_rating` int(11) NOT NULL CHECK (`review_rating` between 1 and 5),
  `review_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `review_client_id` (`review_client_id`),
  KEY `review_caregiver_id` (`review_caregiver_id`),
  KEY `review_assignment_id` (`review_assignment_id`),
  CONSTRAINT `care_reviews_ibfk_1` FOREIGN KEY (`review_client_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `care_reviews_ibfk_2` FOREIGN KEY (`review_caregiver_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `care_reviews_ibfk_3` FOREIGN KEY (`review_assignment_id`) REFERENCES `care_assignments` (`assignment_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_staff_onboarding`
--

CREATE TABLE IF NOT EXISTS `care_staff_onboarding` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `onboarding_id` varchar(100) NOT NULL,
  `staff_name` varchar(120) NOT NULL,
  `staff_email` varchar(120) NOT NULL,
  `staff_phone` varchar(30) NOT NULL,
  `staff_address` varchar(255) DEFAULT NULL,
  `gov_id_type` varchar(80) DEFAULT NULL,
  `gov_id_number` varchar(512) DEFAULT NULL,
  `gov_id_file` varchar(255) DEFAULT NULL,
  `ssn_number` varchar(512) DEFAULT NULL,
  `ssn_file` varchar(255) DEFAULT NULL,
  `background_check_status` varchar(80) DEFAULT NULL,
  `background_check_file` varchar(255) DEFAULT NULL,
  `fwa_status` varchar(80) DEFAULT NULL,
  `fwa_certificate_file` varchar(255) DEFAULT NULL,
  `credential_type` varchar(150) DEFAULT NULL,
  `credential_number` varchar(100) DEFAULT NULL,
  `credential_file` varchar(255) DEFAULT NULL,
  `chore_labor` varchar(255) DEFAULT NULL,
  `chore_labor_notes` text DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `account_type` varchar(50) DEFAULT NULL,
  `routing_number` varchar(512) DEFAULT NULL,
  `account_number` varchar(512) DEFAULT NULL,
  `direct_deposit_file` varchar(255) DEFAULT NULL,
  `w4_filing_status` varchar(100) DEFAULT NULL,
  `w4_file` varchar(255) DEFAULT NULL,
  `i9_work_status` varchar(100) DEFAULT NULL,
  `i9_file` varchar(255) DEFAULT NULL,
  `therap_status` varchar(80) DEFAULT NULL,
  `therap_username` varchar(100) DEFAULT NULL,
  `policy_declaration` tinyint(1) NOT NULL DEFAULT 0,
  `compliance_agreed` tinyint(1) NOT NULL DEFAULT 0,
  `policy_procedures_agreed` tinyint(1) NOT NULL DEFAULT 0,
  `reporting_agreed` tinyint(1) NOT NULL DEFAULT 0,
  `digital_signature` varchar(120) DEFAULT NULL,
  `signature_date` date DEFAULT NULL,
  `drivers_license_number` varchar(512) DEFAULT NULL,
  `drivers_license_state` varchar(50) DEFAULT NULL,
  `drivers_license_expiry` date DEFAULT NULL,
  `drivers_license_file` varchar(255) DEFAULT NULL,
  `drivers_insurance_provider` varchar(120) DEFAULT NULL,
  `drivers_insurance_policy` varchar(512) DEFAULT NULL,
  `drivers_insurance_file` varchar(255) DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `onboarding_status` enum('pending','in_review','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_onboarding_id` (`onboarding_id`),
  KEY `idx_onboarding_status` (`onboarding_status`),
  KEY `idx_staff_email` (`staff_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_onboarding_settings`
--

CREATE TABLE IF NOT EXISTS `care_onboarding_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_onboarding_invites`
--

CREATE TABLE IF NOT EXISTS `care_onboarding_invites` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `invite_id` varchar(100) NOT NULL,
  `staff_name` varchar(120) DEFAULT NULL,
  `staff_email` varchar(120) DEFAULT NULL,
  `code_prefix` varchar(20) NOT NULL,
  `code_hint` varchar(30) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `max_uses` int(11) NOT NULL DEFAULT 1,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` varchar(100) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ob_invite_id` (`invite_id`),
  KEY `idx_ob_code_prefix` (`code_prefix`),
  KEY `idx_ob_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_onboarding_attempts`
--

CREATE TABLE IF NOT EXISTS `care_onboarding_attempts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(64) NOT NULL,
  `event_type` varchar(40) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ob_attempt_lookup` (`ip_address`, `event_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
