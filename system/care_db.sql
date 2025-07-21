-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 04:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `care_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `care_applications`
--

CREATE TABLE `care_applications` (
  `id` bigint(20) NOT NULL,
  `application_id` varchar(100) NOT NULL,
  `application_name` varchar(100) NOT NULL,
  `application_email` varchar(100) NOT NULL,
  `application_phone` varchar(20) NOT NULL,
  `application_experience` varchar(50) NOT NULL,
  `application_service_type` varchar(50) NOT NULL,
  `application_availability` varchar(50) NOT NULL,
  `application_message` text DEFAULT NULL,
  `application_status` enum('pending','reviewed','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_assignments`
--

CREATE TABLE `care_assignments` (
  `id` bigint(20) NOT NULL,
  `assignment_id` varchar(100) NOT NULL,
  `assignment_request_id` varchar(100) NOT NULL,
  `assignment_caregiver_id` varchar(100) NOT NULL,
  `assignment_start_date` date NOT NULL,
  `assignment_end_date` date DEFAULT NULL,
  `assignment_status` enum('active','completed','cancelled') DEFAULT 'active',
  `assignment_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_caregiver_profiles`
--

CREATE TABLE `care_caregiver_profiles` (
  `id` bigint(20) NOT NULL,
  `profile_id` varchar(100) NOT NULL,
  `profile_user_id` varchar(100) NOT NULL,
  `profile_experience` varchar(50) DEFAULT NULL,
  `profile_service_type` varchar(50) DEFAULT NULL,
  `profile_availability` varchar(50) DEFAULT NULL,
  `profile_bio` text DEFAULT NULL,
  `profile_hourly_rate` decimal(10,2) DEFAULT NULL,
  `profile_background_check_status` enum('pending','passed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_contact_messages`
--

CREATE TABLE `care_contact_messages` (
  `id` bigint(20) NOT NULL,
  `message_id` varchar(100) NOT NULL,
  `message_name` varchar(100) NOT NULL,
  `message_email` varchar(100) NOT NULL,
  `message_phone` varchar(20) DEFAULT NULL,
  `message_subject` varchar(100) DEFAULT NULL,
  `message_message` text NOT NULL,
  `message_status` enum('unread','read','replied') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `care_contact_messages`
--

INSERT INTO `care_contact_messages` (`id`, `message_id`, `message_name`, `message_email`, `message_phone`, `message_subject`, `message_message`, `message_status`, `created_at`, `updated_at`) VALUES
(1, '2acad370-644d-4500-a31f-0f3278eeff9c', 'hamza hamidu', 'lanit85225@dogemn.com', '0244512130', 'Employment', 'we good?', 'unread', '2025-07-20 16:53:54', '2025-07-20 16:53:54');

-- --------------------------------------------------------

--
-- Table structure for table `care_reviews`
--

CREATE TABLE `care_reviews` (
  `id` bigint(20) NOT NULL,
  `review_id` varchar(100) NOT NULL,
  `review_client_id` varchar(100) NOT NULL,
  `review_caregiver_id` varchar(100) NOT NULL,
  `review_assignment_id` varchar(100) DEFAULT NULL,
  `review_rating` int(11) NOT NULL CHECK (`review_rating` between 1 and 5),
  `review_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `care_service_requests`
--

CREATE TABLE `care_service_requests` (
  `id` bigint(20) NOT NULL,
  `request_id` varchar(100) NOT NULL,
  `request_client_id` varchar(100) DEFAULT NULL,
  `request_name` varchar(100) DEFAULT NULL,
  `request_email` varchar(100) DEFAULT NULL,
  `request_phone` varchar(20) DEFAULT NULL,
  `request_service_type` varchar(50) NOT NULL,
  `request_care_for` varchar(50) NOT NULL,
  `request_start_date` date NOT NULL,
  `request_frequency` varchar(50) NOT NULL,
  `request_hours` varchar(50) DEFAULT NULL,
  `request_status` enum('pending','approved','assigned','completed','cancelled') DEFAULT 'pending',
  `request_assigned_to` varchar(100) DEFAULT NULL,
  `request_special_requirements` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `care_service_requests`
--

INSERT INTO `care_service_requests` (`id`, `request_id`, `request_client_id`, `request_name`, `request_email`, `request_phone`, `request_service_type`, `request_care_for`, `request_start_date`, `request_frequency`, `request_hours`, `request_status`, `request_assigned_to`, `request_special_requirements`, `created_at`, `updated_at`) VALUES
(1, '63940ddb-fda0-48f7-b731-24d3e4cc8133', NULL, 'hamza hamidu', 'tjhackx111@gmail.com', '0244512130', 'Sick Care', 'Parent', '2025-12-31', 'Daily', '12', 'assigned', NULL, 'nothing new', '2025-07-20 16:02:48', '2025-07-21 14:13:29');

-- --------------------------------------------------------

--
-- Table structure for table `care_users`
--

CREATE TABLE `care_users` (
  `id` bigint(20) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `care_users`
--

INSERT INTO `care_users` (`id`, `user_id`, `user_fullname`, `user_email`, `user_phone`, `user_password`, `user_type`, `user_status`, `user_profile_image`, `user_address`, `user_city`, `user_state`, `user_zip_code`, `created_at`, `updated_at`) VALUES
(1, '901a72fc-er32-4770-a78d-6f33c6d6a55c', 'nuhu inusah', 'admin@admin.com', NULL, '$2y$10$WKV/v9cSvwKwGmJU/P48ouPcCXuOm5eAZ7JEOzXWKzcjidWUj57eu', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, '2025-07-21 12:42:20', '2025-07-21 12:42:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `care_applications`
--
ALTER TABLE `care_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `care_assignments`
--
ALTER TABLE `care_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assignment_id` (`assignment_id`),
  ADD KEY `assignment_request_id` (`assignment_request_id`),
  ADD KEY `assignment_caregiver_id` (`assignment_caregiver_id`);

--
-- Indexes for table `care_caregiver_profiles`
--
ALTER TABLE `care_caregiver_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_user_id` (`profile_user_id`);

--
-- Indexes for table `care_contact_messages`
--
ALTER TABLE `care_contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `care_reviews`
--
ALTER TABLE `care_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_client_id` (`review_client_id`),
  ADD KEY `review_caregiver_id` (`review_caregiver_id`),
  ADD KEY `review_assignment_id` (`review_assignment_id`);

--
-- Indexes for table `care_service_requests`
--
ALTER TABLE `care_service_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD UNIQUE KEY `request_id_2` (`request_id`),
  ADD KEY `request_client_id` (`request_client_id`);

--
-- Indexes for table `care_users`
--
ALTER TABLE `care_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_email` (`user_email`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `user_id_2` (`user_id`),
  ADD UNIQUE KEY `user_id_3` (`user_id`),
  ADD UNIQUE KEY `user_id_4` (`user_id`),
  ADD UNIQUE KEY `user_id_5` (`user_id`),
  ADD UNIQUE KEY `user_id_6` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `care_applications`
--
ALTER TABLE `care_applications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `care_assignments`
--
ALTER TABLE `care_assignments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `care_caregiver_profiles`
--
ALTER TABLE `care_caregiver_profiles`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `care_contact_messages`
--
ALTER TABLE `care_contact_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `care_reviews`
--
ALTER TABLE `care_reviews`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `care_service_requests`
--
ALTER TABLE `care_service_requests`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `care_users`
--
ALTER TABLE `care_users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `care_assignments`
--
ALTER TABLE `care_assignments`
  ADD CONSTRAINT `care_assignments_ibfk_1` FOREIGN KEY (`assignment_request_id`) REFERENCES `care_service_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `care_assignments_ibfk_2` FOREIGN KEY (`assignment_caregiver_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `care_caregiver_profiles`
--
ALTER TABLE `care_caregiver_profiles`
  ADD CONSTRAINT `care_caregiver_profiles_ibfk_1` FOREIGN KEY (`profile_user_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `care_reviews`
--
ALTER TABLE `care_reviews`
  ADD CONSTRAINT `care_reviews_ibfk_1` FOREIGN KEY (`review_client_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `care_reviews_ibfk_2` FOREIGN KEY (`review_caregiver_id`) REFERENCES `care_users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `care_reviews_ibfk_3` FOREIGN KEY (`review_assignment_id`) REFERENCES `care_assignments` (`assignment_id`) ON DELETE SET NULL;

--
-- Constraints for table `care_service_requests`
--
ALTER TABLE `care_service_requests`
  ADD CONSTRAINT `care_service_requests_ibfk_1` FOREIGN KEY (`request_client_id`) REFERENCES `care_users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
