-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2024 at 12:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cdac_emp_voting`
--

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `ID` int(11) NOT NULL,
  `Employee_Name` varchar(100) NOT NULL,
  `isGroupHead` varchar(5) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `Group_ID` int(11) DEFAULT NULL,
  `authType` text NOT NULL DEFAULT 'ldap',
  `role` text NOT NULL DEFAULT 'user',
  `password` varchar(256) DEFAULT NULL,
  `isActive` text NOT NULL DEFAULT 'false',
  `displayImg` varchar(256) NOT NULL DEFAULT 'default'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`ID`, `Employee_Name`, `isGroupHead`, `email`, `Group_ID`, `authType`, `role`, `password`, `isActive`, `displayImg`) VALUES
(1, 'Poll Administrator', NULL, 'poll-admin@cdac.in', NULL, 'self', 'admin', '$2y$10$DLbuBDCvwXJS7x7y4nCdRuaCBFZFgASI/cCLHULVYBxaoWMCwR0TS', 'true', 'default'),
(2, 'Data Entry Operator', NULL, 'doperator@cdac.in', NULL, 'self', 'operator', '$2y$10$DLbuBDCvwXJS7x7y4nCdRuaCBFZFgASI/cCLHULVYBxaoWMCwR0TS', 'true', 'default');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `Group_ID` int(11) NOT NULL,
  `Group_Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs__auth`
--

CREATE TABLE `logs__auth` (
  `log_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `ts_hash` text DEFAULT NULL,
  `remark` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voting`
--

CREATE TABLE `voting` (
  `Voting_ID` int(11) NOT NULL,
  `Group_Vote_ID` int(11) NOT NULL,
  `All_Vote_ID` int(11) NOT NULL,
  `Emp_ID` int(11) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `Group_ID` (`Group_ID`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`Group_ID`),
  ADD UNIQUE KEY `Group_Name` (`Group_Name`);

--
-- Indexes for table `logs__auth`
--
ALTER TABLE `logs__auth`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `users_id` (`users_id`);

--
-- Indexes for table `voting`
--
ALTER TABLE `voting`
  ADD PRIMARY KEY (`Voting_ID`),
  ADD KEY `Group_Vote_ID` (`Group_Vote_ID`),
  ADD KEY `All_Vote_ID` (`All_Vote_ID`),
  ADD KEY `Emp_ID` (`Emp_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `Group_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `logs__auth`
--
ALTER TABLE `logs__auth`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `voting`
--
ALTER TABLE `voting`
  MODIFY `Voting_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`Group_ID`) REFERENCES `groups` (`Group_ID`);

--
-- Constraints for table `logs__auth`
--
ALTER TABLE `logs__auth`
  ADD CONSTRAINT `logs__auth_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `employee` (`ID`);

--
-- Constraints for table `voting`
--
ALTER TABLE `voting`
  ADD CONSTRAINT `voting_ibfk_1` FOREIGN KEY (`Group_Vote_ID`) REFERENCES `employee` (`ID`),
  ADD CONSTRAINT `voting_ibfk_2` FOREIGN KEY (`All_Vote_ID`) REFERENCES `employee` (`ID`),
  ADD CONSTRAINT `voting_ibfk_3` FOREIGN KEY (`Emp_ID`) REFERENCES `employee` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
