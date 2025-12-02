-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 02, 2025 at 02:52 AM
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
-- Database: `founditdb2025`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `created_at`) VALUES
(2, 4, 'DELETE', 'claim_request', 17, 'Claim request deleted by Admin', '2025-11-23 04:02:31'),
(3, 4, 'REJECT', 'claim_request', 18, 'Status changed to rejected by Admin', '2025-11-23 04:05:05'),
(5, 4, 'APPROVE', 'claim_request', 19, 'Status changed to approved by Admin', '2025-11-23 04:14:38'),
(6, 4, 'APPROVE', 'claim_request', 20, 'Status changed to approved by Admin', '2025-11-23 04:17:36'),
(7, 4, 'DELETE', 'claim_request', 20, 'Claim request deleted by Admin', '2025-11-23 04:19:37'),
(8, 4, 'APPROVE', 'claim_request', 21, 'Status changed to approved by Admin', '2025-11-23 04:21:09'),
(9, 4, 'APPROVE', 'claim_request', 22, 'Status changed to approved by Admin', '2025-11-23 04:27:44'),
(10, 4, 'DELETE', 'claim_request', 22, 'Claim request deleted by Admin', '2025-11-23 04:33:17'),
(11, 4, 'DELETE', 'claim_request', 21, 'Claim request deleted by Admin', '2025-11-23 04:33:19'),
(13, 4, 'DECAY', 'found_report', 13, 'Item \'Cellphone\' discarded and moved to decayed_table.', '2025-11-23 05:16:24'),
(18, 4, 'DISCARD', 'decayed_table', 15, 'Item discarded by Joey to OSD', '2025-11-23 05:56:39'),
(19, 4, 'DISCARD', 'decayed_table', 11, 'Item discarded by Joey to SAO/SSC', '2025-11-23 06:09:56'),
(20, 4, 'DISCARD', 'decayed_table', 14, 'Item discarded by Joey to OSD', '2025-11-23 06:50:25'),
(21, 4, 'DISCARD', 'decayed_table', 12, 'Item discarded by Joey to OSD', '2025-11-23 06:53:54'),
(24, 4, 'DISCARD', 'decayed_table', 16, 'Item discarded by Joey to OSD', '2025-11-23 07:00:23'),
(27, 4, 'DISCARD', 'decayed_table', 17, 'Item discarded by Joey to OSD', '2025-11-23 07:03:15'),
(29, 10, 'INSERT', 'lost_report', 10, '{\"lost_name\":\"Clipboard\",\"lost_desc\":\"It has FOUND-IT Documents\",\"location_id\":\"7\",\"category_id\":\"5\",\"image_path\":\"uploads\\/lost_items\\/1764028135_Screenshot 2023-09-21 172520.png\"}', '2025-11-24 23:48:55'),
(30, 4, 'VIEW', 'claim_request', 25, '{\"ticket_code\":\"CLAIM-673313E8\",\"claimer_name\":\"Yinuo Sandoval\",\"claimer_id\":\"23-11111\"}', '2025-11-24 23:57:28'),
(31, 4, 'UPDATE', 'claim_request', 25, '{\"ticket_code\":\"CLAIM-673313E8\",\"claimer_name\":\"Yinuo Sandoval\",\"claimer_id\":\"23-11111\"}', '2025-11-24 23:57:33'),
(32, 4, 'INSERT', 'found_report', 19, '{\"fnd_name\":\"Black bag\",\"fnd_desc\":\"Black shoulder bag\",\"location_id\":\"7\",\"category_id\":\"7\",\"image_path\":\"uploads\\/found_items\\/found_1764047527.png\"}', '2025-11-25 05:12:07'),
(33, 11, 'INSERT', 'lost_report', 11, '{\"lost_name\":\"Black Bag\",\"lost_desc\":\"Black shoulder bag\",\"location_id\":\"7\",\"category_id\":\"7\",\"image_path\":\"uploads\\/lost_items\\/1764049738_pilipins.png\"}', '2025-11-25 05:48:58'),
(34, 4, 'VIEW', 'claim_request', 26, '{\"ticket_code\":\"CLAIM-B1734883\",\"claimer_name\":\"John Lloyd Baes\",\"claimer_id\":\"23-67676\"}', '2025-11-25 05:57:39'),
(35, 4, 'UPDATE', 'claim_request', 26, '{\"ticket_code\":\"CLAIM-B1734883\",\"claimer_name\":\"John Lloyd Baes\",\"claimer_id\":\"23-67676\"}', '2025-11-25 05:57:52'),
(36, 4, 'INSERT', 'found_report', 20, '{\"fnd_name\":\"Computer Mouse\",\"fnd_desc\":\"Razer Mouse\",\"location_id\":\"8\",\"category_id\":\"10\",\"image_path\":\"uploads\\/found_items\\/found_1764051713.png\"}', '2025-11-25 06:21:53'),
(37, 4, 'VIEW', 'claim_request', 26, '{\"ticket_code\":\"CLAIM-B1734883\",\"claimer_name\":\"John Lloyd Baes\",\"claimer_id\":\"23-67676\"}', '2025-11-28 05:03:14'),
(38, 4, 'INSERT', 'found_report', 21, '{\"fnd_name\":\"test12\",\"fnd_desc\":\"TESTING\",\"location_id\":\"9\",\"category_id\":\"13\",\"image_path\":\"uploads\\/found_items\\/found_1764349696.png\"}', '2025-11-28 17:08:16'),
(39, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:11:20'),
(40, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:12:54'),
(41, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:13:38'),
(42, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:14:01'),
(43, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:14:30'),
(44, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:14:41'),
(45, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:17:59'),
(46, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:18:58'),
(47, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:20:07'),
(48, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:20:27'),
(49, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:21:15'),
(50, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:23:51'),
(51, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:27:41'),
(52, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:27:53'),
(53, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:30:08'),
(54, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:31:13'),
(55, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:35:18'),
(56, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:36:01'),
(57, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:36:24'),
(58, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:37:01'),
(59, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:37:36'),
(60, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:39:40'),
(61, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 01:40:47'),
(62, 4, 'INSERT', 'lost_report', 12, '{\"lost_name\":\"Test\",\"lost_desc\":\"Pink\",\"location_id\":\"6\",\"category_id\":\"11\",\"image_path\":\"uploads\\/lost_items\\/1764390189_pilipins.png\"}', '2025-11-29 04:23:09'),
(63, 4, 'VIEW', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 04:25:48'),
(64, 4, 'UPDATE', 'claim_request', 29, '{\"ticket_code\":\"CLAIM-FD0877A8\",\"claimer_name\":\"Jodi Nicole\",\"claimer_id\":\"23-67676\"}', '2025-11-29 04:25:50'),
(65, 13, 'INSERT', 'lost_report', 13, '{\"lost_name\":\"Cellphone\",\"lost_desc\":\"May tape sa gilid\",\"location_id\":\"8\",\"category_id\":\"10\",\"image_path\":\"uploads\\/lost_items\\/1764391266_pilipins.png\"}', '2025-11-29 04:41:06'),
(66, 4, 'INSERT', 'found_report', 22, '{\"fnd_name\":\"Cellphone\",\"fnd_desc\":\"POCO X3 may tape sa gilid\",\"location_id\":\"8\",\"category_id\":\"10\",\"image_path\":\"uploads\\/found_items\\/found_1764391518.png\"}', '2025-11-29 04:45:18'),
(67, 4, 'VIEW', 'claim_request', 31, '{\"ticket_code\":\"CLAIM-4696EFE6\",\"claimer_name\":\"Romell Diaz\",\"claimer_id\":\"23-38357\"}', '2025-11-29 04:49:56'),
(68, 4, 'UPDATE', 'claim_request', 31, '{\"ticket_code\":\"CLAIM-4696EFE6\",\"claimer_name\":\"Romell Diaz\",\"claimer_id\":\"23-38357\"}', '2025-11-29 04:50:06'),
(69, 4, 'VIEW', 'claim_request', 26, '{\"ticket_code\":\"CLAIM-B1734883\",\"claimer_name\":\"John Lloyd Baes\",\"claimer_id\":\"23-67676\"}', '2025-11-29 04:50:28'),
(70, 13, 'DECAY', 'found_report', 21, '{\"fnd_id\":21,\"fnd_name\":\"test12\",\"fnd_desc\":\"TESTING\",\"location_id\":9,\"fnd_datetime\":\"2025-11-20 01:08:16\",\"user_id\":4,\"image_path\":\"uploads\\/found_items\\/found_1764349696.png\",\"category_id\":13,\"fnd_status\":\"unclaimed\"}', '2025-11-29 04:52:04'),
(71, 4, 'DISCARD', 'decayed_table', 21, 'Item discarded by Sir Joey to OSD', '2025-11-29 04:52:40'),
(72, 13, 'INSERT', 'lost_report', 14, '{\"lost_name\":\"Cellphone\",\"lost_desc\":\"Blue Phone\",\"location_id\":\"7\",\"category_id\":\"10\",\"image_path\":\"uploads\\/lost_items\\/1764639663_pilipins.png\"}', '2025-12-02 01:41:03'),
(73, 13, 'DECAY', 'found_report', 18, '{\"fnd_id\":18,\"fnd_name\":\"Clipboard\",\"fnd_desc\":\"Contains FOUND-IT Documents\",\"location_id\":7,\"fnd_datetime\":\"2025-11-25 07:47:55\",\"user_id\":4,\"image_path\":\"uploads\\/found_items\\/found_1764028075.png\",\"category_id\":5,\"fnd_status\":\"claimed\"}', '2025-12-02 01:41:03'),
(74, 4, 'INSERT', 'found_report', 23, '{\"fnd_name\":\"Cellphone\",\"fnd_desc\":\"Blue\",\"location_id\":\"7\",\"category_id\":\"10\",\"image_path\":\"uploads\\/found_items\\/found_1764639873.png\"}', '2025-12-02 01:44:34'),
(75, 4, 'VIEW', 'claim_request', 32, '{\"ticket_code\":\"CLAIM-3A8AE0EB\",\"claimer_name\":\"John Lloyd Baes\",\"claimer_id\":\"23-11111\"}', '2025-12-02 01:48:09'),
(76, 4, 'UPDATE', 'claim_request', 32, '{\"ticket_code\":\"CLAIM-3A8AE0EB\",\"claimer_name\":\"John Lloyd Baes\",\"claimer_id\":\"23-11111\"}', '2025-12-02 01:48:16');

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
(10, 4, 'Admin', 'We are thrilled to announce the official launch of FOUND-IT! 🎉\n\nFOUND-IT is your reliable platform for reporting, tracking, and claiming lost & found items quickly and efficiently. With our system, you can:\n\nReport lost items easily with photos and descriptions.\n\nTrack found items in real-time.\n\nReceive instant notifications when your missing belongings are located.\n\nOur goal is to make lost-and-found management simpler, faster, and more reliable for everyone. Start using FOUND-IT today and never worry about losing your valuables again!\n\nWelcome to a smarter way to keep track of your things — FOUND-IT is here!', '2025-11-19 12:11:07'),
(28, 0, 'Admin', 'test', '2025-11-24 08:21:57'),
(29, 0, 'Admin', 'FOUND-IT System is here!', '2025-11-25 06:00:23'),
(30, 0, 'Admin', 'FOUND-IT now has SMS Integration!', '2025-11-29 02:07:30'),
(31, 0, 'Admin', 'Announcement', '2025-11-29 04:39:14');

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
  `assessor` int(10) UNSIGNED DEFAULT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claim_request`
--

INSERT INTO `claim_request` (`request_id`, `fnd_id`, `user_id`, `ticket_code`, `claimer_name`, `contact_number`, `claimer_id`, `claimer_email`, `proof_of_ownership`, `status`, `assessor`, `request_date`) VALUES
(26, 19, 11, 'CLAIM-B1734883', 'John Lloyd Baes', '', '23-67676', '23-67676@g.batstate-u.edu.ph', 'Black bag naiwan ko sa facade, may sira ung zipper', 'claimed', NULL, '2025-11-25 13:52:39'),
(29, 20, 12, 'CLAIM-FD0877A8', 'Jodi Nicole', '', '23-67676', '23-67676@g.batstate-u.edu.ph', 'Razer Mouse naiwan ko', 'claimed', 4, '2025-11-29 09:06:24'),
(31, 22, 13, 'CLAIM-4696EFE6', 'Romell Diaz', '', '23-38357', '23-38357@g.batstate-u.edu.ph', 'POCO X3 May tape sa gilid', 'claimed', 4, '2025-11-29 12:47:27'),
(32, 23, 13, 'CLAIM-3A8AE0EB', 'John Lloyd Baes', '', '23-11111', '23-11111@g.batstate-u.edu.ph', 'Blue Phone', 'claimed', 4, '2025-12-02 09:46:44');

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
(15, 26, 11, 19, 'CLAIM-B1734883', '../qrcodes/CLAIM-B1734883.png', '2025-11-25 13:54:58'),
(16, 29, 12, 20, 'CLAIM-FD0877A8', '../qrcodes/CLAIM-FD0877A8.png', '2025-11-29 09:08:45'),
(17, 31, 13, 22, 'CLAIM-4696EFE6', '../qrcodes/CLAIM-4696EFE6.png', '2025-11-29 12:48:02'),
(18, 32, 13, 23, 'CLAIM-3A8AE0EB', '../qrcodes/CLAIM-3A8AE0EB.png', '2025-12-02 09:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `decayed_table`
--

CREATE TABLE `decayed_table` (
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
-- Dumping data for table `decayed_table`
--

INSERT INTO `decayed_table` (`fnd_id`, `fnd_name`, `fnd_desc`, `location_id`, `fnd_datetime`, `user_id`, `image_path`, `category_id`, `fnd_status`) VALUES
(11, 'Shoes', 'NIKE', 7, '2025-11-11 05:18:05', 4, 'uploads/found_items/found_1763439485.png', 11, 'discarded'),
(12, 'Bag', 'Brown', 5, '2025-11-11 04:36:48', 4, 'uploads/found_items/found_1763869008.png', 7, 'discarded'),
(13, 'Cellphone', 'PCO X3', 8, '2025-11-11 04:49:29', 4, 'uploads/found_items/found_1763869769.png', 10, 'discarded'),
(14, 'Water Bottle', 'Brown cap', 7, '2025-11-11 05:13:26', 4, 'uploads/found_items/found_1763871206.png', 4, 'discarded'),
(15, 'test', 'test', 4, '2025-11-11 06:43:44', 4, 'uploads/found_items/found_1763876624.png', 13, 'discarded'),
(16, 'test', 'test', 4, '2025-11-11 07:57:21', 4, 'uploads/found_items/found_1763881041.png', 8, 'discarded'),
(17, 'Water bottleA', 'asd', 4, '2025-11-11 15:02:42', 4, 'uploads/found_items/found_1763881362.png', 4, 'discarded'),
(18, 'Clipboard', 'Contains FOUND-IT Documents', 7, '2025-11-25 07:47:55', 4, 'uploads/found_items/found_1764028075.png', 5, 'claimed'),
(21, 'test12', 'TESTING', 9, '2025-11-20 01:08:16', 4, 'uploads/found_items/found_1764349696.png', 13, 'discarded');

-- --------------------------------------------------------

--
-- Table structure for table `discard_table`
--

CREATE TABLE `discard_table` (
  `id` int(11) NOT NULL,
  `fnd_id` int(11) NOT NULL,
  `discarded_by` varchar(100) NOT NULL,
  `discard_location` varchar(50) NOT NULL,
  `discarded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discard_table`
--

INSERT INTO `discard_table` (`id`, `fnd_id`, `discarded_by`, `discard_location`, `discarded_at`) VALUES
(1, 15, 'Joey', 'OSD', '2025-11-23 06:56:39'),
(2, 11, 'Joey', 'SAO/SSC', '2025-11-23 07:09:56'),
(3, 14, 'Joey', 'OSD', '2025-11-23 07:50:25'),
(4, 12, 'Joey', 'OSD', '2025-11-23 07:53:54'),
(5, 16, 'Joey', 'OSD', '2025-11-23 08:00:23'),
(6, 17, 'Joey', 'OSD', '2025-11-23 15:03:15'),
(7, 21, 'Sir Joey', 'OSD', '2025-11-29 12:52:40');

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
(19, 'Black bag', 'Black shoulder bag', 7, '2025-11-25 13:12:07', 4, 'uploads/found_items/found_1764047527.png', 7, 'claimed'),
(20, 'Computer Mouse', 'Razer Mouse', 8, '2025-11-25 14:21:53', 4, 'uploads/found_items/found_1764051713.png', 10, 'claimed'),
(22, 'Cellphone', 'POCO X3 may tape sa gilid', 8, '2025-11-29 12:45:18', 4, 'uploads/found_items/found_1764391518.png', 10, 'claimed'),
(23, 'Cellphone', 'Blue', 7, '2025-12-02 09:44:33', 4, 'uploads/found_items/found_1764639873.png', 10, 'claimed');

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
(6, 'Aquaflask', 'Blue', 7, '2025-11-19 01:37:47', 4, 'uploads/lost_items/1763512667_WATAQF0000428__1.jpg', 4, ''),
(7, 'Receipt', 'Gcash Resibo', 10, '2025-11-19 01:46:23', 4, 'uploads/lost_items/1763513183_989ff506-340e-4666-9da8-d66a78642072.jpg', 5, ''),
(8, 'Bag', 'Brown', 5, '2025-11-23 04:40:30', 4, 'uploads/lost_items/1763869230_foundit-logo.png', 7, ''),
(10, 'Clipboard', 'It has FOUND-IT Documents', 7, '2025-11-25 07:48:55', 10, 'uploads/lost_items/1764028135_Screenshot 2023-09-21 172520.png', 5, 'active'),
(11, 'Black Bag', 'Black shoulder bag', 7, '2025-11-25 13:48:58', 11, 'uploads/lost_items/1764049738_pilipins.png', 7, 'active'),
(12, 'Test', 'Pink', 6, '2025-11-29 12:23:09', 4, 'uploads/lost_items/1764390189_pilipins.png', 11, 'active'),
(13, 'Cellphone', 'May tape sa gilid', 8, '2025-11-29 12:41:06', 13, 'uploads/lost_items/1764391266_pilipins.png', 10, 'active'),
(14, 'Cellphone', 'Blue Phone', 7, '2025-12-02 09:41:03', 13, 'uploads/lost_items/1764639663_pilipins.png', 10, 'active');

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
  `password` varchar(255) NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_table`
--

INSERT INTO `users_table` (`user_id`, `user_name`, `contact_no`, `date_registered`, `is_admin`, `sr_code`, `email`, `password`, `is_approved`) VALUES
(4, 'Admin', '09471098936', '2025-10-26', 1, NULL, 'admin@gmail.com', '$2y$10$XQsNNU6z6a93Z6fTWlydlOjeVboB9seK3uZIwgJ/M0sYFYzyh1ZoO', 1),
(10, 'Yinuo Sandoval', '09761668605', '2025-11-24', 0, '23-11111', 'asdf@gmail.com', '$2y$10$JtRu9flXSFy5H9eUmqsU9eWjK4RXdAhC3kMwU2/60Tj5PJGGSZBOK', 1),
(11, 'Jodi Nicole', '09761668605', '2025-11-25', 0, '23-67676', '23-67676@g.batstate-u.edu.ph', '$2y$10$GYroG2/pKdKArfX.z5H7NOnLqPO.hDgrZaCVuG67SQbZ/HQdZaXhy', 1),
(12, 'John Lloyd Baes', '09761668605', '2025-11-29', 0, '23-99999', '23-99999@g.batstate-u.edu.ph', '$2y$10$hT.u4DfnlVRPdWzwkuaub.uo4fv/sAchZNUw9o.UcKXuNDFcQp/1G', 1),
(13, 'Miguell Diaz', '09761668605', '2025-11-29', 0, '23-88888', '23-88888@g.batstate-u.edu.ph', '$2y$10$b38FAqnGCmyA60mqu7qowuZJ8l0qawtheCUQfMVkUjA7kd5PYoUw.', 1);

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
(44, 10, 9999, 'Yinuo Sandoval', 'i need help g', 0, '2025-11-24 08:22:33'),
(45, 9999, 10, 'Admin', 'wyn?', 0, '2025-11-24 08:22:40'),
(46, 11, 9999, 'John Lloyd Baes', 'Help ung cellphone ko po nadiscarded na po sa system', 0, '2025-11-25 06:05:20'),
(47, 9999, 11, 'Admin', 'Please go to the OSD Office to claim your item/s', 0, '2025-11-25 06:06:07'),
(48, 13, 9999, 'Miguell Diaz', 'help', 0, '2025-11-29 04:38:41'),
(49, 9999, 13, 'Admin', 'ok', 0, '2025-11-29 04:38:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`);

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
  ADD KEY `fk_user_id` (`user_id`),
  ADD KEY `fk_claim_request_assessor` (`assessor`);

--
-- Indexes for table `claim_verification`
--
ALTER TABLE `claim_verification`
  ADD PRIMARY KEY (`verify_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fnd_id` (`fnd_id`);

--
-- Indexes for table `decayed_table`
--
ALTER TABLE `decayed_table`
  ADD PRIMARY KEY (`fnd_id`);

--
-- Indexes for table `discard_table`
--
ALTER TABLE `discard_table`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `claim_request`
--
ALTER TABLE `claim_request`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `claim_verification`
--
ALTER TABLE `claim_verification`
  MODIFY `verify_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `discard_table`
--
ALTER TABLE `discard_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `found_report`
--
ALTER TABLE `found_report`
  MODIFY `fnd_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `lost_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users_table`
--
ALTER TABLE `users_table`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_admin_msgs`
--
ALTER TABLE `user_admin_msgs`
  MODIFY `msg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `claim_request`
--
ALTER TABLE `claim_request`
  ADD CONSTRAINT `fk_claim_request_assessor` FOREIGN KEY (`assessor`) REFERENCES `users_table` (`user_id`) ON DELETE SET NULL,
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
