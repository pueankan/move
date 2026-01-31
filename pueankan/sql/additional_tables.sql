-- ตารางเพิ่มเติมสำหรับระบบที่สมบูรณ์

USE hardware_store;

-- ตารางผู้ใช้งาน
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางบันทึกกิจกรรม
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางคำสั่งซื้อ
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(255),
    shipping_address TEXT,
    total_amount DECIMAL(12,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    vat DECIMAL(10,2) DEFAULT 0,
    grand_total DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash', 'transfer', 'credit_card', 'qr_payment') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'preparing', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_user (user_id),
    INDEX idx_status (order_status),
    INDEX idx_payment (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางรายการสั่งซื้อ
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'ชิ้น',
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางรีวิวสินค้า
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางหมวดหมู่สินค้า (ถ้าต้องการจัดการแยก)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางการตั้งค่าระบบ
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางโปรโมชั่น/ส่วนลด
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2),
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    valid_from DATE,
    valid_until DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_dates (valid_from, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่มคอลัมน์ category_id ในตาราง products
ALTER TABLE products ADD COLUMN category_id INT AFTER category;
ALTER TABLE products ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;
ALTER TABLE products ADD COLUMN image_path VARCHAR(255) AFTER image_url;
ALTER TABLE products ADD COLUMN sku VARCHAR(100) UNIQUE AFTER id;

-- ข้อมูลตัวอย่าง - Users (Admin)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@hardware.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin');
-- Password: password

-- ข้อมูลตัวอย่าง - Categories
INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('สีและอุปกรณ์ทาสี', 'paint-supplies', 'สีทาบ้าน สีรองพื้น แปรง ลูกกลิ้ง', 'fa-paint-roller', 1),
('ปูน ทราย หิน', 'cement-sand-gravel', 'ปูนซีเมนต์ ทรายก่อสร้าง หินเกล็ด', 'fa-boxes', 2),
('ไม้และวัสดุไม้', 'wood-materials', 'คานไม้ ไม้อัด ไม้แปรรูป', 'fa-tree', 3),
('อิฐ บล็อก กระเบื้อง', 'bricks-tiles', 'อิฐมวลเบา บล็อกคอนกรีต กระเบื้อง', 'fa-border-all', 4),
('เหล็กและโลหะ', 'steel-metal', 'เหล็กเส้น เหล็กรูปพรรณ สังกะสี', 'fa-industry', 5),
('ท่อและข้อต่อ', 'pipes-fittings', 'ท่อ PVC ท่อเหล็ก ข้อต่อ', 'fa-tools', 6),
('ไฟฟ้าและแสงสว่าง', 'electrical', 'สายไฟ ปลั๊ก สวิตช์ หลักดไฟ', 'fa-lightbulb', 7),
('สุขภัณฑ์', 'sanitary', 'ห้องน้ำ อ่างล้างหน้า ก็อกน้ำ', 'fa-sink', 8),
('เครื่องมือช่าง', 'tools', 'สว่าน เลื่อย ค้อน เครื่องมือไฟฟ้า', 'fa-toolbox', 9),
('อุปกรณ์ยึดและตรึง', 'fasteners', 'สกรู ตะปู น็อต โบลท์', 'fa-wrench', 10);

-- ข้อมูลตัวอย่าง - Settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'ร้านฮาร์ดแวร์และวัสดุก่อสร้าง', 'text', 'ชื่อร้าน'),
('site_email', 'info@hardware.com', 'text', 'อีเมลร้าน'),
('site_phone', '081-234-5678', 'text', 'เบอร์โทรร้าน'),
('vat_percent', '7', 'number', 'ภาษีมูลค่าเพิ่ม (%)'),
('shipping_base_cost', '50', 'number', 'ค่าส่งขั้นต่ำ'),
('min_order_amount', '200', 'number', 'ยอดสั่งซื้อขั้นต่ำ'),
('currency', 'THB', 'text', 'สกุลเงิน'),
('items_per_page', '12', 'number', 'จำนวนสินค้าต่อหน้า');

-- ข้อมูลตัวอย่าง - Promotions
INSERT INTO promotions (code, description, discount_type, discount_value, min_purchase, valid_from, valid_until, is_active) VALUES
('WELCOME10', 'ส่วนลด 10% สำหรับลูกค้าใหม่', 'percentage', 10.00, 500.00, '2024-01-01', '2024-12-31', TRUE),
('FREESHIP', 'ฟรีค่าจัดส่ง สำหรับยอดซื้อ 1000 บาทขึ้นไป', 'fixed_amount', 50.00, 1000.00, '2024-01-01', '2024-12-31', TRUE);