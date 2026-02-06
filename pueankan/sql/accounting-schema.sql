-- ============================================
-- ไฟล์: sql/accounting-schema.sql
-- คำอธิบาย: Database Schema สำหรับระบบบัญชี
-- วัตถุประสงค์: สร้างตารางบัญชีครบวงจร + Security
-- ============================================

USE hardware_store;

-- ============================================
-- 1. ตารางผังบัญชี (Chart of Accounts)
-- ============================================
CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    account_subtype VARCHAR(100),
    parent_account_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_system_account BOOLEAN DEFAULT FALSE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL,
    INDEX idx_account_code (account_code),
    INDEX idx_account_type (account_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. ตารางงวดบัญชี (Accounting Periods)
-- ============================================
CREATE TABLE IF NOT EXISTS accounting_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(50) NOT NULL,
    fiscal_year INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('open', 'closed', 'locked') DEFAULT 'open',
    closed_at TIMESTAMP NULL,
    closed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_period (fiscal_year, start_date),
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. ตารางรายการบัญชี (Journal Entries)
-- ============================================
CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    entry_date DATE NOT NULL,
    entry_type ENUM('manual', 'sales', 'purchase', 'payment', 'receipt', 'adjustment', 'depreciation', 'closing') NOT NULL,
    period_id INT NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    description TEXT NOT NULL,
    total_debit DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_credit DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'pending', 'approved', 'posted', 'void') DEFAULT 'draft',
    posted_at TIMESTAMP NULL,
    posted_by INT NULL,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    voided_at TIMESTAMP NULL,
    voided_by INT NULL,
    void_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (period_id) REFERENCES accounting_periods(id),
    INDEX idx_entry_number (entry_number),
    INDEX idx_entry_date (entry_date),
    INDEX idx_period (period_id),
    INDEX idx_status (status),
    INDEX idx_reference (reference_type, reference_id),
    
    -- Constraint: Debit = Credit
    CONSTRAINT chk_balanced CHECK (total_debit = total_credit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. ตารางรายละเอียดรายการบัญชี (Journal Entry Lines)
-- ============================================
CREATE TABLE IF NOT EXISTS journal_entry_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    line_number INT NOT NULL,
    account_id INT NOT NULL,
    description TEXT,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
    INDEX idx_entry (entry_id),
    INDEX idx_account (account_id),
    
    -- Constraint: ไม่สามารถมี Debit และ Credit พร้อมกัน
    CONSTRAINT chk_debit_or_credit CHECK (
        (debit_amount > 0 AND credit_amount = 0) OR
        (debit_amount = 0 AND credit_amount > 0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. ตารางลูกหนี้ (Accounts Receivable)
-- ============================================
CREATE TABLE IF NOT EXISTS accounts_receivable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    customer_name VARCHAR(255) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    payment_terms VARCHAR(50) DEFAULT 'cash',
    subtotal DECIMAL(15,2) NOT NULL,
    vat_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'issued', 'partial_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    journal_entry_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. ตารางเจ้าหนี้ (Accounts Payable)
-- ============================================
CREATE TABLE IF NOT EXISTS accounts_payable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(50) UNIQUE NOT NULL,
    vendor_id INT,
    vendor_name VARCHAR(255) NOT NULL,
    bill_date DATE NOT NULL,
    due_date DATE NOT NULL,
    payment_terms VARCHAR(50) DEFAULT 'cash',
    subtotal DECIMAL(15,2) NOT NULL,
    vat_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'received', 'partial_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    journal_entry_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    INDEX idx_bill_number (bill_number),
    INDEX idx_vendor (vendor_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ตารางทรัพย์สิน (Fixed Assets)
-- ============================================
CREATE TABLE IF NOT EXISTS fixed_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) UNIQUE NOT NULL,
    asset_name VARCHAR(255) NOT NULL,
    asset_category VARCHAR(100),
    purchase_date DATE NOT NULL,
    purchase_price DECIMAL(15,2) NOT NULL,
    salvage_value DECIMAL(15,2) DEFAULT 0,
    useful_life_years INT NOT NULL,
    depreciation_method ENUM('straight_line', 'declining_balance', 'sum_of_years') DEFAULT 'straight_line',
    accumulated_depreciation DECIMAL(15,2) DEFAULT 0,
    book_value DECIMAL(15,2) NOT NULL,
    location VARCHAR(255),
    status ENUM('active', 'disposed', 'sold') DEFAULT 'active',
    disposal_date DATE NULL,
    disposal_amount DECIMAL(15,2) NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_asset_code (asset_code),
    INDEX idx_status (status),
    INDEX idx_category (asset_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. ตารางค่าเสื่อมราคา (Depreciation Logs)
-- ============================================
CREATE TABLE IF NOT EXISTS depreciation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    period_id INT NOT NULL,
    depreciation_date DATE NOT NULL,
    depreciation_amount DECIMAL(15,2) NOT NULL,
    accumulated_depreciation DECIMAL(15,2) NOT NULL,
    book_value DECIMAL(15,2) NOT NULL,
    journal_entry_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    
    FOREIGN KEY (asset_id) REFERENCES fixed_assets(id) ON DELETE CASCADE,
    FOREIGN KEY (period_id) REFERENCES accounting_periods(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    INDEX idx_asset (asset_id),
    INDEX idx_period (period_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ตารางบันทึกการตรวจสอบ (Audit Logs)
-- ============================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    details TEXT,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_table (table_name),
    INDEX idx_created (created_at),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. ตารางการตั้งค่า (Settings)
-- ============================================
CREATE TABLE IF NOT EXISTS accounting_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_system BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ข้อมูลเริ่มต้น: ผังบัญชีมาตรฐาน
-- ============================================

-- สินทรัพย์ (Assets)
INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_subtype, is_system_account) VALUES
('1-1000', 'เงินสด', 'asset', 'current_asset', TRUE),
('1-1100', 'เงินฝากธนาคาร', 'asset', 'current_asset', TRUE),
('1-1200', 'ลูกหนี้การค้า', 'asset', 'current_asset', TRUE),
('1-1300', 'สินค้าคงเหลือ', 'asset', 'current_asset', TRUE),
('1-2000', 'ที่ดิน อาคาร และอุปกรณ์', 'asset', 'fixed_asset', TRUE),
('1-2100', 'ค่าเสื่อมราคาสะสม', 'asset', 'fixed_asset', TRUE);

-- หนี้สิน (Liabilities)
INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_subtype, is_system_account) VALUES
('2-1000', 'เจ้าหนี้การค้า', 'liability', 'current_liability', TRUE),
('2-1100', 'ภาษีขาย (VAT)', 'liability', 'current_liability', TRUE),
('2-1200', 'ภาษีซื้อ (VAT)', 'liability', 'current_liability', TRUE),
('2-2000', 'เงินกู้ระยะยาว', 'liability', 'long_term_liability', TRUE);

-- ส่วนของเจ้าของ (Equity)
INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_subtype, is_system_account) VALUES
('3-1000', 'ทุน', 'equity', 'capital', TRUE),
('3-2000', 'กำไรสะสม', 'equity', 'retained_earnings', TRUE),
('3-3000', 'กำไร(ขาดทุน)สุทธิ', 'equity', 'net_income', TRUE);

-- รายได้ (Revenue)
INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_subtype, is_system_account) VALUES
('4-1000', 'รายได้จากการขาย', 'revenue', 'sales_revenue', TRUE),
('4-2000', 'รายได้จากบริการ', 'revenue', 'service_revenue', TRUE),
('4-9000', 'รายได้อื่นๆ', 'revenue', 'other_revenue', TRUE);

-- ค่าใช้จ่าย (Expenses)
INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_subtype, is_system_account) VALUES
('5-1000', 'ต้นทุนขาย', 'expense', 'cost_of_goods_sold', TRUE),
('5-2000', 'เงินเดือนและค่าจ้าง', 'expense', 'operating_expense', TRUE),
('5-2100', 'ค่าเช่า', 'expense', 'operating_expense', TRUE),
('5-2200', 'ค่าไฟฟ้า', 'expense', 'operating_expense', TRUE),
('5-2300', 'ค่าโทรศัพท์', 'expense', 'operating_expense', TRUE),
('5-3000', 'ค่าเสื่อมราคา', 'expense', 'depreciation', TRUE),
('5-9000', 'ค่าใช้จ่ายอื่นๆ', 'expense', 'other_expense', TRUE);

-- ============================================
-- ข้อมูลเริ่มต้น: งวดบัญชีปีแรก
-- ============================================
INSERT INTO accounting_periods (period_name, fiscal_year, start_date, end_date, status) VALUES
('มกราคม 2026', 2026, '2026-01-01', '2026-01-31', 'open'),
('กุมภาพันธ์ 2026', 2026, '2026-02-01', '2026-02-28', 'open'),
('มีนาคม 2026', 2026, '2026-03-01', '2026-03-31', 'open');

-- ============================================
-- Triggers สำหรับ Audit Trail
-- ============================================

-- Trigger: Log การเปลี่ยนแปลง Journal Entries
DELIMITER //

CREATE TRIGGER audit_journal_entry_update
AFTER UPDATE ON journal_entries
FOR EACH ROW
BEGIN
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
END//

DELIMITER ;

-- ============================================
-- สร้าง View สำหรับรายงาน
-- ============================================

-- View: General Ledger (สมุดบัญชีแยกประเภท)
CREATE OR REPLACE VIEW v_general_ledger AS
SELECT 
    jel.id,
    je.entry_number,
    je.entry_date,
    je.entry_type,
    coa.account_code,
    coa.account_name,
    coa.account_type,
    jel.description,
    jel.debit_amount,
    jel.credit_amount,
    je.status,
    je.created_at
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.entry_id = je.id
JOIN chart_of_accounts coa ON jel.account_id = coa.id
WHERE je.status = 'posted'
ORDER BY je.entry_date, je.entry_number, jel.line_number;

-- View: Trial Balance (งบทดลอง)
CREATE OR REPLACE VIEW v_trial_balance AS
SELECT 
    coa.account_code,
    coa.account_name,
    coa.account_type,
    SUM(jel.debit_amount) as total_debit,
    SUM(jel.credit_amount) as total_credit,
    SUM(jel.debit_amount) - SUM(jel.credit_amount) as balance
FROM chart_of_accounts coa
LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
LEFT JOIN journal_entries je ON jel.entry_id = je.id AND je.status = 'posted'
WHERE coa.is_active = TRUE
GROUP BY coa.id, coa.account_code, coa.account_name, coa.account_type
HAVING total_debit > 0 OR total_credit > 0
ORDER BY coa.account_code;

-- ============================================
-- Stored Procedures
-- ============================================

-- Procedure: Post Journal Entry (บันทึกรายการบัญชี)
DELIMITER //

CREATE PROCEDURE sp_post_journal_entry(
    IN p_entry_id INT,
    IN p_user_id INT
)
BEGIN
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
END//

DELIMITER ;

-- ============================================
-- Schema Complete!
-- ============================================
```

---

## 🎯 สรุป

ระบบที่สร้างขึ้นมีความปลอดภัยและครบวงจรดังนี้:

### ✅ Security Features
- CSRF Protection
- Input Sanitization & Validation
- Role-Based Access Control
- Audit Logging
- Rate Limiting
- Secure Error Handling

### ✅ Accounting Features
- Double-Entry Bookkeeping
- Chart of Accounts
- General Ledger
- AR/AP Management
- Fixed Assets & Depreciation
- Period Closing
- Financial Reports

### ✅ Code Quality
- มี Comment อธิบายทุกไฟล์
- ระบุตำแหน่งไฟล์ชัดเจน
- ใช้ Prepared Statements ทั้งหมด
- Transaction Management
- Data Validation ทุกจุด

### 📁 ตำแหน่งไฟล์ทั้งหมด
```
/pueankan/config/security.php          # Security middleware
/pueankan/config/accounting.php        # Accounting config
/pueankan/sql/accounting-schema.sql    # Database schema