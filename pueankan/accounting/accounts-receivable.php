<?php
/**
 * ============================================
 * ไฟล์: accounting/accounts-receivable.php
 * คำอธิบาย: จัดการลูกหนี้การค้า (Accounts Receivable)
 * วัตถุประสงค์: สร้าง ติดตาม และรับชำระลูกหนี้
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.view');

$message = '';
$messageType = '';

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request';
        $messageType = 'danger';
    } else {
        try {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'receive_payment':
                    if (!AccessControl::check('accounting.create')) {
                        throw new Exception('คุณไม่มีสิทธิ์รับชำระเงิน');
                    }
                    
                    $invoiceId = intval($_POST['invoice_id']);
                    $paymentAmount = floatval($_POST['payment_amount']);
                    $paymentDate = $_POST['payment_date'];
                    
                    // ดึงข้อมูลใบแจ้งหนี้
                    $sql = "SELECT * FROM accounts_receivable WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $invoiceId]);
                    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$invoice) {
                        throw new Exception('ไม่พบใบแจ้งหนี้');
                    }
                    
                    if ($paymentAmount > $invoice['balance']) {
                        throw new Exception('ยอดชำระเกินกว่ายอดคงค้าง');
                    }
                    
                    $pdo->beginTransaction();
                    
                    // อัพเดทใบแจ้งหนี้
                    $newPaidAmount = $invoice['paid_amount'] + $paymentAmount;
                    $newBalance = $invoice['balance'] - $paymentAmount;
                    $newStatus = $newBalance <= 0 ? 'paid' : 'partial_paid';
                    
                    $sql = "UPDATE accounts_receivable 
                            SET paid_amount = :paid, 
                                balance = :balance, 
                                status = :status 
                            WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':paid' => $newPaidAmount,
                        ':balance' => $newBalance,
                        ':status' => $newStatus,
                        ':id' => $invoiceId
                    ]);
                    
                    // สร้าง Journal Entry สำหรับรับชำระ
                    $journalData = [
                        'entry_date' => $paymentDate,
                        'entry_type' => 'receipt',
                        'reference_type' => 'ar_payment',
                        'reference_id' => $invoiceId,
                        'description' => "รับชำระเงินจาก {$invoice['customer_name']} - Invoice: {$invoice['invoice_number']}",
                        'lines' => [
                            [
                                'account_id' => get_account_id_by_code('1-1100'), // เงินฝากธนาคาร
                                'description' => "รับชำระ Invoice: {$invoice['invoice_number']}",
                                'debit_amount' => $paymentAmount,
                                'credit_amount' => 0
                            ],
                            [
                                'account_id' => get_account_id_by_code('1-1200'), // ลูกหนี้
                                'description' => "รับชำระ Invoice: {$invoice['invoice_number']}",
                                'debit_amount' => 0,
                                'credit_amount' => $paymentAmount
                            ]
                        ]
                    ];
                    
                    $journalResult = create_journal_entry($journalData);
                    post_journal_entry($journalResult['entry_id']);
                    
                    $pdo->commit();
                    
                    AuditLog::logAccountingEntry(
                        'ar_payment_received',
                        $paymentAmount,
                        "Invoice: {$invoice['invoice_number']}, Amount: {$paymentAmount}"
                    );
                    
                    $message = 'บันทึกการรับชำระเงินสำเร็จ';
                    $messageType = 'success';
                    break;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ดึงรายการลูกหนี้
$filterStatus = $_GET['status'] ?? '';
$filterOverdue = isset($_GET['overdue']) ? true : false;

$sql = "SELECT * FROM accounts_receivable WHERE 1=1";
$params = [];

if ($filterStatus) {
    $sql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

if ($filterOverdue) {
    $sql .= " AND due_date < CURDATE() AND status NOT IN ('paid', 'cancelled')";
}

$sql .= " ORDER BY due_date ASC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// สรุปยอด
$totalAR = 0;
$totalOverdue = 0;
$totalPaid = 0;

foreach ($invoices as $inv) {
    if ($inv['status'] !== 'cancelled') {
        $totalAR += $inv['balance'];
        
        if ($inv['status'] === 'paid') {
            $totalPaid += $inv['total_amount'];
        }
        
        if (strtotime($inv['due_date']) < time() && $inv['balance'] > 0) {
            $totalOverdue += $inv['balance'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลูกหนี้การค้า - ระบบบัญชี</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <?php include '../includes/background.php'; ?>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(10px); border-bottom: 2px solid rgba(255, 107, 0, 0.3);">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-calculator me-2"></i>
                <span class="fw-bold">ระบบบัญชี</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="accounts-receivable.php">
                            <i class="fas fa-hand-holding-usd"></i> ลูกหนี้
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="accounts-payable.php">
                            <i class="fas fa-money-check-alt"></i> เจ้าหนี้
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="page-container">
        <div class="container py-5">
            <!-- Header -->
            <div class="mb-5">
                <h1 class="display-4 fw-bold glow-text">
                    <i class="fas fa-hand-holding-usd me-3"></i>ลูกหนี้การค้า
                </h1>
                <p class="lead">จัดการและติดตามลูกหนี้ (Accounts Receivable)</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-coins fa-3x mb-3" style="color: #ffc107;"></i>
                            <h6 class="text-muted">ลูกหนี้คงค้าง</h6>
                            <h3 style="color: #ffc107;"><?php echo format_accounting_amount($totalAR); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #dc3545;"></i>
                            <h6 class="text-muted">เกินกำหนดชำระ</h6>
                            <h3 style="color: #dc3545;"><?php echo format_accounting_amount($totalOverdue); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-3x mb-3" style="color: #28a745;"></i>
                            <h6 class="text-muted">ชำระแล้ว</h6>
                            <h3 style="color: #28a745;"><?php echo format_accounting_amount($totalPaid); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="content-wrapper mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-custom">สถานะ</label>
                        <select name="status" class="form-control form-control-custom">
                            <option value="">ทั้งหมด</option>
                            <option value="issued" <?php echo $filterStatus === 'issued' ? 'selected' : ''; ?>>ออกแล้ว</option>
                            <option value="partial_paid" <?php echo $filterStatus === 'partial_paid' ? 'selected' : ''; ?>>ชำระบางส่วน</option>
                            <option value="paid" <?php echo $filterStatus === 'paid' ? 'selected' : ''; ?>>ชำระแล้ว</option>
                            <option value="overdue" <?php echo $filterStatus === 'overdue' ? 'selected' : ''; ?>>เกินกำหนด</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label-custom">แสดงเฉพาะเกินกำหนด</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="overdue" 
                                   <?php echo $filterOverdue ? 'checked' : ''; ?>>
                            <label class="form-check-label">เกินกำหนดชำระ</label>
                        </div>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-filter me-2"></i>กรอง
                        </button>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="accounts-receivable.php" class="btn btn-secondary-custom w-100">
                            <i class="fas fa-redo me-2"></i>รีเซ็ต
                        </a>
                    </div>
                </form>
            </div>

            <!-- Invoices Table -->
            <div class="content-wrapper">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th style="width: 12%;">เลขที่ใบแจ้งหนี้</th>
                                <th>ลูกค้า</th>
                                <th style="width: 10%;">วันที่</th>
                                <th style="width: 10%;">ครบกำหนด</th>
                                <th style="width: 12%;" class="text-end">ยอดรวม</th>
                                <th style="width: 12%;" class="text-end">ชำระแล้ว</th>
                                <th style="width: 12%;" class="text-end">คงค้าง</th>
                                <th style="width: 10%;">สถานะ</th>
                                <th style="width: 10%;" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    ไม่พบรายการลูกหนี้
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $invoice): ?>
                                    <?php
                                    $isOverdue = strtotime($invoice['due_date']) < time() && $invoice['balance'] > 0;
                                    $statusColor = [
                                        'draft' => 'secondary',
                                        'issued' => 'info',
                                        'partial_paid' => 'warning',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        'cancelled' => 'dark'
                                    ];
                                    $statusText = [
                                        'draft' => 'ฉบับร่าง',
                                        'issued' => 'ออกแล้ว',
                                        'partial_paid' => 'ชำระบางส่วน',
                                        'paid' => 'ชำระแล้ว',
                                        'overdue' => 'เกินกำหนด',
                                        'cancelled' => 'ยกเลิก'
                                    ];
                                    
                                    $displayStatus = $isOverdue && $invoice['status'] !== 'paid' ? 'overdue' : $invoice['status'];
                                    ?>
                                <tr <?php echo $isOverdue ? 'style="background: rgba(220,53,69,0.1);"' : ''; ?>>
                                    <td>
                                        <strong><?php echo e($invoice['invoice_number']); ?></strong>
                                    </td>
                                    <td><?php echo e($invoice['customer_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?>
                                        <?php if ($isOverdue): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> เกิน <?php echo floor((time() - strtotime($invoice['due_date'])) / 86400); ?> วัน
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo format_accounting_amount($invoice['total_amount']); ?>
                                    </td>
                                    <td class="text-end" style="color: #28a745;">
                                        <?php echo format_accounting_amount($invoice['paid_amount']); ?>
                                    </td>
                                    <td class="text-end">
                                        <strong style="color: <?php echo $invoice['balance'] > 0 ? '#ffc107' : '#28a745'; ?>">
                                            <?php echo format_accounting_amount($invoice['balance']); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $statusColor[$displayStatus] ?? 'secondary'; ?>">
                                            <?php echo $statusText[$displayStatus] ?? $displayStatus; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($invoice['balance'] > 0 && $invoice['status'] !== 'cancelled' && AccessControl::check('accounting.create')): ?>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="receivePayment(<?php echo $invoice['id']; ?>, '<?php echo e($invoice['invoice_number']); ?>', <?php echo $invoice['balance']; ?>)"
                                                    title="รับชำระเงิน">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Receive Payment Modal -->
    <div class="modal fade" id="receivePaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 107, 0, 0.3);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255, 107, 0, 0.3);">
                    <h5 class="modal-title">
                        <i class="fas fa-dollar-sign me-2" style="color: #28a745;"></i>
                        รับชำระเงิน
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <?php CSRF::insertHiddenField(); ?>
                    <input type="hidden" name="action" value="receive_payment">
                    <input type="hidden" name="invoice_id" id="paymentInvoiceId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label-custom">เลขที่ใบแจ้งหนี้</label>
                            <input type="text" class="form-control form-control-custom" id="paymentInvoiceNumber" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-custom">ยอดคงค้าง</label>
                            <input type="text" class="form-control form-control-custom" id="paymentBalance" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-custom">วันที่รับชำระ *</label>
                            <input type="date" name="payment_date" class="form-control form-control-custom" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-custom">ยอดชำระ *</label>
                            <input type="number" name="payment_amount" id="paymentAmount" 
                                   class="form-control form-control-custom" 
                                   step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="alert alert-info-custom">
                            <i class="fas fa-info-circle me-2"></i>
                            ระบบจะบันทึกรายการรับชำระเงินลงสมุดบัญชีโดยอัตโนมัติ
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="border-top: 1px solid rgba(255, 107, 0, 0.3);">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                            ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>บันทึกการรับชำระ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function receivePayment(invoiceId, invoiceNumber, balance) {
            document.getElementById('paymentInvoiceId').value = invoiceId;
            document.getElementById('paymentInvoiceNumber').value = invoiceNumber;
            document.getElementById('paymentBalance').value = '฿' + balance.toFixed(2);
            document.getElementById('paymentAmount').value = balance.toFixed(2);
            document.getElementById('paymentAmount').max = balance;
            
            const modal = new bootstrap.Modal(document.getElementById('receivePaymentModal'));
            modal.show();
        }
    </script>
</body>
</html>