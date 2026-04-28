-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 03, 2026 at 11:05 AM
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
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `publish_charge` decimal(10,2) DEFAULT '0.00',
  `admin_commission` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image1` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `user_id`, `book_name`, `category`, `author_name`, `cover_image`, `description`, `price`, `quantity`, `publish_charge`, `admin_commission`, `status`, `created_at`, `image1`, `image2`, `image3`, `updated_at`) VALUES
(8, 4, 'EBOOK COVER DESIGN', 'Education', 'Charles G Koontiz', 'uploads/1772272695_69a2bc3708537_book1.jpg', 'EBook is reference for the online publishing book guidance', '350.00', 6, '0.00', '0.00', 'approved', '2026-02-28 09:58:15', 'uploads/1772341015_69a3c717288e5_index3.jpg', 'uploads/1772341015_69a3c71728c81_first2.png', 'uploads/1772341015_69a3c7172900a_intro.png', '2026-03-01 05:05:24'),
(9, 4, 'THE EDGE OF UNKNOWN', 'Fiction', 'Andrew Hamburge', 'uploads/1772341015_69a3c717282b8_book3.jpg', 'Beyond the mapped horizons lies a world of untamed peril and breathtaking wonder. The Edge of the Unknown is an epic journey into the heart of the wild, where one explorer must risk everything to uncover a secret buried by time itself.', '200.00', 7, '0.00', '0.00', 'approved', '2026-03-01 04:56:55', 'uploads/1772341015_69a3c717288e5_index3.jpg', 'uploads/1772341015_69a3c71728c81_first2.png', 'uploads/1772341015_69a3c7172900a_intro.png', '2026-03-01 05:05:28'),
(10, 9, 'THE LITTLE MERMAID', 'Comics', 'Usha Baliyan', 'uploads\\the_little.jpeg', 'Discover a sparkling underwater kingdom in this timeless story of wonder, sacrifice, and the dreams that live beneath the deep blue sea.', '100.00', 8, '0.00', '0.00', 'approved', '2026-03-01 05:11:54', 'uploads/1772341914_69a3ca9ad5585_index2.jpg', 'uploads/1772341914_69a3ca9ad5861_first2.png', 'uploads/1772341914_69a3ca9ad5d69_intro.png', '2026-03-01 05:27:08'),
(11, 9, 'THE LONG RODE WE TRAVEL', 'Adventure', 'Jeanne Williams', 'uploads\\rode_we.jpg', 'A moving chronicle of the paths we choose and the lessons learned in the rearview mirror. This is a story of resilience, discovery, and the courage it takes to keep moving forward.', '300.00', 6, '0.00', '0.00', 'approved', '2026-03-01 05:18:34', 'uploads/1772342314_69a3cc2a5f44a_index2.jpg', 'uploads/1772342314_69a3cc2a5f731_first2.png', 'uploads/1772342314_69a3cc2a5f9e5_intro.png', '2026-03-01 05:27:14'),
(12, 9, 'THE C++', 'Technology', 'Bjarne Stroustrup', 'uploads/1772343096_69a3cf38cca1f_book8.jpg', 'C++: MASTERING THE CORE > A comprehensive guide to high-performance programming, from foundational syntax to advanced memory management.', '120.00', 4, '0.00', '0.00', 'approved', '2026-03-01 05:31:36', 'uploads/1772343096_69a3cf38ccd09_index2.jpg', 'uploads/1772343096_69a3cf38ccf7a_first2.png', 'uploads/1772343096_69a3cf38cd423_intro.png', '2026-03-01 05:34:36'),
(13, 4, 'THE JOY OF BEING ALONE', 'Biography', 'Linda Hawk', 'uploads/1772343241_69a3cfc9a5d73_book10.png', 'A quiet chronicle of the strength found in silence and the beauty of self-discovery. From the noise of the world to the peace of oneâ€™s own company, this is a journey into the heart of independence.', '220.00', 6, '0.00', '0.00', 'approved', '2026-03-01 05:34:01', 'uploads/1772343241_69a3cfc9a6364_index2.jpg', 'uploads/1772343241_69a3cfc9a66a9_first2.png', 'uploads/1772343241_69a3cfc9a69eb_intro.png', '2026-03-01 05:34:32'),
(14, 10, 'WHISPER', 'Romance', 'JENI CONRAD', 'uploads/1772343658_69a3d16aae339_book13.jpg', 'A soft confession in the dark, where the loudest truths are the ones barely spoken. In a world full of noise, their love was the only secret worth keeping.', '260.00', 5, '0.00', '0.00', 'approved', '2026-03-01 05:40:58', 'uploads/1772343658_69a3d16aae6bc_index2.jpg', 'uploads/1772343658_69a3d16aae9e1_first2.png', 'uploads/1772343658_69a3d16aaed8f_intro.png', '2026-03-01 05:48:31'),
(15, 10, 'THE SECREAT OF DHAMPIP', 'Fiction', 'RENEE JOINER', 'uploads/1772343955_69a3d2938a0f5_book16.jpg', 'Born of two worlds but belonging to neither, a hidden legacy awakens in the shadows. To protect the living, one must first confront the ancient blood that pulses within.', '400.00', 4, '0.00', '0.00', 'approved', '2026-03-01 05:45:55', 'uploads/1772343955_69a3d2938a47b_index2.jpg', 'uploads/1772343955_69a3d2938a767_first2.png', 'uploads/1772343955_69a3d2938aa3c_intro.png', '2026-03-01 05:48:36'),
(16, 10, 'THE JUNGLE BOOK', 'Comics', 'Rudyard kipling', 'uploads/1772344086_69a3d3167f1aa_book19.jpg', 'A timeless journey through the deep, green heart of the wild, where the law of the jungle is the only rule. Follow Mowgli as he learns the strength of the pack and the courage of a true survivor.', '200.00', 9, '0.00', '0.00', 'approved', '2026-03-01 05:48:06', 'uploads/1772344086_69a3d3167f4fe_index2.jpg', 'uploads/1772344086_69a3d3167f7ed_first2.png', 'uploads/1772344086_69a3d3167fae0_intro.png', '2026-03-01 05:48:40');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
