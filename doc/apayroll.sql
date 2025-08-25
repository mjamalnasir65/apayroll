-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 25, 2025 at 03:24 PM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `apayroll`
--

-- --------------------------------------------------------

--
-- Table structure for table `employer`
--

DROP TABLE IF EXISTS `employer`;
CREATE TABLE IF NOT EXISTS `employer` (
  `employer_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `employer_roc` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `sector` enum('construction','manufacturing','services','plantation','agriculture') NOT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `access_level` enum('free','premium') DEFAULT 'free',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`employer_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_employer_roc` (`employer_roc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salarylog`
--

DROP TABLE IF EXISTS `salarylog`;
CREATE TABLE IF NOT EXISTS `salarylog` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `worker_id` int NOT NULL,
  `employer_id` int DEFAULT NULL,
  `month` date NOT NULL,
  `expected_amount` decimal(10,2) NOT NULL,
  `receipt_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','received','disputed') NOT NULL DEFAULT 'pending',
  `employer_note` text,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `worker_id` (`worker_id`),
  KEY `employer_id` (`employer_id`),
  KEY `idx_salary_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptionpayment`
--

DROP TABLE IF EXISTS `subscriptionpayment`;
CREATE TABLE IF NOT EXISTS `subscriptionpayment` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `worker_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `wallet_ref` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `worker_id` (`worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','worker','employer') NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `role`, `status`, `created_at`, `last_login`) VALUES
(1, 'bangla@email.com', '$2y$10$bUs3nLY/Hsu.p9Px2ODH6.1Uk.lpWWs98z/qttRFMkXFnN2V2dqu.', 'worker', 'active', '2025-08-25 09:18:42', '2025-08-25 09:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `worker`
--

DROP TABLE IF EXISTS `worker`;
CREATE TABLE IF NOT EXISTS `worker` (
  `worker_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `passport_no` varchar(50) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `wallet_id` varchar(100) NOT NULL,
  `wallet_brand` varchar(50) NOT NULL,
  `expiry_date` date NOT NULL,
  `employer_name` varchar(100) NOT NULL,
  `employer_roc` varchar(50) NOT NULL,
  `sector` enum('construction','manufacturing','services','plantation','agriculture') NOT NULL,
  `contract_start` date NOT NULL,
  `copy_passport` varchar(255) NOT NULL,
  `copy_permit` varchar(255) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `copy_contract` varchar(255) NOT NULL,
  `monthly_salary` decimal(10,2) NOT NULL,
  `subscription_status` enum('active','expired','pending') NOT NULL DEFAULT 'pending',
  `subscription_expiry` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`worker_id`),
  UNIQUE KEY `passport_no` (`passport_no`),
  KEY `user_id` (`user_id`),
  KEY `idx_worker_passport` (`passport_no`),
  KEY `idx_subscription_status` (`subscription_status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `worker`
--

INSERT INTO `worker` (`worker_id`, `user_id`, `full_name`, `passport_no`, `nationality`, `dob`, `gender`, `mobile_number`, `address`, `wallet_id`, `wallet_brand`, `expiry_date`, `employer_name`, `employer_roc`, `sector`, `contract_start`, `copy_passport`, `copy_permit`, `photo`, `copy_contract`, `monthly_salary`, `subscription_status`, `subscription_expiry`, `created_at`) VALUES
(1, 1, 'bangla', 'A1234567', 'Bangladesh', '2000-04-04', 'male', '01112312345', 'address address', '123412341234', 'm1pay', '2026-08-26', 'employer sdn bhd', '1234567-A', 'construction', '2025-01-01', '/worker/uploads/68ac3bc182365.png', '/worker/uploads/68ac3bc1828c8.jpg', '/worker/uploads/68ac3bc182b3f.jpg', '/worker/uploads/68ac3bc182d0e.pdf', 1700.00, 'pending', NULL, '2025-08-25 09:18:42');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employer`
--
ALTER TABLE `employer`
  ADD CONSTRAINT `employer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `salarylog`
--
ALTER TABLE `salarylog`
  ADD CONSTRAINT `salarylog_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `worker` (`worker_id`),
  ADD CONSTRAINT `salarylog_ibfk_2` FOREIGN KEY (`employer_id`) REFERENCES `employer` (`employer_id`);

--
-- Constraints for table `subscriptionpayment`
--
ALTER TABLE `subscriptionpayment`
  ADD CONSTRAINT `subscriptionpayment_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `worker` (`worker_id`);

--
-- Constraints for table `worker`
--
ALTER TABLE `worker`
  ADD CONSTRAINT `worker_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
