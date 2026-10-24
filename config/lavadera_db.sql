-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 11:04 AM
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
-- Database: `lavadera_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `laundry_category` enum('clothes_pillowcases','blankets_comforters_bedcovers') DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `pricing_unit` enum('per_7kg','per_5kg','per_kg') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `laundry_category`, `price`, `pricing_unit`, `description`, `is_active`, `created_at`) VALUES
(1, 'Wash, Dry and Fold', 'clothes_pillowcases', 190.00, 'per_7kg', 'Clothes and thin pillowcases', 1, '2026-10-24 07:03:22'),
(2, 'Wash, Dry and Fold', 'blankets_comforters_bedcovers', 190.00, 'per_5kg', 'Blankets, comforters, and thin bed covers', 1, '2026-10-24 07:03:22'),
(3, 'Wash and Dry', 'clothes_pillowcases', 160.00, 'per_7kg', 'Clothes and thin pillowcases', 1, '2026-10-24 07:03:22'),
(4, 'Wash and Dry', 'blankets_comforters_bedcovers', 160.00, 'per_5kg', 'Blankets, comforters, and thin bed covers', 1, '2026-10-24 07:03:22'),
(5, 'Dry Only', NULL, 25.00, 'per_kg', 'Clothes, pillowcases, blankets, and bed covers', 1, '2026-10-24 07:03:22');

-- --------------------------------------------------------

--
-- Table structure for table `service_addons`
--

CREATE TABLE `service_addons` (
  `addon_id` int(11) NOT NULL,
  `addon_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_addons`
--

INSERT INTO `service_addons` (`addon_id`, `addon_name`, `price`, `is_active`, `created_at`) VALUES
(1, 'Extra Dry', 25.00, 1, '2026-10-24 07:03:44'),
(2, 'Extra Ariel', 20.00, 1, '2026-10-24 07:03:44'),
(3, 'Extra Downy', 20.00, 1, '2026-10-24 07:03:44'),
(4, 'Pickup and Delivery', 30.00, 1, '2026-10-24 07:03:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(11) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `login_type` enum('local','google') NOT NULL DEFAULT 'local',
  `role` enum('admin','staff','customer') NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `google_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `login_type`, `role`, `is_active`, `created_at`, `google_id`) VALUES
(1, 'Angelo Umandal', 'angelo@gmail.com', '09952142080', '$2y$10$EdsnGbBvkl8HctwVEu2oAutev9B5rjTXAZ/lcKJaWflDmN9xS.oR6', 'local', 'customer', 1, '2026-10-23 11:58:50', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_addons`
--
ALTER TABLE `service_addons`
  ADD PRIMARY KEY (`addon_id`),
  ADD UNIQUE KEY `addon_name` (`addon_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `service_addons`
--
ALTER TABLE `service_addons`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
