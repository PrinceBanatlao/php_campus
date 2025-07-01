-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2025 at 04:28 AM
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
-- Database: `campusfinder`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `created_at`) VALUES
(1, 1, 'Updated item 6 status to approved', '2025-06-14 11:32:41'),
(2, 1, 'Updated item 6 status to approved', '2025-06-14 11:36:46'),
(3, 1, 'Updated item 5 status to matched', '2025-06-14 11:36:56'),
(4, 1, 'Updated item 1 status to approved', '2025-06-14 11:37:17'),
(5, 1, 'Updated item 7 status to approved', '2025-06-14 13:04:24'),
(6, 1, 'Updated item 7 status to matched', '2025-06-14 13:05:13'),
(7, 1, 'Updated item 7 status to matched', '2025-06-14 13:13:19'),
(8, 1, 'Updated item 10 status to approved', '2025-06-14 13:15:00'),
(9, 1, 'Updated item 14 status to approved', '2025-06-14 13:17:49'),
(10, 1, 'Updated item 13 status to rejected', '2025-06-14 13:17:53'),
(11, 1, 'Updated item 12 status to deleted', '2025-06-14 13:17:59'),
(12, 1, 'Updated item 12 status to deleted', '2025-06-14 13:18:06'),
(13, 1, 'Updated item 13 status to approved', '2025-06-14 13:18:12'),
(14, 1, 'Updated item 13 status to rejected', '2025-06-14 13:18:17'),
(15, 1, 'Updated item 15 status to rejected', '2025-06-14 13:42:22'),
(16, 1, 'Updated item 15 status to matched', '2025-06-14 13:44:07'),
(17, 1, 'Updated item 18 status to approved', '2025-06-14 13:46:22'),
(18, 1, 'Updated item 17 status to rejected', '2025-06-14 13:46:27'),
(19, 1, 'Updated item 17 status to rejected', '2025-06-14 13:54:29'),
(20, 1, 'Updated item 19 status to rejected', '2025-06-14 13:56:27'),
(21, 1, 'Updated item 20 status to rejected', '2025-06-14 15:19:53'),
(22, 1, 'Updated item 20 status to rejected', '2025-06-14 15:20:00'),
(23, 1, 'Updated item 21 status to deleted', '2025-06-14 16:34:06'),
(24, 1, 'Updated item 21 status to deleted', '2025-06-14 16:34:11'),
(25, 1, 'Deleted user 3', '2025-06-14 16:36:32'),
(26, 1, 'Updated item 16 status to rejected', '2025-06-14 17:04:03'),
(27, 1, 'Updated item 11 status to rejected', '2025-06-14 17:04:16'),
(28, 1, 'Updated item 9 status to rejected', '2025-06-14 17:04:22'),
(29, 1, 'Updated item 8 status to rejected', '2025-06-14 17:04:28'),
(30, 1, 'Updated item 4 status to rejected', '2025-06-14 17:04:33'),
(31, 1, 'Updated item 3 status to rejected', '2025-06-14 17:04:38'),
(32, 1, 'Updated item 2 status to rejected', '2025-06-14 17:04:49'),
(33, 1, 'Deleted item 18', '2025-06-14 17:05:05'),
(34, 1, 'Deleted item 1', '2025-06-14 17:19:56'),
(35, 1, 'Deleted item 15', '2025-06-14 17:20:04'),
(36, 1, 'Deleted item 14', '2025-06-14 17:20:12'),
(37, 1, 'Deleted item 10', '2025-06-14 17:20:19'),
(38, 1, 'Deleted item 7', '2025-06-14 17:20:25'),
(39, 1, 'Deleted item 6', '2025-06-14 17:20:32'),
(40, 1, 'Deleted item 5', '2025-06-14 17:20:37'),
(41, 1, 'Updated item 23 status to approved', '2025-06-14 17:40:09'),
(42, 1, 'Deleted item 23', '2025-06-14 17:43:40'),
(43, 1, 'Deleted user 2', '2025-06-14 17:50:28'),
(44, 1, 'Deleted user 4', '2025-06-14 17:50:52'),
(45, 1, 'Updated item 25 status to approved', '2025-06-14 18:13:04'),
(46, 1, 'Updated item 26 status to approved', '2025-06-14 18:13:09'),
(47, 1, 'Updated item 27 status to approved', '2025-06-15 03:45:30'),
(48, 1, 'Updated item 30 status to approved', '2025-06-15 04:34:20'),
(49, 1, 'Updated item 29 status to approved', '2025-06-15 04:34:23'),
(50, 1, 'Updated item 28 status to approved', '2025-06-15 04:34:26'),
(51, 1, 'Updated item 31 status to matched', '2025-06-15 06:28:58'),
(52, 1, 'Updated item 33 status to matched', '2025-06-15 06:47:27'),
(53, 1, 'Updated item 32 status to approved', '2025-06-15 06:47:32'),
(54, 1, 'Updated item 34 status to matched', '2025-06-15 10:02:09'),
(55, 1, 'Updated item 35 status to approved', '2025-06-15 10:04:01'),
(56, 1, 'Updated item 36 status to approved', '2025-06-15 13:02:38'),
(57, 1, 'Updated item 37 status to matched', '2025-06-15 13:04:38'),
(58, 1, 'Updated item 38 status to rejected', '2025-06-15 13:15:14'),
(59, 1, 'Deleted item 32', '2025-06-15 13:15:25'),
(60, 1, 'Deleted user 9', '2025-06-15 13:16:09'),
(61, 1, 'Deleted user 12', '2025-06-15 13:16:15'),
(62, 1, 'Updated item 40 status to approved', '2025-06-15 15:44:07'),
(63, 1, 'Updated item 41 status to rejected', '2025-06-15 15:44:17'),
(64, 1, 'Updated item 40 status to matched', '2025-06-15 15:57:13'),
(65, 1, 'Updated item 27 status to matched', '2025-06-16 02:44:03'),
(66, 1, 'Deleted item 40', '2025-06-16 04:32:37'),
(67, 1, 'Deleted item 37', '2025-06-16 04:32:42'),
(68, 1, 'Deleted item 34', '2025-06-16 04:32:45'),
(69, 1, 'Deleted user 6', '2025-06-16 04:33:07'),
(70, 1, 'Deleted user 15', '2025-06-16 04:33:11'),
(71, 1, 'Deleted user 14', '2025-06-16 04:33:16'),
(72, 1, 'Deleted user 11', '2025-06-16 04:33:21'),
(73, 1, 'Deleted user 10', '2025-06-16 04:33:28'),
(74, 1, 'Updated item 43 status to approved', '2025-06-16 08:05:35'),
(75, 1, 'Updated item 43 status to matched', '2025-06-16 08:17:21'),
(76, 1, 'Deleted item 43', '2025-06-17 02:52:39'),
(77, 1, 'Updated item 46 status to approved', '2025-06-17 03:12:09'),
(78, 1, 'Updated item 44 status to approved', '2025-06-17 03:12:13'),
(79, 1, 'Updated item 47 status to matched', '2025-06-17 03:12:22'),
(80, 1, 'Updated item 48 status to approved', '2025-06-17 03:13:03'),
(81, 1, 'Updated item 45 status to matched', '2025-06-17 03:13:11'),
(82, 1, 'Updated item 46 status to matched', '2025-06-17 10:14:34'),
(83, 1, 'Updated item 52 status to approved', '2025-06-17 10:15:13'),
(84, 1, 'Updated item 49 status to approved', '2025-06-17 10:15:16'),
(85, 1, 'Updated item 51 status to approved', '2025-06-17 10:17:31'),
(86, 1, 'Updated item 53 status to approved', '2025-06-17 10:21:04'),
(87, 1, 'Updated item 53 status to matched', '2025-06-25 09:52:57'),
(88, 1, 'Updated item 50 status to approved', '2025-06-25 09:53:32'),
(89, 1, 'Deleted user 17', '2025-06-25 09:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `feedback_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `feedback_text`, `created_at`) VALUES
(3, NULL, ' sana huwag magisa dahil magagalit si lord', '2025-06-17 08:28:11');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('lost','found') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','matched','approved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `user_id`, `type`, `name`, `description`, `location`, `date`, `image`, `status`, `created_at`) VALUES
(8, 1, 'lost', 'ggwp', 'sdvsejfisgfoisng', 'dasdasd', '2025-06-08', 'Uploads/684d7360cb6ab_Screenshot (12).png', '', '2025-06-14 13:04:32'),
(9, 1, 'lost', 'ggwp', 'sdvsejfisgfoisng', 'dasdasd', '2025-06-08', 'Uploads/684d73919bdf8_Screenshot (12).png', '', '2025-06-14 13:05:21'),
(11, 1, 'lost', 'fsdfsd', 'sfsdfsd', 'sdfsdfsd', '2025-06-18', 'Uploads/684d75e263544_Screenshot (15).png', '', '2025-06-14 13:15:14'),
(44, 5, 'found', 'notebook', 'notebook subject cc225, color black.', 'room 205, CCST BUILDING', '2025-06-26', 'Uploads/6850d951bb889_black notebook.jpg', 'approved', '2025-06-17 02:56:17'),
(45, 5, 'lost', 'Handkerchief', 'color blue, have a design, big', 'around cafeteria', '2025-06-30', 'Uploads/6850dafe626ae_blue panyo.jpg', 'matched', '2025-06-17 03:02:04'),
(46, 5, 'found', 'Umbrella', 'color yellow, AVON brand', 'Library', '2025-06-28', 'Uploads/6850db5a0a9b9_yellow umbrella.jpg', 'matched', '2025-06-17 03:04:58'),
(49, 5, 'found', 'Wallet', 'color brown, have money inside', 'Library', '2025-06-08', 'Uploads/6851260b00525_brown panyo.jpg', 'approved', '2025-06-17 08:23:39'),
(50, 5, 'lost', 'Key', 'Key motor of honda click, have a keychain of sticth and bear', 'around motor parking', '2025-06-23', NULL, 'approved', '2025-06-17 08:25:23'),
(51, 20, 'lost', 'payong', 'color yellow, the brand is AVON', 'around cafeteria', '2025-06-26', NULL, 'approved', '2025-06-17 10:04:41'),
(52, 20, 'found', 'dasdas', 'asdasd', 'asda', '2025-06-10', 'Uploads/68513fc667de4_IMG_20250118_140514_513.jpg', 'approved', '2025-06-17 10:13:26'),
(53, 20, 'lost', 'fsdfds', 'sdfsd', 'sdfs', '2025-06-17', NULL, 'matched', '2025-06-17 10:20:43');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `created_at`) VALUES
(61, 5, 'Your report (Umbrella) has been approved! Please deliver it to the CCST building student council 2nd floor.', '2025-06-17 11:12:09'),
(62, 5, 'Your report (notebook) has been approved! Please deliver it to the CCST building student council 2nd floor.', '2025-06-17 11:12:13'),
(65, 5, 'An item matching your report (Handkerchief) has been found! Please go to the CCST building student council 2nd floor to claim it.', '2025-06-17 11:13:11'),
(67, 20, 'Your report (dasdas) has been approved! Please deliver it to the CCST building student council 2nd floor.', '2025-06-17 18:15:13'),
(70, 20, 'Your report (fsdfds) has been approved! Please claim it to the CCST building student council 2nd floor.', '2025-06-17 18:21:04'),
(71, 20, 'An item matching your report (fsdfds) has been found! Please go to the CCST building student council 2nd floor to claim it.', '2025-06-25 17:52:57'),
(72, 5, 'Your report (Key) has been approved! Please claim it to the CCST building student council 2nd floor.', '2025-06-25 17:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin12', 'Admin', '', 'User', 'admin12@gmail.com', '$2y$10$F6Zd781ZUrAKSuZX2jVu9eNH10oMjZo/zWtXCwR5woP3Fi97JRRSG', 'admin', '2025-06-14 11:49:35'),
(5, 'banatlao2D', 'xXUradaXx', 'nuevo', 'banatlao', 'princebanatlao04@gmail.com', '$2y$10$9dMTatx6BKi0oeC8KSwBDOCWLwOR6GI8K.vI1d8.3cH0oJlxf9c1O', 'user', '2025-06-14 17:52:06'),
(18, 'prince', 'princee', '', 'banatlaoo', 'princebanatlao033@gmail.com', '$2y$10$Vhur06NHsJX/Pk86tKZng.VFJwGm1hVX0wIxO83SLtB6.pd12/HEi', 'user', '2025-06-17 09:56:49'),
(19, '123jercel#$%&amp;', 'jercellll', '', 'oca', 'jerceloca34@gmail.com', '$2y$10$.N74ADFw0keoEUxiXh5od.thPHO62w/OTJIXL6N9UAKwbnwWoQu9i', 'user', '2025-06-17 10:00:07'),
(20, 'qwerty', 'sample', '', 'sampleee', 'sample@gmail.com', '$2y$10$wWc5b5EMAHa8N7z97LMbJubxoEhSB3t.qOXq.NHZusZyMcbzPIaVq', 'user', '2025-06-17 10:02:19');

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
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
