<?php
/**
 * ============================================
 * ไฟล์: accounting/vat-report.php
 * คำอธิบาย: รายงานภาษีมูลค่าเพิ่ม ภ.พ.30
 * วัตถุประสงค์: สรุปภาษีซื้อ-ขาย สำหรับยื่นรายเดือน
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.view');

// รับค่าเดือน/ปี
$selectedMonth = $_GET['month'] ?? date('m');
$selectedYear = $_GET['year'] ?? date('Y');

$dateFrom = "{$selectedYear}-{$selectedMonth}-01";
$dateTo = date('Y-m-t', strtotime($dateFrom));

// ดึงข้อมูล VAT ขาย (Output VAT)
$sqlSales = "SELECT 
                ar.invoice_number,
                ar.customer_name,
                ar.customer_tax_id,
                ar.invoice_date,
                ar.subtotal,
                ar.vat_amount,
                ar.total_amount
             FROM accounts_receivable ar
             WHERE ar.invoice_date BETWEEN :date_from AND :date_to
             AND ar.status != 'cancelled'
             ORDER BY ar.invoice_date, ar.invoice_number";

$stmtSales = $pdo->prepare($sqlSales);
$stmtSales->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
$salesVAT = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล VAT ซื้อ (Input VAT)
$sqlPurchase = "SELECT 
                    ap.bill_number,
                    ap.vendor_name,
                    ap.vendor_tax_id,
                    ap.bill_date,
                    ap.subtotal,
                    ap.vat_amount,
                    ap.total_amount
                FROM accounts_payable ap
                WHERE ap.bill_date BETWEEN :date_from AND :date_to
                AND ap.status != 'cancelled'
                ORDER BY ap.bill_date, ap.bill_number";

$stmtPurchase = $pdo->prepare($sqlPurchase);
$stmtPurchase->execute([':date_from' => $dateFrom, ':date_to' => $dateTo]);
$purchaseVAT = $stmtPurchase->fetchAll(PDO::FETCH_ASSOC);

// คำนวณยอดรวม
$totalOutputVAT = array_sum(array_column($salesVAT, 'vat_amount'));
$totalInputVAT = array_sum(array_column($purchaseVAT, 'vat_amount'));
$netVAT = $totalOutputVAT - $totalInputVAT;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน ภ.พ.30 - ระบบบัญชี</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <style>
        .vat-summary-card {
            background: linear-gradient(135deg, rgba(0,173,181,0.1), rgba(255,107,0,0.1));
            border: 2px solid rgba(255,107,0,0.3);
            border-radius: 12px;
            padding: 25px;
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
                        <a class="nav-link active" href="vat-report.php">
                            <i class="fas fa-file-invoice"></i> ภ.พ.30
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="financial-statements.php">
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
                    <i class="fas fa-file-invoice me-3"></i>รายงาน ภ.พ.30
                </h1>
                <p class="lead">รายงานภาษีมูลค่าเพิ่ม (VAT Report)</p>
            </div>

            <!-- Period Selection -->
            <div class="content-wrapper mb-4 no-print">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-custom">
                            <i class="fas fa-calendar me-2"></i>เดือน
                        </label>
                        <select name="month" class="form-control form-control-custom">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                        <?php echo $selectedMonth == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                    <?php
                                    $thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                                                   'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                                    echo $thaiMonths[$m - 1];
                                    ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label-custom">
                            <i class="fas fa-calendar-alt me-2"></i>ปี
                        </label>
                        <select name="year" class="form-control form-control-custom">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>>
                                    <?php echo $y + 543; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search me-2"></i>ดูรายงาน
                        </button>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-success w-100" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>พิมพ์
                        </button>
                    </div>
                </form>
            </div>

            <!-- Company Header (for print) -->
            <div class="text-center mb-4" style="display: none;" id="printHeader">
                <h3><?php echo COMPANY_NAME; ?></h3>
                <p>เลขประจำตัวผู้เสียภาษี: <?php echo COMPANY_TAX_ID; ?></p>
                <h4 class="mt-3">รายงานภาษีมูลค่าเพิ่ม ภ.พ.30</h4>
                <p>ประจำเดือน <?php 
                    $thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                                   'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                    echo $thaiMonths[(int)$selectedMonth - 1] . ' ' . ($selectedYear + 543);
                ?></p>
            </div>

            <!-- VAT Summary -->
            <div class="vat-summary-card mb-4">
                <h4 class="mb-4">
                    <i class="fas fa-calculator me-2" style="color: #ff6b00;"></i>
                    สรุปภาษีมูลค่าเพิ่ม
                </h4>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-arrow-up fa-2x mb-2" style="color: #28a745;"></i>
                            <h6 class="text-muted">ภาษีขาย (Output VAT)</h6>
                            <h3 style="color: #28a745;"><?php echo format_accounting_amount($totalOutputVAT); ?></h3>
                            <small class="text-muted"><?php echo count($salesVAT); ?> รายการ</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-arrow-down fa-2x mb-2" style="color: #dc3545;"></i>
                            <h6 class="text-muted">ภาษีซื้อ (Input VAT)</h6>
                            <h3 style="color: #dc3545;"><?php echo format_accounting_amount($totalInputVAT); ?></h3>
                            <small class="text-muted"><?php echo count($purchaseVAT); ?> รายการ</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-balance-scale fa-2x mb-2" style="color: #ffc107;"></i>
                            <h6 class="text-muted">ภาษีสุทธิ (Net VAT)</h6>
                            <h3 style="color: <?php echo $netVAT >= 0 ? '#ffc107' : '#28a745'; ?>">
                                <?php echo format_accounting_amount($netVAT); ?>
                            </h3>
                            <small class="text-muted">
                                <?php if ($netVAT > 0): ?>
                                    <span class="text-warning">ต้องนำส่งภาษี</span>
                                <?php elseif ($netVAT < 0): ?>
                                    <span class="text-success">ขอคืนภาษี</span>
                                <?php else: ?>
                                    <span class="text-muted">ไม่มีภาษีนำส่ง</span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Output VAT (ภาษีขาย) -->
            <div class="content-wrapper mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-arrow-up me-2" style="color: #28a745;"></i>
                    ภาษีขาย (Output VAT)
                </h5>
                
                <div class="table-responsive">
                    <table class="table table-custom table-sm">
                        <thead>
                            <tr>
                                <th style="width: 8%;">ลำดับ</th>
                                <th style="width: 12%;">วันที่</th>
                                <th style="width: 15%;">เลขที่ใบกำกับ</th>
                                <th>ชื่อลูกค้า</th>
                                <th style="width: 13%;">เลขประจำตัวผู้เสียภาษี</th>
                                <th style="width: 12%;" class="text-end">มูลค่าสินค้า</th>
                                <th style="width: 10%;" class="text-end">ภาษี 7%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($salesVAT)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">ไม่มีรายการภาษีขาย</td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                $subtotalSum = 0;
                                $vatSum = 0;
                                foreach ($salesVAT as $sale): 
                                    $subtotalSum += $sale['subtotal'];
                                    $vatSum += $sale['vat_amount'];
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($sale['invoice_date'])); ?></td>
                                    <td><?php echo e($sale['invoice_number']); ?></td>
                                    <td><?php echo e($sale['customer_name']); ?></td>
                                    <td><?php echo e($sale['customer_tax_id'] ?: '-'); ?></td>
                                    <td class="text-end"><?php echo number_format($sale['subtotal'], 2); ?></td>
                                    <td class="text-end"><?php echo number_format($sale['vat_amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <!-- Total Row -->
                                <tr style="background: rgba(40, 167, 69, 0.1); font-weight: bold;">
                                    <td colspan="5" class="text-end">รวมภาษีขาย</td>
                                    <td class="text-end"><?php echo number_format($subtotalSum, 2); ?></td>
                                    <td class="text-end" style="color: #28a745;"><?php echo number_format($vatSum, 2); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Input VAT (ภาษีซื้อ) -->
            <div class="content-wrapper mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-arrow-down me-2" style="color: #dc3545;"></i>
                    ภาษีซื้อ (Input VAT)
                </h5>
                
                <div class="table-responsive">
                    <table class="table table-custom table-sm">
                        <thead>
                            <tr>
                                <th style="width: 8%;">ลำดับ</th>
                                <th style="width: 12%;">วันที่</th>
                                <th style="width: 15%;">เลขที่ใบกำกับ</th>
                                <th>ชื่อผู้ขาย</th>
                                <th style="width: 13%;">เลขประจำตัวผู้เสียภาษี</th>
                                <th style="width: 12%;" class="text-end">มูลค่าสินค้า</th>
                                <th style="width: 10%;" class="text-end">ภาษี 7%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchaseVAT)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">ไม่มีรายการภาษีซื้อ</td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                $subtotalSum = 0;
                                $vatSum = 0;
                                foreach ($purchaseVAT as $purchase): 
                                    $subtotalSum += $purchase['subtotal'];
                                    $vatSum += $purchase['vat_amount'];
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $no++; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($purchase['bill_date'])); ?></td>
                                    <td><?php echo e($purchase['bill_number']); ?></td>
                                    <td><?php echo e($purchase['vendor_name']); ?></td>
                                    <td><?php echo e($purchase['vendor_tax_id'] ?: '-'); ?></td>
                                    <td class="text-end"><?php echo number_format($purchase['subtotal'], 2); ?></td>
                                    <td class="text-end"><?php echo number_format($purchase['vat_amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <!-- Total Row -->
                                <tr style="background: rgba(220, 53, 69, 0.1); font-weight: bold;">
                                    <td colspan="5" class="text-end">รวมภาษีซื้อ</td>
                                    <td class="text-end"><?php echo number_format($subtotalSum, 2); ?></td>
                                    <td class="text-end" style="color: #dc3545;"><?php echo number_format($vatSum, 2); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Net VAT Summary -->
            <div class="content-wrapper">
                <h5 class="mb-3">
                    <i class="fas fa-calculator me-2" style="color: #ffc107;"></i>
                    สรุปภาษีสุทธิ
                </h5>
                
                <table class="table table-borderless">
                    <tr>
                        <td class="text-end" style="width: 70%;">
                            <strong>ภาษีขาย (Output VAT)</strong>
                        </td>
                        <td class="text-end" style="width: 30%; color: #28a745;">
                            <h5><?php echo format_accounting_amount($totalOutputVAT); ?></h5>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-end">
                            <strong>ภาษีซื้อ (Input VAT)</strong>
                        </td>
                        <td class="text-end" style="color: #dc3545;">
                            <h5>(<?php echo format_accounting_amount($totalInputVAT); ?>)</h5>
                        </td>
                    </tr>
                    <tr style="border-top: 2px solid rgba(255, 107, 0, 0.5);">
                        <td class="text-end">
                            <strong style="font-size: 1.2rem;">ภาษีสุทธิที่ต้องนำส่ง</strong>
                        </td>
                        <td class="text-end">
                            <h3 style="color: <?php echo $netVAT >= 0 ? '#ffc107' : '#28a745'; ?>">
                                <?php echo format_accounting_amount($netVAT); ?>
                            </h3>
                        </td>
                    </tr>
                </table>
                
                <?php if ($netVAT > 0): ?>
                <div class="alert alert-warning-custom mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>ต้องนำส่งภาษี:</strong> ชำระภายในวันที่ 15 ของเดือนถัดไป
                </div>
                <?php elseif ($netVAT < 0): ?>
                <div class="alert alert-success-custom mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>สามารถขอคืนภาษี:</strong> ยื่นขอคืนได้ภายในกำหนด
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show print header when printing
        window.addEventListener('beforeprint', function() {
            document.getElementById('printHeader').style.display = 'block';
        });
        
        window.addEventListener('afterprint', function() {
            document.getElementById('printHeader').style.display = 'none';
        });
    </script>
</body>
</html>