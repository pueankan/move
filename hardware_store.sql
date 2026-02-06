-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2026 at 02:08 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_post_journal_entry` (IN `p_entry_id` INT, IN `p_user_id` INT)   BEGIN
    DECLARE v_debit DECIMAL(15,2);
    DECLARE v_credit DECIMAL(15,2);
    DECLARE v_period_status VARCHAR(20);
    
    -- Start Transaction
    START TRANSACTION;
    
    -- ตรวจสอบยอด Debit = Credit
    SELECT total_debit, total_credit INTO v_debit, v_credit
    FROM journal_entries
    WHERE id = p_entry_id;
    
    IF v_debit != v_credit THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ยอด Debit ไม่เท่ากับ Credit';
    END IF;
    
    -- ตรวจสอบงวดบัญชีไม่ปิด
    SELECT status INTO v_period_status
    FROM accounting_periods ap
    JOIN journal_entries je ON je.period_id = ap.id
    WHERE je.id = p_entry_id;
    
    IF v_period_status = 'closed' OR v_period_status = 'locked' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ไม่สามารถบันทึกได้ งวดบัญชีปิดแล้ว';
    END IF;
    
    -- Update Status
    UPDATE journal_entries
    SET status = 'posted',
        posted_at = NOW(),
        posted_by = p_user_id
    WHERE id = p_entry_id;
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accounting_periods`
--

CREATE TABLE `accounting_periods` (
  `id` int(11) NOT NULL,
  `period_name` varchar(50) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','closed','locked') DEFAULT 'open',
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounting_periods`
--

INSERT INTO `accounting_periods` (`id`, `period_name`, `fiscal_year`, `start_date`, `end_date`, `status`, `closed_at`, `closed_by`, `created_at`) VALUES
(1, 'มกราคม 2026', 2026, '2026-01-01', '2026-01-31', 'open', NULL, NULL, '2026-01-31 13:13:51'),
(2, 'กุมภาพันธ์ 2026', 2026, '2026-02-01', '2026-02-28', 'open', NULL, NULL, '2026-01-31 13:13:51'),
(3, 'มีนาคม 2026', 2026, '2026-03-01', '2026-03-31', 'open', NULL, NULL, '2026-01-31 13:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `accounting_settings`
--

CREATE TABLE `accounting_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accounts_payable`
--

CREATE TABLE `accounts_payable` (
  `id` int(11) NOT NULL,
  `bill_number` varchar(50) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `payment_terms` varchar(50) DEFAULT 'cash',
  `subtotal` decimal(15,2) NOT NULL,
  `vat_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL,
  `status` enum('draft','received','partial_paid','paid','overdue','cancelled') DEFAULT 'draft',
  `journal_entry_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accounts_receivable`
--

CREATE TABLE `accounts_receivable` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `payment_terms` varchar(50) DEFAULT 'cash',
  `subtotal` decimal(15,2) NOT NULL,
  `vat_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL,
  `status` enum('draft','issued','partial_paid','paid','overdue','cancelled') DEFAULT 'draft',
  `journal_entry_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 'login_failed', 'Failed login attempt: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 00:43:17'),
(2, NULL, 'login_failed', 'Failed login attempt: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 00:43:24'),
(3, 2, 'register', 'New user registered: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 00:44:18'),
(4, NULL, 'login_failed', 'Failed login attempt: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 13:02:57'),
(5, NULL, 'login_failed', 'Failed login attempt: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 13:03:02'),
(6, NULL, 'login_failed', 'Failed login attempt: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 13:03:17'),
(7, NULL, 'login_failed', 'Failed login attempt: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 13:05:45'),
(8, NULL, 'login_failed', 'Failed login attempt: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 13:05:53');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
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
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `account_subtype` varchar(100) DEFAULT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_system_account` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `account_subtype`, `parent_account_id`, `is_active`, `is_system_account`, `description`, `created_at`, `updated_at`, `created_by`) VALUES
(1, '1-1000', 'เงินสด', 'asset', 'current_asset', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(2, '1-1100', 'เงินฝากธนาคาร', 'asset', 'current_asset', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(3, '1-1200', 'ลูกหนี้การค้า', 'asset', 'current_asset', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(4, '1-1300', 'สินค้าคงเหลือ', 'asset', 'current_asset', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(5, '1-2000', 'ที่ดิน อาคาร และอุปกรณ์', 'asset', 'fixed_asset', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(6, '1-2100', 'ค่าเสื่อมราคาสะสม', 'asset', 'fixed_asset', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(7, '2-1000', 'เจ้าหนี้การค้า', 'liability', 'current_liability', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(8, '2-1100', 'ภาษีขาย (VAT)', 'liability', 'current_liability', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(9, '2-1200', 'ภาษีซื้อ (VAT)', 'liability', 'current_liability', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(10, '2-2000', 'เงินกู้ระยะยาว', 'liability', 'long_term_liability', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(11, '3-1000', 'ทุน', 'equity', 'capital', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(12, '3-2000', 'กำไรสะสม', 'equity', 'retained_earnings', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(13, '3-3000', 'กำไร(ขาดทุน)สุทธิ', 'equity', 'net_income', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(14, '4-1000', 'รายได้จากการขาย', 'revenue', 'sales_revenue', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(15, '4-2000', 'รายได้จากบริการ', 'revenue', 'service_revenue', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(16, '4-9000', 'รายได้อื่นๆ', 'revenue', 'other_revenue', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(17, '5-1000', 'ต้นทุนขาย', 'expense', 'cost_of_goods_sold', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(18, '5-2000', 'เงินเดือนและค่าจ้าง', 'expense', 'operating_expense', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(19, '5-2100', 'ค่าเช่า', 'expense', 'operating_expense', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(20, '5-2200', 'ค่าไฟฟ้า', 'expense', 'operating_expense', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(21, '5-2300', 'ค่าโทรศัพท์', 'expense', 'operating_expense', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(22, '5-3000', 'ค่าเสื่อมราคา', 'expense', 'depreciation', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL),
(23, '5-9000', 'ค่าใช้จ่ายอื่นๆ', 'expense', 'other_expense', NULL, 1, 1, NULL, '2026-01-31 13:13:51', '2026-01-31 13:17:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `depreciation_logs`
--

CREATE TABLE `depreciation_logs` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `depreciation_date` date NOT NULL,
  `depreciation_amount` decimal(15,2) NOT NULL,
  `accumulated_depreciation` decimal(15,2) NOT NULL,
  `book_value` decimal(15,2) NOT NULL,
  `journal_entry_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixed_assets`
--

CREATE TABLE `fixed_assets` (
  `id` int(11) NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_category` varchar(100) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `purchase_price` decimal(15,2) NOT NULL,
  `salvage_value` decimal(15,2) DEFAULT 0.00,
  `useful_life_years` int(11) NOT NULL,
  `depreciation_method` enum('straight_line','declining_balance','sum_of_years') DEFAULT 'straight_line',
  `accumulated_depreciation` decimal(15,2) DEFAULT 0.00,
  `book_value` decimal(15,2) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('active','disposed','sold') DEFAULT 'active',
  `disposal_date` date DEFAULT NULL,
  `disposal_amount` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('manual','sales','purchase','payment','receipt','adjustment','depreciation','closing') NOT NULL,
  `period_id` int(11) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `total_debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','pending','approved','posted','void') DEFAULT 'draft',
  `posted_at` timestamp NULL DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` int(11) DEFAULT NULL,
  `void_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Triggers `journal_entries`
--
DELIMITER $$
CREATE TRIGGER `audit_journal_entry_update` AFTER UPDATE ON `journal_entries` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status OR OLD.total_debit != NEW.total_debit THEN
        INSERT INTO audit_logs (
            action, table_name, record_id, 
            old_values, new_values, severity
        ) VALUES (
            'journal_entry_updated',
            'journal_entries',
            NEW.id,
            JSON_OBJECT('status', OLD.status, 'total_debit', OLD.total_debit),
            JSON_OBJECT('status', NEW.status, 'total_debit', NEW.total_debit),
            'critical'
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` int(11) NOT NULL,
  `entry_id` int(11) NOT NULL,
  `line_number` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

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
(1, 'admin', 'admin@hardware.com', '$2y$10$HrFiK/pm4r9/xpvw5VLYVuWBM0PcH4AtBrTIiYgbdtxUzqIRQxhEO', 'ผู้ดูแลระบบ', NULL, NULL, 'admin', 1, NULL, '2026-01-29 06:59:19', '2026-02-01 01:01:52'),
(2, 'test', 'test@gmail.com', '$2y$10$HrFiK/pm4r9/xpvw5VLYVuWBM0PcH4AtBrTIiYgbdtxUzqIRQxhEO', 'test', '0983849344', NULL, 'customer', 1, NULL, '2026-02-01 00:44:18', '2026-02-01 00:44:18');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_general_ledger`
-- (See below for the actual view)
--
CREATE TABLE `v_general_ledger` (
`id` int(11)
,`entry_number` varchar(50)
,`entry_date` date
,`entry_type` enum('manual','sales','purchase','payment','receipt','adjustment','depreciation','closing')
,`account_code` varchar(20)
,`account_name` varchar(255)
,`account_type` enum('asset','liability','equity','revenue','expense')
,`description` text
,`debit_amount` decimal(15,2)
,`credit_amount` decimal(15,2)
,`status` enum('draft','pending','approved','posted','void')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_trial_balance`
-- (See below for the actual view)
--
CREATE TABLE `v_trial_balance` (
`account_code` varchar(20)
,`account_name` varchar(255)
,`account_type` enum('asset','liability','equity','revenue','expense')
,`total_debit` decimal(37,2)
,`total_credit` decimal(37,2)
,`balance` decimal(38,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_general_ledger`
--
DROP TABLE IF EXISTS `v_general_ledger`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_general_ledger`  AS SELECT `jel`.`id` AS `id`, `je`.`entry_number` AS `entry_number`, `je`.`entry_date` AS `entry_date`, `je`.`entry_type` AS `entry_type`, `coa`.`account_code` AS `account_code`, `coa`.`account_name` AS `account_name`, `coa`.`account_type` AS `account_type`, `jel`.`description` AS `description`, `jel`.`debit_amount` AS `debit_amount`, `jel`.`credit_amount` AS `credit_amount`, `je`.`status` AS `status`, `je`.`created_at` AS `created_at` FROM ((`journal_entry_lines` `jel` join `journal_entries` `je` on(`jel`.`entry_id` = `je`.`id`)) join `chart_of_accounts` `coa` on(`jel`.`account_id` = `coa`.`id`)) WHERE `je`.`status` = 'posted' ORDER BY `je`.`entry_date` ASC, `je`.`entry_number` ASC, `jel`.`line_number` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_trial_balance`
--
DROP TABLE IF EXISTS `v_trial_balance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_trial_balance`  AS SELECT `coa`.`account_code` AS `account_code`, `coa`.`account_name` AS `account_name`, `coa`.`account_type` AS `account_type`, sum(`jel`.`debit_amount`) AS `total_debit`, sum(`jel`.`credit_amount`) AS `total_credit`, sum(`jel`.`debit_amount`) - sum(`jel`.`credit_amount`) AS `balance` FROM ((`chart_of_accounts` `coa` left join `journal_entry_lines` `jel` on(`coa`.`id` = `jel`.`account_id`)) left join `journal_entries` `je` on(`jel`.`entry_id` = `je`.`id` and `je`.`status` = 'posted')) WHERE `coa`.`is_active` = 1 GROUP BY `coa`.`id`, `coa`.`account_code`, `coa`.`account_name`, `coa`.`account_type` HAVING `total_debit` > 0 OR `total_credit` > 0 ORDER BY `coa`.`account_code` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting_periods`
--
ALTER TABLE `accounting_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_period` (`fiscal_year`,`start_date`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `accounting_settings`
--
ALTER TABLE `accounting_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_number` (`bill_number`),
  ADD KEY `journal_entry_id` (`journal_entry_id`),
  ADD KEY `idx_bill_number` (`bill_number`),
  ADD KEY `idx_vendor` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `journal_entry_id` (`journal_entry_id`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_severity` (`severity`);

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
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `parent_account_id` (`parent_account_id`),
  ADD KEY `idx_account_code` (`account_code`),
  ADD KEY `idx_account_type` (`account_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `depreciation_logs`
--
ALTER TABLE `depreciation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_entry_id` (`journal_entry_id`),
  ADD KEY `idx_asset` (`asset_id`),
  ADD KEY `idx_period` (`period_id`);

--
-- Indexes for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_code` (`asset_code`),
  ADD KEY `idx_asset_code` (`asset_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`asset_category`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_number` (`entry_number`),
  ADD KEY `idx_entry_number` (`entry_number`),
  ADD KEY `idx_entry_date` (`entry_date`),
  ADD KEY `idx_period` (`period_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entry` (`entry_id`),
  ADD KEY `idx_account` (`account_id`);

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
-- AUTO_INCREMENT for table `accounting_periods`
--
ALTER TABLE `accounting_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `accounting_settings`
--
ALTER TABLE `accounting_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
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
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `depreciation_logs`
--
ALTER TABLE `depreciation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  ADD CONSTRAINT `accounts_payable_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`);

--
-- Constraints for table `accounts_receivable`
--
ALTER TABLE `accounts_receivable`
  ADD CONSTRAINT `accounts_receivable_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`);

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `chart_of_accounts_ibfk_1` FOREIGN KEY (`parent_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `depreciation_logs`
--
ALTER TABLE `depreciation_logs`
  ADD CONSTRAINT `depreciation_logs_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `fixed_assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `depreciation_logs_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `accounting_periods` (`id`),
  ADD CONSTRAINT `depreciation_logs_ibfk_3` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`);

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`period_id`) REFERENCES `accounting_periods` (`id`);

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `journal_entry_lines_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_entry_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`);

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
