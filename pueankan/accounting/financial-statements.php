<?php
/**
 * ============================================
 * ไฟล์: accounting/financial-statements.php
 * คำอธิบาย: งบการเงิน (Financial Statements)
 * วัตถุประสงค์: งบดุล, งบกำไรขาดทุน, งบกระแสเงินสด
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.view');

// รับค่าวันที่
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
$statementType = $_GET['type'] ?? 'balance_sheet';

// ดึง Trial Balance
$trialBalance = get_trial_balance($asOfDate);

// จัดกลุ่มตามประเภทบัญชี
$balances = [
    'asset' => [],
    'liability' => [],
    'equity' => [],
    'revenue' => [],
    'expense' => []
];

$totals = [
    'asset' => 0,
    'liability' => 0,
    'equity' => 0,
    'revenue' => 0,
    'expense' => 0
];

foreach ($trialBalance as $item) {
    $type = $item['account_type'];
    $balance = floatval($item['balance']);
    
    $balances[$type][] = $item;
    $totals[$type] += $balance;
}

// คำนวณกำไร/ขาดทุน
$netIncome = $totals['revenue'] - $totals['expense'];

// คำนวณส่วนของเจ้าของรวม (Equity + Net Income)
$totalEquityWithIncome = $totals['equity'] + $netIncome;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งบการเงิน - ระบบบัญชี</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <style>
        .statement-section {
            margin-bottom: 30px;
        }
        
        .statement-total {
            background: rgba(255, 107, 0, 0.1);
            border-top: 2px solid #ff6b00;
            border-bottom: 2px double #ff6b00;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .statement-subtotal {
            background: rgba(0, 173, 181, 0.05);
            border-top: 1px solid rgba(0, 173, 181, 0.3);
            font-weight: bold;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .content-wrapper { background: white !important; border: none !important; }
        }
    </style>
</head>
<body>
    <?php include '../includes/background.php'; ?>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top no-print" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(10px); border-bottom: 2px solid rgba(255, 107, 0, 0.3);">
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
                        <a class="nav-link" href="vat-report.php">
                            <i class="fas fa-file-invoice"></i> ภ.พ.30
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="financial-statements.php">
                            <i class="fas fa-chart-line"></i> งบการเงิน
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="page-container">
        <div class="container py-5">
            <!-- Header -->
            <div class="mb-5 no-print">
                <h1 class="display-4 fw-bold glow-text">
                    <i class="fas fa-chart-line me-3"></i>งบการเงิน
                </h1>
                <p class="lead">รายงานทางการเงิน (Financial Statements)</p>
            </div>

            <!-- Controls -->
            <div class="content-wrapper mb-4 no-print">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-custom">
                            <i class="fas fa-file-alt me-2"></i>ประเภทงบ
                        </label>
                        <select name="type" class="form-control form-control-custom">
                            <option value="balance_sheet" <?php echo $statementType === 'balance_sheet' ? 'selected' : ''; ?>>
                                งบดุล (Balance Sheet)
                            </option>
                            <option value="income_statement" <?php echo $statementType === 'income_statement' ? 'selected' : ''; ?>>
                                งบกำไรขาดทุน (Income Statement)
                            </option>
                            <option value="trial_balance" <?php echo $statementType === 'trial_balance' ? 'selected' : ''; ?>>
                                งบทดลอง (Trial Balance)
                            </option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label-custom">
                            <i class="fas fa-calendar me-2"></i>ณ วันที่
                        </label>
                        <input type="date" name="as_of_date" class="form-control form-control-custom" 
                               value="<?php echo e($asOfDate); ?>">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search me-2"></i>แสดง
                        </button>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-success w-100" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>พิมพ์
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($statementType === 'balance_sheet'): ?>
            <!-- Balance Sheet -->
            <div class="content-wrapper">
                <div class="text-center mb-4">
                    <h3><?php echo COMPANY_NAME; ?></h3>
                    <h4>งบดุล (Balance Sheet)</h4>
                    <p>ณ วันที่ <?php echo date('d/m/Y', strtotime($asOfDate)); ?></p>
                </div>
                
                <!-- Assets -->
                <div class="statement-section">
                    <h5 style="color: #ff6b00;">สินทรัพย์ (Assets)</h5>
                    <table class="table table-borderless">
                        <?php foreach ($balances['asset'] as $item): ?>
                        <tr>
                            <td style="padding-left: 20px;"><?php echo e($item['account_name']); ?></td>
                            <td class="text-end" style="width: 20%;">
                                <?php echo format_accounting_amount($item['balance']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="statement-subtotal">
                            <td>รวมสินทรัพย์</td>
                            <td class="text-end"><?php echo format_accounting_amount($totals['asset']); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Liabilities -->
                <div class="statement-section">
                    <h5 style="color: #ff6b00;">หนี้สิน (Liabilities)</h5>
                    <table class="table table-borderless">
                        <?php foreach ($balances['liability'] as $item): ?>
                        <tr>
                            <td style="padding-left: 20px;"><?php echo e($item['account_name']); ?></td>
                            <td class="text-end" style="width: 20%;">
                                <?php echo format_accounting_amount($item['balance']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="statement-subtotal">
                            <td>รวมหนี้สิน</td>
                            <td class="text-end"><?php echo format_accounting_amount($totals['liability']); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Equity -->
                <div class="statement-section">
                    <h5 style="color: #ff6b00;">ส่วนของเจ้าของ (Equity)</h5>
                    <table class="table table-borderless">
                        <?php foreach ($balances['equity'] as $item): ?>
                        <tr>
                            <td style="padding-left: 20px;"><?php echo e($item['account_name']); ?></td>
                            <td class="text-end" style="width: 20%;">
                                <?php echo format_accounting_amount($item['balance']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td style="padding-left: 20px;">กำไร(ขาดทุน)สะสม</td>
                            <td class="text-end" style="width: 20%;">
                                <?php echo format_accounting_amount($netIncome); ?>
                            </td>
                        </tr>
                        <tr class="statement-subtotal">
                            <td>รวมส่วนของเจ้าของ</td>
                            <td class="text-end"><?php echo format_accounting_amount($totalEquityWithIncome); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Total -->
                <table class="table table-borderless">
                    <tr class="statement-total">
                        <td>รวมหนี้สินและส่วนของเจ้าของ</td>
                        <td class="text-end" style="width: 20%;">
                            <?php echo format_accounting_amount($totals['liability'] + $totalEquityWithIncome); ?>
                        </td>
                    </tr>
                </table>
                
                <!-- Verification -->
                <div class="alert alert-<?php echo abs($totals['asset'] - ($totals['liability'] + $totalEquityWithIncome)) < 0.01 ? 'success' : 'danger'; ?>-custom mt-3">
                    <i class="fas fa-<?php echo abs($totals['asset'] - ($totals['liability'] + $totalEquityWithIncome)) < 0.01 ? 'check' : 'exclamation-triangle'; ?>-circle me-2"></i>
                    <strong>สมดุล:</strong>
                    <?php if (abs($totals['asset'] - ($totals['liability'] + $totalEquityWithIncome)) < 0.01): ?>
                        งบดุลสมดุล (Assets = Liabilities + Equity)
                    <?php else: ?>
                        งบดุลไม่สมดุล! ตรวจสอบรายการบัญชี
                    <?php endif; ?>
                </div>
            </div>
            
            <?php elseif ($statementType === 'income_statement'): ?>
            <!-- Income Statement -->
            <div class="content-wrapper">
                <div class="text-center mb-4">
                    <h3><?php echo COMPANY_NAME; ?></h3>
                    <h4>งบกำไรขาดทุน (Income Statement)</h4>
                    <p>สำหรับงวดสิ้นสุด ณ วันที่ <?php echo date('d/m/Y', strtotime($asOfDate)); ?></p>
                </div>
                
                <!-- Revenue -->
                <div class="statement-section">
                    <h5 style="color: #28a745;">รายได้ (Revenue)</h5>
                    <table class="table table-borderless">
                        <?php foreach ($balances['revenue'] as $item): ?>
                        <tr>
                            <td style="padding-left: 20px;"><?php echo e($item['account_name']); ?></td>
                            <td class="text-end" style="width: 20%;">
                                <?php echo format_accounting_amount($item['balance']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="statement-subtotal">
                            <td>รวมรายได้</td>
                            <td class="text-end"><?php echo format_accounting_amount($totals['revenue']); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Expenses -->
                <div class="statement-section">
                    <h5 style="color: #dc3545;">ค่าใช้จ่าย (Expenses)</h5>
                    <table class="table table-borderless">
                        <?php foreach ($balances['expense'] as $item): ?>
                        <tr>
                            <td style="padding-left: 20px;"><?php echo e($item['account_name']); ?></td>
                            <td class="text-end" style="width: 20%;">
                                (<?php echo format_accounting_amount($item['balance']); ?>)
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="statement-subtotal">
                            <td>รวมค่าใช้จ่าย</td>
                            <td class="text-end">(<?php echo format_accounting_amount($totals['expense']); ?>)</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Net Income -->
                <table class="table table-borderless">
                    <tr class="statement-total">
                        <td>
                            <strong style="font-size: 1.2rem;">
                                <?php echo $netIncome >= 0 ? 'กำไรสุทธิ (Net Income)' : 'ขาดทุนสุทธิ (Net Loss)'; ?>
                            </strong>
                        </td>
                        <td class="text-end" style="width: 20%; color: <?php echo $netIncome >= 0 ? '#28a745' : '#dc3545'; ?>;">
                            <strong style="font-size: 1.2rem;">
                                <?php echo format_accounting_amount($netIncome); ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php elseif ($statementType === 'trial_balance'): ?>
            <!-- Trial Balance -->
            <div class="content-wrapper">
                <div class="text-center mb-4">
                    <h3><?php echo COMPANY_NAME; ?></h3>
                    <h4>งบทดลอง (Trial Balance)</h4>
                    <p>ณ วันที่ <?php echo date('d/m/Y', strtotime($asOfDate)); ?></p>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th style="width: 15%;">รหัสบัญชี</th>
                                <th>ชื่อบัญชี</th>
                                <th style="width: 15%;" class="text-end">Debit</th>
                                <th style="width: 15%;" class="text-end">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalDebit = 0;
                            $totalCredit = 0;
                            
                            foreach ($trialBalance as $item):
                                $balance = floatval($item['balance']);
                                $debit = $balance >= 0 ? $balance : 0;
                                $credit = $balance < 0 ? abs($balance) : 0;
                                
                                $totalDebit += $debit;
                                $totalCredit += $credit;
                            ?>
                            <tr>
                                <td><?php echo e($item['account_code']); ?></td>
                                <td><?php echo e($item['account_name']); ?></td>
                                <td class="text-end">
                                    <?php echo $debit > 0 ? format_accounting_amount($debit) : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo $credit > 0 ? format_accounting_amount($credit) : '-'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr class="statement-total">
                                <td colspan="2" class="text-end"><strong>รวม</strong></td>
                                <td class="text-end">
                                    <strong><?php echo format_accounting_amount($totalDebit); ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo format_accounting_amount($totalCredit); ?></strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-<?php echo abs($totalDebit - $totalCredit) < 0.01 ? 'success' : 'danger'; ?>-custom mt-3">
                    <i class="fas fa-<?php echo abs($totalDebit - $totalCredit) < 0.01 ? 'check' : 'exclamation-triangle'; ?>-circle me-2"></i>
                    <strong>สมดุล:</strong>
                    <?php if (abs($totalDebit - $totalCredit) < 0.01): ?>
                        งบทดลองสมดุล (Debit = Credit)
                    <?php else: ?>
                        งบทดลองไม่สมดุล! ต่าง <?php echo format_accounting_amount(abs($totalDebit - $totalCredit)); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>