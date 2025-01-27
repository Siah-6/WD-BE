-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 27, 2025 at 10:24 PM
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
-- Database: `corememories`
--

-- --------------------------------------------------------

--
-- Table structure for table `islandcontents`
--

CREATE TABLE `islandcontents` (
  `islandContentID` int(4) NOT NULL,
  `islandOfPersonalityID` int(4) NOT NULL,
  `image` varchar(50) DEFAULT NULL,
  `content` varchar(300) NOT NULL,
  `color` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `islandcontents`
--

INSERT INTO `islandcontents` (`islandContentID`, `islandOfPersonalityID`, `image`, `content`, `color`) VALUES
(1, 1, 'cat1.jpg', '', NULL),
(2, 2, 'ml2.jpg', '', NULL),
(3, 2, 'ml1.jpg', '', NULL),
(4, 2, 'ml3.jpg', '', NULL),
(5, 2, 'ml4.jpg', '', NULL),
(6, 4, 'anime1.jpg', '', NULL),
(7, 4, 'anime2.jpg', '', NULL),
(8, 4, 'anime3.jpg', '', NULL),
(9, 4, 'anime4.jpg', '', NULL),
(10, 4, 'anime5.jpg', '', NULL),
(11, 2, 'ml5.jpg', '', NULL),
(13, 1, 'cat2.jpg', '', NULL),
(14, 1, 'cat3.jpg', '', NULL),
(15, 3, 'pic1.jpg', '', NULL),
(16, 3, 'pic2.jpg', '', NULL),
(17, 3, 'pic3.jpg', '', NULL),
(18, 3, 'pic4.jpg', '', NULL),
(19, 1, 'cat4.jpg', '', NULL),
(20, 2, 'ml6.jpg', '', NULL),
(21, 3, 'pic5.jpg', '', NULL),
(22, 3, 'pic6.jpg', '', NULL),
(23, 1, 'cat5.jpg', '', NULL),
(24, 1, 'cat6.jpg', '', NULL),
(25, 2, 'ml7.jpg', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `islandsofpersonality`
--

CREATE TABLE `islandsofpersonality` (
  `islandOfPersonalityID` int(4) NOT NULL,
  `name` varchar(40) NOT NULL,
  `shortDescription` varchar(300) DEFAULT NULL,
  `longDescription` varchar(900) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL,
  `image` varchar(50) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `islandsofpersonality`
--

INSERT INTO `islandsofpersonality` (`islandOfPersonalityID`, `name`, `shortDescription`, `longDescription`, `color`, `image`, `status`) VALUES
(1, 'Meowy the Cat', '', NULL, NULL, 'catlogo.jpg', NULL),
(2, 'MLBB', '', NULL, NULL, 'gamelogo.jpg', NULL),
(3, 'Random Me', '', NULL, NULL, 'piclogo.jpg', NULL),
(4, 'Best Anime', '', NULL, NULL, 'animelogo.jpg', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `islandcontents`
--
ALTER TABLE `islandcontents`
  ADD PRIMARY KEY (`islandContentID`);

--
-- Indexes for table `islandsofpersonality`
--
ALTER TABLE `islandsofpersonality`
  ADD PRIMARY KEY (`islandOfPersonalityID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `islandcontents`
--
ALTER TABLE `islandcontents`
  MODIFY `islandContentID` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `islandsofpersonality`
--
ALTER TABLE `islandsofpersonality`
  MODIFY `islandOfPersonalityID` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
