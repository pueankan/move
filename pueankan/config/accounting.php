<?php
/**
 * ============================================
 * ไฟล์: config/accounting.php
 * คำอธิบาย: Accounting System Configuration
 * วัตถุประสงค์: กำหนดค่าพื้นฐานระบบบัญชี
 * ============================================
 */

// ตั้งค่าพื้นฐาน
define('COMPANY_NAME', 'ร้านฮาร์ดแวร์และวัสดุก่อสร้าง "เพื่อนกัน"');
define('COMPANY_TAX_ID', 'X-XXXX-XXXXX-XX-X'); // เลขประจำตัวผู้เสียภาษี
define('ACCOUNTING_CURRENCY', 'THB');
define('ACCOUNTING_DECIMAL_PLACES', 2);

// ตั้งค่า VAT
define('VAT_RATE', 7); // %
define('VAT_ENABLED', true);

// ตั้งค่างวดบัญชี
define('FISCAL_YEAR_START_MONTH', 1); // มกราคม
define('FISCAL_YEAR_START_DAY', 1);

// ประเภทบัญชีหลัก (Account Types)
define('ACCOUNT_TYPES', [
    'asset' => 'สินทรัพย์',
    'liability' => 'หนี้สิน',
    'equity' => 'ส่วนของเจ้าของ',
    'revenue' => 'รายได้',
    'expense' => 'ค่าใช้จ่าย'
]);

// ประเภทรายการบัญชี (Entry Types)
define('ENTRY_TYPES', [
    'manual' => 'บันทึกทั่วไป',
    'sales' => 'ขาย',
    'purchase' => 'ซื้อ',
    'payment' => 'จ่ายเงิน',
    'receipt' => 'รับเงิน',
    'adjustment' => 'ปรับปรุง',
    'depreciation' => 'ค่าเสื่อมราคา',
    'closing' => 'ปิดงวด'
]);

// สถานะงวดบัญชี
define('PERIOD_STATUS', [
    'open' => 'เปิดอยู่',
    'closed' => 'ปิดแล้ว',
    'locked' => 'ล็อก'
]);

// วิธีคิดค่าเสื่อมราคา
define('DEPRECIATION_METHODS', [
    'straight_line' => 'เส้นตรง',
    'declining_balance' => 'ยอดลดลง',
    'sum_of_years' => 'ผลรวมจำนวนปี'
]);

/**
 * Account Code Structure
 * รหัสบัญชี: X-XXXX
 * หลักที่ 1: ประเภทบัญชีหลัก (1=สินทรัพย์, 2=หนี้สิน, 3=ส่วนของเจ้าของ, 4=รายได้, 5=ค่าใช้จ่าย)
 * หลักที่ 2-5: รหัสบัญชีย่อย
 */
define('ACCOUNT_CODE_PREFIX', [
    'asset' => '1',
    'liability' => '2',
    'equity' => '3',
    'revenue' => '4',
    'expense' => '5'
]);

/**
 * Payment Terms (เงื่อนไขการชำระเงิน)
 */
define('PAYMENT_TERMS', [
    'cash' => 'เงินสด',
    'credit_7' => 'เครดิต 7 วัน',
    'credit_15' => 'เครดิต 15 วัน',
    'credit_30' => 'เครดิต 30 วัน',
    'credit_60' => 'เครดิต 60 วัน',
    'credit_90' => 'เครดิต 90 วัน'
]);

/**
 * Transaction Status
 */
define('TRANSACTION_STATUS', [
    'draft' => 'ฉบับร่าง',
    'pending' => 'รอดำเนินการ',
    'approved' => 'อนุมัติแล้ว',
    'posted' => 'บันทึกแล้ว',
    'void' => 'ยกเลิก'
]);

/**
 * ฟังก์ชันช่วยเหลือ
 */

// Format currency
function format_accounting_amount($amount, $showSymbol = true) {
    $formatted = number_format($amount, ACCOUNTING_DECIMAL_PLACES);
    return $showSymbol ? ACCOUNTING_CURRENCY . ' ' . $formatted : $formatted;
}

// Parse accounting date
function parse_accounting_date($date) {
    return date('Y-m-d', strtotime($date));
}

// Get current fiscal year
function get_current_fiscal_year() {
    $today = new DateTime();
    $fiscalStart = new DateTime(date('Y') . '-' . FISCAL_YEAR_START_MONTH . '-' . FISCAL_YEAR_START_DAY);
    
    if ($today < $fiscalStart) {
        return (int)date('Y') - 1;
    }
    return (int)date('Y');
}

// Get fiscal year start/end
function get_fiscal_year_period($year = null) {
    if ($year === null) {
        $year = get_current_fiscal_year();
    }
    
    $start = new DateTime("{$year}-" . FISCAL_YEAR_START_MONTH . "-" . FISCAL_YEAR_START_DAY);
    $end = clone $start;
    $end->modify('+1 year -1 day');
    
    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d'),
        'year' => $year
    ];
}

// Validate account code format
function validate_account_code($code) {
    return preg_match('/^[1-5]-\d{4}$/', $code);
}

// Calculate VAT
function calculate_vat($amount, $inclusive = false) {
    if (!VAT_ENABLED) {
        return [
            'base' => $amount,
            'vat' => 0,
            'total' => $amount
        ];
    }
    
    $rate = VAT_RATE / 100;
    
    if ($inclusive) {
        // ราคารวม VAT แล้ว
        $base = $amount / (1 + $rate);
        $vat = $amount - $base;
        return [
            'base' => round($base, ACCOUNTING_DECIMAL_PLACES),
            'vat' => round($vat, ACCOUNTING_DECIMAL_PLACES),
            'total' => $amount
        ];
    } else {
        // ราคายังไม่รวม VAT
        $vat = $amount * $rate;
        $total = $amount + $vat;
        return [
            'base' => $amount,
            'vat' => round($vat, ACCOUNTING_DECIMAL_PLACES),
            'total' => round($total, ACCOUNTING_DECIMAL_PLACES)
        ];
    }
}