-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 03, 2026 at 11:04 AM
-- Server version: 5.7.40
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `books_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `otp` varchar(6) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  `last_earnings_view` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_account_view` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `role`, `otp`, `otp_expires`, `last_earnings_view`, `last_account_view`) VALUES
(3, 'Admin', 'admin123@gmail.com', '9999999999', 'Surat', '$2y$10$M9nbZjs61d0h.Da4FQpFheraGUSfTE7cl4dKWWrVsjPf0VGJ6O8/K', 'admin', NULL, NULL, '2026-01-01 22:08:59', '2026-01-01 22:20:23'),
(4, 'jay', 'jay1012@gmail.com', '1234567890', 'Vapi', '$2y$10$2EBiB8IAPEGnMtPUj5BD5.XD9s1RPGefuJHIhoXc0iOLRcKAhMkOG', 'user', NULL, NULL, '2026-02-27 16:29:58', '2026-03-02 10:56:19'),
(9, 'Deep_10', 'deepprajapati1012@gmail.com', '6353947624', 'Jahangirpura , Surta , Gujarat', '$2y$10$YVKHLbgEPWAfdbhKC46VneqhdRfol6sG0wSsqbkMmOPVH8aLQMd8u', 'user', NULL, NULL, '2026-03-01 10:39:14', '2026-03-01 11:05:43'),
(10, 'Pooja', 'konteh12102001@gmail.com', '8866156481', 'Pinjrat , Surat', '$2y$10$4qNNW8/X3hzVF8wK6AiWT.JJOJZrhQbfTuneXhbKc4OggznYgwozW', 'user', NULL, NULL, '2026-03-01 11:08:11', '2026-03-02 11:50:34');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
