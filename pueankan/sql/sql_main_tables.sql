-- ===================================================
-- Main Database Tables
-- ตารางหลักสำหรับร้านฮาร์ดแวร์และวัสดุก่อสร้าง
-- ===================================================

CREATE DATABASE IF NOT EXISTS hardware_store;
USE hardware_store;

-- ===================================================
-- ตารางสินค้า (Products)
-- ===================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    category_id INT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost_price DECIMAL(10,2) DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    min_stock INT DEFAULT 10,
    unit VARCHAR(50) DEFAULT 'ชิ้น',
    image_url VARCHAR(255),
    image_path VARCHAR(255),
    brand VARCHAR(100),
    weight DECIMAL(10,2),
    dimensions VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_category_id (category_id),
    INDEX idx_active (is_active),
    INDEX idx_featured (featured),
    INDEX idx_sku (sku),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ตารางบริการ (Services)
-- ===================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_code VARCHAR(50) UNIQUE,
    service_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unit VARCHAR(50) DEFAULT 'งาน',
    duration VARCHAR(50),
    requirements TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    FULLTEXT idx_search (service_name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ตารางคำขอใช้บริการ (Service Requests)
-- ===================================================
CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) UNIQUE NOT NULL,
    service_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(255),
    customer_address TEXT,
    requested_date DATE,
    preferred_time VARCHAR(50),
    details TEXT,
    estimated_price DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    INDEX idx_request_number (request_number),
    INDEX idx_service (service_id),
    INDEX idx_status (status),
    INDEX idx_customer_phone (customer_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ตารางใบเสนอราคา (Quotations)
-- ===================================================
CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(255),
    customer_address TEXT,
    company_name VARCHAR(255),
    tax_id VARCHAR(50),
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    discount_type ENUM('amount', 'percent') DEFAULT 'amount',
    vat DECIMAL(10,2) DEFAULT 0.00,
    vat_percent DECIMAL(5,2) DEFAULT 7.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    terms TEXT,
    valid_until DATE,
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quotation_number (quotation_number),
    INDEX idx_customer_phone (customer_phone),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ตารางรายการใบเสนอราคา (Quotation Items)
-- ===================================================
CREATE TABLE IF NOT EXISTS quotation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    item_type ENUM('product', 'service', 'custom') DEFAULT 'product',
    product_id INT,
    service_id INT,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'ชิ้น',
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    INDEX idx_quotation (quotation_id),
    INDEX idx_item_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ตารางบันทึกการคำนวณ (Calculator Logs)
-- ===================================================
CREATE TABLE IF NOT EXISTS calculator_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calculator_type ENUM('paint', 'gypsum', 'cement', 'transport') NOT NULL,
    input_data JSON NOT NULL,
    result_data JSON NOT NULL,
    project_name VARCHAR(255),
    customer_name VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_calculator_type (calculator_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ตารางตะกร้าสินค้า (Cart) - Optional
-- ===================================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ตารางรายการในตะกร้า (Cart Items)
-- ===================================================
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_cart (cart_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- ข้อมูลตัวอย่าง - Products
-- ===================================================
INSERT INTO products (sku, name, description, category, price, cost_price, stock, unit, brand, is_active, featured) VALUES
-- สีและอุปกรณ์ทาสี
('PAINT-001', 'สีทาบ้านภายนอก TOA SuperShield', 'สีน้ำอะครีลิคทาบ้านภายนอก กันน้ำดี ทนแดด', 'สีและอุปกรณ์ทาสี', 890.00, 650.00, 50, 'กระป๋อง', 'TOA', TRUE, TRUE),
('PAINT-002', 'สีทาภายใน Dulux Easy Clean', 'สีทาภายในสูตรล้างได้ ทำความสะอาดง่าย', 'สีและอุปกรณ์ทาสี', 750.00, 550.00, 40, 'กระป๋อง', 'Dulux', TRUE, TRUE),
('PAINT-003', 'แปรงทาสี 4 นิ้ว', 'แปรงทาสีคุณภาพดี ขนนุ่ม ทาได้เรียบเนียน', 'สีและอุปกรณ์ทาสี', 85.00, 45.00, 100, 'อัน', 'Standard', TRUE, FALSE),
('PAINT-004', 'ลูกกลิ้งทาสี 9 นิ้ว พร้อมด้าม', 'ลูกกลิ้งทาสีคุณภาพดี ทาเนียน ไม่หลุดร่วง', 'สีและอุปกรณ์ทาสี', 120.00, 65.00, 80, 'ชุด', 'Standard', TRUE, FALSE),

-- ปูน ทราย หิน
('CEMENT-001', 'ปูนซีเมนต์ตราเสือ', 'ปูนซีเมนต์ปอร์ตแลนด์ประเภท 1 คุณภาพมาตรฐาน', 'ปูน ทราย หิน', 150.00, 120.00, 500, 'ถุง', 'ตราเสือ', TRUE, TRUE),
('CEMENT-002', 'ปูนซีเมนต์ผสม SCG', 'ปูนผสมเสร็จใช้งานง่าย เติมน้ำได้เลย', 'ปูน ทราย หิน', 85.00, 65.00, 300, 'ถุง', 'SCG', TRUE, TRUE),
('SAND-001', 'ทรายหยาบ', 'ทรายหยาบสำหรับงานก่อสร้าง', 'ปูน ทราย หิน', 450.00, 350.00, 100, 'คิว', 'Standard', TRUE, FALSE),
('GRAVEL-001', 'หินเกล็ด 3/4 นิ้ว', 'หินเกล็ดสำหรับงานคอนกรีต', 'ปูน ทราย หิน', 550.00, 450.00, 80, 'คิว', 'Standard', TRUE, FALSE),

-- เหล็กและโลหะ
('STEEL-001', 'เหล็กเส้นข้ออ้อย RB6 ขนาด 6 มม.', 'เหล็กเส้นข้ออ้อย คุณภาพดี มาตรฐาน มอก.', 'เหล็กและโลหะ', 25.00, 18.00, 1000, 'เส้น', 'มิตรผล', TRUE, TRUE),
('STEEL-002', 'เหล็กเส้นข้ออ้อย RB9 ขนาด 9 มม.', 'เหล็กเส้นข้ออ้อย มอก. สำหรับโครงสร้าง', 'เหล็กและโลหะ', 48.00, 35.00, 800, 'เส้น', 'มิตรผล', TRUE, TRUE),
('STEEL-003', 'เหล็กฉาก 1.5x1.5 นิ้ว', 'เหล็กฉากสำหรับโครงสร้าง', 'เหล็กและโลหะ', 185.00, 140.00, 200, 'เส้น', 'TATA', TRUE, FALSE),

-- ท่อและข้อต่อ
('PIPE-001', 'ท่อ PVC 1/2 นิ้ว (สีฟ้า)', 'ท่อ PVC สำหรับน้ำประปา', 'ท่อและข้อต่อ', 32.00, 22.00, 500, 'เส้น', 'SCG', TRUE, TRUE),
('PIPE-002', 'ท่อ PVC 1 นิ้ว (สีฟ้า)', 'ท่อ PVC สำหรับน้ำประปา', 'ท่อและข้อต่อ', 68.00, 48.00, 400, 'เส้น', 'SCG', TRUE, TRUE),
('FITTING-001', 'ข้อต่อตรง 1/2 นิ้ว', 'ข้อต่อตรง PVC', 'ท่อและข้อต่อ', 5.00, 3.00, 1000, 'ตัว', 'SCG', TRUE, FALSE),
('FITTING-002', 'ข้อโค้ง 90 องศา 1/2 นิ้ว', 'ข้อโค้ง 90 องศา PVC', 'ท่อและข้อต่อ', 8.00, 5.00, 800, 'ตัว', 'SCG', TRUE, FALSE),

-- เครื่องมือช่าง
('TOOL-001', 'สว่านไฟฟ้า BOSCH GSB 550', 'สว่านไฟฟ้าไร้สาย แรง ทนทาน', 'เครื่องมือช่าง', 2850.00, 2200.00, 15, 'เครื่อง', 'BOSCH', TRUE, TRUE),
('TOOL-002', 'เลื่อยวงเดือน', 'เลื่อยวงเดือนคุณภาพดี ตัดไม้ได้คม', 'เครื่องมือช่าง', 250.00, 150.00, 50, 'อัน', 'Standard', TRUE, FALSE),
('TOOL-003', 'ค้อนหงอนบัว 16 ออนซ์', 'ค้อนหงอนบัวคุณภาพดี ทนทาน', 'เครื่องมือช่าง', 180.00, 120.00, 60, 'อัน', 'Standard', TRUE, FALSE);

-- ===================================================
-- ข้อมูลตัวอย่าง - Services
-- ===================================================
INSERT INTO services (service_code, service_name, description, category, base_price, unit, duration, is_active) VALUES
('SRV-001', 'งานทาสีภายใน', 'บริการทาสีภายในบ้าน คอนโด อาคาร โดยช่างมืออาชีพ', 'งานทาสี', 150.00, 'ตารางเมตร', '1-3 วัน', TRUE),
('SRV-002', 'งานทาสีภายนอก', 'บริการทาสีภายนอกอาคาร ทนทาน กันน้ำ', 'งานทาสี', 180.00, 'ตารางเมตร', '2-5 วัน', TRUE),
('SRV-003', 'งานติดตั้งยิปซั่ม', 'บริการติดตั้งฝ้ายิปซั่ม ฝ้าทีบาร์ โดยช่างผู้เชี่ยวชาญ', 'งานฝ้า-ผนัง', 250.00, 'ตารางเมตร', '3-7 วัน', TRUE),
('SRV-004', 'งานก่อสร้างทั่วไป', 'บริการรับเหมาก่อสร้างทั่วไป ต่อเติม ซ่อมแซม', 'งานก่อสร้าง', 500.00, 'งาน', 'ตามโครงการ', TRUE),
('SRV-005', 'งานประปา-สุขภัณฑ์', 'บริการติดตั้งและซ่อมระบบประปา อ่างล้างหน้า ชักโครก', 'งานประปา', 350.00, 'งาน', '1-2 วัน', TRUE),
('SRV-006', 'งานไฟฟ้า', 'บริการติดตั้งและซ่อมระบบไฟฟ้า ปลั๊ก สวิตช์', 'งานไฟฟ้า', 400.00, 'งาน', '1-2 วัน', TRUE),
('SRV-007', 'งานปูกระเบื้อง', 'บริการปูกระเบื้องพื้น กระเบื้องผนัง โดยช่างมืออาชีพ', 'งานปูพื้น', 200.00, 'ตารางเมตร', '3-5 วัน', TRUE),
('SRV-008', 'งานเทพื้นคอนกรีต', 'บริการเทพื้นคอนกรีต ลานจอดรถ พื้นโรงงาน', 'งานคอนกรีต', 350.00, 'ตารางเมตร', '5-10 วัน', TRUE);

-- ===================================================
-- สร้าง Schema Complete!
-- ===================================================