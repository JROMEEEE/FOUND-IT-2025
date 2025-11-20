-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 20, 2025 at 08:42 AM
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
-- Database: `founditdb25`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_announcements`
--

CREATE TABLE `admin_announcements` (
  `announcement_id` int(11) NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `user_id`, `user_name`, `message`, `created_at`) VALUES
(10, 4, 'Admin', 'We are thrilled to announce the official launch of FOUND-IT! 🎉\n\nFOUND-IT is your reliable platform for reporting, tracking, and claiming lost & found items quickly and efficiently. With our system, you can:\n\nReport lost items easily with photos and descriptions.\n\nTrack found items in real-time.\n\nReceive instant notifications when your missing belongings are located.\n\nOur goal is to make lost-and-found management simpler, faster, and more reliable for everyone. Start using FOUND-IT today and never worry about losing your valuables again!\n\nWelcome to a smarter way to keep track of your things — FOUND-IT is here!', '2025-11-19 12:11:07');

-- --------------------------------------------------------

--
-- Table structure for table `claim_request`
--

CREATE TABLE `claim_request` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `fnd_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ticket_code` varchar(100) NOT NULL,
  `claimer_name` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `claimer_id` varchar(50) DEFAULT NULL,
  `claimer_email` varchar(100) DEFAULT NULL,
  `proof_of_ownership` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','claimed') NOT NULL DEFAULT 'pending',
  `request_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claim_request`
--

INSERT INTO `claim_request` (`request_id`, `fnd_id`, `user_id`, `ticket_code`, `claimer_name`, `contact_number`, `claimer_id`, `claimer_email`, `proof_of_ownership`, `status`, `request_date`) VALUES
(14, 9, 4, 'CLAIM-541238F1', 'JR Diaz', '', '23-38357', '23-38357@gmail.com', 'Test', 'claimed', '2025-11-12 00:21:19'),
(15, 10, 4, 'CLAIM-A5EDE40D', 'JR Diaz', '', '23-22222', '23-38357@gmail.com', 'Claimer statement blabla', 'claimed', '2025-11-12 00:35:56'),
(16, 11, 5, 'CLAIM-CC122BFA', 'John Lloyd Baes', '', '23-11111', '23-11111@gmail.com', 'I own these shoes I left em lmao', 'approved', '2025-11-18 12:19:10'),
(17, 7, 4, 'CLAIM-D815C10E', 'John Lloyd Baes', '', '23-11111', '23-11111@gmail.com', 'Blue', 'claimed', '2025-11-19 08:49:53');

-- --------------------------------------------------------

--
-- Table structure for table `claim_verification`
--

CREATE TABLE `claim_verification` (
  `verify_id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `fnd_id` int(10) UNSIGNED NOT NULL,
  `ticket_code` varchar(100) NOT NULL,
  `qr_image_path` varchar(255) NOT NULL,
  `date_generated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claim_verification`
--

INSERT INTO `claim_verification` (`verify_id`, `request_id`, `user_id`, `fnd_id`, `ticket_code`, `qr_image_path`, `date_generated`) VALUES
(6, 14, 4, 9, 'CLAIM-541238F1', '../qrcodes/CLAIM-541238F1.png', '2025-11-12 00:21:45'),
(7, 15, 4, 10, 'CLAIM-A5EDE40D', '../qrcodes/CLAIM-A5EDE40D.png', '2025-11-12 00:36:22'),
(8, 16, 5, 11, 'CLAIM-CC122BFA', '../qrcodes/CLAIM-CC122BFA.png', '2025-11-18 12:19:37'),
(9, 17, 4, 7, 'CLAIM-D815C10E', '../qrcodes/CLAIM-D815C10E.png', '2025-11-19 08:54:32');

-- --------------------------------------------------------

--
-- Table structure for table `found_report`
--

CREATE TABLE `found_report` (
  `fnd_id` int(10) UNSIGNED NOT NULL,
  `fnd_name` varchar(255) NOT NULL,
  `fnd_desc` text DEFAULT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `fnd_datetime` datetime NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `fnd_status` enum('unclaimed','claimed','discarded') NOT NULL DEFAULT 'unclaimed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `found_report`
--

INSERT INTO `found_report` (`fnd_id`, `fnd_name`, `fnd_desc`, `location_id`, `fnd_datetime`, `user_id`, `image_path`, `category_id`, `fnd_status`) VALUES
(6, 'RTX 2080', 'Graphics Card, Black', 8, '2025-11-11 11:51:08', 4, 'uploads/found_items/1762858268_geforce-rtx-2080-technical-photography-angled-003.png', 10, 'unclaimed'),
(7, 'Aquaflask', 'Blue', 8, '2025-11-11 12:15:18', 4, 'uploads/found_items/1762859718_WATAQF0000428__1.jpg', 4, 'claimed'),
(8, 'SSD', 'SATA', 7, '2025-11-11 12:21:18', 4, 'uploads/found_items/1762860078_seagate-barracuda-q1-ssd-rear-lo-res.png', 10, 'claimed'),
(9, 'Aquaflask', 'wthelly', 4, '2025-11-11 16:20:33', 4, 'uploads/found_items/found_1762874433.png', 4, 'claimed'),
(10, 'Ukulele', 'Brown Ukulele All strings attached', 6, '2025-11-11 17:24:38', 4, 'uploads/found_items/found_1762878278.png', 13, 'claimed'),
(11, 'Shoes', 'NIKE', 7, '2025-11-18 05:18:05', 4, 'uploads/found_items/found_1763439485.png', 11, 'claimed');

-- --------------------------------------------------------

--
-- Table structure for table `item_category`
--

CREATE TABLE `item_category` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_category`
--

INSERT INTO `item_category` (`category_id`, `category_name`) VALUES
(4, 'Drinkware'),
(5, 'Documents'),
(6, 'Jewelry'),
(7, 'Wallets & Purses'),
(8, 'Keys'),
(9, 'Accessories'),
(10, 'Electronics'),
(11, 'Clothing & Wearables'),
(12, 'Contraband, Cash and Confidential Materials'),
(13, 'Others');

-- --------------------------------------------------------

--
-- Table structure for table `location_table`
--

CREATE TABLE `location_table` (
  `location_id` int(10) UNSIGNED NOT NULL,
  `location_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_table`
--

INSERT INTO `location_table` (`location_id`, `location_name`) VALUES
(4, 'VMB'),
(5, 'GZB'),
(6, 'AAB'),
(7, 'FACADE'),
(8, 'COMP LAB'),
(9, 'SCIENCE LAB'),
(10, 'Others');

-- --------------------------------------------------------

--
-- Table structure for table `lost_report`
--

CREATE TABLE `lost_report` (
  `lost_id` int(10) UNSIGNED NOT NULL,
  `lost_name` varchar(255) NOT NULL,
  `lost_desc` text DEFAULT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `lost_datetime` datetime NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `lost_status` enum('active','expired','closed') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_report`
--

INSERT INTO `lost_report` (`lost_id`, `lost_name`, `lost_desc`, `location_id`, `lost_datetime`, `user_id`, `image_path`, `category_id`, `lost_status`) VALUES
(3, 'Testtes', 'test', 4, '2025-11-11 13:16:39', 4, 'uploads/lost_items/1762863399_WATAQF0000428__1.jpg', 4, ''),
(4, 'resdad', 'asdasd', 6, '2025-11-11 13:26:01', 4, 'uploads/lost_items/1762863961_WATAQF0000428__1.jpg', 4, ''),
(5, 'WATAH BOTTA', 'Weewoo', 5, '2025-11-18 04:59:19', 5, 'uploads/lost_items/1763438359_WATAQF0000428__1.jpg', 4, ''),
(6, 'Aquaflask', 'Blue', 7, '2025-11-19 01:37:47', 4, 'uploads/lost_items/1763512667_WATAQF0000428__1.jpg', 4, ''),
(7, 'Receipt', 'Gcash Resibo', 10, '2025-11-19 01:46:23', 4, 'uploads/lost_items/1763513183_989ff506-340e-4666-9da8-d66a78642072.jpg', 5, '');

-- --------------------------------------------------------

--
-- Table structure for table `users_table`
--

CREATE TABLE `users_table` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `contact_no` varchar(50) NOT NULL,
  `date_registered` date NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `sr_code` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_table`
--

INSERT INTO `users_table` (`user_id`, `user_name`, `contact_no`, `date_registered`, `is_admin`, `sr_code`, `email`, `password`) VALUES
(3, 'John Romell Diaz', '09471098936', '2025-10-24', 0, '23-38357', '23-38357@g.batstate-u.edu.ph', '$2y$10$Yyc08utyHOH42EdWJV9KpuYMNQxTBZLEymNWcy1fCwxg5rYu7ZPs.'),
(4, 'Admin', '09471098936', '2025-10-26', 1, NULL, 'admin@gmail.com', '$2y$10$XQsNNU6z6a93Z6fTWlydlOjeVboB9seK3uZIwgJ/M0sYFYzyh1ZoO'),
(5, 'John Lloyd Baes', '09761668605', '2025-11-18', 0, '23-11111', '23-11111@g.batstate-u.edu.ph', '$2y$10$wccQUhZ9jRtcfWVqZ8ImHePAadhFC4WV1QFasHp.1ibtFzr91HZHa'),
(6, 'Joey Admin', '09761668605', '2025-11-20', 1, NULL, 'admin2@gmail.com', '$2y$10$V1WbU0PCYez/7m7mQQ87j.cRq1jW.cEgNnl9pUYaY5TeKv5z/Y3Wu');

-- --------------------------------------------------------

--
-- Table structure for table `user_admin_msgs`
--

CREATE TABLE `user_admin_msgs` (
  `msg_id` int(11) NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_admin_msgs`
--

INSERT INTO `user_admin_msgs` (`msg_id`, `sender_id`, `receiver_id`, `sender_name`, `message`, `is_read`, `created_at`) VALUES
(24, 3, 9999, 'John Romell Diaz', 'test', 0, '2025-11-19 16:17:12'),
(25, 4, 3, 'Admin', 'yesrr', 0, '2025-11-19 16:24:33'),
(26, 4, 3, 'Admin', 'yoo', 0, '2025-11-19 16:27:23'),
(28, 9999, 3, 'Admin', 'test', 0, '2025-11-19 16:30:51'),
(29, 3, 9999, 'John Romell Diaz', 'YOOO', 0, '2025-11-19 16:31:11'),
(30, 9999, 3, 'Admin', 'YOOOO', 0, '2025-11-19 16:31:14'),
(31, 3, 9999, 'John Romell Diaz', 'sheesh', 0, '2025-11-19 16:32:00'),
(32, 9999, 3, 'Admin', 'again', 0, '2025-11-19 16:33:00'),
(33, 9999, 3, 'Admin', 'again', 0, '2025-11-19 16:36:39'),
(34, 3, 9999, 'John Romell Diaz', 'yoo', 0, '2025-11-19 16:36:44'),
(35, 9999, 3, 'Joey Admin', 'yeet', 0, '2025-11-19 16:49:38'),
(36, 3, 9999, 'John Romell Diaz', 'tesr', 0, '2025-11-20 01:47:02'),
(37, 9999, 3, 'Joey Admin', 'yo', 0, '2025-11-20 01:47:14'),
(38, 9999, 3, 'Joey Admin', 'test', 0, '2025-11-20 07:23:34'),
(39, 3, 9999, 'John Romell Diaz', 'heop', 0, '2025-11-20 07:23:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_announcements`
--
ALTER TABLE `admin_announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `claim_request`
--
ALTER TABLE `claim_request`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `ticket_code` (`ticket_code`),
  ADD KEY `fk_fnd_id` (`fnd_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `claim_verification`
--
ALTER TABLE `claim_verification`
  ADD PRIMARY KEY (`verify_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fnd_id` (`fnd_id`);

--
-- Indexes for table `found_report`
--
ALTER TABLE `found_report`
  ADD PRIMARY KEY (`fnd_id`),
  ADD KEY `found_report_user_fk` (`user_id`),
  ADD KEY `found_report_location_fk` (`location_id`),
  ADD KEY `found_report_category_fk` (`category_id`);

--
-- Indexes for table `item_category`
--
ALTER TABLE `item_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `location_table`
--
ALTER TABLE `location_table`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `lost_report`
--
ALTER TABLE `lost_report`
  ADD PRIMARY KEY (`lost_id`),
  ADD KEY `lost_report_user_fk` (`user_id`),
  ADD KEY `lost_report_location_fk` (`location_id`),
  ADD KEY `lost_report_category_fk` (`category_id`);

--
-- Indexes for table `users_table`
--
ALTER TABLE `users_table`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_admin_msgs`
--
ALTER TABLE `user_admin_msgs`
  ADD PRIMARY KEY (`msg_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_announcements`
--
ALTER TABLE `admin_announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `claim_request`
--
ALTER TABLE `claim_request`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `claim_verification`
--
ALTER TABLE `claim_verification`
  MODIFY `verify_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `found_report`
--
ALTER TABLE `found_report`
  MODIFY `fnd_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `item_category`
--
ALTER TABLE `item_category`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `location_table`
--
ALTER TABLE `location_table`
  MODIFY `location_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lost_report`
--
ALTER TABLE `lost_report`
  MODIFY `lost_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users_table`
--
ALTER TABLE `users_table`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_admin_msgs`
--
ALTER TABLE `user_admin_msgs`
  MODIFY `msg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_announcements`
--
ALTER TABLE `admin_announcements`
  ADD CONSTRAINT `fk_admin_announcements_user` FOREIGN KEY (`admin_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `claim_request`
--
ALTER TABLE `claim_request`
  ADD CONSTRAINT `fk_claim_request_found` FOREIGN KEY (`fnd_id`) REFERENCES `found_report` (`fnd_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_claim_request_user` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `claim_verification`
--
ALTER TABLE `claim_verification`
  ADD CONSTRAINT `claim_verification_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `claim_request` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `claim_verification_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `claim_verification_ibfk_3` FOREIGN KEY (`fnd_id`) REFERENCES `found_report` (`fnd_id`) ON DELETE CASCADE;

--
-- Constraints for table `found_report`
--
ALTER TABLE `found_report`
  ADD CONSTRAINT `found_report_category_fk` FOREIGN KEY (`category_id`) REFERENCES `item_category` (`category_id`),
  ADD CONSTRAINT `found_report_location_fk` FOREIGN KEY (`location_id`) REFERENCES `location_table` (`location_id`),
  ADD CONSTRAINT `found_report_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`);

--
-- Constraints for table `lost_report`
--
ALTER TABLE `lost_report`
  ADD CONSTRAINT `lost_report_category_fk` FOREIGN KEY (`category_id`) REFERENCES `item_category` (`category_id`),
  ADD CONSTRAINT `lost_report_location_fk` FOREIGN KEY (`location_id`) REFERENCES `location_table` (`location_id`),
  ADD CONSTRAINT `lost_report_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users_table` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
