-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 09:15 AM
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
-- Database: `maintenance_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `audit_id` int(11) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `action_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaint`
--

CREATE TABLE `complaint` (
  `complaint_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` varchar(50) DEFAULT NULL,
  `impact` varchar(50) DEFAULT NULL,
  `date_reported` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `infrastructure_type_id` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint`
--

INSERT INTO `complaint` (`complaint_id`, `description`, `severity`, `impact`, `date_reported`, `user_id`, `location_id`, `infrastructure_type_id`, `status_id`, `image_url`) VALUES
(3, 'qwert', 'Medium', 'Wide', '2026-04-14 14:37:18', 1, 4, 1, 1, 'uploads/1776148638_Color_Hunt_Palette_3852b45e7ac4f3be7af08d39.png'),
(4, 'test', 'High', 'Wide', '2026-04-14 14:42:33', 1, 5, 1, 1, 'uploads/1776148953_35.png'),
(5, 'test2', 'High', 'Wide', '2026-04-14 14:43:37', 2, 6, 1, 1, 'uploads/1776149017_34.png'),
(6, 'test', 'Medium', 'Wide', '2026-04-14 14:50:16', 1, 7, 3, 1, 'uploads/1776149416_Color_Hunt_Palette_3852b45e7ac4f3be7af08d39.png'),
(7, 'tet', 'Medium', 'Wide', '2026-04-14 14:50:52', 2, 8, 3, 1, 'uploads/1776149452_Color_Hunt_Palette_3852b45e7ac4f3be7af08d39.png');

-- --------------------------------------------------------

--
-- Table structure for table `complaint_merge`
--

CREATE TABLE `complaint_merge` (
  `merge_id` int(11) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `duplicate_complaint_id` int(11) DEFAULT NULL,
  `merge_date` datetime DEFAULT NULL,
  `distance_meter` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint_merge`
--

INSERT INTO `complaint_merge` (`merge_id`, `complaint_id`, `duplicate_complaint_id`, `merge_date`, `distance_meter`) VALUES
(1, 4, 5, '2026-04-14 14:43:37', 13.37),
(2, 3, 6, '2026-04-14 14:50:16', 5.23),
(3, 6, 7, '2026-04-14 14:50:52', 6.58);

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

CREATE TABLE `contact_inquiries` (
  `contact_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `inquiry_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `rating` int(1) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_inquiries`
--

INSERT INTO `contact_inquiries` (`contact_id`, `full_name`, `email`, `inquiry_type`, `message`, `rating`, `ip_address`, `created_at`) VALUES
(1, 'hanim nabila', 'hanimnabila@gmail.com', 'Other', 'test', NULL, '::1', '2026-04-14 07:03:45'),
(2, 'hanim nabila', 'hanimnabila@gmail.com', 'GIS Navigation', 'test2', NULL, '::1', '2026-04-14 07:06:15'),
(3, 'hanim nabila', 'hanimnabila@gmail.com', 'GIS Navigation', 'test2', NULL, '::1', '2026-04-14 07:06:44'),
(4, 'hanim nabila', 'hanimnabila@gmail.com', 'GIS Navigation', 'test2', NULL, '::1', '2026-04-14 07:08:23'),
(5, 'hanim nabila', 'hanimnabila@gmail.com', 'Feature Suggestion', 'test', 3, '::1', '2026-04-14 07:08:42'),
(6, 'hanim nabila', 'hanimnabila@gmail.com', 'Feature Suggestion', 'test', NULL, '::1', '2026-04-14 07:09:14'),
(7, 'hanim nabila', 'hanimnabila@gmail.com', 'Feature Suggestion', 'test', NULL, '::1', '2026-04-14 07:09:30'),
(8, 'hanim nabila', 'hanimnabila@gmail.com', 'Feature Suggestion', 'test', 3, '::1', '2026-04-14 07:09:38'),
(9, 'hanim nabila', 'hanimnabila@gmail.com', 'General Feedback', 'qwert', 5, '::1', '2026-04-14 07:09:47'),
(10, 'hanim nabila', 'hanimnabila@gmail.com', 'General Feedback', 'qwert', 5, '::1', '2026-04-14 07:10:14'),
(11, 'hanim nabila', 'hanimnabila@gmail.com', 'General Feedback', 'qwert', 5, '::1', '2026-04-14 07:10:38'),
(12, 'hanim nabila', 'hanimnabila@gmail.com', 'General Feedback', 'qwert', 5, '::1', '2026-04-14 07:11:07'),
(13, 'hanim nabila', 'hanimnabila@gmail.com', 'General Feedback', 'qwert', 5, '::1', '2026-04-14 07:11:15'),
(14, 'hanim nabila', 'hanimnabila@gmail.com', 'General Feedback', 'qwert', 5, '::1', '2026-04-14 07:12:13'),
(15, 'hanim nabila', 'hanimnabila@gmail.com', 'General Feedback', 'qwert', 5, '::1', '2026-04-14 07:13:14'),
(16, 'hanim nabila', 'hanimnabila@gmail.com', 'Feature Suggestion', 'abc', 5, '::1', '2026-04-14 07:13:27'),
(17, 'hanim nabila', 'hanimnabila@gmail.com', 'Feature Suggestion', 'abc', 5, '::1', '2026-04-14 07:14:36'),
(18, 'hanim nabila', 'hanimnabila@gmail.com', 'Feature Suggestion', 'abc', 5, '::1', '2026-04-14 07:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `infrastructure_type`
--

CREATE TABLE `infrastructure_type` (
  `infrastructure_type_id` int(11) NOT NULL,
  `type_name` varchar(100) DEFAULT NULL,
  `weight_modifier` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `infrastructure_type`
--

INSERT INTO `infrastructure_type` (`infrastructure_type_id`, `type_name`, `weight_modifier`) VALUES
(1, 'Road Damage', 5),
(2, 'Water Leak', 4),
(3, 'Streetlight', 4),
(4, 'Drainage', 3),
(5, 'Waste', 2);

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`location_id`, `latitude`, `longitude`, `address`, `district`) VALUES
(4, 2.311891, 102.318601, 'test', 'Alor Gajah'),
(5, 2.309083, 102.320734, 'test', 'Alor Gajah'),
(6, 2.309104, 102.320616, 'test', 'Alor Gajah'),
(7, 2.311895, 102.318648, 'test', 'Alor Gajah'),
(8, 2.311838, 102.318663, 'test', 'Alor Gajah');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_team`
--

CREATE TABLE `maintenance_team` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone_num` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status_id`, `status_name`) VALUES
(1, 'Pending'),
(2, 'In Progress'),
(3, 'Resolved');

-- --------------------------------------------------------

--
-- Table structure for table `team_member`
--

CREATE TABLE `team_member` (
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `user_ic` varchar(20) DEFAULT NULL,
  `user_phonenum` varchar(20) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_name`, `role`, `user_ic`, `user_phonenum`, `user_email`, `password`) VALUES
(1, 'hanim nabila', 'Citizen', '040424110454', '01117895604', 'hanimnabila@gmail.com', '0000'),
(2, 'ahmad azhari', 'Citizen', '020812080201', '01117895666', 'az@gmail.com', '9999');

-- --------------------------------------------------------

--
-- Table structure for table `weight_score`
--

CREATE TABLE `weight_score` (
  `priority_id` int(11) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `severity_weight` int(11) DEFAULT NULL,
  `impact_weight` int(11) DEFAULT NULL,
  `priority_score` decimal(5,2) DEFAULT NULL,
  `calculated_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weight_score`
--

INSERT INTO `weight_score` (`priority_id`, `complaint_id`, `severity_weight`, `impact_weight`, `priority_score`, `calculated_date`) VALUES
(1, 3, 3, 5, 20.00, '2026-04-14 14:37:18'),
(2, 4, 5, 5, 30.00, '2026-04-14 14:42:33'),
(3, 5, 5, 5, 30.00, '2026-04-14 14:43:37'),
(4, 6, 3, 5, 17.00, '2026-04-14 14:50:16'),
(5, 7, 3, 5, 17.00, '2026-04-14 14:50:52');

-- --------------------------------------------------------

--
-- Table structure for table `work_order`
--

CREATE TABLE `work_order` (
  `work_order_id` int(11) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `date_assigned` datetime DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  `work_status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `complaint_id` (`complaint_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `complaint`
--
ALTER TABLE `complaint`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `infrastructure_type_id` (`infrastructure_type_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `complaint_merge`
--
ALTER TABLE `complaint_merge`
  ADD PRIMARY KEY (`merge_id`),
  ADD KEY `complaint_id` (`complaint_id`),
  ADD KEY `duplicate_complaint_id` (`duplicate_complaint_id`);

--
-- Indexes for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `infrastructure_type`
--
ALTER TABLE `infrastructure_type`
  ADD PRIMARY KEY (`infrastructure_type_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `maintenance_team`
--
ALTER TABLE `maintenance_team`
  ADD PRIMARY KEY (`team_id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `team_member`
--
ALTER TABLE `team_member`
  ADD PRIMARY KEY (`team_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `weight_score`
--
ALTER TABLE `weight_score`
  ADD PRIMARY KEY (`priority_id`),
  ADD KEY `complaint_id` (`complaint_id`);

--
-- Indexes for table `work_order`
--
ALTER TABLE `work_order`
  ADD PRIMARY KEY (`work_order_id`),
  ADD KEY `complaint_id` (`complaint_id`),
  ADD KEY `team_id` (`team_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `complaint`
--
ALTER TABLE `complaint`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `complaint_merge`
--
ALTER TABLE `complaint_merge`
  MODIFY `merge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `infrastructure_type`
--
ALTER TABLE `infrastructure_type`
  MODIFY `infrastructure_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `maintenance_team`
--
ALTER TABLE `maintenance_team`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `weight_score`
--
ALTER TABLE `weight_score`
  MODIFY `priority_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `work_order`
--
ALTER TABLE `work_order`
  MODIFY `work_order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaint` (`complaint_id`),
  ADD CONSTRAINT `audit_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `complaint`
--
ALTER TABLE `complaint`
  ADD CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `complaint_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`),
  ADD CONSTRAINT `complaint_ibfk_3` FOREIGN KEY (`infrastructure_type_id`) REFERENCES `infrastructure_type` (`infrastructure_type_id`),
  ADD CONSTRAINT `complaint_ibfk_4` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`);

--
-- Constraints for table `complaint_merge`
--
ALTER TABLE `complaint_merge`
  ADD CONSTRAINT `complaint_merge_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaint` (`complaint_id`),
  ADD CONSTRAINT `complaint_merge_ibfk_2` FOREIGN KEY (`duplicate_complaint_id`) REFERENCES `complaint` (`complaint_id`);

--
-- Constraints for table `team_member`
--
ALTER TABLE `team_member`
  ADD CONSTRAINT `team_member_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `maintenance_team` (`team_id`),
  ADD CONSTRAINT `team_member_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `weight_score`
--
ALTER TABLE `weight_score`
  ADD CONSTRAINT `weight_score_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaint` (`complaint_id`);

--
-- Constraints for table `work_order`
--
ALTER TABLE `work_order`
  ADD CONSTRAINT `work_order_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaint` (`complaint_id`),
  ADD CONSTRAINT `work_order_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `maintenance_team` (`team_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
