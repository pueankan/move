<?php
/**
 * ============================================
 * ไฟล์: accounting/accounts-payable.php
 * คำอธิบาย: จัดการเจ้าหนี้การค้า (Accounts Payable)
 * วัตถุประสงค์: สร้าง ติดตาม และชำระเจ้าหนี้
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

// ดึงรายการเจ้าหนี้
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT * FROM accounts_payable WHERE 1=1";
$params = [];

if ($filterStatus) {
    $sql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

$sql .= " ORDER BY due_date ASC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// สรุปยอด
$totalAP = 0;
$totalOverdue = 0;
$totalPaid = 0;

foreach ($bills as $bill) {
    if ($bill['status'] !== 'cancelled') {
        $totalAP += $bill['balance'];
        
        if ($bill['status'] === 'paid') {
            $totalPaid += $bill['total_amount'];
        }
        
        if (strtotime($bill['due_date']) < time() && $bill['balance'] > 0) {
            $totalOverdue += $bill['balance'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เจ้าหนี้การค้า - ระบบบัญชี</title>
    
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
                        <a class="nav-link" href="accounts-receivable.php">
                            <i class="fas fa-hand-holding-usd"></i> ลูกหนี้
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="accounts-payable.php">
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
                    <i class="fas fa-money-check-alt me-3"></i>เจ้าหนี้การค้า
                </h1>
                <p class="lead">จัดการและติดตามเจ้าหนี้ (Accounts Payable)</p>
            </div>

            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-file-invoice-dollar fa-3x mb-3" style="color: #dc3545;"></i>
                            <h6 class="text-muted">เจ้าหนี้คงค้าง</h6>
                            <h3 style="color: #dc3545;"><?php echo format_accounting_amount($totalAP); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #ffc107;"></i>
                            <h6 class="text-muted">เกินกำหนดชำระ</h6>
                            <h3 style="color: #ffc107;"><?php echo format_accounting_amount($totalOverdue); ?></h3>
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

            <!-- Bills Table -->
            <div class="content-wrapper">
                <h4 class="mb-4">
                    <i class="fas fa-list me-2" style="color: #ff6b00;"></i>
                    รายการเจ้าหนี้
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>เลขที่บิล</th>
                                <th>ผู้ขาย/เจ้าหนี้</th>
                                <th>วันที่</th>
                                <th>ครบกำหนด</th>
                                <th class="text-end">ยอดรวม</th>
                                <th class="text-end">คงค้าง</th>
                                <th>สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bills)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    ไม่พบรายการเจ้าหนี้
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($bills as $bill): ?>
                                    <?php
                                    $isOverdue = strtotime($bill['due_date']) < time() && $bill['balance'] > 0;
                                    ?>
                                <tr <?php echo $isOverdue ? 'style="background: rgba(255,193,7,0.1);"' : ''; ?>>
                                    <td><strong><?php echo e($bill['bill_number']); ?></strong></td>
                                    <td><?php echo e($bill['vendor_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($bill['bill_date'])); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($bill['due_date'])); ?>
                                        <?php if ($isOverdue): ?>
                                            <br><small class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> เกิน <?php echo floor((time() - strtotime($bill['due_date'])) / 86400); ?> วัน
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo format_accounting_amount($bill['total_amount']); ?></td>
                                    <td class="text-end">
                                        <strong style="color: <?php echo $bill['balance'] > 0 ? '#dc3545' : '#28a745'; ?>">
                                            <?php echo format_accounting_amount($bill['balance']); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'received' => 'info',
                                            'partial_paid' => 'warning',
                                            'paid' => 'success',
                                            'overdue' => 'danger'
                                        ];
                                        $statusText = [
                                            'draft' => 'ฉบับร่าง',
                                            'received' => 'รับแล้ว',
                                            'partial_paid' => 'ชำระบางส่วน',
                                            'paid' => 'ชำระแล้ว',
                                            'overdue' => 'เกินกำหนด'
                                        ];
                                        $displayStatus = $isOverdue && $bill['status'] !== 'paid' ? 'overdue' : $bill['status'];
                                        ?>
                                        <span class="badge bg-<?php echo $statusColors[$displayStatus] ?? 'secondary'; ?>">
                                            <?php echo $statusText[$displayStatus] ?? $displayStatus; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </button>
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

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>