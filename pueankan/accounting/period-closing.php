<?php
/**
 * ============================================
 * ไฟล์: accounting/period-closing.php
 * คำอธิบาย: ปิดงวดบัญชี (Period Closing)
 * วัตถุประสงค์: ปิดงวด ตรวจสอบ ล็อครายการ
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.edit');

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
                case 'close_period':
                    $periodId = intval($_POST['period_id']);
                    close_period($periodId);
                    
                    $message = 'ปิดงวดบัญชีสำเร็จ';
                    $messageType = 'success';
                    break;
                    
                case 'reopen_period':
                    if (!AccessControl::check('accounting.admin')) {
                        throw new Exception('เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเปิดงวดใหม่ได้');
                    }
                    
                    $periodId = intval($_POST['period_id']);
                    
                    $sql = "UPDATE accounting_periods 
                            SET status = 'open', 
                                closed_date = NULL, 
                                closed_by = NULL 
                            WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $periodId]);
                    
                    AuditLog::log('period_reopened', "Period ID: {$periodId}", 'critical');
                    
                    $message = 'เปิดงวดบัญชีใหม่สำเร็จ';
                    $messageType = 'success';
                    break;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ดึงรายการงวดบัญชี
$sql = "SELECT 
            ap.*,
            u.full_name as closed_by_name,
            (SELECT COUNT(*) FROM journal_entries je 
             WHERE je.period_id = ap.id AND je.status != 'posted') as unposted_count
        FROM accounting_periods ap
        LEFT JOIN users u ON ap.closed_by = u.id
        ORDER BY ap.start_date DESC";

$stmt = $pdo->query($sql);
$periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปิดงวดบัญชี - ระบบบัญชี</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <style>
        .period-card {
            background: linear-gradient(135deg, rgba(255,107,0,0.05), rgba(0,173,181,0.05));
            border: 2px solid rgba(255,107,0,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .period-card:hover {
            border-color: rgba(255,107,0,0.5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,107,0,0.2);
        }
        
        .period-card.closed {
            border-color: rgba(108,117,125,0.3);
            opacity: 0.8;
        }
        
        .period-card.locked {
            border-color: rgba(220,53,69,0.3);
            background: linear-gradient(135deg, rgba(220,53,69,0.05), rgba(108,117,125,0.05));
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
                        <a class="nav-link" href="assets.php">
                            <i class="fas fa-building"></i> ทรัพย์สิน
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="period-closing.php">
                            <i class="fas fa-lock"></i> ปิดงวด
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
                    <i class="fas fa-lock me-3"></i>ปิดงวดบัญชี
                </h1>
                <p class="lead">จัดการและปิดงวดบัญชีรายเดือน (Period Closing)</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="content-wrapper mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-info-circle me-2" style="color: #00adb5;"></i>
                    เกี่ยวกับการปิดงวดบัญชี
                </h5>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="alert alert-info-custom">
                            <strong><i class="fas fa-check-circle me-2"></i>ก่อนปิดงวด:</strong>
                            <ul class="mb-0 mt-2">
                                <li>ตรวจสอบรายการทั้งหมด Posted แล้ว</li>
                                <li>ตรวจสอบยอด AR/AP</li>
                                <li>คำนวณค่าเสื่อมราคา</li>
                                <li>กระทบยอดธนาคาร</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="alert alert-warning-custom">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>ผลการปิดงวด:</strong>
                            <ul class="mb-0 mt-2">
                                <li>ล็อครายการในงวด</li>
                                <li>ไม่สามารถแก้ไขรายการได้</li>
                                <li>ยืนยันตัวเลขทางการเงิน</li>
                                <li>พร้อมสำหรับรายงาน</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="alert alert-danger-custom">
                            <strong><i class="fas fa-shield-alt me-2"></i>การเปิดงวดใหม่:</strong>
                            <ul class="mb-0 mt-2">
                                <li>ต้องมีสิทธิ์ Admin</li>
                                <li>บันทึก Audit Log</li>
                                <li>ใช้เฉพาะกรณีจำเป็น</li>
                                <li>แจ้งผู้เกี่ยวข้อง</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Periods List -->
            <div class="content-wrapper">
                <h5 class="mb-4">
                    <i class="fas fa-calendar-alt me-2" style="color: #ff6b00;"></i>
                    รายการงวดบัญชี
                </h5>
                
                <?php foreach ($periods as $period): ?>
                    <?php
                    $statusIcons = [
                        'open' => ['icon' => 'unlock', 'color' => '#28a745', 'text' => 'เปิดอยู่'],
                        'closed' => ['icon' => 'lock', 'color' => '#ffc107', 'text' => 'ปิดแล้ว'],
                        'locked' => ['icon' => 'lock', 'color' => '#dc3545', 'text' => 'ล็อก']
                    ];
                    $status = $statusIcons[$period['status']];
                    ?>
                <div class="period-card <?php echo $period['status']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h5 class="mb-1" style="color: #ff6b00;">
                                <i class="fas fa-calendar me-2"></i>
                                <?php echo e($period['period_name']); ?>
                            </h5>
                            <p class="mb-0 text-muted">
                                <small>ปีบัญชี: <?php echo $period['fiscal_year'] + 543; ?></small>
                            </p>
                        </div>
                        
                        <div class="col-md-3">
                            <small class="text-muted d-block">ช่วงเวลา</small>
                            <strong>
                                <?php echo date('d/m/Y', strtotime($period['start_date'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($period['end_date'])); ?>
                            </strong>
                        </div>
                        
                        <div class="col-md-2 text-center">
                            <span class="badge" style="background: <?php echo $status['color']; ?>; font-size: 1rem; padding: 8px 15px;">
                                <i class="fas fa-<?php echo $status['icon']; ?> me-2"></i>
                                <?php echo $status['text']; ?>
                            </span>
                        </div>
                        
                        <div class="col-md-2">
                            <?php if ($period['status'] === 'open'): ?>
                                <?php if ($period['unposted_count'] > 0): ?>
                                <div class="alert alert-warning-custom mb-0 py-2">
                                    <small>
                                        <i class="fas fa-exclamation-triangle"></i>
                                        รายการยัง Post ไม่ครบ<br>
                                        <strong><?php echo $period['unposted_count']; ?> รายการ</strong>
                                    </small>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-success-custom mb-0 py-2">
                                    <small>
                                        <i class="fas fa-check-circle"></i>
                                        พร้อมปิดงวด
                                    </small>
                                </div>
                                <?php endif; ?>
                            <?php elseif ($period['status'] === 'closed'): ?>
                            <small class="text-muted">
                                ปิดเมื่อ: <?php echo date('d/m/Y H:i', strtotime($period['closed_date'])); ?><br>
                                โดย: <?php echo e($period['closed_by_name']); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-2 text-end">
                            <?php if ($period['status'] === 'open'): ?>
                                <?php if ($period['unposted_count'] == 0): ?>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('ยืนยันการปิดงวด <?php echo e($period['period_name']); ?>?\n\nรายการในงวดนี้จะถูกล็อค ไม่สามารถแก้ไขได้')">
                                    <?php CSRF::insertHiddenField(); ?>
                                    <input type="hidden" name="action" value="close_period">
                                    <input type="hidden" name="period_id" value="<?php echo $period['id']; ?>">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-lock me-2"></i>ปิดงวด
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled title="กรุณา Post รายการให้ครบก่อน">
                                    <i class="fas fa-lock me-2"></i>ปิดงวด
                                </button>
                                <?php endif; ?>
                            <?php elseif ($period['status'] === 'closed' && AccessControl::check('accounting.admin')): ?>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('⚠️ คำเตือน: การเปิดงวดใหม่เป็นการดำเนินการที่มีความสำคัญ\n\nคุณแน่ใจหรือไม่?')">
                                <?php CSRF::insertHiddenField(); ?>
                                <input type="hidden" name="action" value="reopen_period">
                                <input type="hidden" name="period_id" value="<?php echo $period['id']; ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-unlock me-2"></i>เปิดงวดใหม่
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Statistics -->
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-unlock fa-3x mb-3" style="color: #28a745;"></i>
                            <h6 class="text-muted">งวดเปิดอยู่</h6>
                            <h3 style="color: #28a745;">
                                <?php echo count(array_filter($periods, fn($p) => $p['status'] === 'open')); ?>
                            </h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-lock fa-3x mb-3" style="color: #ffc107;"></i>
                            <h6 class="text-muted">งวดปิดแล้ว</h6>
                            <h3 style="color: #ffc107;">
                                <?php echo count(array_filter($periods, fn($p) => $p['status'] === 'closed')); ?>
                            </h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-3x mb-3" style="color: #dc3545;"></i>
                            <h6 class="text-muted">งวดล็อก</h6>
                            <h3 style="color: #dc3545;">
                                <?php echo count(array_filter($periods, fn($p) => $p['status'] === 'locked')); ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>