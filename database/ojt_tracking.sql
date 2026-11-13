-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2026 at 07:23 PM
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
-- Database: `ojt_tracking`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_profiles`
--

CREATE TABLE `admin_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `access_level` varchar(50) DEFAULT 'super'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_profiles`
--

INSERT INTO `admin_profiles` (`id`, `user_id`, `access_level`) VALUES
(1, 1, 'super');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `lunch_deducted` tinyint(1) DEFAULT 0,
  `status` enum('Whole Day','Half Day','Undertime','Overtime','Exceeded Overtime','Absent') DEFAULT 'Whole Day',
  `punctuality` enum('Early','On Time','Late') DEFAULT 'On Time',
  `remarks` varchar(255) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_profiles`
--

CREATE TABLE `company_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `industry` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `available_positions` varchar(255) DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `hr_contact_person` varchar(150) DEFAULT NULL,
  `social_media` varchar(255) DEFAULT NULL,
  `is_partner` tinyint(1) DEFAULT 0,
  `max_slots` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `duration_hours` int(11) DEFAULT 486,
  `academic_year` varchar(20) DEFAULT '2025-2026',
  `year_level` varchar(20) DEFAULT '4th Year',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `duration_hours`, `academic_year`, `year_level`, `created_at`) VALUES
(1, 'BSIT', 'BS Information Technology', 486, '2025-2026', '4th Year', '2026-11-13 17:27:45'),
(2, 'BSCS', 'BS Computer Science', 486, '2025-2026', '4th Year', '2026-11-13 17:27:45');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `doc_type` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_evaluations`
--

CREATE TABLE `final_evaluations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_profiles`
--

CREATE TABLE `school_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_name` varchar(200) NOT NULL,
  `department` varchar(150) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_no` varchar(11) DEFAULT NULL,
  `course` varchar(150) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT '2025-2026',
  `section` varchar(50) DEFAULT NULL,
  `school_name` varchar(200) DEFAULT NULL,
  `required_hours` int(11) DEFAULT 486,
  `shift_start` time DEFAULT '08:00:00',
  `shift_end` time DEFAULT '17:00:00',
  `company_id` int(11) DEFAULT NULL,
  `application_status` enum('unassigned','pending','accepted','rejected') DEFAULT 'unassigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('student','company','school','admin') NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'System', 'Administrator', 'admin@practitrack.local', '$2y$10$MYya1ZMIlXCtIFg.kBeZH.pEcxZMbethORjdy.jytrlaE95BUGgry', NULL, 1, '2026-11-13 17:28:06', '2026-11-13 17:28:06');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_reports`
--

CREATE TABLE `weekly_reports` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `report_type` enum('weekly','monthly') DEFAULT 'weekly',
  `week_start` date NOT NULL,
  `week_end` date NOT NULL,
  `hours_rendered` decimal(5,2) DEFAULT 0.00,
  `activities` text NOT NULL,
  `learnings` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `company_feedback` text DEFAULT NULL,
  `company_approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_report_attendance`
--

CREATE TABLE `weekly_report_attendance` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL,
  `log_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_date` (`student_id`,`log_date`);

--
-- Indexes for table `company_profiles`
--
ALTER TABLE `company_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `final_evaluations`
--
ALTER TABLE `final_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `school_profiles`
--
ALTER TABLE `school_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weekly_reports`
--
ALTER TABLE `weekly_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `weekly_report_attendance`
--
ALTER TABLE `weekly_report_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `attendance_id` (`attendance_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_profiles`
--
ALTER TABLE `company_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `final_evaluations`
--
ALTER TABLE `final_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_profiles`
--
ALTER TABLE `school_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `weekly_reports`
--
ALTER TABLE `weekly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weekly_report_attendance`
--
ALTER TABLE `weekly_report_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD CONSTRAINT `admin_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `company_profiles`
--
ALTER TABLE `company_profiles`
  ADD CONSTRAINT `company_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `final_evaluations`
--
ALTER TABLE `final_evaluations`
  ADD CONSTRAINT `final_evaluations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `final_evaluations_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_profiles`
--
ALTER TABLE `school_profiles`
  ADD CONSTRAINT `school_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_profiles_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `weekly_reports`
--
ALTER TABLE `weekly_reports`
  ADD CONSTRAINT `weekly_reports_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_report_attendance`
--
ALTER TABLE `weekly_report_attendance`
  ADD CONSTRAINT `weekly_report_attendance_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `weekly_reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `weekly_report_attendance_ibfk_2` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
