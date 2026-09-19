-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 11:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `or_scheduling`
--

-- --------------------------------------------------------

--
-- Table structure for table `anesthetics`
--

CREATE TABLE `anesthetics` (
  `anesthetic_id` int(11) NOT NULL,
  `anesthetic_name` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anesthetics`
--

INSERT INTO `anesthetics` (`anesthetic_id`, `anesthetic_name`, `status`, `created_at`) VALUES
(1, 'General Anesthesia', 'Active', '2026-09-10 06:01:11'),
(2, 'Spinal Anesthesia', 'Active', '2026-09-10 06:01:11');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix_name` varchar(30) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `nick_name` varchar(100) DEFAULT NULL,
  `sex_gender` varchar(30) DEFAULT NULL,
  `service_class` varchar(100) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `last_name`, `first_name`, `middle_name`, `suffix_name`, `birth_date`, `nick_name`, `sex_gender`, `service_class`, `specialization`, `status`, `created_at`) VALUES
(1, 'Dela Cruz', 'Juan', 'Santos', 'MD', '1978-05-12', 'Dr. Juan', 'Male', 'Medical', 'Surgeon', 'Active', '2026-09-10 02:45:18'),
(2, 'Santos', 'Maria', 'Garcia', NULL, '1982-09-20', 'Dr. Maria', 'Female', 'Medical', 'Surgeon', 'Active', '2026-09-10 02:45:18'),
(3, 'Reyes', 'Pedro', 'Lopez', 'MD', '1975-03-08', 'Dr. Pedro', 'Male', 'Medical', 'Anesthesiologist', 'Active', '2026-09-10 02:45:18'),
(4, 'Garcia', 'Ana', 'Mendoza', NULL, '1980-11-15', 'Dr. Ana', 'Female', 'Medical', 'Cardiologist', 'Active', '2026-09-10 02:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `operating_rooms`
--

CREATE TABLE `operating_rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `room_description` varchar(255) DEFAULT NULL,
  `status` enum('Available','Occupied','Maintenance','Inactive') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operating_rooms`
--

INSERT INTO `operating_rooms` (`room_id`, `room_name`, `room_description`, `status`, `created_at`) VALUES
(1, 'theater 1', 'sample lang', 'Available', '2026-09-07 07:21:00'),
(2, 'theater 2', 'sample 2', 'Occupied', '2026-09-07 07:21:26'),
(3, 'theater 3', 'sample 3', 'Available', '2026-09-09 06:40:49'),
(4, 'theater 4', 'room 4', 'Available', '2026-09-11 05:18:25'),
(5, 'theater 5', 'tesrt', 'Available', '2026-09-11 05:45:50'),
(6, 'theater 6', 'fsdfsdfsf', 'Available', '2026-09-11 05:46:01'),
(7, 'theater 7', '7', 'Available', '2026-09-16 06:06:25'),
(8, 'theater 8', '8', 'Available', '2026-09-16 06:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `or_schedules`
--

CREATE TABLE `or_schedules` (
  `schedule_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_registry_no` varchar(50) DEFAULT NULL,
  `registry_date` date DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `registry_type` varchar(50) DEFAULT NULL,
  `patient_room_no` varchar(50) DEFAULT NULL,
  `bed_no` varchar(50) DEFAULT NULL,
  `surgery_date` date NOT NULL,
  `date_end` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_id` int(11) NOT NULL,
  `is_stat` tinyint(1) NOT NULL DEFAULT 0,
  `priority` enum('Elective','Urgent','Emergency') NOT NULL DEFAULT 'Elective',
  `surgeon_doctor_id` int(11) NOT NULL,
  `anesthesiologist_doctor_id` int(11) DEFAULT NULL,
  `anesthetic` varchar(150) DEFAULT NULL,
  `cardiologist` varchar(150) DEFAULT NULL,
  `circulating_nurse` varchar(150) DEFAULT NULL,
  `instrument_nurse` varchar(150) DEFAULT NULL,
  `procedure_id` int(11) NOT NULL,
  `surgical_type` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `infections` text DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `cancel_reason` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `or_schedules`
--

INSERT INTO `or_schedules` (`schedule_id`, `patient_id`, `patient_registry_no`, `registry_date`, `birth_date`, `registry_type`, `patient_room_no`, `bed_no`, `surgery_date`, `date_end`, `start_time`, `end_time`, `room_id`, `is_stat`, `priority`, `surgeon_doctor_id`, `anesthesiologist_doctor_id`, `anesthetic`, `cardiologist`, `circulating_nurse`, `instrument_nurse`, `procedure_id`, `surgical_type`, `remarks`, `infections`, `status`, `cancel_reason`, `cancelled_by`, `cancelled_at`, `completed_by`, `completed_at`, `created_by`, `created_at`, `updated_at`) VALUES
(22, 8, 'REG-2026-0003', '2026-09-03', '1978-12-03', 'Emergency', 'ER-01', '1', '2026-09-16', '2026-09-16', '14:35:00', '15:35:00', 1, 0, 'Elective', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'Angel', 'Jonel', 1, 'Major', NULL, NULL, 'Completed', NULL, NULL, NULL, NULL, NULL, 1, '2026-09-16 06:36:10', '2026-09-17 01:16:05'),
(23, 6, 'REG-2026-0001', '2026-09-01', '1985-04-20', 'Inpatient', '201', 'A', '2026-09-16', '2026-09-16', '14:40:00', '14:45:00', 4, 1, 'Urgent', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'Angel', 'Jonel', 5, 'Major', NULL, NULL, 'Cancelled', 'jsut tewst', 1, '2026-09-17 09:17:29', NULL, NULL, 1, '2026-09-16 06:40:40', '2026-09-17 01:17:29'),
(24, 9, 'REG-2026-0004', '2026-09-04', '1995-06-28', 'Outpatient', NULL, NULL, '2026-09-16', '2026-09-16', '15:41:00', '16:41:00', 7, 1, 'Emergency', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'Angel', 'Jonel', 4, 'Minor', NULL, NULL, 'Completed', NULL, NULL, NULL, 1, '2026-09-17 10:01:40', 1, '2026-09-16 06:41:49', '2026-09-17 02:01:40'),
(25, 8, 'REG-2026-0003', '2026-09-03', '1978-12-03', 'Emergency', 'ER-01', '1', '2026-09-17', '2026-09-17', '10:05:00', '11:05:00', 8, 0, 'Elective', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'a', 'a', 10, 'Major', NULL, NULL, 'Completed', NULL, NULL, NULL, 1, '2026-09-17 10:08:17', 1, '2026-09-17 02:06:22', '2026-09-17 02:08:17'),
(26, 8, 'REG-2026-0003', '2026-09-03', '1978-12-03', 'Emergency', 'ER-01', '1', '2026-09-17', '2026-09-17', '10:10:00', '11:10:00', 8, 0, 'Elective', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'asdad', 'asdasd', 9, 'Major', NULL, NULL, 'Cancelled', 'test cancel', 1, '2026-09-17 10:11:40', NULL, NULL, 1, '2026-09-17 02:11:14', '2026-09-17 02:11:40'),
(27, 8, 'REG-2026-0003', '2026-09-03', '1978-12-03', 'Emergency', 'ER-01', '1', '2026-09-17', '2026-09-17', '10:13:00', '11:13:00', 1, 0, 'Elective', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'a', 'b', 7, 'Minor', NULL, NULL, 'Completed', NULL, NULL, NULL, 1, '2026-09-17 11:22:18', 1, '2026-09-17 02:13:24', '2026-09-17 03:22:18'),
(28, 6, 'REG-2026-0001', '2026-09-01', '1985-04-20', 'Inpatient', '201', 'A', '2026-09-17', '2026-09-17', '11:49:00', '12:49:00', 2, 1, 'Urgent', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'asd', NULL, 8, 'Major', 'asd', 'asd', 'Cancelled', 'refuse', 1, '2026-09-17 11:51:03', NULL, NULL, 1, '2026-09-17 03:49:30', '2026-09-17 03:51:03'),
(29, 8, 'REG-2026-0003', '2026-09-03', '1978-12-03', 'Emergency', 'ER-01', '1', '2026-09-18', '2026-09-18', '15:15:00', '16:15:00', 1, 1, 'Urgent', 1, 3, 'General Anesthesia', 'Ana Mendoza Garcia', 'angel', 'jonel', 8, 'Major', 'asdsad', 'asdasd', 'Completed', NULL, NULL, NULL, 1, '2026-09-18 15:17:58', 1, '2026-09-18 07:15:43', '2026-09-18 07:17:58'),
(30, 6, 'REG-2026-0001', '2026-09-01', '1985-04-20', 'Inpatient', '201', 'A', '2026-09-18', '2026-09-18', '17:16:00', '18:16:00', 2, 0, 'Urgent', 1, 3, 'Spinal Anesthesia', 'Ana Mendoza Garcia', 'asd', 'asd', 5, 'Major', 'asdsad', 'asdasd', 'Scheduled', NULL, NULL, NULL, NULL, NULL, 1, '2026-09-18 07:16:40', '2026-09-18 07:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `or_schedule_assistants`
--

CREATE TABLE `or_schedule_assistants` (
  `schedule_assistant_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `assistant_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `or_schedule_assistants`
--

INSERT INTO `or_schedule_assistants` (`schedule_assistant_id`, `schedule_id`, `doctor_id`, `assistant_order`, `created_at`) VALUES
(32, 22, 2, 1, '2026-09-16 06:36:10'),
(33, 23, 1, 1, '2026-09-16 06:40:40'),
(34, 23, 2, 2, '2026-09-16 06:40:40'),
(35, 24, 1, 1, '2026-09-16 06:41:49'),
(36, 25, 1, 1, '2026-09-17 02:06:22'),
(37, 25, 2, 2, '2026-09-17 02:06:22'),
(38, 26, 1, 1, '2026-09-17 02:11:15'),
(39, 27, 1, 1, '2026-09-17 02:13:24'),
(40, 28, 2, 1, '2026-09-17 03:49:30'),
(41, 29, 2, 1, '2026-09-18 07:15:44'),
(42, 30, 1, 1, '2026-09-18 07:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `patient_number` varchar(50) NOT NULL,
  `registry_date` date DEFAULT NULL,
  `registry_type` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `patient_room_no` varchar(50) DEFAULT NULL,
  `bed_no` varchar(50) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `patient_number`, `registry_date`, `registry_type`, `first_name`, `middle_name`, `last_name`, `birth_date`, `sex`, `patient_room_no`, `bed_no`, `contact_number`, `address`, `created_at`) VALUES
(6, 'REG-2026-0001', '2026-09-01', 'Inpatient', 'Juan', 'Santos', 'Dela Cruz', '1985-04-20', 'Male', '201', 'A', '09171234567', 'Quezon City, Metro Manila', '2026-09-09 08:28:44'),
(8, 'REG-2026-0003', '2026-09-03', 'Emergency', 'Pedro', 'Reyes', 'Cruz', '1978-12-03', 'Male', 'ER-01', '1', '09191234567', 'Pasig City, Metro Manila', '2026-09-09 08:28:44'),
(9, 'REG-2026-0004', '2026-09-04', 'Outpatient', 'Angela', 'Lopez', 'Garcia', '1995-06-28', 'Female', NULL, NULL, '09201234567', 'Makati City, Metro Manila', '2026-09-09 08:28:44');

-- --------------------------------------------------------

--
-- Table structure for table `procedures`
--

CREATE TABLE `procedures` (
  `procedure_id` int(11) NOT NULL,
  `procedure_name` varchar(200) NOT NULL,
  `surgical_type` varchar(100) DEFAULT NULL,
  `procedure_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procedures`
--

INSERT INTO `procedures` (`procedure_id`, `procedure_name`, `surgical_type`, `procedure_code`, `description`, `status`, `created_at`) VALUES
(1, 'Appendectomy', 'Major', 'APP-001', NULL, 'Active', '2026-09-10 06:18:45'),
(2, 'Cholecystectomy', 'Major', 'CHO-001', NULL, 'Active', '2026-09-10 06:18:45'),
(3, 'Cesarean Section', 'Major', 'CS-001', NULL, 'Active', '2026-09-10 06:18:45'),
(4, 'Hernia Repair', 'Minor', 'HERN-001', NULL, 'Active', '2026-09-10 06:18:45'),
(5, 'Total Knee Replacement', 'Major', 'TKR-001', NULL, 'Active', '2026-09-10 06:18:45'),
(6, 'Total Hip Replacement', 'Major', 'THR-001', NULL, 'Active', '2026-09-10 06:18:45'),
(7, 'Cardiac Catheterization', 'Minor', 'CATH-001', NULL, 'Active', '2026-09-10 06:18:45'),
(8, 'Coronary Artery Bypass Graft', 'Major', 'CABG-001', NULL, 'Active', '2026-09-10 06:18:45'),
(9, 'Total Abdominal Hysterectomy', 'Major', 'TAH-001', NULL, 'Active', '2026-09-10 06:18:45'),
(10, 'Transurethral Resection of Prostate', 'Major', 'TURP-001', NULL, 'Active', '2026-09-10 06:18:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('Administrator','Scheduler','Staff') NOT NULL DEFAULT 'Staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$gCbG.CK0iSQrZvm9T8CwmuScY01to.P/ppRsGcSerzcYQ7rwnUdHO', 'System Administrator', 'Administrator', '2026-09-07 02:08:42'),
(2, 'JSA', '$2y$10$cpKtLRHShDD1R2wn6ggSLuXu1QJDRQWhVjqrEriXtvW0tjV5UQcyS', 'JONEL ANSILAN', 'Scheduler', '2026-09-17 05:16:40'),
(3, 'CRIS', '$2y$10$3kdGbrcC22Bf0OTG0y8HVuyFNn/SYSMNJqd/qpuW838aUozZkHndO', 'CRIS PAGAROGAN', 'Staff', '2026-09-17 06:05:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anesthetics`
--
ALTER TABLE `anesthetics`
  ADD PRIMARY KEY (`anesthetic_id`),
  ADD UNIQUE KEY `uq_anesthetic_name` (`anesthetic_name`),
  ADD KEY `idx_anesthetic_status` (`status`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD KEY `idx_doctor_name` (`last_name`,`first_name`),
  ADD KEY `idx_specialization` (`specialization`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `operating_rooms`
--
ALTER TABLE `operating_rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `or_schedules`
--
ALTER TABLE `or_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_surgeon_doctor_id` (`surgeon_doctor_id`),
  ADD KEY `idx_anesthesiologist_doctor_id` (`anesthesiologist_doctor_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_procedure_id` (`procedure_id`),
  ADD KEY `idx_surgery_date` (`surgery_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `or_schedule_assistants`
--
ALTER TABLE `or_schedule_assistants`
  ADD PRIMARY KEY (`schedule_assistant_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_assistant_doctor_id` (`doctor_id`),
  ADD KEY `idx_assistant_order` (`assistant_order`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `uq_patient_number` (`patient_number`),
  ADD KEY `idx_patient_name` (`last_name`,`first_name`),
  ADD KEY `idx_birth_date` (`birth_date`);

--
-- Indexes for table `procedures`
--
ALTER TABLE `procedures`
  ADD PRIMARY KEY (`procedure_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anesthetics`
--
ALTER TABLE `anesthetics`
  MODIFY `anesthetic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `operating_rooms`
--
ALTER TABLE `operating_rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `or_schedules`
--
ALTER TABLE `or_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `or_schedule_assistants`
--
ALTER TABLE `or_schedule_assistants`
  MODIFY `schedule_assistant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `procedures`
--
ALTER TABLE `procedures`
  MODIFY `procedure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `or_schedules`
--
ALTER TABLE `or_schedules`
  ADD CONSTRAINT `fk_schedule_anesthesiologist_doctor` FOREIGN KEY (`anesthesiologist_doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_procedure` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`procedure_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_room` FOREIGN KEY (`room_id`) REFERENCES `operating_rooms` (`room_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_surgeon_doctor` FOREIGN KEY (`surgeon_doctor_id`) REFERENCES `doctors` (`doctor_id`) ON UPDATE CASCADE;

--
-- Constraints for table `or_schedule_assistants`
--
ALTER TABLE `or_schedule_assistants`
  ADD CONSTRAINT `fk_schedule_assistant_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_assistant_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `or_schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
