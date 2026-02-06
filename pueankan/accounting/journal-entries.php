<?php
/**
 * ============================================
 * ไฟล์: accounting/journal-entries.php
 * คำอธิบาย: บันทึกและจัดการรายการบัญชี (Journal Entries)
 * วัตถุประสงค์: Double-Entry Bookkeeping System
 * ============================================
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/security.php';
require_once '../config/accounting.php';
require_once '../includes/accounting-functions.php';

// ตรวจสอบสิทธิ์
AccessControl::requirePermission('accounting.view');

// Rate Limiting
RateLimit::check('journal_entries', 50, 600); // 50 requests per 10 minutes

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
                case 'create':
                    if (!AccessControl::check('accounting.create')) {
                        throw new Exception('คุณไม่มีสิทธิ์สร้างรายการบัญชี');
                    }
                    
                    // Prepare data
                    $entryData = [
                        'entry_date' => $_POST['entry_date'],
                        'entry_type' => $_POST['entry_type'],
                        'description' => $_POST['description'],
                        'reference_type' => $_POST['reference_type'] ?? null,
                        'reference_id' => $_POST['reference_id'] ?? null,
                        'lines' => []
                    ];
                    
                    // Parse lines
                    if (isset($_POST['lines']) && is_array($_POST['lines'])) {
                        foreach ($_POST['lines'] as $line) {
                            if (!empty($line['account_id'])) {
                                $entryData['lines'][] = [
                                    'account_id' => intval($line['account_id']),
                                    'description' => $line['description'] ?? '',
                                    'debit_amount' => floatval($line['debit_amount'] ?? 0),
                                    'credit_amount' => floatval($line['credit_amount'] ?? 0)
                                ];
                            }
                        }
                    }
                    
                    $result = create_journal_entry($entryData);
                    
                    if ($result['success']) {
                        $message = "สร้างรายการบัญชีสำเร็จ เลขที่: {$result['entry_number']}";
                        $messageType = 'success';
                    }
                    break;
                    
                case 'post':
                    if (!AccessControl::check('accounting.edit')) {
                        throw new Exception('คุณไม่มีสิทธิ์ Post รายการบัญชี');
                    }
                    
                    $entryId = intval($_POST['entry_id']);
                    post_journal_entry($entryId);
                    
                    $message = 'Post รายการบัญชีสำเร็จ';
                    $messageType = 'success';
                    break;
                    
                case 'void':
                    if (!AccessControl::check('accounting.edit')) {
                        throw new Exception('คุณไม่มีสิทธิ์ยกเลิกรายการบัญชี');
                    }
                    
                    $entryId = intval($_POST['entry_id']);
                    $reason = $_POST['void_reason'];
                    
                    void_journal_entry($entryId, $reason);
                    
                    $message = 'ยกเลิกรายการบัญชีสำเร็จ';
                    $messageType = 'success';
                    break;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ดึงรายการบัญชี
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

$sql = "SELECT je.*, u.full_name as created_by_name,
               ap.period_name
        FROM journal_entries je
        LEFT JOIN users u ON je.created_by = u.id
        LEFT JOIN accounting_periods ap ON je.period_id = ap.id
        WHERE 1=1";

$params = [];

if ($filterStatus) {
    $sql .= " AND je.status = :status";
    $params[':status'] = $filterStatus;
}

if ($filterType) {
    $sql .= " AND je.entry_type = :type";
    $params[':type'] = $filterType;
}

if ($filterDateFrom) {
    $sql .= " AND je.entry_date >= :date_from";
    $params[':date_from'] = $filterDateFrom;
}

if ($filterDateTo) {
    $sql .= " AND je.entry_date <= :date_to";
    $params[':date_to'] = $filterDateTo;
}

$sql .= " ORDER BY je.entry_date DESC, je.id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total
$sqlCount = "SELECT COUNT(*) FROM journal_entries je WHERE 1=1";
if ($filterStatus) $sqlCount .= " AND je.status = :status";
if ($filterType) $sqlCount .= " AND je.entry_type = :type";
if ($filterDateFrom) $sqlCount .= " AND je.entry_date >= :date_from";
if ($filterDateTo) $sqlCount .= " AND je.entry_date <= :date_to";

$stmtCount = $pdo->prepare($sqlCount);
foreach ($params as $key => $value) {
    if ($key !== ':limit' && $key !== ':offset') {
        $stmtCount->bindValue($key, $value);
    }
}
$stmtCount->execute();
$totalEntries = $stmtCount->fetchColumn();
$totalPages = ceil($totalEntries / $perPage);

// ดึงบัญชีทั้งหมดสำหรับ dropdown
$accounts = get_accounts(['is_active' => 1]);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการบัญชี - ระบบบัญชี</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    
    <style>
        .entry-line-row {
            background: rgba(22, 33, 62, 0.5);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .balance-indicator {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .balance-ok {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .balance-error {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
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
                        <a class="nav-link" href="chart-of-accounts.php">
                            <i class="fas fa-list"></i> ผังบัญชี
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="journal-entries.php">
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
                    <i class="fas fa-book me-3"></i>รายการบัญชี
                </h1>
                <p class="lead">บันทึกรายการบัญชีแบบ Double-Entry</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Filter & Search -->
            <div class="content-wrapper mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label-custom">วันที่เริ่มต้น</label>
                        <input type="date" name="date_from" class="form-control form-control-custom" 
                               value="<?php echo e($filterDateFrom); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label-custom">วันที่สิ้นสุด</label>
                        <input type="date" name="date_to" class="form-control form-control-custom" 
                               value="<?php echo e($filterDateTo); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label-custom">ประเภทรายการ</label>
                        <select name="type" class="form-control form-control-custom">
                            <option value="">ทั้งหมด</option>
                            <?php foreach (ENTRY_TYPES as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filterType === $key ? 'selected' : ''; ?>>
                                    <?php echo e($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label-custom">สถานะ</label>
                        <select name="status" class="form-control form-control-custom">
                            <option value="">ทั้งหมด</option>
                            <?php foreach (TRANSACTION_STATUS as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>>
                                    <?php echo e($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Action Buttons -->
            <div class="mb-4">
                <?php if (AccessControl::check('accounting.create')): ?>
                <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createEntryModal">
                    <i class="fas fa-plus me-2"></i>สร้างรายการบัญชี
                </button>
                <?php endif; ?>
            </div>

            <!-- Entries Table -->
            <div class="content-wrapper">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th style="width: 12%;">เลขที่</th>
                                <th style="width: 10%;">วันที่</th>
                                <th style="width: 12%;">ประเภท</th>
                                <th>รายละเอียด</th>
                                <th style="width: 12%;" class="text-end">ยอดเงิน</th>
                                <th style="width: 10%;">สถานะ</th>
                                <th style="width: 10%;" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    ไม่พบรายการบัญชี
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($entry['entry_number']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo e($entry['period_name']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($entry['entry_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo e(ENTRY_TYPES[$entry['entry_type']] ?? $entry['entry_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo e(mb_substr($entry['description'], 0, 60)); ?>
                                        <?php if (mb_strlen($entry['description']) > 60): ?>...<?php endif; ?>
                                        <?php if ($entry['reference_type']): ?>
                                            <br>
                                            <small class="text-muted">
                                                Ref: <?php echo e($entry['reference_type']); ?> #<?php echo e($entry['reference_id']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong style="color: #ffc107;">
                                            <?php echo format_accounting_amount($entry['total_debit']); ?>
                                        </strong>
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
                                        ?>
                                        <span class="badge bg-<?php echo $statusColors[$entry['status']] ?? 'secondary'; ?>">
                                            <?php echo e(TRANSACTION_STATUS[$entry['status']] ?? $entry['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" 
                                                    onclick="viewEntry(<?php echo $entry['id']; ?>)"
                                                    title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($entry['status'] === 'draft' && AccessControl::check('accounting.edit')): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('ต้องการ Post รายการนี้หรือไม่?')">
                                                <?php CSRF::insertHiddenField(); ?>
                                                <input type="hidden" name="action" value="post">
                                                <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="btn btn-success" title="Post">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($entry['status'] === 'posted' && AccessControl::check('accounting.edit')): ?>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="voidEntry(<?php echo $entry['id']; ?>)"
                                                    title="ยกเลิก">
                                                <i class="fas fa-ban"></i>
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
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo e($filterStatus); ?>&type=<?php echo e($filterType); ?>&date_from=<?php echo e($filterDateFrom); ?>&date_to=<?php echo e($filterDateTo); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Entry Modal -->
    <div class="modal fade" id="createEntryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 107, 0, 0.3);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255, 107, 0, 0.3);">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2" style="color: #ff6b00;"></i>
                        สร้างรายการบัญชี
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" id="createEntryForm">
                    <?php CSRF::insertHiddenField(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="modal-body">
                        <!-- Entry Header -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label-custom">วันที่ *</label>
                                <input type="date" name="entry_date" class="form-control form-control-custom" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label-custom">ประเภทรายการ *</label>
                                <select name="entry_type" class="form-control form-control-custom" required>
                                    <?php foreach (ENTRY_TYPES as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo e($value); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label-custom">คำอธิบาย *</label>
                                <input type="text" name="description" class="form-control form-control-custom" 
                                       placeholder="รายละเอียดรายการ" required>
                            </div>
                        </div>
                        
                        <!-- Entry Lines -->
                        <h6 class="mb-3">
                            <i class="fas fa-list me-2" style="color: #ff6b00;"></i>
                            รายการบัญชี (Debit/Credit)
                        </h6>
                        
                        <div id="entryLinesContainer">
                            <!-- Line 1 -->
                            <div class="entry-line-row" data-line="1">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label-custom">บัญชี *</label>
                                        <select name="lines[0][account_id]" class="form-control form-control-custom" required>
                                            <option value="">-- เลือกบัญชี --</option>
                                            <?php foreach ($accounts as $account): ?>
                                                <option value="<?php echo $account['id']; ?>">
                                                    <?php echo e($account['account_code'] . ' - ' . $account['account_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label-custom">Debit</label>
                                        <input type="number" name="lines[0][debit_amount]" 
                                               class="form-control form-control-custom debit-input" 
                                               step="0.01" min="0" value="0"
                                               onchange="updateBalance()">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label-custom">Credit</label>
                                        <input type="number" name="lines[0][credit_amount]" 
                                               class="form-control form-control-custom credit-input" 
                                               step="0.01" min="0" value="0"
                                               onchange="updateBalance()">
                                    </div>
                                    
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger w-100" onclick="removeLine(this)" disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Line 2 -->
                            <div class="entry-line-row" data-line="2">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label-custom">บัญชี *</label>
                                        <select name="lines[1][account_id]" class="form-control form-control-custom" required>
                                            <option value="">-- เลือกบัญชี --</option>
                                            <?php foreach ($accounts as $account): ?>
                                                <option value="<?php echo $account['id']; ?>">
                                                    <?php echo e($account['account_code'] . ' - ' . $account['account_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <input type="number" name="lines[1][debit_amount]" 
                                               class="form-control form-control-custom debit-input" 
                                               step="0.01" min="0" value="0"
                                               onchange="updateBalance()">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <input type="number" name="lines[1][credit_amount]" 
                                               class="form-control form-control-custom credit-input" 
                                               step="0.01" min="0" value="0"
                                               onchange="updateBalance()">
                                    </div>
                                    
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger w-100" onclick="removeLine(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-secondary-custom mt-3" onclick="addLine()">
                            <i class="fas fa-plus me-2"></i>เพิ่มรายการ
                        </button>
                        
                        <!-- Balance Summary -->
                        <div class="alert alert-info-custom mt-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Total Debit:</strong>
                                    <span id="totalDebit" class="float-end">0.00</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Credit:</strong>
                                    <span id="totalCredit" class="float-end">0.00</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>สถานะ:</strong>
                                    <span id="balanceStatus" class="balance-indicator balance-error float-end">
                                        ไม่สมดุล
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="border-top: 1px solid rgba(255, 107, 0, 0.3);">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>ยกเลิก
                        </button>
                        <button type="submit" class="btn btn-primary-custom" id="submitBtn" disabled>
                            <i class="fas fa-save me-2"></i>บันทึกรายการ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Void Entry Modal -->
    <div class="modal fade" id="voidEntryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 107, 0, 0.3);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255, 107, 0, 0.3);">
                    <h5 class="modal-title">
                        <i class="fas fa-ban me-2" style="color: #dc3545;"></i>
                        ยกเลิกรายการบัญชี
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <?php CSRF::insertHiddenField(); ?>
                    <input type="hidden" name="action" value="void">
                    <input type="hidden" name="entry_id" id="voidEntryId">
                    
                    <div class="modal-body">
                        <div class="alert alert-warning-custom">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            การยกเลิกรายการจะไม่สามารถย้อนกลับได้
                        </div>
                        
                        <label class="form-label-custom">เหตุผลในการยกเลิก *</label>
                        <textarea name="void_reason" class="form-control form-control-custom" 
                                  rows="3" required placeholder="ระบุเหตุผล..."></textarea>
                    </div>
                    
                    <div class="modal-footer" style="border-top: 1px solid rgba(255, 107, 0, 0.3);">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                            ปิด
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban me-2"></i>ยืนยันการยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let lineCount = 2;
        
        function addLine() {
            const container = document.getElementById('entryLinesContainer');
            const newLine = document.createElement('div');
            newLine.className = 'entry-line-row';
            newLine.setAttribute('data-line', lineCount);
            
            newLine.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-5">
                        <select name="lines[${lineCount}][account_id]" class="form-control form-control-custom">
                            <option value="">-- เลือกบัญชี --</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo e($account['account_code'] . ' - ' . $account['account_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="lines[${lineCount}][debit_amount]" 
                               class="form-control form-control-custom debit-input" 
                               step="0.01" min="0" value="0" onchange="updateBalance()">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="lines[${lineCount}][credit_amount]" 
                               class="form-control form-control-custom credit-input" 
                               step="0.01" min="0" value="0" onchange="updateBalance()">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger w-100" onclick="removeLine(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(newLine);
            lineCount++;
            updateBalance();
        }
        
        function removeLine(btn) {
            const row = btn.closest('.entry-line-row');
            row.remove();
            updateBalance();
        }
        
        function updateBalance() {
            let totalDebit = 0;
            let totalCredit = 0;
            
            document.querySelectorAll('.debit-input').forEach(input => {
                totalDebit += parseFloat(input.value) || 0;
            });
            
            document.querySelectorAll('.credit-input').forEach(input => {
                totalCredit += parseFloat(input.value) || 0;
            });
            
            document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
            document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);
            
            const balanced = Math.abs(totalDebit - totalCredit) < 0.01;
            const statusEl = document.getElementById('balanceStatus');
            const submitBtn = document.getElementById('submitBtn');
            
            if (balanced && totalDebit > 0) {
                statusEl.textContent = 'สมดุล';
                statusEl.className = 'balance-indicator balance-ok float-end';
                submitBtn.disabled = false;
            } else {
                statusEl.textContent = 'ไม่สมดุล';
                statusEl.className = 'balance-indicator balance-error float-end';
                submitBtn.disabled = true;
            }
        }
        
        function viewEntry(entryId) {
            window.location.href = 'journal-entry-detail.php?id=' + entryId;
        }
        
        function voidEntry(entryId) {
            document.getElementById('voidEntryId').value = entryId;
            const modal = new bootstrap.Modal(document.getElementById('voidEntryModal'));
            modal.show();
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateBalance();
        });
    </script>
</body>
</html>