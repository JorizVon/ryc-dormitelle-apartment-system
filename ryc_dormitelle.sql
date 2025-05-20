-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 01:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ryc_dormitelle`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

CREATE TABLE `access_logs` (
  `access_ID` mediumint(4) NOT NULL,
  `unit_no` varchar(10) NOT NULL,
  `tenant_ID` varchar(12) NOT NULL,
  `card_no` varchar(10) NOT NULL,
  `date_and_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `access_status` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`access_ID`, `unit_no`, `tenant_ID`, `card_no`, `date_and_time`, `access_status`) VALUES
(1, 'A-001', '20250424A004', '44739242', '2025-05-10 18:45:32', 'Success'),
(2, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(3, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(4, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(5, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(6, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(7, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(8, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(9, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(10, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(11, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(12, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(13, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(14, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(15, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(16, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(17, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(18, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(19, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(20, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(21, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(22, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(23, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(24, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(25, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(26, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(27, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(28, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(29, 'A-001', '20250421A001', '44739242', '2025-05-10 18:45:32', 'Success'),
(30, '', '20250421A001', '44739242', '2025-05-10 18:55:46', 'Success'),
(31, '', '20250421A001', '44739242', '2025-05-10 18:56:00', 'Success'),
(32, '', '20250421A001', '44739242', '2025-05-10 19:02:05', 'Success'),
(33, '', '20250421A001', '44739242', '2025-05-10 19:02:19', 'Success'),
(34, '', '20250421A001', '44739242', '2025-05-10 19:03:57', 'Success'),
(35, '', '20250421A001', '44739242', '2025-05-10 19:04:24', 'Success'),
(36, '', '20250421A001', '44739242', '2025-05-10 19:05:29', 'Success'),
(37, '', '20250421A001', '44739242', '2025-05-10 19:05:55', 'Success'),
(38, '', '20250421A001', '44739242', '2025-05-10 19:06:16', 'Success'),
(39, '', '20250421A001', '44739242', '2025-05-10 19:06:48', 'Success'),
(40, '', '20250421A001', '44739242', '2025-05-10 19:08:03', 'Success'),
(41, '', '20250421A001', '44739242', '2025-05-10 19:08:20', 'Success'),
(42, '', '20250421A001', '44739242', '2025-05-10 19:09:06', 'Success'),
(43, '', '20250421A001', '44739242', '2025-05-10 19:09:41', 'Success'),
(44, '', '20250421A001', '44739242', '2025-05-10 19:10:45', 'Success'),
(45, '', '20250421A001', '44739242', '2025-05-10 19:11:42', 'Success'),
(46, '', '20250421A001', '44739242', '2025-05-10 19:11:52', 'Success'),
(47, '', '20250421A001', '44739242', '2025-05-10 19:14:57', 'Success'),
(48, '', '20250421A001', '44739242', '2025-05-10 19:18:44', 'Success'),
(49, '', '20250421A001', '44739242', '2025-05-10 19:19:17', 'Success'),
(50, '', '20250421A001', '44739242', '2025-05-10 19:19:54', 'Success'),
(51, '', '20250421A001', '44739242', '2025-05-10 19:28:31', 'Success'),
(52, '', '20250421A001', '44739242', '2025-05-10 19:30:08', 'Success'),
(53, '', '20250421A001', '44739242', '2025-05-10 19:30:39', 'Success'),
(54, '', '20250421A001', '44739242', '2025-05-10 19:32:54', 'Success'),
(55, '', '20250421A001', '44739242', '2025-05-10 19:43:38', 'Success'),
(56, '', '20250421A001', '44739242', '2025-05-10 19:43:57', 'Success'),
(57, '', '20250421A001', '44739242', '2025-05-10 19:47:23', 'Success'),
(58, '', '20250421A001', '44739242', '2025-05-10 19:47:44', 'Success'),
(59, '', '20250421A001', '44739242', '2025-05-10 19:48:19', 'Success'),
(60, '', '20250421A001', '44739242', '2025-05-10 19:49:07', 'Success'),
(61, '', '20250421A001', '44739242', '2025-05-10 19:53:18', 'Success'),
(62, '', '20250421A001', '44739242', '2025-05-10 19:59:26', 'Success'),
(63, '', '20250421A001', '44739242', '2025-05-10 20:11:04', 'Success'),
(64, '', '20250421A001', '44739242', '2025-05-10 20:11:18', 'Success'),
(65, '', '20250421A001', '44739242', '2025-05-10 20:16:09', 'Success'),
(66, '', '20250421A001', '44739242', '2025-05-10 20:21:10', 'Success'),
(67, '', '20250421A001', '44739242', '2025-05-10 20:29:28', 'Success'),
(68, '', '20250421A001', '44739242', '2025-05-10 20:31:49', 'Success'),
(69, '', '20250421A001', '44739242', '2025-05-10 20:32:30', 'Success'),
(70, '', '20250421A001', '44739242', '2025-05-10 20:32:41', 'Success'),
(71, '', '20250421A001', '44739242', '2025-05-10 20:35:56', 'Success'),
(72, '', '20250421A001', '44739242', '2025-05-10 20:36:29', 'Success'),
(73, '', '20250421A001', '44739242', '2025-05-10 20:37:06', 'Success'),
(74, '', '20250421A001', '44739242', '2025-05-10 20:38:15', 'Success'),
(75, '', '20250421A001', '44739242', '2025-05-10 20:40:26', 'Success'),
(76, '', '20250421A001', '44739242', '2025-05-10 20:40:34', 'Success'),
(77, '', '20250421A001', '44739242', '2025-05-10 20:40:41', 'Success'),
(78, '', '20250421A001', '44739242', '2025-05-10 20:42:48', 'Success');

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_ID` smallint(6) NOT NULL,
  `username` varchar(15) NOT NULL,
  `email_account` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `user_type` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_ID`, `username`, `email_account`, `password`, `user_type`) VALUES
(1, 'Admin123', 'gjorizvon@gmail.com', '$2y$10$7ZibYsklvEX2FFkkHcu5/OuwmifG9b5GJBOsCKb2ROuy5ieCYUcD2', 'admin'),
(2, 'abel123', 'abel@gmail.com', '$2y$10$LtNStjSWtbJfPUx2lX/t5.rro7VsJYGKXMdytbeCjyVp7u97sgUt2', 'tenant'),
(3, 'kyle0123', 'kyle@gmail.com', '$2y$10$CeAEMCakg7cXAEKFlDG/4eiXOI9flTcatWC1Rp5GlTlAo.DsVaDKS', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `admin_account`
--

CREATE TABLE `admin_account` (
  `admin_ID` smallint(4) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(70) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_account`
--

INSERT INTO `admin_account` (`admin_ID`, `username`, `password`) VALUES
(1, 'Admin1234', '$2y$10$4MXBrqHzK6Zp2GCq0suqu.qx7fkrR11bR.wZoiIGjX6gKEPZP6Ieu');

-- --------------------------------------------------------

--
-- Table structure for table `card_registration`
--

CREATE TABLE `card_registration` (
  `card_no` varchar(15) NOT NULL,
  `unit_no` varchar(10) NOT NULL,
  `tenant_ID` varchar(12) NOT NULL,
  `registration_date` date NOT NULL,
  `card_expiry` date NOT NULL,
  `card_status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `card_registration`
--

INSERT INTO `card_registration` (`card_no`, `unit_no`, `tenant_ID`, `registration_date`, `card_expiry`, `card_status`) VALUES
('44739242', 'A-001', '20250421A001', '2025-04-01', '2026-03-01', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `transaction_no` varchar(12) NOT NULL,
  `unit_no` varchar(10) NOT NULL,
  `tenant_ID` varchar(12) NOT NULL,
  `amount_paid` int(6) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_status` varchar(15) NOT NULL,
  `payment_method` varchar(30) NOT NULL,
  `transaction_type` varchar(15) NOT NULL,
  `confirmation_status` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`transaction_no`, `unit_no`, `tenant_ID`, `amount_paid`, `payment_date`, `payment_status`, `payment_method`, `transaction_type`, `confirmation_status`) VALUES
('202504210001', 'A-001', '20250421A001', 10000, '2025-04-16', 'Fully Paid', 'Gcash', 'Rent Payment', 'Confirmed'),
('202504210002', 'A-003', '20250423A003', 10000, '2025-04-29', 'Paid Overdue', 'Cash', 'Deposit', 'Pending'),
('20250518', 'A-003', '20250423', 5000, '2025-05-18', 'Partially Paid', 'settle with deposit', 'Use Deposit', 'pending'),
('202505180002', 'A-003', '20250423', 5000, '2025-05-18', 'Partially Paid', 'settle with deposit', 'Use Deposit', 'pending'),
('202505180003', 'A-003', '20250423A003', 5000, '2025-05-18', 'Paid Overdue', 'settle with deposit', 'Use Deposit', 'pending'),
('202505180004', 'A-003', '20250423A003', 5000, '2025-05-18', 'Paid Overdue', 'settle with deposit', 'Use Deposit', 'pending'),
('202505180005', 'A-003', '20250423A003', 5000, '2025-05-18', 'Paid Overdue', 'settle with deposit', 'Use Deposit', 'pending'),
('202505180006', 'A-003', '20250423A003', 5000, '2025-05-18', 'Paid Overdue', 'settle with deposit', 'Use Deposit', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `pending_inquiry`
--

CREATE TABLE `pending_inquiry` (
  `inquiry_date_time` datetime NOT NULL,
  `unit_no` varchar(10) NOT NULL,
  `full_name` varchar(30) NOT NULL,
  `contact_no` varchar(13) NOT NULL,
  `email` varchar(30) NOT NULL,
  `pref_move_date` date NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payment_due_date` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `tenant_ID` varchar(12) NOT NULL,
  `tenant_name` varchar(15) NOT NULL,
  `contact_number` varchar(13) NOT NULL,
  `email` varchar(30) NOT NULL,
  `emergency_contact_name` varchar(25) NOT NULL,
  `emergency_contact_num` varchar(13) NOT NULL,
  `tenant_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`tenant_ID`, `tenant_name`, `contact_number`, `email`, `emergency_contact_name`, `emergency_contact_num`, `tenant_image`) VALUES
('20250421A001', 'Kyle Catiis', '+639987654320', 'KyleCatiis@gmail.com', 'Abegail Rullan', '+639123456781', 'allen.jpg'),
('20250422A002', 'Adrian Abriol', '+639123456789', 'Abrioladrian@gmail.com', 'Luis Micheal Lapak', '+639123456786', '20231024_132008.jpg'),
('20250423A003', 'Abel Reyes', '+639987654321', 'abel@gmail.com', 'Joriz Pogi Gutierrez', '+639123456788', '20240215_112215.jpg'),
('20250424A004', 'karl pogings', '+639192871231', 'karl@gmail.com', 'Karl Pangit', '+639123456789', '93f1cba8-b9e2-49a9-a9da-dc61859fd4ddphoto.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `tenant_unit`
--

CREATE TABLE `tenant_unit` (
  `tenant_ID` varchar(12) NOT NULL,
  `unit_no` varchar(10) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `occupant_count` smallint(2) NOT NULL,
  `deposit` mediumint(6) NOT NULL,
  `balance` mediumint(6) NOT NULL,
  `payment_due` varchar(30) NOT NULL,
  `billing_period` varchar(31) NOT NULL,
  `lease_status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenant_unit`
--

INSERT INTO `tenant_unit` (`tenant_ID`, `unit_no`, `start_date`, `end_date`, `occupant_count`, `deposit`, `balance`, `payment_due`, `billing_period`, `lease_status`) VALUES
('20250421A001', 'A-001', '2025-04-01', '2032-04-01', 3, 20000, 10000, 'Every 1st day of the month', 'Until the 9th day of the month', 'Active'),
('20250422A002', 'A-002', '2025-04-02', '2032-04-02', 3, 10000, 10000, 'Every 2nd day of the month', 'Until the 9th day of the month', 'Pending'),
('20250423A003', 'A-003', '2025-02-01', '2032-02-01', 3, 25000, 5000, 'Every 3rd day of the month', 'Until the 9th day of the month', 'Active'),
('20250424A004', 'A-004', '2025-04-23', '2025-05-23', 3, 10000, 10000, 'Every 23th day of the month', 'Until the 9th day of the month', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `unit_no` varchar(10) NOT NULL,
  `apartment_no` varchar(15) NOT NULL,
  `unit_address` varchar(30) NOT NULL,
  `unit_size` varchar(10) NOT NULL,
  `occupant_capacity` smallint(6) NOT NULL,
  `floor_level` varchar(10) NOT NULL,
  `unit_type` varchar(10) NOT NULL,
  `monthly_rent_amount` varchar(10) NOT NULL,
  `unit_status` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`unit_no`, `apartment_no`, `unit_address`, `unit_size`, `occupant_capacity`, `floor_level`, `unit_type`, `monthly_rent_amount`, `unit_status`) VALUES
('A-001', 'APT-001', 'Daet, Camarines Norte', '70', 2, '1', '2BR', '10000', 'Occupied'),
('A-002', 'APT-002', 'Daet, Camarines Norte', '70', 1, '2', '1BR', '10000', 'Occupied'),
('A-003', 'APT-003', 'Daet, Camarines Norte', '70', 2, '1', '2BR', '10000', 'Occupied'),
('A-004', 'APT-001', 'Daet, Camarines Norte', '70', 2, '1', '2BR', '10000', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `unit_images`
--

CREATE TABLE `unit_images` (
  `unit_image_ID` mediumint(9) NOT NULL,
  `unit_no` varchar(10) NOT NULL,
  `unit_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit_images`
--

INSERT INTO `unit_images` (`unit_image_ID`, `unit_no`, `unit_image`) VALUES
(17, 'A-004', '20231124_123115.jpg'),
(18, 'A-004', '20231124_123126.jpg'),
(19, 'A-004', '20231124_123128.jpg'),
(20, 'A-004', '20231124_123130.jpg'),
(21, 'A-004', '20231124_123135.jpg'),
(22, 'A-004', '20231124_123137.jpg'),
(23, 'A-004', '20231124_123139.jpg'),
(24, 'A-004', '20231124_123143.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`access_ID`);

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_ID`);

--
-- Indexes for table `admin_account`
--
ALTER TABLE `admin_account`
  ADD PRIMARY KEY (`admin_ID`);

--
-- Indexes for table `card_registration`
--
ALTER TABLE `card_registration`
  ADD PRIMARY KEY (`card_no`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`transaction_no`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`tenant_ID`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`unit_no`);

--
-- Indexes for table `unit_images`
--
ALTER TABLE `unit_images`
  ADD PRIMARY KEY (`unit_image_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `access_ID` mediumint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_ID` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_account`
--
ALTER TABLE `admin_account`
  MODIFY `admin_ID` smallint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `unit_images`
--
ALTER TABLE `unit_images`
  MODIFY `unit_image_ID` mediumint(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
