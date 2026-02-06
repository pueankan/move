<?php
/**
 * ============================================
 * ไฟล์: accounting/general-ledger.php
 * คำอธิบาย: สมุดบัญชีแยกประเภท (General Ledger)
 * วัตถุประสงค์: แสดงรายการเดินบัญชีของแต่ละบัญชี
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.view');

// รับค่า Filter
$accountId = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // วันแรกของเดือน
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// ดึงบัญชีทั้งหมดสำหรับ dropdown
$accounts = get_accounts(['is_active' => 1]);

// ดึงข้อมูลบัญชีที่เลือก
$selectedAccount = null;
$ledgerEntries = [];
$openingBalance = 0;
$runningBalance = 0;

if ($accountId > 0) {
    $selectedAccount = get_account_by_id($accountId);
    
    if ($selectedAccount) {
        // คำนวณยอดยกมา (ก่อนวันที่เริ่มต้น)
        $openingBalance = get_account_balance($accountId, date('Y-m-d', strtotime($dateFrom . ' -1 day')));
        $runningBalance = $openingBalance;
        
        // ดึงรายการเดินบัญชี
        $sql = "SELECT 
                    jel.*,
                    je.entry_number,
                    je.entry_date,
                    je.entry_type,
                    je.description as entry_description,
                    je.status
                FROM journal_entry_lines jel
                JOIN journal_entries je ON jel.entry_id = je.id
                WHERE jel.account_id = :account_id
                AND je.entry_date BETWEEN :date_from AND :date_to
                AND je.status = 'posted'
                ORDER BY je.entry_date, je.id, jel.line_number";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':account_id' => $accountId,
            ':date_from' => $dateFrom,
            ':date_to' => $dateTo
        ]);
        
        $ledgerEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมุดบัญชีแยกประเภท - ระบบบัญชี</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <style>
        .ledger-summary {
            background: linear-gradient(135deg, rgba(255,107,0,0.1), rgba(0,173,181,0.1));
            border: 2px solid rgba(255,107,0,0.3);
            border-radius: 12px;
            padding: 20px;
        }
        
        .balance-row {
            background: rgba(0, 173, 181, 0.1);
            font-weight: bold;
        }
    </style>
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
                        <a class="nav-link active" href="general-ledger.php">
                            <i class="fas fa-book-open"></i> สมุดบัญชี
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
                    <i class="fas fa-book-open me-3"></i>สมุดบัญชีแยกประเภท
                </h1>
                <p class="lead">รายการเดินบัญชีของแต่ละบัญชี (General Ledger)</p>
            </div>

            <!-- Filter -->
            <div class="content-wrapper mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label-custom">
                            <i class="fas fa-list me-2"></i>เลือกบัญชี *
                        </label>
                        <select name="account_id" class="form-control form-control-custom" required>
                            <option value="">-- เลือกบัญชี --</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>" 
                                        <?php echo $accountId === intval($account['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($account['account_code'] . ' - ' . $account['account_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label-custom">
                            <i class="fas fa-calendar me-2"></i>วันที่เริ่มต้น
                        </label>
                        <input type="date" name="date_from" class="form-control form-control-custom" 
                               value="<?php echo e($dateFrom); ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label-custom">
                            <i class="fas fa-calendar me-2"></i>วันที่สิ้นสุด
                        </label>
                        <input type="date" name="date_to" class="form-control form-control-custom" 
                               value="<?php echo e($dateTo); ?>" required>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($selectedAccount): ?>
            <!-- Account Info & Summary -->
            <div class="ledger-summary mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <h4 style="color: #ff6b00;"><?php echo e($selectedAccount['account_code']); ?></h4>
                        <h5><?php echo e($selectedAccount['account_name']); ?></h5>
                        <p class="mb-0 text-muted">
                            ประเภท: <?php echo e(ACCOUNT_TYPES[$selectedAccount['account_type']]); ?>
                            <?php if ($selectedAccount['account_subtype']): ?>
                                - <?php echo e($selectedAccount['account_subtype']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="col-md-6 text-end">
                        <div class="mb-2">
                            <small class="text-muted">ยอดยกมา (<?php echo date('d/m/Y', strtotime($dateFrom . ' -1 day')); ?>)</small>
                            <h4 style="color: #ffc107;">
                                <?php echo format_accounting_amount($openingBalance); ?>
                            </h4>
                        </div>
                        <div>
                            <small class="text-muted">ช่วงเวลา</small>
                            <p class="mb-0">
                                <?php echo date('d/m/Y', strtotime($dateFrom)); ?> - 
                                <?php echo date('d/m/Y', strtotime($dateTo)); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="content-wrapper">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th style="width: 10%;">วันที่</th>
                                <th style="width: 15%;">เลขที่รายการ</th>
                                <th>รายละเอียด</th>
                                <th style="width: 12%;" class="text-end">Debit</th>
                                <th style="width: 12%;" class="text-end">Credit</th>
                                <th style="width: 12%;" class="text-end">ยอดคงเหลือ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Opening Balance -->
                            <tr class="balance-row">
                                <td colspan="3">
                                    <strong>ยอดยกมา</strong>
                                </td>
                                <td class="text-end">-</td>
                                <td class="text-end">-</td>
                                <td class="text-end">
                                    <strong><?php echo format_accounting_amount($openingBalance); ?></strong>
                                </td>
                            </tr>
                            
                            <!-- Ledger Entries -->
                            <?php if (empty($ledgerEntries)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    ไม่มีรายการในช่วงเวลาที่เลือก
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($ledgerEntries as $entry): ?>
                                    <?php
                                    $debit = floatval($entry['debit_amount']);
                                    $credit = floatval($entry['credit_amount']);
                                    $runningBalance += ($debit - $credit);
                                    ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($entry['entry_date'])); ?></td>
                                    <td>
                                        <a href="journal-entries.php?id=<?php echo $entry['entry_id']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo e($entry['entry_number']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo e($entry['description'] ?: $entry['entry_description']); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo e(ENTRY_TYPES[$entry['entry_type']] ?? $entry['entry_type']); ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($debit > 0): ?>
                                            <strong style="color: #28a745;">
                                                <?php echo format_accounting_amount($debit); ?>
                                            </strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($credit > 0): ?>
                                            <strong style="color: #dc3545;">
                                                <?php echo format_accounting_amount($credit); ?>
                                            </strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong style="color: #ffc107;">
                                            <?php echo format_accounting_amount($runningBalance); ?>
                                        </strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <!-- Closing Balance -->
                                <tr class="balance-row">
                                    <td colspan="3">
                                        <strong>ยอดคงเหลือ ณ <?php echo date('d/m/Y', strtotime($dateTo)); ?></strong>
                                    </td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">
                                        <strong style="color: #ffc107; font-size: 1.1rem;">
                                            <?php echo format_accounting_amount($runningBalance); ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Export Buttons -->
                <div class="mt-3 d-flex gap-2">
                    <a href="?account_id=<?php echo $accountId; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&export=pdf" 
                       class="btn btn-outline-custom">
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </a>
                    <a href="?account_id=<?php echo $accountId; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&export=excel" 
                       class="btn btn-outline-custom">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </a>
                </div>
            </div>
            
            <?php else: ?>
            <!-- No Account Selected -->
            <div class="content-wrapper text-center py-5">
                <i class="fas fa-book-open fa-4x mb-3" style="opacity: 0.3;"></i>
                <h4>กรุณาเลือกบัญชีที่ต้องการดู</h4>
                <p class="text-muted">เลือกบัญชีจากเมนูด้านบนเพื่อดูรายการเดินบัญชี</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>