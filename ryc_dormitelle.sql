-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2025 at 07:27 PM
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
-- Database: `ryc_dormitelle`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

CREATE TABLE `access_logs` (
  `access_ID` varchar(10) NOT NULL,
  `tenant_ID` varchar(12) NOT NULL,
  `card_no` varchar(10) NOT NULL,
  `date_and_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `access_status` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`access_ID`, `tenant_ID`, `card_no`, `date_and_time`, `access_status`) VALUES
('1', '20250421A001', '1234567890', '2025-04-26 18:29:38', 'Successful'),
('2', '20250422A002', '2345678901', '2025-04-26 18:29:38', 'Successful'),
('3', '20250423A003', '3456789012', '2025-04-26 18:30:51', 'Successful'),
('4', '20250424A004', '4567890123', '2025-04-26 18:29:38', 'Successful'),
('5', '20250423A003', '5678901234', '2025-04-26 18:29:38', 'Failed');

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_ID` smallint(6) NOT NULL,
  `email_account` varchar(30) NOT NULL,
  `password` varchar(30) NOT NULL,
  `user_type` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `tenant_ID` varchar(12) NOT NULL,
  `registration_date` date NOT NULL,
  `card_expiry` date NOT NULL,
  `card_status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `card_registration`
--

INSERT INTO `card_registration` (`card_no`, `tenant_ID`, `registration_date`, `card_expiry`, `card_status`) VALUES
('1234567890', '20250421A001', '2025-04-01', '2026-03-01', 'Active'),
('2345678901', '20250422A002', '2025-04-02', '2026-03-02', 'Expired'),
('3456789012', '20250423A003', '2025-04-03', '2026-03-03', 'Active'),
('4567890123', '20250424A004', '2025-04-04', '2026-03-04', 'Expired');

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
  `payment_due` varchar(27) NOT NULL,
  `payment_status` varchar(15) NOT NULL,
  `billing_period` varchar(31) NOT NULL,
  `payment_method` varchar(10) NOT NULL,
  `transaction_type` varchar(15) NOT NULL,
  `confirmation_status` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`transaction_no`, `unit_no`, `tenant_ID`, `amount_paid`, `payment_date`, `payment_due`, `payment_status`, `billing_period`, `payment_method`, `transaction_type`, `confirmation_status`) VALUES
('202504210001', 'A-001', '20250421A001', 10000, '2025-04-16', 'Every 4th day of the month', 'Fully Paid', 'Until the 9th day of the month', 'Gcash', 'Rent Payment', 'Confirmed'),
('202504210002', 'A-003', '20250423A003', 10000, '2025-04-29', 'Every 24th day of the month', 'Paid Overdue', 'Until the 28th day of the month', 'Cash', 'Deposit', 'Pending');

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
('20250423A003', 'Abel Reyes', '+639987654321', 'Abel@gmail.com', 'Joriz Pogi Gutierrez', '+639123456788', '20240215_112215.jpg'),
('20250424A004', 'karl pogings', '+639192871231', 'karl@gmail.com', 'Karl Pangit', '+639123456789', '93f1cba8-b9e2-49a9-a9da-dc61859fd4ddphoto.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `tenant_unit`
--

CREATE TABLE `tenant_unit` (
  `tenant_ID` varchar(12) NOT NULL,
  `unit_no` varchar(10) NOT NULL,
  `lease_start_date` date NOT NULL,
  `lease_end_date` date NOT NULL,
  `occupant_count` smallint(2) NOT NULL,
  `deposit` mediumint(6) NOT NULL,
  `balance` mediumint(6) NOT NULL,
  `lease_payment_due` varchar(30) NOT NULL,
  `lease_status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenant_unit`
--

INSERT INTO `tenant_unit` (`tenant_ID`, `unit_no`, `lease_start_date`, `lease_end_date`, `occupant_count`, `deposit`, `balance`, `lease_payment_due`, `lease_status`) VALUES
('20250421A001', 'A-001', '2025-04-01', '2032-04-01', 3, 10000, 10000, 'Every 1st day of the month', 'Active'),
('20250422A002', 'A-002', '2025-04-02', '2032-04-02', 3, 10000, 10000, 'Every 2nd day of the month', 'Pending'),
('20250423A003', 'A-003', '2025-02-01', '2032-02-01', 3, 10000, 10000, 'Every 3rd day of the month', 'Active'),
('20250424A004', 'A-004', '2025-04-23', '2025-05-23', 3, 10000, 10000, 'Every 23th day of the month', 'Active');

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
('A-003', 'APT-003', 'Daet, Camarines Norte', '70', 2, '1', '2BR', '10000', 'Occupied');

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
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_ID` smallint(6) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_account`
--
ALTER TABLE `admin_account`
  MODIFY `admin_ID` smallint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `unit_images`
--
ALTER TABLE `unit_images`
  MODIFY `unit_image_ID` mediumint(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
