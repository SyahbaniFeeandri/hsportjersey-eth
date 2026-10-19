-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2026 at 02:24 PM
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
-- Database: `h_sport_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetMonthlyReport` (IN `p_month` INT, IN `p_year` INT)   BEGIN
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders_count,
        SUM(total_amount) as revenue,
        COUNT(DISTINCT user_id) as unique_customers
    FROM orders
    WHERE MONTH(created_at) = p_month 
        AND YEAR(created_at) = p_year
        AND status IN ('completed', 'shipped')
    GROUP BY DATE(created_at)
    ORDER BY date DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateProductStock` (IN `p_product_id` INT, IN `p_quantity` INT)   BEGIN
    UPDATE products 
    SET stock = stock - p_quantity 
    WHERE id = p_product_id AND stock >= p_quantity;
    
    SELECT ROW_COUNT() as affected_rows;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `best_selling_products`
-- (See below for the actual view)
--
CREATE TABLE `best_selling_products` (
`id` int(11)
,`name` varchar(100)
,`category` varchar(50)
,`total_sold` decimal(32,0)
,`total_revenue` decimal(44,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `size` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `custom_text` varchar(100) DEFAULT NULL,
  `custom_design` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_statistics`
-- (See below for the actual view)
--
CREATE TABLE `customer_statistics` (
`id` int(11)
,`username` varchar(50)
,`email` varchar(100)
,`total_orders` bigint(21)
,`total_spent` decimal(34,2)
,`last_order_date` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `custom_designs`
--

CREATE TABLE `custom_designs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `design_image` varchar(255) DEFAULT NULL,
  `design_data` text DEFAULT NULL,
  `design_name` varchar(100) DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `custom_designs`
--

INSERT INTO `custom_designs` (`id`, `user_id`, `design_image`, `design_data`, `design_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, '1779708423_D37-0893.jpg', NULL, NULL, 'draft', '2026-05-25 11:27:03', '2026-05-25 11:27:03');

-- --------------------------------------------------------

--
-- Stand-in structure for view `daily_sales`
-- (See below for the actual view)
--
CREATE TABLE `daily_sales` (
`sale_date` date
,`total_orders` bigint(21)
,`total_revenue` decimal(34,2)
,`average_order_value` decimal(16,6)
);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','production','shipped','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `shipping_method` varchar(50) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `status`, `payment_status`, `shipping_method`, `shipping_address`, `tracking_number`, `notes`, `created_at`, `updated_at`) VALUES
(6, 2, 'INV-20260523-9004', 150000.00, 'completed', 'paid', 'JNE Reguler', 'jl bojong bogor', NULL, NULL, '2026-05-23 07:50:59', '2026-05-23 07:51:26'),
(7, 3, 'INV-20260523-3479', 150000.00, 'completed', 'paid', 'J&T Express', 'jl kecapi V no 48', NULL, NULL, '2026-05-23 13:07:50', '2026-05-23 13:09:32'),
(8, 3, 'INV-20260525-4372', 150000.00, 'completed', 'paid', 'JNE Reguler', 'jl perbanas', NULL, NULL, '2026-05-25 11:27:47', '2026-05-25 11:28:22');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `before_insert_orders` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('INV-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `custom_design` varchar(255) DEFAULT NULL,
  `custom_text` varchar(100) DEFAULT NULL,
  `size` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `custom_design`, `custom_text`, `size`, `color`, `created_at`) VALUES
(6, 6, 1, 1, 150000.00, NULL, '', 'L', 'Putih', '2026-05-23 07:50:59'),
(7, 7, 1, 1, 150000.00, NULL, 'Arby 80', 'S', 'Biru', '2026-05-23 13:07:50'),
(8, 8, 1, 1, 150000.00, NULL, '', 'M', '#f0f0f0', '2026-05-25 11:27:47');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `payment_proof`, `amount`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES
(4, 6, 'QRIS', NULL, 150000.00, 'verified', NULL, '2026-05-23 07:51:04', '2026-05-23 07:51:04'),
(5, 7, 'QRIS', NULL, 150000.00, 'verified', NULL, '2026-05-23 13:07:55', '2026-05-23 13:07:55'),
(6, 8, 'QRIS', NULL, 150000.00, 'verified', NULL, '2026-05-25 11:27:51', '2026-05-25 11:27:51');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `material` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sizes` varchar(100) DEFAULT 'S,M,L,XL,XXL',
  `colors` text DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `material`, `price`, `description`, `image`, `sizes`, `colors`, `stock`, `created_at`, `updated_at`) VALUES
(1, 'Jersey Futsal Pro', 'Futsal', 'Dryfit', 150000.00, 'Jersey futsal premium dengan kualitas terbaik. Bahan adem dan tidak mudah kusut. Cocok untuk tim futsal profesional.', 'persib home.png', 'S,M,L,XL,XXL', 'Merah,Biru,Hijau,Kuning,Putih', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(2, 'Jersey Sepak Bola Elite', 'Sepak Bola', 'Polyester', 180000.00, 'Desain modern untuk tim profesional. Bahan polyester berkualitas tinggi yang nyaman dipakai.', 'persib home.png', 'S,M,L,XL,XXL', 'Putih,Hitam,Merah,Biru,Kuning', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(3, 'Jersey Basket Premium', 'Basket', 'Single Jersey', 200000.00, 'Jersey basket nyaman dengan bahan adem. Desain longgar untuk gerakan bebas saat bermain.', 'persib home.png', 'S,M,L,XL,XXL', 'Hitam,Merah,Biru,Abu-abu,Putih', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(4, 'Jersey Cycle Racing', 'Sepeda', 'Microfiber', 170000.00, 'Jersey sepeda aerodinamis dengan bahan microfiber yang cepat kering dan ringan.', 'persib home.png', 'S,M,L,XL,XXL', 'Kuning,Hijau,Biru,Merah,Putih', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(5, 'Jersey Running Pro', 'Lari', 'Nylon', 160000.00, 'Jersey lari ringan dengan ventilasi udara yang baik untuk kenyamanan maksimal.', 'persib home.png', 'S,M,L,XL,XXL', 'Biru,Merah,Hijau,Kuning,Orange', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(6, 'Jersey Padel Classic', 'Padel', 'Polyester', 175000.00, 'Jersey padel dengan desain klasik dan bahan berkualitas untuk performa terbaik.', 'persib home.png', 'S,M,L,XL,XXL', 'Putih,Merah,Biru,Hitam', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(7, 'Jersey Futsal Retro', 'Futsal', 'Cotton Combed', 140000.00, 'Jersey futsal model retro dengan bahan katun yang nyaman dipakai sehari-hari.', 'persib home.png', 'S,M,L,XL,XXL', 'Merah,Biru,Hijau,Kuning,Putih', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(8, 'Jersey Sepak Bola Limited', 'Sepak Bola', 'Dryfit', 220000.00, 'Edisi terbatas jersey sepak bola dengan desain eksklusif dan bahan premium.', 'persib home.png', 'S,M,L,XL,XXL', 'Hitam,Putih,Merah,Biru', 100, '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(9, 'Jersey Sepak Bola Retro', 'Sepak Bola', 'cutton', 100000.00, 'Jersey Sepak Bola Retro dengan bahan cutton, Menangkap keindahan masa lalu dengan gaya modern yang nyaman dipakai sehari-hari.', 'persib home.png', 'S,M,L,XL,XXL', 'Merah,Biru,Hijau,Kuning', 100, '2026-05-25 11:29:59', '2026-05-25 11:29:59'),
(10, 'Jersey Basket', 'Basket', 'cutton', 565000.00, 'Jersey Basket dengan bahan yang berkualitas tinggi', 'persib home.png', 'S,M,L,XL,XXL', 'Merah,Biru,Hijau,Kuning', 100, '2026-05-25 11:41:26', '2026-05-25 11:41:26'),
(11, 'Jersey Futsal', 'Futsal', 'cutton', 850000.00, 'Jersey Futsal dengan bahan berkualitas tinggi', 'persib home.png', 'S,M,L,XL,XXL', 'Merah,Biru,Hijau,Kuning', 100, '2026-05-25 11:41:57', '2026-05-25 11:41:57'),
(12, 'Jersey Futsal', 'Futsal', 'cutton', 450000.00, 'Jersey dengan kualitas bahan terbaik', 'persib home.png', 'S,M,L,XL,XXL', 'Merah,Biru,Hijau,Kuning', 100, '2026-05-25 11:42:25', '2026-05-25 11:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@hsport.com', '0192023a7bbd73250516f069df18b500', 'admin', '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(2, 'demo_customer', 'customer@example.com', 'f4ad231214cb99a985dff0f056a36242', 'customer', '2026-05-05 04:59:37', '2026-05-05 04:59:37'),
(3, 'Arby Poetra', 'arbyhsport@gmail.com', '66c17a34e89aa0eb75955c808802d5ac', 'customer', '2026-05-23 13:06:16', '2026-05-23 13:06:16');

-- --------------------------------------------------------

--
-- Structure for view `best_selling_products`
--
DROP TABLE IF EXISTS `best_selling_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `best_selling_products`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`category` AS `category`, sum(`oi`.`quantity`) AS `total_sold`, sum(`oi`.`quantity` * `oi`.`price`) AS `total_revenue` FROM ((`order_items` `oi` join `products` `p` on(`oi`.`product_id` = `p`.`id`)) join `orders` `o` on(`oi`.`order_id` = `o`.`id`)) WHERE `o`.`status` in ('completed','shipped') GROUP BY `p`.`id`, `p`.`name`, `p`.`category` ORDER BY sum(`oi`.`quantity`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `customer_statistics`
--
DROP TABLE IF EXISTS `customer_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `customer_statistics`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, count(`o`.`id`) AS `total_orders`, coalesce(sum(`o`.`total_amount`),0) AS `total_spent`, max(`o`.`created_at`) AS `last_order_date` FROM (`users` `u` left join `orders` `o` on(`u`.`id` = `o`.`user_id` and `o`.`status` in ('completed','shipped'))) WHERE `u`.`role` = 'customer' GROUP BY `u`.`id`, `u`.`username`, `u`.`email` ORDER BY coalesce(sum(`o`.`total_amount`),0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `daily_sales`
--
DROP TABLE IF EXISTS `daily_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `daily_sales`  AS SELECT cast(`orders`.`created_at` as date) AS `sale_date`, count(0) AS `total_orders`, sum(`orders`.`total_amount`) AS `total_revenue`, avg(`orders`.`total_amount`) AS `average_order_value` FROM `orders` WHERE `orders`.`status` in ('completed','shipped') GROUP BY cast(`orders`.`created_at` as date) ORDER BY cast(`orders`.`created_at` as date) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `custom_designs`
--
ALTER TABLE `custom_designs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_orders_status_date` (`status`,`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_order_items_order_product` (`order_id`,`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_products_category_price` (`category`,`price`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`name`,`description`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_search` (`name`,`description`,`category`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `custom_designs`
--
ALTER TABLE `custom_designs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_designs`
--
ALTER TABLE `custom_designs`
  ADD CONSTRAINT `custom_designs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================
-- MIGRATION: Tabel Reviews (tambahan)
-- Jalankan jika tabel reviews belum ada
-- ============================================================
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `comment` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`order_id`,`product_id`,`user_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
