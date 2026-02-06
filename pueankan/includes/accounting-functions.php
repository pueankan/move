 <?php
/**
 * ============================================
 * ไฟล์: includes/accounting-functions.php
 * คำอธิบาย: ฟังก์ชันหลักสำหรับระบบบัญชี
 * วัตถุประสงค์: ประมวลผลทางบัญชีและสร้างรายการ
 * ============================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/accounting.php';

/**
 * ============================================
 * CHART OF ACCOUNTS FUNCTIONS
 * ============================================
 */

/**
 * สร้างบัญชีใหม่
 */
function create_account($data) {
    global $pdo;
    
    // Validate
    if (!validate_account_code($data['account_code'])) {
        throw new Exception('รหัสบัญชีไม่ถูกต้อง');
    }
    
    if (!Validate::required($data['account_name'])) {
        throw new Exception('กรุณาระบุชื่อบัญชี');
    }
    
    if (!Validate::enum($data['account_type'], array_keys(ACCOUNT_TYPES))) {
        throw new Exception('ประเภทบัญชีไม่ถูกต้อง');
    }
    
    try {
        $sql = "INSERT INTO chart_of_accounts 
                (account_code, account_name, account_type, account_subtype, 
                 parent_account_id, description, created_by) 
                VALUES (:code, :name, :type, :subtype, :parent, :desc, :user)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':code' => Sanitize::string($data['account_code']),
            ':name' => Sanitize::string($data['account_name']),
            ':type' => $data['account_type'],
            ':subtype' => Sanitize::string($data['account_subtype'] ?? ''),
            ':parent' => $data['parent_account_id'] ?? null,
            ':desc' => Sanitize::string($data['description'] ?? ''),
            ':user' => $_SESSION['user_id']
        ]);
        
        if ($result) {
            $accountId = $pdo->lastInsertId();
            AuditLog::log('account_created', "Account: {$data['account_code']} - {$data['account_name']}", 'info');
            return $accountId;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Create Account Error: " . $e->getMessage());
        throw new Exception('ไม่สามารถสร้างบัญชีได้');
    }
}

/**
 * ดึงรายการบัญชีทั้งหมด
 */
function get_accounts($filters = []) {
    global $pdo;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE 1=1";
    $params = [];
    
    if (!empty($filters['account_type'])) {
        $sql .= " AND account_type = :type";
        $params[':type'] = $filters['account_type'];
    }
    
    if (isset($filters['is_active'])) {
        $sql .= " AND is_active = :active";
        $params[':active'] = $filters['is_active'];
    }
    
    $sql .= " ORDER BY account_code";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ดึงข้อมูลบัญชีตาม ID
 */
function get_account_by_id($accountId) {
    global $pdo;
    
    $sql = "SELECT * FROM chart_of_accounts WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $accountId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ============================================
 * ACCOUNTING PERIOD FUNCTIONS
 * ============================================
 */

/**
 * ดึงงวดบัญชีปัจจุบัน
 */
function get_current_period() {
    global $pdo;
    
    $today = date('Y-m-d');
    
    $sql = "SELECT * FROM accounting_periods 
            WHERE start_date <= :today 
            AND end_date >= :today 
            AND status = 'open'
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':today' => $today]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ตรวจสอบว่างวดบัญชีเปิดอยู่หรือไม่
 */
function is_period_open($date) {
    global $pdo;
    
    $sql = "SELECT status FROM accounting_periods 
            WHERE start_date <= :date 
            AND end_date >= :date 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $date]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $period && $period['status'] === 'open';
}

/**
 * ปิดงวดบัญชี
 */
function close_period($periodId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // ตรวจสอบว่ามีรายการที่ยังไม่ Posted หรือไม่
        $sql = "SELECT COUNT(*) as count FROM journal_entries 
                WHERE period_id = :period_id 
                AND status != 'posted'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':period_id' => $periodId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception('ยังมีรายการที่ยังไม่ได้ Post กรุณา Post ให้ครบก่อน');
        }
        
        // ปิดงวด
        $sql = "UPDATE accounting_periods 
                SET status = 'closed', 
                    closed_at = NOW(), 
                    closed_by = :user_id 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $periodId,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $pdo->commit();
        
        AuditLog::log('period_closed', "Period ID: {$periodId}", 'critical');
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Close Period Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * ============================================
 * JOURNAL ENTRY FUNCTIONS
 * ============================================
 */

/**
 * สร้างรายการบัญชี (Journal Entry)
 */
function create_journal_entry($data) {
    global $pdo;
    
    // Validate
    if (!Validate::date($data['entry_date'])) {
        throw new Exception('วันที่ไม่ถูกต้อง');
    }
    
    if (!is_period_open($data['entry_date'])) {
        throw new Exception('ไม่สามารถบันทึกได้ งวดบัญชีปิดแล้ว');
    }
    
    if (empty($data['lines']) || count($data['lines']) < 2) {
        throw new Exception('ต้องมีรายการอย่างน้อย 2 รายการ');
    }
    
    // คำนวณยอด Debit และ Credit
    $totalDebit = 0;
    $totalCredit = 0;
    
    foreach ($data['lines'] as $line) {
        $totalDebit += floatval($line['debit_amount'] ?? 0);
        $totalCredit += floatval($line['credit_amount'] ?? 0);
    }
    
    // ตรวจสอบว่า Debit = Credit
    if (abs($totalDebit - $totalCredit) > 0.01) {
        throw new Exception('ยอด Debit (' . $totalDebit . ') ไม่เท่ากับ Credit (' . $totalCredit . ')');
    }
    
    try {
        $pdo->beginTransaction();
        
        // สร้างเลขที่รายการ
        $entryNumber = generate_entry_number($data['entry_type']);
        
        // หางวดบัญชี
        $period = get_current_period();
        if (!$period) {
            throw new Exception('ไม่พบงวดบัญชีที่เปิดอยู่');
        }
        
        // Insert Journal Entry Header
        $sql = "INSERT INTO journal_entries 
                (entry_number, entry_date, entry_type, period_id, 
                 reference_type, reference_id, description, 
                 total_debit, total_credit, status, created_by) 
                VALUES (:number, :date, :type, :period, :ref_type, :ref_id, 
                        :desc, :debit, :credit, :status, :user)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':number' => $entryNumber,
            ':date' => $data['entry_date'],
            ':type' => $data['entry_type'],
            ':period' => $period['id'],
            ':ref_type' => $data['reference_type'] ?? null,
            ':ref_id' => $data['reference_id'] ?? null,
            ':desc' => Sanitize::string($data['description']),
            ':debit' => $totalDebit,
            ':credit' => $totalCredit,
            ':status' => 'draft',
            ':user' => $_SESSION['user_id']
        ]);
        
        $entryId = $pdo->lastInsertId();
        
        // Insert Journal Entry Lines
        $lineNumber = 1;
        foreach ($data['lines'] as $line) {
            $sql = "INSERT INTO journal_entry_lines 
                    (entry_id, line_number, account_id, description, 
                     debit_amount, credit_amount) 
                    VALUES (:entry_id, :line_num, :account_id, :desc, :debit, :credit)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':entry_id' => $entryId,
                ':line_num' => $lineNumber++,
                ':account_id' => $line['account_id'],
                ':desc' => Sanitize::string($line['description'] ?? ''),
                ':debit' => floatval($line['debit_amount'] ?? 0),
                ':credit' => floatval($line['credit_amount'] ?? 0)
            ]);
        }
        
        $pdo->commit();
        
        AuditLog::logAccountingEntry(
            'journal_entry_created',
            $totalDebit,
            "Entry: {$entryNumber} - {$data['description']}"
        );
        
        return [
            'success' => true,
            'entry_id' => $entryId,
            'entry_number' => $entryNumber
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create Journal Entry Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * สร้างเลขที่รายการบัญชี
 */
function generate_entry_number($type) {
    global $pdo;
    
    $prefix = match($type) {
        'sales' => 'JV-S',
        'purchase' => 'JV-P',
        'payment' => 'JV-PY',
        'receipt' => 'JV-RC',
        'adjustment' => 'JV-ADJ',
        'depreciation' => 'JV-DEP',
        'closing' => 'JV-CL',
        default => 'JV'
    };
    
    $date = date('Ymd');
    
    // หาเลขที่ล่าสุดของวันนี้
    $sql = "SELECT entry_number FROM journal_entries 
            WHERE entry_number LIKE :pattern 
            ORDER BY id DESC LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pattern' => $prefix . '-' . $date . '-%']);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last) {
        // Extract running number
        $parts = explode('-', $last['entry_number']);
        $runningNumber = intval(end($parts)) + 1;
    } else {
        $runningNumber = 1;
    }
    
    return $prefix . '-' . $date . '-' . str_pad($runningNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Post รายการบัญชี (อนุมัติและบันทึก)
 */
function post_journal_entry($entryId) {
    global $pdo;
    
    try {
        // เรียกใช้ Stored Procedure
        $sql = "CALL sp_post_journal_entry(:entry_id, :user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':entry_id' => $entryId,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        AuditLog::logAccountingEntry(
            'journal_entry_posted',
            0,
            "Entry ID: {$entryId} posted"
        );
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Post Journal Entry Error: " . $e->getMessage());
        throw new Exception($e->getMessage());
    }
}

/**
 * ยกเลิกรายการบัญชี
 */
function void_journal_entry($entryId, $reason) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // ตรวจสอบสถานะ
        $sql = "SELECT status, period_id FROM journal_entries WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $entryId]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            throw new Exception('ไม่พบรายการบัญชี');
        }
        
        if ($entry['status'] === 'void') {
            throw new Exception('รายการนี้ถูกยกเลิกไปแล้ว');
        }
        
        // ตรวจสอบงวดบัญชี
        $sql = "SELECT status FROM accounting_periods WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $entry['period_id']]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($period['status'] === 'locked') {
            throw new Exception('ไม่สามารถยกเลิกได้ งวดบัญชีถูกล็อก');
        }
        
        // ยกเลิก
        $sql = "UPDATE journal_entries 
                SET status = 'void', 
                    voided_at = NOW(), 
                    voided_by = :user_id, 
                    void_reason = :reason 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $entryId,
            ':user_id' => $_SESSION['user_id'],
            ':reason' => Sanitize::string($reason)
        ]);
        
        $pdo->commit();
        
        AuditLog::log('journal_entry_voided', "Entry ID: {$entryId}, Reason: {$reason}", 'warning');
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Void Journal Entry Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * ============================================
 * ACCOUNTS RECEIVABLE FUNCTIONS
 * ============================================
 */

/**
 * สร้างใบแจ้งหนี้ (Invoice)
 */
function create_invoice($data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // คำนวณ VAT
        $vatCalc = calculate_vat($data['subtotal'], false);
        
        // สร้างเลขที่ใบแจ้งหนี้
        $invoiceNumber = generate_invoice_number();
        
        // Insert Invoice
        $sql = "INSERT INTO accounts_receivable 
                (invoice_number, customer_id, customer_name, invoice_date, 
                 due_date, payment_terms, subtotal, vat_amount, total_amount, 
                 balance, status, created_by) 
                VALUES (:inv_num, :cust_id, :cust_name, :inv_date, :due_date, 
                        :terms, :subtotal, :vat, :total, :balance, :status, :user)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':inv_num' => $invoiceNumber,
            ':cust_id' => $data['customer_id'] ?? null,
            ':cust_name' => Sanitize::string($data['customer_name']),
            ':inv_date' => $data['invoice_date'],
            ':due_date' => $data['due_date'],
            ':terms' => $data['payment_terms'] ?? 'cash',
            ':subtotal' => $vatCalc['base'],
            ':vat' => $vatCalc['vat'],
            ':total' => $vatCalc['total'],
            ':balance' => $vatCalc['total'],
            ':status' => 'issued',
            ':user' => $_SESSION['user_id']
        ]);
        
        $invoiceId = $pdo->lastInsertId();
        
        // สร้าง Journal Entry สำหรับใบแจ้งหนี้
        $journalData = [
            'entry_date' => $data['invoice_date'],
            'entry_type' => 'sales',
            'reference_type' => 'invoice',
            'reference_id' => $invoiceId,
            'description' => "ขายสินค้า/บริการ - {$data['customer_name']}",
            'lines' => [
                [
                    'account_id' => get_account_id_by_code('1-1200'), // ลูกหนี้
                    'description' => "Invoice: {$invoiceNumber}",
                    'debit_amount' => $vatCalc['total'],
                    'credit_amount' => 0
                ],
                [
                    'account_id' => get_account_id_by_code('4-1000'), // รายได้จากการขาย
                    'description' => "Invoice: {$invoiceNumber}",
                    'debit_amount' => 0,
                    'credit_amount' => $vatCalc['base']
                ],
                [
                    'account_id' => get_account_id_by_code('2-1100'), // ภาษีขาย
                    'description' => "Invoice: {$invoiceNumber}",
                    'debit_amount' => 0,
                    'credit_amount' => $vatCalc['vat']
                ]
            ]
        ];
        
        $journalResult = create_journal_entry($journalData);
        
        // อัพเดท journal_entry_id ในใบแจ้งหนี้
        $sql = "UPDATE accounts_receivable SET journal_entry_id = :je_id WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':je_id' => $journalResult['entry_id'],
            ':id' => $invoiceId
        ]);
        
        // Post Journal Entry ทันที
        post_journal_entry($journalResult['entry_id']);
        
        $pdo->commit();
        
        AuditLog::logAccountingEntry(
            'invoice_created',
            $vatCalc['total'],
            "Invoice: {$invoiceNumber}"
        );
        
        return [
            'success' => true,
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoiceNumber
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create Invoice Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * สร้างเลขที่ใบแจ้งหนี้
 */
function generate_invoice_number() {
    global $pdo;
    
    $prefix = 'INV';
    $date = date('Ym');
    
    $sql = "SELECT invoice_number FROM accounts_receivable 
            WHERE invoice_number LIKE :pattern 
            ORDER BY id DESC LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pattern' => $prefix . '-' . $date . '-%']);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last) {
        $parts = explode('-', $last['invoice_number']);
        $runningNumber = intval(end($parts)) + 1;
    } else {
        $runningNumber = 1;
    }
    
    return $prefix . '-' . $date . '-' . str_pad($runningNumber, 5, '0', STR_PAD_LEFT);
}

/**
 * ============================================
 * HELPER FUNCTIONS
 * ============================================
 */

/**
 * หา Account ID จาก Account Code
 */
function get_account_id_by_code($accountCode) {
    global $pdo;
    
    $sql = "SELECT id FROM chart_of_accounts WHERE account_code = :code LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':code' => $accountCode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['id'] : null;
}

/**
 * ดึงยอดคงเหลือบัญชี
 */
function get_account_balance($accountId, $asOfDate = null) {
    global $pdo;
    
    $asOfDate = $asOfDate ?? date('Y-m-d');
    
    $sql = "SELECT 
                COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                COALESCE(SUM(jel.credit_amount), 0) as total_credit
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.entry_id = je.id
            WHERE jel.account_id = :account_id
            AND je.entry_date <= :as_of_date
            AND je.status = 'posted'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':account_id' => $accountId,
        ':as_of_date' => $asOfDate
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total_debit'] - $result['total_credit'];
}

/**
 * ดึง Trial Balance
 */
function get_trial_balance($asOfDate = null) {
    global $pdo;
    
    $asOfDate = $asOfDate ?? date('Y-m-d');
    
    $sql = "SELECT * FROM v_trial_balance";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}