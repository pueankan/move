<?php
/**
 * ============================================
 * ไฟล์: accounting/chart-of-accounts.php
 * คำอธิบาย: จัดการผังบัญชี (Chart of Accounts)
 * วัตถุประสงค์: แสดง เพิ่ม แก้ไข ผังบัญชี
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.view');

// จัดการ Form Submit
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request';
        $messageType = 'danger';
    } else {
        try {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'create':
                        if (AccessControl::check('accounting.create')) {
                            $data = [
                                'account_code' => $_POST['account_code'],
                                'account_name' => $_POST['account_name'],
                                'account_type' => $_POST['account_type'],
                                'account_subtype' => $_POST['account_subtype'] ?? '',
                                'parent_account_id' => !empty($_POST['parent_account_id']) ? $_POST['parent_account_id'] : null,
                                'description' => $_POST['description'] ?? ''
                            ];
                            
                            $accountId = create_account($data);
                            
                            if ($accountId) {
                                $message = 'สร้างบัญชีสำเร็จ';
                                $messageType = 'success';
                            } else {
                                $message = 'ไม่สามารถสร้างบัญชีได้';
                                $messageType = 'danger';
                            }
                        } else {
                            $message = 'คุณไม่มีสิทธิ์สร้างบัญชี';
                            $messageType = 'danger';
                        }
                        break;
                        
                    case 'update':
                        if (AccessControl::check('accounting.edit')) {
                            // TODO: Implement update
                            $message = 'ฟังก์ชันแก้ไขยังไม่พร้อมใช้งาน';
                            $messageType = 'warning';
                        } else {
                            $message = 'คุณไม่มีสิทธิ์แก้ไขบัญชี';
                            $messageType = 'danger';
                        }
                        break;
                        
                    case 'toggle_status':
                        if (AccessControl::check('accounting.edit')) {
                            $accountId = intval($_POST['account_id']);
                            $sql = "UPDATE chart_of_accounts 
                                    SET is_active = NOT is_active 
                                    WHERE id = :id AND is_system_account = 0";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':id' => $accountId]);
                            
                            AuditLog::log('account_status_changed', "Account ID: {$accountId}");
                            
                            $message = 'เปลี่ยนสถานะบัญชีสำเร็จ';
                            $messageType = 'success';
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ดึงข้อมูลบัญชีทั้งหมด
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '1';
$search = $_GET['search'] ?? '';

$filters = [];
if ($filterType) {
    $filters['account_type'] = $filterType;
}
if ($filterStatus !== '') {
    $filters['is_active'] = $filterStatus;
}

$accounts = get_accounts($filters);

// กรองตาม search
if ($search) {
    $accounts = array_filter($accounts, function($account) use ($search) {
        return stripos($account['account_code'], $search) !== false ||
               stripos($account['account_name'], $search) !== false;
    });
}

// จัดกลุ่มตามประเภท
$groupedAccounts = [];
foreach ($accounts as $account) {
    $type = $account['account_type'];
    if (!isset($groupedAccounts[$type])) {
        $groupedAccounts[$type] = [];
    }
    $groupedAccounts[$type][] = $account;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผังบัญชี - ระบบบัญชี</title>
    
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
                        <a class="nav-link active" href="chart-of-accounts.php">
                            <i class="fas fa-list"></i> ผังบัญชี
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="journal-entries.php">
                            <i class="fas fa-book"></i> รายการบัญชี
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
                    <i class="fas fa-list-alt me-3"></i>ผังบัญชี
                </h1>
                <p class="lead">จัดการและดูรายการบัญชีทั้งหมด</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <div class="content-wrapper mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-custom">
                            <i class="fas fa-search me-2"></i>ค้นหา
                        </label>
                        <input type="text" name="search" class="form-control form-control-custom" 
                               placeholder="รหัสบัญชีหรือชื่อบัญชี..." 
                               value="<?php echo e($search); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label-custom">
                            <i class="fas fa-filter me-2"></i>ประเภทบัญชี
                        </label>
                        <select name="type" class="form-control form-control-custom">
                            <option value="">ทั้งหมด</option>
                            <?php foreach (ACCOUNT_TYPES as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filterType === $key ? 'selected' : ''; ?>>
                                    <?php echo e($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label-custom">
                            <i class="fas fa-toggle-on me-2"></i>สถานะ
                        </label>
                        <select name="status" class="form-control form-control-custom">
                            <option value="">ทั้งหมด</option>
                            <option value="1" <?php echo $filterStatus === '1' ? 'selected' : ''; ?>>ใช้งานอยู่</option>
                            <option value="0" <?php echo $filterStatus === '0' ? 'selected' : ''; ?>>ปิดใช้งาน</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search me-2"></i>ค้นหา
                        </button>
                    </div>
                </form>
            </div>

            <!-- Action Buttons -->
            <div class="mb-4 d-flex gap-2">
                <?php if (AccessControl::check('accounting.create')): ?>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                    <i class="fas fa-plus me-2"></i>สร้างบัญชีใหม่
                </button>
                <?php endif; ?>
                
                <a href="?export=excel" class="btn btn-outline-custom">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </a>
                
                <a href="?export=pdf" class="btn btn-outline-custom">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </a>
            </div>

            <!-- Accounts Table by Type -->
            <?php foreach (ACCOUNT_TYPES as $typeKey => $typeName): ?>
                <?php if (isset($groupedAccounts[$typeKey]) && !empty($groupedAccounts[$typeKey])): ?>
                <div class="content-wrapper mb-4">
                    <h4 class="mb-3">
                        <i class="fas fa-folder-open me-2" style="color: #ff6b00;"></i>
                        <?php echo e($typeName); ?>
                        <span class="badge bg-secondary ms-2"><?php echo count($groupedAccounts[$typeKey]); ?> บัญชี</span>
                    </h4>
                    
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">รหัสบัญชี</th>
                                    <th>ชื่อบัญชี</th>
                                    <th style="width: 20%;">ประเภทย่อย</th>
                                    <th style="width: 15%;" class="text-end">ยอดคงเหลือ</th>
                                    <th style="width: 10%;" class="text-center">สถานะ</th>
                                    <th style="width: 10%;" class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groupedAccounts[$typeKey] as $account): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($account['account_code']); ?></strong>
                                        <?php if ($account['is_system_account']): ?>
                                            <span class="badge bg-info ms-1" title="บัญชีระบบ">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($account['account_name']); ?></td>
                                    <td>
                                        <?php if ($account['account_subtype']): ?>
                                            <small class="text-muted"><?php echo e($account['account_subtype']); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php
                                        try {
                                            $balance = get_account_balance($account['id']);
                                            $color = $balance >= 0 ? '#28a745' : '#dc3545';
                                        } catch (Exception $e) {
                                            $balance = 0;
                                            $color = '#6c757d';
                                        }
                                        ?>
                                        <strong style="color: <?php echo $color; ?>">
                                            <?php echo format_accounting_amount($balance); ?>
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($account['is_active']): ?>
                                            <span class="badge bg-success">ใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ปิด</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" 
                                                    onclick="viewAccount(<?php echo $account['id']; ?>)"
                                                    title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if (AccessControl::check('accounting.edit') && !$account['is_system_account']): ?>
                                            <button type="button" class="btn btn-warning" 
                                                    onclick="editAccount(<?php echo $account['id']; ?>)"
                                                    title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('ต้องการเปลี่ยนสถานะบัญชีนี้หรือไม่?')">
                                                <?php CSRF::insertHiddenField(); ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                <button type="submit" class="btn btn-secondary" title="เปลี่ยนสถานะ">
                                                    <i class="fas fa-toggle-on"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- No Results -->
            <?php if (empty($groupedAccounts)): ?>
            <div class="content-wrapper text-center py-5">
                <i class="fas fa-inbox fa-4x mb-3" style="opacity: 0.3;"></i>
                <h4>ไม่พบข้อมูลบัญชี</h4>
                <p class="text-muted">ลองเปลี่ยนเงื่อนไขการค้นหา หรือสร้างบัญชีใหม่</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Account Modal -->
    <div class="modal fade" id="createAccountModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 107, 0, 0.3);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255, 107, 0, 0.3);">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2" style="color: #ff6b00;"></i>
                        สร้างบัญชีใหม่
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <?php CSRF::insertHiddenField(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Account Code -->
                            <div class="col-md-4">
                                <label class="form-label-custom">รหัสบัญชี *</label>
                                <input type="text" name="account_code" class="form-control form-control-custom" 
                                       placeholder="X-XXXX" pattern="[1-5]-\d{4}" required
                                       title="รูปแบบ: X-XXXX (เช่น 1-1000)">
                                <small class="text-muted">รูปแบบ: X-XXXX (เช่น 1-1000)</small>
                            </div>
                            
                            <!-- Account Name -->
                            <div class="col-md-8">
                                <label class="form-label-custom">ชื่อบัญชี *</label>
                                <input type="text" name="account_name" class="form-control form-control-custom" 
                                       placeholder="เช่น เงินสดในมือ" required>
                            </div>
                            
                            <!-- Account Type -->
                            <div class="col-md-6">
                                <label class="form-label-custom">ประเภทบัญชีหลัก *</label>
                                <select name="account_type" class="form-control form-control-custom" required>
                                    <option value="">-- เลือกประเภท --</option>
                                    <?php foreach (ACCOUNT_TYPES as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo e($value); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Account Subtype -->
                            <div class="col-md-6">
                                <label class="form-label-custom">ประเภทย่อย</label>
                                <input type="text" name="account_subtype" class="form-control form-control-custom" 
                                       placeholder="เช่น สินทรัพย์หมุนเวียน">
                            </div>
                            
                            <!-- Parent Account -->
                            <div class="col-md-6">
                                <label class="form-label-custom">บัญชีหลัก (ถ้ามี)</label>
                                <select name="parent_account_id" class="form-control form-control-custom">
                                    <option value="">-- ไม่มี --</option>
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?php echo $acc['id']; ?>">
                                            <?php echo e($acc['account_code'] . ' - ' . $acc['account_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label-custom">คำอธิบาย</label>
                                <textarea name="description" class="form-control form-control-custom" rows="3"
                                          placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                            </div>
                        </div>
                        
                        <!-- Account Code Reference -->
                        <div class="alert alert-info-custom mt-3">
                            <strong><i class="fas fa-info-circle me-2"></i>รหัสบัญชีมาตรฐาน:</strong>
                            <ul class="mb-0 mt-2">
                                <li>1-XXXX = สินทรัพย์ (Assets)</li>
                                <li>2-XXXX = หนี้สิน (Liabilities)</li>
                                <li>3-XXXX = ส่วนของเจ้าของ (Equity)</li>
                                <li>4-XXXX = รายได้ (Revenue)</li>
                                <li>5-XXXX = ค่าใช้จ่าย (Expenses)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="border-top: 1px solid rgba(255, 107, 0, 0.3);">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save me-2"></i>บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewAccount(accountId) {
            // TODO: Implement view account details
            window.location.href = 'general-ledger.php?account_id=' + accountId;
        }
        
        function editAccount(accountId) {
            // TODO: Implement edit account
            alert('ฟังก์ชันแก้ไขยังไม่พร้อมใช้งาน');
        }
    </script>
</body>
</html>