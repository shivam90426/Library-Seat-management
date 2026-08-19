-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2026 at 11:22 PM
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
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diary`
--

CREATE TABLE `diary` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `diary_date` date NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `last_saved_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diary_entries`
--

CREATE TABLE `diary_entries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diary_entries`
--

INSERT INTO `diary_entries` (`id`, `user_id`, `entry_date`, `content`, `created_at`, `updated_at`) VALUES
(1, 3, '2026-03-26', '', '2026-03-26 20:24:26', '2026-03-26 20:38:10'),
(8, 3, '2026-04-06', '', '2026-04-06 11:18:30', '2026-04-06 11:19:53'),
(13, 3, '2026-04-23', '', '2026-04-23 12:48:27', '2026-04-23 12:48:27'),
(14, 5, '2026-06-01', 'trhfghdfgvdfh\ngjdfhdsysdrhfj\ntytyreryaeryte\nyrsgdshtdsjdgthj\nuysgrsgfdshdfh\nthrshdhdtsjtr\nuhtrhettrhjr\nutehtrjrdyyrsyergsdhsr\nyrehtruehzdfjueydrsjutestj\nyrwhetwh46hey\nrytyujrdhjdhfgseryghryeuty\nuyeytdrydgtyrdtkmytnrhygte\ntyryrteghyumuityurheg\nyturyegehmuytu', '2026-06-01 21:45:32', '2026-06-01 21:45:32'),
(15, 1, '2026-08-19', 'today mausam was suhana i want to study very very to much much abt ai', '2026-08-19 13:46:36', '2026-08-19 13:47:09'),
(17, 8, '2026-08-19', '87687uygfjfhjfygfuykgyuj', '2026-08-19 14:48:16', '2026-08-19 14:48:56');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_order_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('UPI','QR','RAZORPAY') NOT NULL,
  `utr_no` varchar(100) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','success','failed','rejected') DEFAULT 'pending',
  `verified_by_admin` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `subscription_id`, `transaction_id`, `gateway_order_id`, `amount`, `payment_method`, `utr_no`, `screenshot_path`, `status`, `verified_by_admin`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '6557', NULL, 999.00, 'UPI', NULL, NULL, 'rejected', NULL, NULL, '2026-02-15 17:23:51', '2026-02-15 17:43:46'),
(2, 1, NULL, '87768', NULL, 999.00, 'UPI', NULL, NULL, 'success', 2, '2026-03-26 20:59:01', '2026-02-15 17:44:52', '2026-03-26 15:29:01'),
(3, 1, NULL, '878534', NULL, 999.00, 'UPI', NULL, NULL, 'rejected', 2, '2026-03-26 20:59:06', '2026-02-15 17:49:03', '2026-03-26 15:29:06'),
(4, 3, NULL, '87657646534', NULL, 999.00, 'UPI', NULL, NULL, 'rejected', 2, '2026-03-26 20:59:10', '2026-03-01 06:47:16', '2026-03-26 15:29:10'),
(5, 3, NULL, '89786567678588', NULL, 999.00, 'UPI', NULL, NULL, 'success', 2, '2026-04-06 11:16:30', '2026-04-06 05:43:41', '2026-04-06 05:46:30'),
(6, 4, NULL, '55768797674542', NULL, 999.00, 'UPI', NULL, NULL, 'success', 2, '2026-05-18 00:17:23', '2026-05-17 18:46:05', '2026-05-17 18:47:23'),
(7, 5, NULL, '56547563465', NULL, 999.00, 'UPI', NULL, NULL, 'success', 2, '2026-06-01 21:42:41', '2026-06-01 16:10:28', '2026-06-01 16:12:41'),
(8, 6, NULL, '873847872385', NULL, 999.00, 'UPI', NULL, NULL, 'rejected', 2, '2026-08-19 13:41:42', '2026-06-18 09:54:37', '2026-08-19 08:11:42'),
(9, 7, NULL, '696969420420', NULL, 999.00, 'UPI', NULL, NULL, 'success', 2, '2026-08-19 13:41:33', '2026-08-19 08:10:00', '2026-08-19 08:11:33'),
(10, 1, NULL, '5474574756', NULL, 999.00, 'UPI', NULL, NULL, 'success', 2, '2026-08-19 13:44:26', '2026-08-19 08:13:53', '2026-08-19 08:14:26'),
(11, 8, NULL, '87675674676767', NULL, 999.00, 'UPI', NULL, NULL, 'success', 2, '2026-08-19 14:45:33', '2026-08-19 09:14:50', '2026-08-19 09:15:33');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `upi_id` varchar(100) NOT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `upi_id`, `qr_image`, `active`, `updated_by`, `updated_at`) VALUES
(1, '8828191034@ybl', 'uploads/qr/1771175810_WhatsApp Image 2026-02-15 at 10.46.07 PM.jpeg', 1, 1, '2026-02-15 17:16:50');

-- --------------------------------------------------------

--
-- Table structure for table `schema_version`
--

CREATE TABLE `schema_version` (
  `id` int(11) NOT NULL,
  `version` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schema_version`
--

INSERT INTO `schema_version` (`id`, `version`, `description`, `applied_at`) VALUES
(1, '1.0.0', 'Initial production deployment schema', '2026-02-15 05:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `id` int(11) NOT NULL,
  `seat_no` varchar(20) NOT NULL,
  `seat_type` enum('6h','12h','24h') NOT NULL,
  `section_name` enum('six','twelve_left','twelve_mid','twentyfour') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_maintenance` tinyint(1) DEFAULT 0,
  `position_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pos_row` int(11) DEFAULT 0,
  `pos_col` int(11) DEFAULT 0,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`id`, `seat_no`, `seat_type`, `section_name`, `is_active`, `is_maintenance`, `position_order`, `created_at`, `pos_row`, `pos_col`, `section_id`) VALUES
(31, '6H-1', '6h', 'six', 1, 0, 0, '2026-03-07 17:56:57', 0, 0, 1),
(32, '6H-2', '6h', 'six', 1, 0, 1, '2026-03-07 17:56:57', 0, 0, 1),
(33, '6H-3', '6h', 'six', 1, 0, 2, '2026-03-07 17:56:57', 0, 0, 1),
(34, '6H-4', '6h', 'six', 1, 0, 3, '2026-03-07 17:56:57', 0, 0, 1),
(35, '6H-5', '6h', 'six', 1, 0, 4, '2026-03-07 17:56:57', 0, 0, 1),
(36, '6H-6', '6h', 'six', 1, 0, 5, '2026-03-07 17:56:57', 0, 0, 1),
(37, '6H-7', '6h', 'six', 1, 0, 6, '2026-03-07 17:56:57', 0, 0, 1),
(38, '6H-8', '6h', 'six', 1, 0, 7, '2026-03-07 17:56:57', 0, 0, 1),
(39, '6H-9', '6h', 'six', 1, 0, 8, '2026-03-07 17:56:57', 0, 0, 1),
(40, '6H-10', '6h', 'six', 1, 0, 9, '2026-03-07 17:56:57', 0, 0, 1),
(41, '6H-11', '6h', 'six', 1, 0, 10, '2026-03-07 17:56:57', 0, 0, 1),
(42, '6H-12', '6h', 'six', 1, 0, 11, '2026-03-07 17:56:57', 0, 0, 1),
(43, '6H-13', '6h', 'six', 1, 0, 12, '2026-03-07 17:56:57', 0, 0, 1),
(44, '6H-14', '6h', 'six', 1, 0, 13, '2026-03-07 17:56:57', 0, 0, 1),
(45, '6H-15', '6h', 'six', 1, 0, 14, '2026-03-07 17:56:57', 0, 0, 1),
(46, '6H-16', '6h', 'six', 1, 0, 15, '2026-03-07 17:56:57', 0, 0, 1),
(47, '6H-17', '6h', 'six', 1, 0, 16, '2026-03-07 17:56:57', 0, 0, 1),
(48, '6H-18', '6h', 'six', 1, 0, 17, '2026-03-07 17:56:57', 0, 0, 1),
(49, '6H-19', '6h', 'six', 1, 0, 18, '2026-03-07 17:56:57', 0, 0, 1),
(50, '6H-20', '6h', 'six', 1, 0, 19, '2026-03-07 17:56:57', 0, 0, 1),
(51, '6H-21', '6h', 'six', 1, 0, 20, '2026-03-07 17:56:57', 0, 0, 1),
(52, '6H-22', '6h', 'six', 1, 0, 21, '2026-03-07 17:56:57', 0, 0, 1),
(53, '6H-23', '6h', 'six', 1, 0, 22, '2026-03-07 17:56:57', 0, 0, 1),
(54, '6H-24', '6h', 'six', 1, 0, 23, '2026-03-07 17:56:57', 0, 0, 1),
(55, '6H-25', '6h', 'six', 1, 0, 24, '2026-03-07 17:56:57', 0, 0, 1),
(56, '6H-26', '6h', 'six', 1, 0, 25, '2026-03-07 17:56:57', 0, 0, 1),
(57, '6H-27', '6h', 'six', 1, 0, 26, '2026-03-07 17:56:57', 0, 0, 1),
(58, '6H-28', '6h', 'six', 1, 0, 27, '2026-03-07 17:56:57', 0, 0, 1),
(59, '6H-29', '6h', 'six', 1, 0, 28, '2026-03-07 17:56:57', 0, 0, 1),
(60, '6H-30', '6h', 'six', 1, 0, 29, '2026-03-07 17:56:57', 0, 0, 1),
(61, '6H-31', '6h', 'six', 1, 0, 30, '2026-03-07 17:56:57', 0, 0, 1),
(62, '6H-32', '6h', 'six', 1, 0, 31, '2026-03-07 17:56:57', 0, 0, 1),
(63, '6H-33', '6h', 'six', 1, 0, 32, '2026-03-07 17:56:57', 0, 0, 1),
(64, '6H-34', '6h', 'six', 1, 0, 33, '2026-03-07 17:56:57', 0, 0, 1),
(65, '6H-35', '6h', 'six', 1, 0, 34, '2026-03-07 17:56:57', 0, 0, 1),
(66, '6H-36', '6h', 'six', 1, 0, 35, '2026-03-07 17:56:57', 0, 0, 1),
(67, '6H-37', '6h', 'six', 1, 0, 36, '2026-03-07 17:56:57', 0, 0, 1),
(68, '6H-38', '6h', 'six', 1, 0, 37, '2026-03-07 17:56:57', 0, 0, 1),
(69, '6H-39', '6h', 'six', 1, 0, 38, '2026-03-07 17:56:57', 0, 0, 1),
(70, '6H-40', '6h', 'six', 1, 0, 39, '2026-03-07 17:56:57', 0, 0, 1),
(71, '6H-41', '6h', 'six', 1, 0, 40, '2026-03-07 17:56:57', 0, 0, 1),
(72, '6H-42', '6h', 'six', 1, 0, 41, '2026-03-07 17:56:57', 0, 0, 1),
(73, '6H-43', '6h', 'six', 1, 0, 42, '2026-03-07 17:56:57', 0, 0, 1),
(74, '6H-44', '6h', 'six', 1, 0, 43, '2026-03-07 17:56:57', 0, 0, 1),
(75, '6H-45', '6h', 'six', 1, 0, 44, '2026-03-07 17:56:57', 0, 0, 1),
(76, '6H-46', '6h', 'six', 1, 0, 45, '2026-03-07 17:56:57', 0, 0, 1),
(77, '6H-47', '6h', 'six', 1, 0, 46, '2026-03-07 17:56:57', 0, 0, 1),
(78, '6H-48', '6h', 'six', 1, 0, 47, '2026-03-07 17:56:57', 0, 0, 1),
(79, '6H-49', '6h', 'six', 1, 0, 48, '2026-03-07 17:56:57', 0, 0, 1),
(80, '6H-50', '6h', 'six', 1, 0, 49, '2026-03-07 17:56:57', 0, 0, 1),
(81, '6H-51', '6h', 'six', 1, 0, 50, '2026-03-07 17:56:57', 0, 0, 1),
(82, '6H-52', '6h', 'six', 1, 0, 51, '2026-03-07 17:56:57', 0, 0, 1),
(83, '6H-53', '6h', 'six', 1, 0, 52, '2026-03-07 17:56:57', 0, 0, 1),
(84, '6H-54', '6h', 'six', 1, 0, 53, '2026-03-07 17:56:57', 0, 0, 1),
(85, '6H-55', '6h', 'six', 1, 0, 54, '2026-03-07 17:56:57', 0, 0, 1),
(86, '6H-56', '6h', 'six', 1, 0, 55, '2026-03-07 17:56:57', 0, 0, 1),
(87, '6H-57', '6h', 'six', 1, 0, 56, '2026-03-07 17:56:57', 0, 0, 1),
(88, '6H-58', '6h', 'six', 1, 0, 57, '2026-03-07 17:56:57', 0, 0, 1),
(89, '6H-59', '6h', 'six', 1, 0, 58, '2026-03-07 17:56:57', 0, 0, 1),
(90, '6H-60', '6h', 'six', 1, 0, 59, '2026-03-07 17:56:57', 0, 0, 1),
(91, '6H-61', '6h', 'six', 1, 0, 60, '2026-03-07 17:56:57', 0, 0, 1),
(92, '6H-62', '6h', 'six', 1, 0, 61, '2026-03-07 17:56:57', 0, 0, 1),
(93, '6H-63', '6h', 'six', 1, 0, 62, '2026-03-07 17:56:57', 0, 0, 1),
(94, '12H-L-1', '12h', 'twelve_left', 1, 0, 0, '2026-03-07 17:57:20', 0, 0, 3),
(95, '12H-L-2', '12h', 'twelve_left', 1, 0, 1, '2026-03-07 17:57:20', 0, 0, 3),
(96, '12H-L-3', '12h', 'twelve_left', 1, 0, 2, '2026-03-07 17:57:20', 0, 0, 3),
(97, '12H-L-4', '12h', 'twelve_left', 1, 0, 3, '2026-03-07 17:57:20', 0, 0, 3),
(98, '12H-L-5', '12h', 'twelve_left', 1, 0, 4, '2026-03-07 17:57:20', 0, 0, 3),
(99, '12H-L-6', '12h', 'twelve_left', 1, 0, 5, '2026-03-07 17:57:20', 0, 0, 3),
(100, '12H-L-7', '12h', 'twelve_left', 1, 0, 6, '2026-03-07 17:57:20', 0, 0, 3),
(101, '12H-L-8', '12h', 'twelve_left', 1, 0, 7, '2026-03-07 17:57:20', 0, 0, 3),
(102, '12H-L-9', '12h', 'twelve_left', 1, 0, 8, '2026-03-07 17:57:20', 0, 0, 3),
(103, '12H-L-10', '12h', 'twelve_left', 1, 0, 9, '2026-03-07 17:57:20', 0, 0, 3),
(104, '12H-L-11', '12h', 'twelve_left', 1, 0, 10, '2026-03-07 17:57:20', 0, 0, 3),
(105, '12H-L-12', '12h', 'twelve_left', 1, 0, 11, '2026-03-07 17:57:20', 0, 0, 3),
(106, '12H-L-13', '12h', 'twelve_left', 1, 0, 12, '2026-03-07 17:57:20', 0, 0, 3),
(107, '12H-L-14', '12h', 'twelve_left', 1, 0, 13, '2026-03-07 17:57:20', 0, 0, 3),
(108, '12H-L-15', '12h', 'twelve_left', 1, 0, 14, '2026-03-07 17:57:20', 0, 0, 3),
(109, '12H-L-16', '12h', 'twelve_left', 1, 0, 15, '2026-03-07 17:57:20', 0, 0, 3),
(112, '12H-L-19', '12h', 'twelve_left', 1, 0, 18, '2026-03-07 17:57:20', 0, 0, 3),
(113, '12H-L-20', '12h', 'twelve_left', 1, 0, 19, '2026-03-07 17:57:20', 0, 0, 3),
(125, '12H-M-1', '12h', 'twelve_mid', 1, 0, 0, '2026-03-07 17:57:47', 0, 0, 4),
(126, '12H-M-2', '12h', 'twelve_mid', 1, 0, 1, '2026-03-07 17:57:47', 0, 0, 4),
(127, '12H-M-3', '12h', 'twelve_mid', 1, 0, 2, '2026-03-07 17:57:47', 0, 0, 4),
(128, '12H-M-4', '12h', 'twelve_mid', 1, 0, 3, '2026-03-07 17:57:47', 0, 0, 4),
(129, '12H-M-5', '12h', 'twelve_mid', 1, 0, 4, '2026-03-07 17:57:47', 0, 0, 4),
(130, '12H-M-6', '12h', 'twelve_mid', 1, 0, 5, '2026-03-07 17:57:47', 0, 0, 4),
(131, '12H-M-7', '12h', 'twelve_mid', 1, 0, 6, '2026-03-07 17:57:47', 0, 0, 4),
(132, '12H-M-8', '12h', 'twelve_mid', 1, 0, 7, '2026-03-07 17:57:47', 0, 0, 4),
(133, '12H-M-9', '12h', 'twelve_mid', 1, 0, 8, '2026-03-07 17:57:47', 0, 0, 4),
(134, '12H-M-10', '12h', 'twelve_mid', 1, 0, 9, '2026-03-07 17:57:47', 0, 0, 4),
(140, '24H-1', '24h', 'twentyfour', 1, 0, 0, '2026-03-07 17:58:10', 0, 0, 5),
(141, '24H-2', '24h', 'twentyfour', 1, 0, 1, '2026-03-07 17:58:10', 0, 0, 5),
(142, '24H-3', '24h', 'twentyfour', 1, 0, 2, '2026-03-07 17:58:10', 0, 0, 5),
(143, '24H-4', '24h', 'twentyfour', 1, 0, 3, '2026-03-07 17:58:10', 0, 0, 5),
(144, '24H-5', '24h', 'twentyfour', 1, 0, 4, '2026-03-07 17:58:10', 0, 0, 5),
(145, '24H-6', '24h', 'twentyfour', 1, 0, 5, '2026-03-07 17:58:10', 0, 0, 5),
(146, '24H-7', '24h', 'twentyfour', 1, 0, 6, '2026-03-07 17:58:10', 0, 0, 5),
(147, '24H-8', '24h', 'twentyfour', 1, 0, 7, '2026-03-07 17:58:10', 0, 0, 5),
(148, '24H-9', '24h', 'twentyfour', 1, 0, 8, '2026-03-07 17:58:10', 0, 0, 5),
(149, '24H-10', '24h', 'twentyfour', 1, 0, 9, '2026-03-07 17:58:10', 0, 0, 5),
(151, '24H-12', '24h', 'twentyfour', 1, 0, 11, '2026-03-07 17:58:10', 0, 0, 5),
(152, '24H-13', '24h', 'twentyfour', 1, 0, 12, '2026-03-07 17:58:10', 0, 0, 5),
(153, '24H-14', '24h', 'twentyfour', 1, 0, 13, '2026-03-07 17:58:10', 0, 0, 5),
(154, '24H-15', '24h', 'twentyfour', 1, 0, 14, '2026-03-07 17:58:10', 0, 0, 5),
(188, '12H-1', '12h', 'twelve_mid', 1, 0, 10, '2026-03-26 15:39:49', 0, 0, 4),
(189, '12H-2', '12h', 'twelve_mid', 1, 0, 11, '2026-03-26 15:39:51', 0, 0, 4),
(190, '12H-3', '12h', 'twelve_left', 1, 0, 20, '2026-03-26 18:15:22', 0, 0, 3),
(191, '12H-4', '12h', 'twelve_left', 1, 0, 21, '2026-03-26 18:15:23', 0, 0, 3),
(192, '12H-5', '12h', 'twelve_left', 1, 0, 22, '2026-03-26 18:15:24', 0, 0, 3),
(193, '12H-6', '12h', 'twelve_left', 1, 0, 23, '2026-03-26 18:15:24', 0, 0, 3),
(194, '12H-7', '12h', 'twelve_left', 1, 0, 24, '2026-03-26 18:15:24', 0, 0, 3),
(195, '12H-8', '12h', 'twelve_left', 1, 0, 25, '2026-03-26 18:15:25', 0, 0, 3),
(196, '12H-9', '12h', 'twelve_left', 1, 0, 26, '2026-03-26 18:15:25', 0, 0, 3),
(197, '12H-10', '12h', 'twelve_left', 1, 0, 27, '2026-03-26 18:15:25', 0, 0, 3),
(198, '12H-11', '12h', 'twelve_left', 1, 0, 28, '2026-03-26 18:15:26', 0, 0, 3),
(199, '6H-64', '6h', 'six', 1, 0, 63, '2026-08-19 08:18:52', 0, 0, NULL),
(200, '6H-65', '6h', 'six', 1, 0, 64, '2026-08-19 08:18:54', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seat_bookings`
--

CREATE TABLE `seat_bookings` (
  `id` int(11) NOT NULL,
  `seat_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `booking_type` enum('daily','fixed') NOT NULL,
  `booking_date` date DEFAULT NULL,
  `booking_start` datetime NOT NULL,
  `booking_end` datetime NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seat_bookings`
--

INSERT INTO `seat_bookings` (`id`, `seat_id`, `user_id`, `subscription_id`, `booking_type`, `booking_date`, `booking_start`, `booking_end`, `status`, `created_at`) VALUES
(1, 103, 3, 2, 'daily', '2026-03-26', '2026-03-26 20:21:21', '0000-00-00 00:00:00', 'active', '2026-03-26 14:51:21'),
(2, 62, 3, 4, 'daily', '2026-04-06', '2026-04-06 11:17:55', '0000-00-00 00:00:00', 'active', '2026-04-06 05:47:55'),
(3, 54, 1, 3, 'daily', '2026-04-06', '2026-04-06 11:24:34', '0000-00-00 00:00:00', 'active', '2026-04-06 05:54:34'),
(4, 143, 3, 4, 'daily', '2026-04-16', '2026-04-16 14:53:05', '0000-00-00 00:00:00', 'active', '2026-04-16 09:23:05'),
(5, 94, 3, 4, 'daily', '2026-04-23', '2026-04-23 12:47:53', '0000-00-00 00:00:00', 'active', '2026-04-23 07:17:53'),
(6, 99, 3, 4, 'daily', '2026-05-17', '2026-05-17 22:19:02', '0000-00-00 00:00:00', 'active', '2026-05-17 16:49:02'),
(7, 108, 5, 6, 'daily', '2026-06-01', '2026-06-01 21:43:47', '0000-00-00 00:00:00', 'active', '2026-06-01 16:13:47'),
(8, 125, 7, 7, 'daily', '2026-08-19', '2026-08-19 13:42:33', '0000-00-00 00:00:00', 'active', '2026-08-19 08:12:33'),
(9, 141, 1, 8, 'daily', '2026-08-19', '2026-08-19 13:45:22', '0000-00-00 00:00:00', 'active', '2026-08-19 08:15:22'),
(10, 188, 8, 9, 'daily', '2026-08-19', '2026-08-19 14:47:52', '0000-00-00 00:00:00', 'active', '2026-08-19 09:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `seat_exchange_requests`
--

CREATE TABLE `seat_exchange_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_seat_id` int(11) NOT NULL,
  `requested_seat_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seat_sections`
--

CREATE TABLE `seat_sections` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `section_code` varchar(50) DEFAULT NULL,
  `pos_x` int(11) DEFAULT 0,
  `pos_y` int(11) DEFAULT 0,
  `width` int(11) DEFAULT 4,
  `height` int(11) DEFAULT 4,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seat_sections`
--

INSERT INTO `seat_sections` (`id`, `name`, `section_code`, `pos_x`, `pos_y`, `width`, `height`, `created_at`) VALUES
(1, '6h Section', 'six', 22, 0, 8, 6, '2026-03-07 20:26:39'),
(2, 'Office', 'office', 379, 3, 3, 6, '2026-03-07 20:26:39'),
(3, '12h Left', 'twelve_left', 532, 0, 4, 4, '2026-03-07 20:26:39'),
(4, '12h Middle', 'twelve_mid', 725, 8, 2, 4, '2026-03-07 20:26:39'),
(5, '24h Section', 'twentyfour', 868, 4, 4, 4, '2026-03-07 20:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_name` varchar(50) NOT NULL,
  `seat_type` enum('6h','12h','24h') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_months` int(11) DEFAULT 1,
  `bonus_days` int(11) DEFAULT 0,
  `renewal_type` enum('normal','early_renewal','bulk_3month') DEFAULT 'normal',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled','queued') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan_name`, `seat_type`, `price`, `duration_months`, `bonus_days`, `renewal_type`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '1 Month Plan', '6h', 999.00, 1, 0, 'normal', '2026-02-15', '2026-03-15', 'active', '2026-02-15 17:49:23', '2026-02-15 17:49:23'),
(2, 3, '1 Month Plan', '6h', 999.00, 1, 0, 'normal', '2026-03-01', '2026-04-01', 'active', '2026-03-01 06:48:03', '2026-03-01 06:48:03'),
(3, 1, '1 Month Plan', '6h', 999.00, 1, 0, 'normal', '2026-03-26', '2026-04-26', 'active', '2026-03-26 15:29:01', '2026-03-26 15:29:01'),
(4, 3, '3 Month Premium', '6h', 2500.00, 3, 7, 'bulk_3month', '2026-04-06', '2026-07-13', 'active', '2026-04-06 05:46:30', '2026-04-06 05:46:30'),
(5, 4, '1 Month Plan', '6h', 999.00, 1, 0, 'normal', '2026-05-17', '2026-06-17', 'active', '2026-05-17 18:47:23', '2026-05-17 18:47:23'),
(6, 5, '1 Month Plan', '6h', 999.00, 1, 0, 'normal', '2026-06-01', '2026-07-01', 'active', '2026-06-01 16:12:41', '2026-06-01 16:12:41'),
(7, 7, '3 Month Premium', '6h', 2500.00, 3, 7, 'bulk_3month', '2026-08-19', '2026-11-26', 'active', '2026-08-19 08:11:33', '2026-08-19 08:11:33'),
(8, 1, '1 Month Plan', '6h', 999.00, 1, 0, 'normal', '2026-08-19', '2026-09-19', 'active', '2026-08-19 08:14:26', '2026-08-19 08:14:26'),
(9, 8, '1 Month Plan', '6h', 999.00, 1, 0, 'normal', '2026-08-19', '2026-09-19', 'active', '2026-08-19 09:15:33', '2026-08-19 09:15:33');

-- --------------------------------------------------------

--
-- Table structure for table `timings`
--

CREATE TABLE `timings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `seat_id` int(11) DEFAULT NULL,
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 0,
  `status` enum('running','completed') DEFAULT 'running',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timings`
--

INSERT INTO `timings` (`id`, `user_id`, `seat_id`, `entry_time`, `exit_time`, `duration_minutes`, `status`, `created_at`) VALUES
(1, 3, NULL, '2026-03-01 12:19:34', '2026-03-01 12:19:41', -270, 'running', '2026-03-01 06:49:34'),
(2, 3, NULL, '2026-03-01 12:19:43', '2026-03-01 12:36:15', -254, 'running', '2026-03-01 06:49:43'),
(3, 3, NULL, '2026-03-06 20:42:27', '2026-03-06 20:56:12', -257, 'running', '2026-03-06 15:12:27'),
(4, 3, NULL, '2026-03-06 20:56:13', '2026-03-07 20:35:09', 1148, 'running', '2026-03-06 15:26:13'),
(5, 3, NULL, '2026-03-07 21:54:34', '2026-03-07 22:08:19', -257, 'running', '2026-03-07 16:24:34'),
(6, 3, NULL, '2026-03-07 22:08:29', '2026-03-07 22:17:02', -262, 'running', '2026-03-07 16:38:29'),
(7, 3, NULL, '2026-03-07 22:17:05', '2026-03-07 23:49:40', -178, 'running', '2026-03-07 16:47:05'),
(8, 3, NULL, '2026-03-07 23:49:56', '2026-03-08 00:42:25', -218, 'running', '2026-03-07 18:19:56'),
(9, 3, NULL, '2026-03-08 00:45:28', '2026-03-08 00:45:35', -270, 'running', '2026-03-07 19:15:28'),
(10, 3, NULL, '2026-03-08 00:45:36', '2026-03-08 00:45:39', -270, 'running', '2026-03-07 19:15:36'),
(11, 3, NULL, '2026-03-08 00:45:40', '2026-03-08 01:21:52', -234, 'running', '2026-03-07 19:15:40'),
(12, 3, NULL, '2026-03-08 14:44:04', '2026-03-08 14:44:06', -270, 'running', '2026-03-08 09:14:04'),
(13, 3, NULL, '2026-03-09 13:56:30', '2026-03-26 20:25:03', 24598, 'running', '2026-03-09 08:26:30'),
(14, 3, NULL, '2026-03-26 20:25:04', '2026-03-26 20:27:03', -269, 'running', '2026-03-26 14:55:04'),
(15, 3, NULL, '2026-03-26 20:27:04', '2026-03-26 20:27:06', -270, 'running', '2026-03-26 14:57:04'),
(16, 3, NULL, '2026-04-06 11:18:22', '2026-04-06 11:20:08', -209, 'running', '2026-04-06 05:48:22'),
(17, 3, NULL, '2026-04-06 11:20:17', '2026-04-06 11:20:23', -210, 'running', '2026-04-06 05:50:17'),
(18, 3, NULL, '2026-04-06 11:20:24', '2026-04-16 13:13:01', 14302, 'running', '2026-04-06 05:50:24'),
(19, 3, NULL, '2026-04-16 14:59:48', '2026-04-16 15:04:56', -205, 'running', '2026-04-16 09:29:48'),
(20, 3, NULL, '2026-04-16 16:49:56', '2026-04-16 16:50:04', 0, 'running', '2026-04-16 11:19:56'),
(21, 3, NULL, '2026-04-23 12:47:25', '2026-04-23 12:49:37', 0, 'running', '2026-04-23 07:17:25'),
(22, 3, NULL, '2026-05-17 22:18:39', '2026-05-17 22:18:41', 0, 'running', '2026-05-17 16:48:39'),
(23, 3, NULL, '2026-05-17 22:22:01', '2026-05-18 00:12:22', 0, 'running', '2026-05-17 16:52:01'),
(24, 5, NULL, '2026-06-01 21:44:34', NULL, 0, 'running', '2026-06-01 16:14:34'),
(25, 8, NULL, '2026-08-19 14:48:06', '2026-08-19 14:49:14', 0, 'running', '2026-08-19 09:18:06'),
(26, 8, NULL, '2026-08-19 14:49:17', '2026-08-19 14:49:18', 0, 'running', '2026-08-19 09:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `profile_pic` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `failed_attempts` int(11) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `profile_pic`, `phone`, `is_active`, `failed_attempts`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Shivam Kushwaha', 'shivamkushwaha90426@gmail.com', '$2y$10$jkmoOlFXwuDfzzcIUXZN0ea4hfLjikfwn9NxU6res/ij1V9ErE5cK', 'user', 'assets/images/profile/1771171965_Screenshot 2024-06-10 133108.png', NULL, 1, 0, NULL, '2026-02-15 16:12:45', '2026-02-15 16:12:45'),
(2, 'Admin', 'admin@gmail.com', '$2y$10$l0dyXzjHJz40LipLqwV/.OWP2dwYDSc34sZ64spPPPCqL/DGgsqPi', 'admin', NULL, NULL, 1, 0, NULL, '2026-02-15 17:31:39', '2026-02-15 17:31:39'),
(3, 'Shivam', 'shivamkushwaha@gmail.com', '$2y$10$.vyD08D7rRwM.mNintwaM.WS0Yb2WGp6bzaWcfEKOYWUfCPwSDwO2', 'user', 'assets/images/profile/1771171965_Screenshot 2024-06-10 133108.png', NULL, 1, 0, NULL, '2026-02-15 17:57:53', '2026-03-07 19:39:20'),
(4, 'Dixit ji', 'shivansh@gmail.com', '$2y$10$RcZOslsHN8Kw2qGw9nPUbORcDRLN2im7ugf2hY0IKEAtjav4pfi.O', 'user', 'assets/images/profile/1779043430_Screenshot 2024-06-10 132847.png', NULL, 1, 0, NULL, '2026-05-17 18:43:50', '2026-05-17 18:43:50'),
(5, 'Anshika Kushwaha', 'anshika@gmail.com', '$2y$10$oxnl3k70AYT7Ml89oUijVObL2yMvxd9XBeWriGivYeSGckWWQa5Iy', 'user', 'assets/images/profile/1780329875_Screenshot 2024-06-10 132928.png', NULL, 1, 0, NULL, '2026-06-01 16:04:35', '2026-06-01 16:04:35'),
(6, 'Satyaprakash', 'satyaprakash@gmail.com', '$2y$10$IUSl5gG/YcDlLTefFm5hG.L833GEnbZWCLeIhsgfcKvtLs4a3dv1m', 'user', 'assets/images/profile/4a8fea80997943fe9ae93a70c8d91e46.png', NULL, 1, 0, NULL, '2026-06-18 09:53:16', '2026-06-18 09:53:16'),
(7, 'Shivam Singh patel', 'shivamsinghpatel@gmail.com', '$2y$10$uICPsnIeYC/jGNHR9.mqX.77OYxWMyyAKkRblO4wKOjwYwGGmAg0y', 'user', 'assets/images/profile/ec2c8e8490032fcad36309694ab8862c.jpg', NULL, 1, 0, NULL, '2026-08-19 08:06:15', '2026-08-19 08:06:15'),
(8, 'Saurabh', 'saurabh@gmail.com', '$2y$10$lPfER6xYvEF.HjfwpmQ5MuvWwh8X10eXmxpVv1Hr1ytX6T1ACDnGm', 'user', NULL, NULL, 1, 0, NULL, '2026-08-19 09:13:11', '2026-08-19 09:13:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `diary`
--
ALTER TABLE `diary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`diary_date`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`diary_date`);

--
-- Indexes for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_entry_date` (`user_id`,`entry_date`),
  ADD KEY `idx_diary_user_date` (`user_id`,`entry_date`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utr_no` (`utr_no`),
  ADD KEY `subscription_id` (`subscription_id`),
  ADD KEY `verified_by_admin` (`verified_by_admin`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `schema_version`
--
ALTER TABLE `schema_version`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `seat_no` (`seat_no`),
  ADD KEY `idx_type` (`seat_type`),
  ADD KEY `idx_section` (`section_name`);

--
-- Indexes for table `seat_bookings`
--
ALTER TABLE `seat_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seat` (`seat_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`booking_date`),
  ADD KEY `fk_booking_subscription` (`subscription_id`);

--
-- Indexes for table `seat_exchange_requests`
--
ALTER TABLE `seat_exchange_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `fk_exchange_old_seat` (`old_seat_id`),
  ADD KEY `fk_exchange_new_seat` (`requested_seat_id`);

--
-- Indexes for table `seat_sections`
--
ALTER TABLE `seat_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_code` (`section_code`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `end_date` (`end_date`);

--
-- Indexes for table `timings`
--
ALTER TABLE `timings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_entry` (`entry_time`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_2` (`email`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diary`
--
ALTER TABLE `diary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schema_version`
--
ALTER TABLE `schema_version`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `seat_bookings`
--
ALTER TABLE `seat_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `seat_exchange_requests`
--
ALTER TABLE `seat_exchange_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seat_sections`
--
ALTER TABLE `seat_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `timings`
--
ALTER TABLE `timings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`verified_by_admin`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD CONSTRAINT `payment_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `seat_bookings`
--
ALTER TABLE `seat_bookings`
  ADD CONSTRAINT `fk_booking_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seat_exchange_requests`
--
ALTER TABLE `seat_exchange_requests`
  ADD CONSTRAINT `fk_exchange_new_seat` FOREIGN KEY (`requested_seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exchange_old_seat` FOREIGN KEY (`old_seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exchange_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timings`
--
ALTER TABLE `timings`
  ADD CONSTRAINT `fk_timings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
