-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2025 at 04:23 PM
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
-- Database: `user_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `inbound_qty` int(11) NOT NULL DEFAULT 0,
  `outbound_qty` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL,
  `supplier` varchar(255) NOT NULL,
  `last_restocked` datetime NOT NULL,
  `stockpile` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `product_name`, `inbound_qty`, `outbound_qty`, `current_stock`, `supplier`, `last_restocked`, `stockpile`) VALUES
(1, 'Software Licenses', 50, 0, 50, 'Aryan', '2025-03-24 21:43:23', 50),
(2, 'raw materials', 0, 0, 0, 'Fran Fran', '2025-03-24 21:41:33', 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_amount` decimal(10,0) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `status` varchar(50) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `username`, `product_id`, `quantity`, `total_amount`, `payment_method`, `status`) VALUES
(1, 'amiel', 3, 1, 500, 'Cash', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `supplier` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `price`, `quantity`, `supplier`) VALUES
(3, 'Electric Guitar', 500.00, 2, 'Francine'),
(4, 'Buldak', 80.00, 3, 'Rischa'),
(5, 'Drums', 40000.00, 2, 'Fritz');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `event_type`, `user_id`, `username`, `details`, `success`, `timestamp`) VALUES
(19, 'USER_REGISTERED', NULL, 'System', 'New user registered: amiel (amiel_paguia@dlsu.edu.ph)', 1, '2025-08-12 21:38:44'),
(20, 'LOGIN_FAILURE', NULL, 'Unknown', 'Failed attempt 1 of 5', 0, '2025-08-12 21:39:15'),
(21, 'LOGIN_FAILURE', NULL, 'Unknown', 'Failed attempt 2 of 5', 0, '2025-08-12 21:39:19'),
(22, 'LOGIN_SUCCESS', 6, 'amiel', 'Login successful', 1, '2025-08-12 21:39:43'),
(23, 'PAGE_ACCESS', 6, 'amiel', 'Accessed order_products.php page', 1, '2025-08-12 21:42:02'),
(24, 'PAGE_ACCESS', 6, 'amiel', 'Accessed order_products.php page', 1, '2025-08-12 21:43:52'),
(25, 'PAGE_ACCESS', 6, 'amiel', 'Accessed order_products.php page', 1, '2025-08-12 21:45:35'),
(26, 'PAGE_ACCESS', 6, 'amiel', 'Accessed order_products.php page', 1, '2025-08-12 21:45:36'),
(27, 'PAGE_ACCESS', 6, 'amiel', 'Accessed order_products.php page', 1, '2025-08-12 21:45:42'),
(28, 'PAGE_ACCESS', 6, 'amiel', 'Accessed order_products.php page', 1, '2025-08-12 21:46:11'),
(29, 'ORDER_PLACED', 6, 'amiel', 'Order placed successfully: Customer: amiel, Product: Electric Guitar, Quantity: 1, Amount: 500.00, Payment: Cash', 1, '2025-08-12 21:46:57'),
(30, 'ACCESS_DENIED', 6, 'amiel', 'Access denied to resource: /ITSECWB-Group-6-main/MenuPage.php', 0, '2025-08-12 21:56:29'),
(31, 'ACCESS_DENIED', 6, 'amiel', 'Access denied to resource: /ITSECWB-Group-6-main/MenuPage.php', 0, '2025-08-12 21:56:33'),
(32, 'ACCESS_DENIED', 6, 'amiel', 'Access denied to resource: /ITSECWB-Group-6-main/MenuPage.php', 0, '2025-08-12 21:56:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `security_question` varchar(255) NOT NULL,
  `security_answer` varchar(255) NOT NULL,
  `usertype` enum('customer','manager','owner','') NOT NULL DEFAULT 'customer',
  `failed_login_attempts` int(11) DEFAULT 0,
  `last_failed_login` datetime DEFAULT NULL,
  `account_locked_until` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `security_question`, `security_answer`, `usertype`, `failed_login_attempts`, `last_failed_login`, `account_locked_until`, `last_password_change`) VALUES
(2, 'arvin', 'arvintorno@gmail.com', '$2y$10$YzxDVRaZMWiRdcHeE9VKfe5ZCnU45fFyiAI5/6KISBSOMVOTrm40i', 'What is your favorite color?', 'Blue', 'owner', 0, NULL, NULL, '2025-07-01 15:02:51'),
(4, 'gale', 'gale@yahoo.com', '$2y$10$Im0psZI3Ss23RHNU7PIWl.sO9zA0ZDNmVxWODYhxvU/XcebXbhy0S', 'What is your favorite color?', 'orange', 'customer', 0, NULL, NULL, '2025-08-12 03:11:09'),
(5, 'ren', 'renamamiya@dlsu.edu.ph', '$2y$10$nWPZW8m4SIezVDju1jVypeCti/UzP0pIyqO198jH0n7.1XsN58eym', 'What is your favorite movie?', 'persona', 'customer', 0, NULL, NULL, '2025-08-12 03:12:12'),
(6, 'amiel', 'amiel_paguia@dlsu.edu.ph', '$2y$10$D3QTsc7PcbNQuqKCO/iFt.riBljmdqo.lS/a2wlUunLDdMwdA/Huy', 'What is your favorite color?', 'yellow', 'customer', 0, '2025-08-12 21:39:19', NULL, '2025-08-12 21:38:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_success` (`success`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
