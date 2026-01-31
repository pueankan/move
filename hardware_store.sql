-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jan 31, 2026 at 01:47 PM
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
-- Database: `hardware_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calculator_logs`
--

CREATE TABLE `calculator_logs` (
  `id` int(11) NOT NULL,
  `calculator_type` enum('paint','gypsum','concrete','transport') NOT NULL,
  `input_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input_data`)),
  `result_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`result_data`)),
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'สีและอุปกรณ์ทาสี', 'paint-supplies', 'สีทาบ้าน สีรองพื้น แปรง ลูกกลิ้ง', 'fa-paint-roller', 1, 1, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(2, 'ปูน ทราย หิน', 'cement-sand-gravel', 'ปูนซีเมนต์ ทรายก่อสร้าง หินเกล็ด', 'fa-boxes', 1, 2, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(3, 'ไม้และวัสดุไม้', 'wood-materials', 'คานไม้ ไม้อัด ไม้แปรรูป', 'fa-tree', 1, 3, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(4, 'อิฐ บล็อก กระเบื้อง', 'bricks-tiles', 'อิฐมวลเบา บล็อกคอนกรีต กระเบื้อง', 'fa-border-all', 1, 4, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(5, 'เหล็กและโลหะ', 'steel-metal', 'เหล็กเส้น เหล็กรูปพรรณ สังกะสี', 'fa-industry', 1, 5, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(6, 'ท่อและข้อต่อ', 'pipes-fittings', 'ท่อ PVC ท่อเหล็ก ข้อต่อ', 'fa-tools', 1, 6, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(7, 'ไฟฟ้าและแสงสว่าง', 'electrical', 'สายไฟ ปลั๊ก สวิตช์ หลักดไฟ', 'fa-lightbulb', 1, 7, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(8, 'สุขภัณฑ์', 'sanitary', 'ห้องน้ำ อ่างล้างหน้า ก็อกน้ำ', 'fa-sink', 1, 8, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(9, 'เครื่องมือช่าง', 'tools', 'สว่าน เลื่อย ค้อน เครื่องมือไฟฟ้า', 'fa-toolbox', 1, 9, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(10, 'อุปกรณ์ยึดและตรึง', 'fasteners', 'สกรู ตะปู น็อต โบลท์', 'fa-wrench', 1, 10, '2026-01-29 06:59:19', '2026-01-29 06:59:19');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `vat` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','transfer','credit_card','qr_payment') DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','preparing','shipping','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT 'ชิ้น',
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT 'ชิ้น',
  `image_url` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `category`, `category_id`, `description`, `price`, `stock`, `unit`, `image_url`, `image_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'สีทาบ้าน TOA สีขาว', 'สี', NULL, 'สีทาบ้านคุณภาพสูง ทาได้ 2 ชั้น', 850.00, 50, 'ถัง', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(2, NULL, 'ปูนซีเมนต์ปูนตราช้าง', 'ปูน', NULL, 'ปูนซีเมนต์ปอร์ตแลนด์ประเภท 1', 150.00, 200, 'ถุง', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(3, NULL, 'ทรายหยาบ', 'ทราย', NULL, 'ทรายหยาบสำหรับงานก่อสร้าง', 120.00, 100, 'คิว', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(4, NULL, 'แผ่นยิปซั่ม Gyproc', 'ยิปซั่ม', NULL, 'แผ่นยิปซั่มมาตรฐาน หนา 12mm', 180.00, 80, 'แผ่น', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(5, NULL, 'สกรูยิปซั่ม', 'อุปกรณ์', NULL, 'สกรูยิปซั่มแบบหัวจม', 5.00, 500, 'ตัว', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(6, NULL, 'คานไม้สัก 2x4', 'ไม้', NULL, 'คานไม้สักเนื้อแข็ง', 280.00, 60, 'เส้น', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(7, NULL, 'กระเบื้องหลังคา SCG', 'กระเบื้อง', NULL, 'กระเบื้องลอนคู่ สีแดง', 45.00, 300, 'แผ่น', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(8, NULL, 'สว่านไฟฟ้า Bosch', 'เครื่องมือ', NULL, 'สว่านไฟฟ้า 550W พร้อมชุดดอกสว่าน', 2500.00, 15, 'ชิ้น', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(9, NULL, 'ท่อ PVC 2 นิ้ว', 'ท่อ', NULL, 'ท่อพีวีซี สีเทา หนา Class 5', 120.00, 150, 'เส้น', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(10, NULL, 'หินเกล็ด 3/4', 'หิน', NULL, 'หินเกล็ดขนาด 3/4 นิ้ว สำหรับผสมคอนกรีต', 180.00, 120, 'คิว', NULL, NULL, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_purchase`, `max_discount`, `usage_limit`, `usage_count`, `valid_from`, `valid_until`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'ส่วนลด 10% สำหรับลูกค้าใหม่', 'percentage', 10.00, 500.00, NULL, NULL, 0, '2024-01-01', '2024-12-31', 1, '2026-01-29 06:59:19', '2026-01-29 06:59:19'),
(2, 'FREESHIP', 'ฟรีค่าจัดส่ง สำหรับยอดซื้อ 1000 บาทขึ้นไป', 'fixed_amount', 50.00, 1000.00, NULL, NULL, 0, '2024-01-01', '2024-12-31', 1, '2026-01-29 06:59:19', '2026-01-29 06:59:19');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quotation_number` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `vat` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','sent','approved','rejected') DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `item_type` enum('product','service','custom') DEFAULT 'product',
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT 'ชิ้น',
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'งาน',
  `duration_days` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `description`, `base_price`, `unit`, `duration_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ทาสีภายใน', 'บริการทาสีภายในบ้าน รวมเตรียมผิว', 150.00, 'ตร.ม.', 3, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(2, 'ทาสีภายนอก', 'บริการทาสีภายนอกบ้าน กันซึม กันรั่ว', 180.00, 'ตร.ม.', 5, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(3, 'ติดตั้งฝ้ายิปซั่ม', 'ติดตั้งฝ้ายิปซั่มเพดาน พร้อมโครงเหล็ก', 280.00, 'ตร.ม.', 7, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(4, 'เทพื้นคอนกรีต', 'เทพื้นคอนกรีต รวมวัสดุและแรงงาน', 350.00, 'ตร.ม.', 5, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(5, 'ก่อผนังอิฐ', 'ก่อผนังอิฐมอญ พร้อมฉาบปูน', 320.00, 'ตร.ม.', 10, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(6, 'ติดตั้งหลังคา', 'ติดตั้งหลังคาสังกะสี/กระเบื้อง พร้อมโครง', 450.00, 'ตร.ม.', 14, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(7, 'งานไฟฟ้า', 'ติดตั้งระบบไฟฟ้าภายในบ้าน', 5000.00, 'งาน', 7, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02'),
(8, 'งานประปา', 'ติดตั้งระบบประปาและสุขภัณฑ์', 4500.00, 'งาน', 5, 1, '2026-01-29 06:11:02', '2026-01-29 06:11:02');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'ร้านฮาร์ดแวร์และวัสดุก่อสร้าง', 'text', 'ชื่อร้าน', '2026-01-29 06:59:19'),
(2, 'site_email', 'info@hardware.com', 'text', 'อีเมลร้าน', '2026-01-29 06:59:19'),
(3, 'site_phone', '081-234-5678', 'text', 'เบอร์โทรร้าน', '2026-01-29 06:59:19'),
(4, 'vat_percent', '7', 'number', 'ภาษีมูลค่าเพิ่ม (%)', '2026-01-29 06:59:19'),
(5, 'shipping_base_cost', '50', 'number', 'ค่าส่งขั้นต่ำ', '2026-01-29 06:59:19'),
(6, 'min_order_amount', '200', 'number', 'ยอดสั่งซื้อขั้นต่ำ', '2026-01-29 06:59:19'),
(7, 'currency', 'THB', 'text', 'สกุลเงิน', '2026-01-29 06:59:19'),
(8, 'items_per_page', '12', 'number', 'จำนวนสินค้าต่อหน้า', '2026-01-29 06:59:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','staff','customer') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@hardware.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', NULL, NULL, 'admin', 1, NULL, '2026-01-29 06:59:19', '2026-01-29 06:59:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `calculator_logs`
--
ALTER TABLE `calculator_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`calculator_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`order_status`),
  ADD KEY `idx_payment` (`payment_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_dates` (`valid_from`,`valid_until`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_number` (`quotation_number`),
  ADD KEY `idx_quotation_number` (`quotation_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quotation` (`quotation_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_request_date` (`request_date`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
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
-- AUTO_INCREMENT for table `calculator_logs`
--
ALTER TABLE `calculator_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
