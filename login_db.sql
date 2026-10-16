-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2026 at 03:06 AM
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
-- Database: `login_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'student',
  `fullname` varchar(100) DEFAULT '',
  `nickname` varchar(50) DEFAULT '',
  `address` varchar(255) DEFAULT '',
  `preferred_course_2` varchar(100) DEFAULT '',
  `preferred_univ_2` varchar(100) DEFAULT '',
  `favorite_subject` varchar(100) DEFAULT '',
  `dream_job` varchar(100) DEFAULT '',
  `greatest_fear` varchar(255) DEFAULT '',
  `least_favorite_subject` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `fullname`, `nickname`, `address`, `preferred_course_2`, `preferred_univ_2`, `favorite_subject`, `dream_job`, `greatest_fear`, `least_favorite_subject`) VALUES
(5, 'charm@gmail.com', '$2y$10$pACe5N77A0eTNbYzRsfcaeXV0rIxga7Mop9T2zdeuflBWg.10l8dW', 'student', 'Charm', 'asj', 'asj', 'fha', 'kfah', 'klashfio', 'l;khfiewq', 'fkjs', 'ihrahkj'),
(8, 'main_admin@gmail.com', '$2y$10$SQCkTtMWEvs6knRLL2ve1e/cJ32nTk7lpBbnWtnn2jhScfzPJtTYe', 'admin', '', '', '', '', '', '', '', '', '');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
