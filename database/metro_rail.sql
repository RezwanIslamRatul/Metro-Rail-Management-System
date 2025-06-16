-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 05:04 PM
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
-- Database: `metro_rail`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 4, 'login', 'User logged in', '::1', '2025-05-18 15:54:02'),
(2, 4, 'logout', 'User logged out', '::1', '2025-05-18 15:55:47'),
(3, 4, 'login', 'User logged in', '::1', '2025-05-18 16:01:18'),
(4, 1, 'login', 'Admin login via direct login script', '::1', '2025-05-18 16:08:28'),
(5, 1, 'logout', 'User logged out', '::1', '2025-05-18 16:09:25'),
(6, 4, 'login', 'User logged in', '::1', '2025-05-18 16:09:27'),
(7, 4, 'logout', 'User logged out', '::1', '2025-05-18 16:42:58'),
(8, 1, 'login', 'Admin login via direct login script', '::1', '2025-05-18 16:44:21'),
(9, 1, 'logout', 'User logged out', '::1', '2025-05-18 16:54:14'),
(10, 4, 'login', 'User logged in', '::1', '2025-05-18 16:54:17'),
(11, 4, 'booking_created', 'Created a new booking #MR202505181656515506', '::1', '2025-05-18 16:56:51'),
(12, 4, 'booking_created', 'Created a new booking #MR202505181659333175', '::1', '2025-05-18 16:59:33'),
(13, 4, 'booking_cancelled', 'Cancelled booking #MR202505181659333175', '::1', '2025-05-18 17:01:18'),
(14, 4, 'booking_cancelled', 'Cancelled booking #MR202505181656515506', '::1', '2025-05-18 17:01:28'),
(15, 4, 'booking_created', 'Created a new booking #MR202505181702185804', '::1', '2025-05-18 17:02:18'),
(16, 4, 'booking_created', 'Created a new booking #MR202505181838229420', '::1', '2025-05-18 18:38:22'),
(17, 4, 'booking_cancelled', 'Cancelled booking #MR202505181702185804', '::1', '2025-05-18 19:02:12'),
(18, 4, 'booking_cancelled', 'Cancelled booking #MR202505181838229420', '::1', '2025-05-18 19:02:15'),
(19, 4, 'booking_created', 'Created a new booking #MR202505181906022438', '::1', '2025-05-18 19:06:02'),
(20, 4, 'logout', 'User logged out', '::1', '2025-05-18 19:23:39'),
(21, 1, 'logout', 'User logged out', '::1', '2025-05-18 19:30:43'),
(22, 4, 'login', 'User logged in', '::1', '2025-05-18 20:23:51'),
(23, 4, 'password_changed', 'Changed account password', '::1', '2025-05-18 20:47:01'),
(24, 4, 'logout', 'User logged out', '::1', '2025-05-18 20:47:06'),
(25, 4, 'login', 'User logged in', '::1', '2025-05-18 20:47:11'),
(26, 4, 'booking_created', 'Created a new booking #MR202505182047332167', '::1', '2025-05-18 20:47:33'),
(27, 4, 'logout', 'User logged out', '::1', '2025-05-18 21:22:37'),
(28, 1, 'logout', 'User logged out', '::1', '2025-05-18 21:23:08'),
(29, 1, 'login', 'User logged in', '::1', '2025-05-18 21:23:14'),
(30, 1, 'login', 'User logged in', '::1', '2025-05-19 09:33:45'),
(31, 1, 'booking_status_updated', 'Updated booking #6 status to completed', '::1', '2025-05-19 09:56:11'),
(32, 4, 'login', 'User logged in', '::1', '2025-05-19 13:53:45');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `start_date`, `end_date`, `created_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'New East Line Opening Next Month', 'The much-awaited East Line connecting Downtown to Tech Park will be operational from next month.', '2025-05-15', '2025-06-15', 1, 'active', '2025-05-18 21:53:24', NULL),
(2, 'Weekend Maintenance Schedule', 'Planned maintenance work on the North Line this weekend. Check alternate routes.', '2025-05-16', '2025-05-23', 1, 'active', '2025-05-18 21:53:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `from_station_id` int(11) NOT NULL,
  `to_station_id` int(11) NOT NULL,
  `journey_date` date NOT NULL,
  `booking_date` datetime NOT NULL,
  `passengers` int(11) NOT NULL DEFAULT 1,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `booking_status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_number`, `user_id`, `schedule_id`, `from_station_id`, `to_station_id`, `journey_date`, `booking_date`, `passengers`, `amount`, `payment_status`, `booking_status`, `created_at`, `updated_at`) VALUES
(1, 'MR202505181656515506', 4, 11, 1, 6, '2025-05-19', '2025-05-18 16:56:51', 1, 1.50, 'pending', 'cancelled', '2025-05-18 16:56:51', '2025-05-18 17:01:28'),
(2, 'MR202505181659333175', 4, 11, 6, 3, '2025-05-19', '2025-05-18 16:59:33', 1, 20.36, 'pending', 'cancelled', '2025-05-18 16:59:33', '2025-05-18 17:01:18'),
(3, 'MR202505181702185804', 4, 11, 6, 3, '2025-05-18', '2025-05-18 17:02:18', 1, 20.36, 'pending', 'cancelled', '2025-05-18 17:02:18', '2025-05-18 19:02:12'),
(4, 'MR202505181838229420', 4, 11, 6, 3, '2025-05-18', '2025-05-18 18:38:22', 1, 20.36, 'pending', 'cancelled', '2025-05-18 18:38:22', '2025-05-18 19:02:15'),
(5, 'MR202505181906022438', 4, 12, 6, 7, '2025-05-18', '2025-05-18 19:06:02', 1, 1.50, 'pending', 'pending', '2025-05-18 19:06:02', NULL),
(6, 'MR202505182047332167', 4, 12, 6, 3, '2025-05-18', '2025-05-18 20:47:33', 1, 20.36, 'pending', 'completed', '2025-05-18 20:47:33', '2025-05-19 15:56:11');

-- --------------------------------------------------------

--
-- Table structure for table `fares`
--

CREATE TABLE `fares` (
  `id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `from_station_id` int(11) NOT NULL,
  `to_station_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fares`
--

INSERT INTO `fares` (`id`, `route_id`, `from_station_id`, `to_station_id`, `amount`) VALUES
(1, 1, 2, 1, 2.50),
(2, 1, 1, 5, 2.50),
(3, 1, 2, 5, 4.50),
(4, 2, 3, 1, 2.00),
(5, 2, 1, 4, 2.00),
(6, 2, 3, 4, 3.50),
(7, 3, 1, 6, 1.50),
(8, 3, 6, 7, 1.50),
(9, 3, 7, 9, 2.00),
(10, 3, 9, 3, 2.00),
(11, 3, 3, 1, 2.00),
(12, 4, 1, 10, 8.00),
(13, 4, 10, 1, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `status` enum('new','in_progress','resolved') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedules`
--

CREATE TABLE `maintenance_schedules` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `affected_routes` text DEFAULT NULL,
  `affected_stations` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_schedules`
--

INSERT INTO `maintenance_schedules` (`id`, `title`, `description`, `start_datetime`, `end_datetime`, `affected_routes`, `affected_stations`, `created_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'North Line Weekend Maintenance', 'Routine maintenance and track inspection', '2025-05-22 23:00:00', '2025-05-23 05:00:00', '1', '1,2', 1, 'scheduled', '2025-05-18 21:53:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_method` enum('credit_card','debit_card','paypal','bank_transfer','cash') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `name`, `code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'North-South Line', 'NSL', 'Main line connecting north and south areas', 'active', '2025-05-18 21:53:24', NULL),
(2, 'East-West Line', 'EWL', 'Main line connecting east and west areas', 'active', '2025-05-18 21:53:24', NULL),
(3, 'Circle Line', 'CCL', 'Circular route connecting major stations', 'active', '2025-05-18 21:53:24', NULL),
(4, 'Airport Express', 'AEL', 'Express service to airport', 'active', '2025-05-18 21:53:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `route_stations`
--

CREATE TABLE `route_stations` (
  `id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `stop_order` int(11) NOT NULL,
  `distance_from_start` decimal(10,2) NOT NULL,
  `estimated_time` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route_stations`
--

INSERT INTO `route_stations` (`id`, `route_id`, `station_id`, `stop_order`, `distance_from_start`, `estimated_time`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 0.00, 0, '2025-05-18 21:53:24', NULL),
(2, 1, 1, 2, 3.50, 7, '2025-05-18 21:53:24', NULL),
(3, 1, 5, 3, 7.50, 15, '2025-05-18 21:53:24', NULL),
(4, 2, 3, 1, 0.00, 0, '2025-05-18 21:53:24', NULL),
(5, 2, 1, 2, 3.00, 6, '2025-05-18 21:53:24', NULL),
(6, 2, 4, 3, 6.00, 12, '2025-05-18 21:53:24', NULL),
(7, 3, 1, 1, 0.00, 0, '2025-05-18 21:53:24', NULL),
(8, 3, 6, 2, 2.50, 5, '2025-05-18 21:53:24', NULL),
(9, 3, 7, 3, 5.00, 10, '2025-05-18 21:53:24', NULL),
(10, 3, 9, 4, 8.00, 16, '2025-05-18 21:53:24', NULL),
(11, 3, 3, 5, 10.50, 21, '2025-05-18 21:53:24', NULL),
(12, 3, 1, 6, 13.50, 27, '2025-05-18 21:53:24', NULL),
(13, 4, 1, 1, 0.00, 0, '2025-05-18 21:53:24', NULL),
(14, 4, 10, 2, 18.00, 35, '2025-05-18 21:53:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `train_id` int(11) NOT NULL,
  `departure_time` time NOT NULL,
  `days` varchar(20) NOT NULL,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `route_id`, `train_id`, `departure_time`, `days`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '06:00:00', '1,2,3,4,5', 'active', '2025-05-18 21:53:24', NULL),
(2, 1, 1, '06:30:00', '1,2,3,4,5', 'active', '2025-05-18 21:53:24', NULL),
(3, 1, 1, '07:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(4, 1, 1, '07:30:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(5, 1, 1, '08:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(6, 2, 3, '06:15:00', '1,2,3,4,5', 'active', '2025-05-18 21:53:24', NULL),
(7, 2, 3, '06:45:00', '1,2,3,4,5', 'active', '2025-05-18 21:53:24', NULL),
(8, 2, 3, '07:15:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(9, 2, 3, '07:45:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(10, 2, 3, '08:15:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(11, 3, 6, '06:20:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(12, 3, 6, '07:20:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(13, 3, 6, '08:20:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(14, 4, 8, '06:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(15, 4, 8, '07:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(16, 4, 8, '08:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(17, 4, 8, '09:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(18, 4, 8, '10:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(19, 4, 8, '11:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL),
(20, 4, 8, '12:00:00', '1,2,3,4,5,6,7', 'active', '2025-05-18 21:53:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedule_stations`
--

CREATE TABLE `schedule_stations` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `departure_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_stations`
--

INSERT INTO `schedule_stations` (`id`, `schedule_id`, `station_id`, `arrival_time`, `departure_time`) VALUES
(1, 1, 2, NULL, '06:00:00'),
(2, 1, 1, '06:07:00', '06:10:00'),
(3, 1, 5, '06:25:00', NULL),
(4, 2, 2, NULL, '06:30:00'),
(5, 2, 1, '06:37:00', '06:40:00'),
(6, 2, 5, '06:55:00', NULL),
(7, 6, 3, NULL, '06:15:00'),
(8, 6, 1, '06:21:00', '06:23:00'),
(9, 6, 4, '06:35:00', NULL),
(10, 11, 1, NULL, '06:20:00'),
(11, 11, 6, '06:25:00', '06:27:00'),
(12, 11, 7, '06:32:00', '06:34:00'),
(13, 11, 9, '06:40:00', '06:42:00'),
(14, 11, 3, '06:47:00', '06:49:00'),
(15, 11, 1, '06:56:00', NULL),
(16, 11, 1, NULL, '06:20:00'),
(17, 11, 6, '06:25:00', '06:27:00'),
(18, 11, 7, '06:32:00', '06:34:00'),
(19, 11, 9, '06:40:00', '06:42:00'),
(20, 11, 3, '06:47:00', '06:49:00'),
(21, 11, 1, '06:56:00', NULL),
(22, 14, 1, NULL, '06:00:00'),
(23, 14, 10, '06:35:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_description`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Metro Rail', 'Site Name', 'general', '2025-05-19 04:19:51', NULL),
(2, 'site_description', 'Book your train tickets online', 'Site Description', 'general', '2025-05-19 04:19:51', NULL),
(3, 'contact_email', 'contact@metrorail.com', 'Contact Email', 'general', '2025-05-19 04:19:51', NULL),
(4, 'contact_phone', '123-456-7890', 'Contact Phone', 'general', '2025-05-19 04:19:51', NULL),
(5, 'booking_fee', '10.00', 'Booking Fee', 'general', '2025-05-19 04:19:51', NULL),
(6, 'tax_rate', '7.5', 'Tax Rate', 'payment', '2025-05-19 04:19:51', NULL),
(7, 'enable_online_booking', 'yes', 'Enable Online Booking', 'general', '2025-05-19 04:19:51', NULL),
(8, 'maintenance_mode', 'no', 'Maintenance Mode', 'general', '2025-05-19 04:19:51', NULL),
(9, 'terms_and_conditions', 'Default Terms and Conditions', 'Terms And Conditions', 'legal', '2025-05-19 04:19:51', NULL),
(10, 'privacy_policy', 'Default Privacy Policy', 'Privacy Policy', 'legal', '2025-05-19 04:19:51', NULL),
(11, 'cancellation_policy', 'Default Cancellation Policy', 'Cancellation Policy', 'legal', '2025-05-19 04:19:51', NULL),
(12, 'currency', 'USD', 'Currency', 'payment', '2025-05-19 04:19:51', NULL),
(13, 'currency_symbol', '$', 'Currency Symbol', 'payment', '2025-05-19 04:19:51', NULL),
(14, 'date_format', 'Y-m-d', 'Date Format', 'general', '2025-05-19 04:19:51', NULL),
(15, 'time_format', 'H:i', 'Time Format', 'general', '2025-05-19 04:19:51', NULL),
(16, 'timezone', 'UTC', 'Timezone', 'general', '2025-05-19 04:19:51', NULL),
(17, 'google_analytics_id', '', 'Google Analytics Id', 'general', '2025-05-19 04:19:51', NULL),
(18, 'smtp_host', '', 'Smtp Host', 'email', '2025-05-19 04:19:51', NULL),
(19, 'smtp_port', '', 'Smtp Port', 'email', '2025-05-19 04:19:51', NULL),
(20, 'smtp_username', '', 'Smtp Username', 'email', '2025-05-19 04:19:51', NULL),
(21, 'smtp_password', '', 'Smtp Password', 'email', '2025-05-19 04:19:51', NULL),
(22, 'smtp_encryption', 'tls', 'Smtp Encryption', 'email', '2025-05-19 04:19:51', NULL),
(23, 'mail_from_address', 'noreply@metrorail.com', 'Mail From Address', 'email', '2025-05-19 04:19:51', NULL),
(24, 'mail_from_name', 'Metro Rail', 'Mail From Name', 'email', '2025-05-19 04:19:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stations`
--

CREATE TABLE `stations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `status` enum('active','inactive','under_maintenance') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stations`
--

INSERT INTO `stations` (`id`, `name`, `code`, `address`, `latitude`, `longitude`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Central Station', 'CTR', 'Downtown Central Area', 40.71280000, -74.00600000, 'active', '2025-05-18 21:53:24', NULL),
(2, 'North Terminal', 'NTR', 'Northern District', 40.73280000, -74.02600000, 'active', '2025-05-18 21:53:24', NULL),
(3, 'East Junction', 'EJN', 'Eastern Suburbs', 40.72280000, -73.98600000, 'active', '2025-05-18 21:53:24', NULL),
(4, 'West Gate', 'WGT', 'Western Business District', 40.70280000, -74.02600000, 'active', '2025-05-18 21:53:24', NULL),
(5, 'South End', 'SND', 'Southern Residential Area', 40.69280000, -74.00600000, 'active', '2025-05-18 21:53:24', NULL),
(6, 'Business District', 'BSD', 'Financial Center', 40.71310000, -74.00700000, 'active', '2025-05-18 21:53:24', NULL),
(7, 'Shopping Mall', 'SML', 'Central Shopping District', 40.71350000, -73.99000000, 'active', '2025-05-18 21:53:24', NULL),
(8, 'University', 'UNI', 'University Campus Area', 40.73500000, -74.02800000, 'active', '2025-05-18 21:53:24', NULL),
(9, 'Tech Park', 'TPK', 'Technology Park', 40.72400000, -73.97500000, 'active', '2025-05-18 21:53:24', NULL),
(10, 'Airport', 'APT', 'International Airport', 40.68950000, -74.02500000, 'active', '2025-05-18 21:53:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `passenger_name` varchar(100) NOT NULL,
  `seat_number` varchar(10) DEFAULT NULL,
  `barcode` varchar(255) NOT NULL,
  `status` enum('active','used','cancelled') NOT NULL DEFAULT 'active',
  `checked_in` tinyint(1) NOT NULL DEFAULT 0,
  `checked_in_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `booking_id`, `ticket_number`, `passenger_name`, `seat_number`, `barcode`, `status`, `checked_in`, `checked_in_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'TK202505181656519889', 'Mahmud Hasan', 'A12', 'kSreXIpKBr1fO3JWjwFe', 'cancelled', 0, NULL, '2025-05-18 16:56:51', '2025-05-18 17:01:28'),
(2, 2, 'TK202505181659339753', 'Mahmud Hasan', 'A31', 'G9DrLTOiKcftmeUXXAH1', 'cancelled', 0, NULL, '2025-05-18 16:59:33', '2025-05-18 17:01:18'),
(3, 3, 'TK202505181702184086', 'Mahmud Hasan', 'A26', 'v7jlcdhA5MGMyu21Jmt4', 'cancelled', 0, NULL, '2025-05-18 17:02:18', '2025-05-18 19:02:12'),
(4, 4, 'TK202505181838221333', 'Mahmud Hasan', 'A26', '1hfllKEDx8b5NadP8SBv', 'cancelled', 0, NULL, '2025-05-18 18:38:22', '2025-05-18 19:02:15'),
(5, 5, 'TK202505181906021464', 'Mahmud Hasan', 'A20', 'ipqrzXchh0FJknadRGsL', 'active', 0, NULL, '2025-05-18 19:06:02', NULL),
(6, 6, 'TK202505182047331232', 'Mahmud Hasan', 'A44', '72fKV85Eleu3ZfFdjysa', 'active', 0, NULL, '2025-05-18 20:47:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trains`
--

CREATE TABLE `trains` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `train_number` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL,
  `status` enum('active','inactive','under_maintenance') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trains`
--

INSERT INTO `trains` (`id`, `name`, `train_number`, `capacity`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Express 1', 'EXP001', 500, 'active', '2025-05-18 21:53:24', NULL),
(2, 'Express 2', 'EXP002', 500, 'active', '2025-05-18 21:53:24', NULL),
(3, 'City Metro 1', 'CTM001', 400, 'active', '2025-05-18 21:53:24', NULL),
(4, 'City Metro 2', 'CTM002', 400, 'active', '2025-05-18 21:53:24', NULL),
(5, 'City Metro 3', 'CTM003', 400, 'active', '2025-05-18 21:53:24', NULL),
(6, 'Suburban 1', 'SUB001', 350, 'active', '2025-05-18 21:53:24', NULL),
(7, 'Suburban 2', 'SUB002', 350, 'active', '2025-05-18 21:53:24', NULL),
(8, 'Airport Link', 'APL001', 450, 'active', '2025-05-18 21:53:24', NULL),
(11, 'City Mertro 4', 'CTCUST2', 50, 'active', '2025-05-19 03:48:49', NULL),
(12, 'City Metro 4', 'CTCST', 100, 'active', '2025-05-19 05:38:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','user') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@metrorail.com', '1234567890', '$2y$10$9wOjFbUULUQ2.hqUK.YNMeeQ71L4.ucizXrU0jOMtNVMJkAa1CIW6', 'admin', 'active', '2025-05-18 21:53:24', NULL),
(2, 'Staff User', 'staff@metrorail.com', '1234567891', '$2y$10$bUEvfgg.gYr.lSq1.NWJxeG5hB.c8CCYTFiJ0DjSf1JJQNGqrZwIi', 'staff', 'active', '2025-05-18 21:53:24', NULL),
(3, 'Regular User', 'user@metrorail.com', '1234567892', '$2y$10$bUEvfgg.gYr.lSq1.NWJxeG5hB.c8CCYTFiJ0DjSf1JJQNGqrZwIi', 'user', 'active', '2025-05-18 21:53:24', NULL),
(4, 'Mahmud Hasan', 'mahmudhs949@gmail.com', '8801863833452', '$2y$10$inP635EP8H4iMbvUdynqOe8SG4Zvbek/qT5sNZWWf8U5j3sM.cgtm', 'user', 'active', '2025-05-18 15:53:59', '2025-05-18 20:47:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `from_station_id` (`from_station_id`),
  ADD KEY `to_station_id` (`to_station_id`);

--
-- Indexes for table `fares`
--
ALTER TABLE `fares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `route_id` (`route_id`,`from_station_id`,`to_station_id`),
  ADD KEY `from_station_id` (`from_station_id`),
  ADD KEY `to_station_id` (`to_station_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `route_stations`
--
ALTER TABLE `route_stations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `route_id` (`route_id`,`stop_order`),
  ADD KEY `station_id` (`station_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `train_id` (`train_id`);

--
-- Indexes for table `schedule_stations`
--
ALTER TABLE `schedule_stations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `station_id` (`station_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `trains`
--
ALTER TABLE `trains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `train_number` (`train_number`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fares`
--
ALTER TABLE `fares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `route_stations`
--
ALTER TABLE `route_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `schedule_stations`
--
ALTER TABLE `schedule_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `stations`
--
ALTER TABLE `stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `trains`
--
ALTER TABLE `trains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`from_station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`to_station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fares`
--
ALTER TABLE `fares`
  ADD CONSTRAINT `fares_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fares_ibfk_2` FOREIGN KEY (`from_station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fares_ibfk_3` FOREIGN KEY (`to_station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  ADD CONSTRAINT `maintenance_schedules_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `route_stations`
--
ALTER TABLE `route_stations`
  ADD CONSTRAINT `route_stations_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `route_stations_ibfk_2` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`train_id`) REFERENCES `trains` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_stations`
--
ALTER TABLE `schedule_stations`
  ADD CONSTRAINT `schedule_stations_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_stations_ibfk_2` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
