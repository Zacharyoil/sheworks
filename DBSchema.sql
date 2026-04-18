-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 18, 2026 at 05:35 PM
-- Server version: 10.11.14-MariaDB-0+deb12u2
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sheworks`
--

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `industry` varchar(120) DEFAULT NULL,
  `hq_country` varchar(200) DEFAULT NULL,
  `hq_city` varchar(120) DEFAULT NULL,
  `website` varchar(300) DEFAULT NULL,
  `logo_url` varchar(300) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `display_name` varchar(400) NOT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(200) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `location_id` int(10) UNSIGNED DEFAULT NULL,
  `job_title` varchar(200) NOT NULL,
  `field` varchar(150) NOT NULL,
  `gender` enum('female','male','non_binary','prefer_not_to_say') NOT NULL,
  `employment_status` enum('current','former') DEFAULT 'current',
  `years_at_company` tinyint(3) UNSIGNED DEFAULT 0,
  `rating_overall` tinyint(3) UNSIGNED NOT NULL CHECK (`rating_overall` between 1 and 5),
  `rating_pay_equity` tinyint(3) UNSIGNED NOT NULL CHECK (`rating_pay_equity` between 1 and 5),
  `rating_gap_perceived` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `rating_culture` tinyint(3) UNSIGNED NOT NULL CHECK (`rating_culture` between 1 and 5),
  `rating_growth` tinyint(3) UNSIGNED NOT NULL CHECK (`rating_growth` between 1 and 5),
  `rating_flexibility` tinyint(3) UNSIGNED NOT NULL CHECK (`rating_flexibility` between 1 and 5),
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL,
  `job_title` varchar(200) NOT NULL,
  `field` varchar(150) NOT NULL,
  `gender` enum('female','male','non_binary','prefer_not_to_say') NOT NULL,
  `salary` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `country` varchar(100) NOT NULL,
  `years_experience` tinyint(3) UNSIGNED DEFAULT 0,
  `education_level` varchar(20) DEFAULT 'bachelors',
  `is_first_job` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_gender` (`gender`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_gender` (`gender`),
  ADD KEY `idx_field` (`field`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salaries`
--
ALTER TABLE `salaries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salaries`
--
ALTER TABLE `salaries`
  ADD CONSTRAINT `salaries_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
