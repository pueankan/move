<?php
/**
 * ============================================
 * ไฟล์: accounting/index.php
 * คำอธิบาย: Dashboard ระบบบัญชี
 * วัตถุประสงค์: แสดงภาพรวมข้อมูลทางบัญชี
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.view');

// ดึงข้อมูลสรุป
try {
    // งวดบัญชีปัจจุบัน
    $currentPeriod = get_current_period();
    
    // สรุปยอดจากงบทดลอง
    $trialBalance = get_trial_balance();
    
    $totalAssets = 0;
    $totalLiabilities = 0;
    $totalEquity = 0;
    $totalRevenue = 0;
    $totalExpense = 0;
    
    foreach ($trialBalance as $account) {
        $balance = floatval($account['balance']);
        
        switch ($account['account_type']) {
            case 'asset':
                $totalAssets += $balance;
                break;
            case 'liability':
                $totalLiabilities += abs($balance);
                break;
            case 'equity':
                $totalEquity += abs($balance);
                break;
            case 'revenue':
                $totalRevenue += abs($balance);
                break;
            case 'expense':
                $totalExpense += $balance;
                break;
        }
    }
    
    $netIncome = $totalRevenue - $totalExpense;
    
    // ลูกหนี้คงค้าง
    $sql = "SELECT COUNT(*) as count, SUM(balance) as total 
            FROM accounts_receivable 
            WHERE status IN ('issued', 'partial_paid', 'overdue')";
    $stmt = $pdo->query($sql);
    $arSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // เจ้าหนี้คงค้าง
    $sql = "SELECT COUNT(*) as count, SUM(balance) as total 
            FROM accounts_payable 
            WHERE status IN ('received', 'partial_paid', 'overdue')";
    $stmt = $pdo->query($sql);
    $apSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // รายการบัญชีล่าสุด
    $sql = "SELECT je.*, u.full_name as created_by_name 
            FROM journal_entries je
            LEFT JOIN users u ON je.created_by = u.id
            ORDER BY je.created_at DESC 
            LIMIT 10";
    $stmt = $pdo->query($sql);
    $recentEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = "เกิดข้อผิดพลาดในการโหลดข้อมูล";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบัญชี - Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <style>
        .dashboard-stat-card {
            background: linear-gradient(135deg, rgba(255,107,0,0.1), rgba(0,173,181,0.1));
            border: 2px solid rgba(255,107,0,0.3);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
        }
        
        .dashboard-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255,107,0,0.3);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0 5px 0;
        }
        
        .stat-label {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }
        
        .accounting-menu-card {
            background: rgba(22,33,62,0.8);
            border: 1px solid rgba(255,107,0,0.3);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            color: white;
        }
        
        .accounting-menu-card:hover {
            background: rgba(255,107,0,0.2);
            border-color: #ff6b00;
            transform: translateY(-3px);
            color: white;
        }
        
        .accounting-menu-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
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
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="chart-of-accounts.php">
                            <i class="fas fa-list"></i> ผังบัญชี
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal-entries.php">
                            <i class="fas fa-book"></i> รายการบัญชี
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/index.php">
                            <i class="fas fa-arrow-left"></i> กลับหน้าร้าน
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="page-container">
        <div class="container py-5">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold glow-text">
                    <i class="fas fa-chart-line me-3"></i>Dashboard ระบบบัญชี
                </h1>
                <p class="lead"><?php echo e(COMPANY_NAME); ?></p>
                
                <?php if ($currentPeriod): ?>
                <div class="alert alert-info-custom mt-3">
                    <i class="fas fa-calendar me-2"></i>
                    งวดบัญชีปัจจุบัน: <strong><?php echo e($currentPeriod['period_name']); ?></strong>
                    (<?php echo date('d/m/Y', strtotime($currentPeriod['start_date'])); ?> - 
                     <?php echo date('d/m/Y', strtotime($currentPeriod['end_date'])); ?>)
                    <span class="badge bg-success ms-2">เปิดอยู่</span>
                </div>
                <?php else: ?>
                <div class="alert alert-warning-custom mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ไม่พบงวดบัญชีที่เปิดอยู่ กรุณาติดต่อผู้ดูแลระบบ
                </div>
                <?php endif; ?>
            </div>

            <!-- Financial Summary -->
            <div class="row g-4 mb-5">
                <!-- Total Assets -->
                <div class="col-md-6 col-lg-3">
                    <div class="dashboard-stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                <i class="fas fa-wallet text-white"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-label">สินทรัพย์รวม</div>
                                <div class="stat-value" style="color: #28a745;">
                                    <?php echo format_accounting_amount($totalAssets, false); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Liabilities -->
                <div class="col-md-6 col-lg-3">
                    <div class="dashboard-stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #e83e8c);">
                                <i class="fas fa-file-invoice-dollar text-white"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-label">หนี้สินรวม</div>
                                <div class="stat-value" style="color: #dc3545;">
                                    <?php echo format_accounting_amount($totalLiabilities, false); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Equity -->
                <div class="col-md-6 col-lg-3">
                    <div class="dashboard-stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #007bff, #00adb5);">
                                <i class="fas fa-chart-pie text-white"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-label">ส่วนของเจ้าของ</div>
                                <div class="stat-value" style="color: #00adb5;">
                                    <?php echo format_accounting_amount($totalEquity, false); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Income -->
                <div class="col-md-6 col-lg-3">
                    <div class="dashboard-stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ff6b00);">
                                <i class="fas fa-coins text-white"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="stat-label">กำไร(ขาดทุน)สุทธิ</div>
                                <div class="stat-value" style="color: <?php echo $netIncome >= 0 ? '#ffc107' : '#dc3545'; ?>">
                                    <?php echo format_accounting_amount($netIncome, false); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AR/AP Summary -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="content-wrapper">
                        <h5 class="mb-3">
                            <i class="fas fa-hand-holding-usd me-2" style="color: #ff6b00;"></i>
                            ลูกหนี้คงค้าง (AR)
                        </h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted mb-1">จำนวนรายการ</div>
                                <h3 class="mb-0"><?php echo $arSummary['count'] ?? 0; ?> รายการ</h3>
                            </div>
                            <div class="text-end">
                                <div class="text-muted mb-1">ยอดรวม</div>
                                <h3 class="mb-0" style="color: #ffc107;">
                                    <?php echo format_accounting_amount($arSummary['total'] ?? 0); ?>
                                </h3>
                            </div>
                        </div>
                        <a href="accounts-receivable.php" class="btn btn-primary-custom w-100 mt-3">
                            <i class="fas fa-arrow-right me-2"></i>ดูรายละเอียด
                        </a>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="content-wrapper">
                        <h5 class="mb-3">
                            <i class="fas fa-money-check-alt me-2" style="color: #ff6b00;"></i>
                            เจ้าหนี้คงค้าง (AP)
                        </h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted mb-1">จำนวนรายการ</div>
                                <h3 class="mb-0"><?php echo $apSummary['count'] ?? 0; ?> รายการ</h3>
                            </div>
                            <div class="text-end">
                                <div class="text-muted mb-1">ยอดรวม</div>
                                <h3 class="mb-0" style="color: #dc3545;">
                                    <?php echo format_accounting_amount($apSummary['total'] ?? 0); ?>
                                </h3>
                            </div>
                        </div>
                        <a href="accounts-payable.php" class="btn btn-primary-custom w-100 mt-3">
                            <i class="fas fa-arrow-right me-2"></i>ดูรายละเอียด
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Access Menu -->
            <div class="content-wrapper mb-5">
                <h4 class="mb-4">
                    <i class="fas fa-th me-2" style="color: #ff6b00;"></i>
                    เมนูด่วน
                </h4>
                
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="chart-of-accounts.php" class="accounting-menu-card">
                            <i class="fas fa-list-alt" style="color: #00adb5;"></i>
                            <div>ผังบัญชี</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="journal-entries.php" class="accounting-menu-card">
                            <i class="fas fa-book" style="color: #ff6b00;"></i>
                            <div>รายการบัญชี</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="general-ledger.php" class="accounting-menu-card">
                            <i class="fas fa-book-open" style="color: #ffc107;"></i>
                            <div>สมุดบัญชี</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="accounts-receivable.php" class="accounting-menu-card">
                            <i class="fas fa-hand-holding-usd" style="color: #28a745;"></i>
                            <div>ลูกหนี้</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="accounts-payable.php" class="accounting-menu-card">
                            <i class="fas fa-money-check-alt" style="color: #dc3545;"></i>
                            <div>เจ้าหนี้</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="vat-report.php" class="accounting-menu-card">
                            <i class="fas fa-percent" style="color: #17a2b8;"></i>
                            <div>ภ.พ.30</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="assets.php" class="accounting-menu-card">
                            <i class="fas fa-building" style="color: #6f42c1;"></i>
                            <div>ทรัพย์สิน</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="payroll.php" class="accounting-menu-card">
                            <i class="fas fa-users" style="color: #e83e8c;"></i>
                            <div>เงินเดือน</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="financial-statements.php" class="accounting-menu-card">
                            <i class="fas fa-chart-bar" style="color: #20c997;"></i>
                            <div>งบการเงิน</div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="period-closing.php" class="accounting-menu-card">
                            <i class="fas fa-calendar-check" style="color: #fd7e14;"></i>
                            <div>ปิดงวด</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Entries -->
            <div class="content-wrapper">
                <h4 class="mb-4">
                    <i class="fas fa-history me-2" style="color: #ff6b00;"></i>
                    รายการบัญชีล่าสุด
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>เลขที่</th>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>รายละเอียด</th>
                                <th>ยอดเงิน</th>
                                <th>สถานะ</th>
                                <th>ผู้บันทึก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentEntries)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">ยังไม่มีรายการบัญชี</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentEntries as $entry): ?>
                                <tr>
                                    <td>
                                        <a href="journal-entries.php?id=<?php echo $entry['id']; ?>" class="text-decoration-none">
                                            <?php echo e($entry['entry_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($entry['entry_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo e(ENTRY_TYPES[$entry['entry_type']] ?? $entry['entry_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e(mb_substr($entry['description'], 0, 50)) . '...'; ?></td>
                                    <td class="text-end">
                                        <?php echo format_accounting_amount($entry['total_debit']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'pending' => 'warning',
                                            'approved' => 'info',
                                            'posted' => 'success',
                                            'void' => 'danger'
                                        ];
                                        $statusText = [
                                            'draft' => 'ฉบับร่าง',
                                            'pending' => 'รอดำเนินการ',
                                            'approved' => 'อนุมัติแล้ว',
                                            'posted' => 'บันทึกแล้ว',
                                            'void' => 'ยกเลิก'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusColors[$entry['status']] ?? 'secondary'; ?>">
                                            <?php echo $statusText[$entry['status']] ?? $entry['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($entry['created_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="journal-entries.php" class="btn btn-outline-custom">
                        ดูทั้งหมด <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>