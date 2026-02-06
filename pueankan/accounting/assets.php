<?php
/**
 * ============================================
 * ไฟล์: accounting/assets.php
 * คำอธิบาย: จัดการทรัพย์สินถาวร (Fixed Assets)
 * วัตถุประสงค์: บันทึก คำนวณค่าเสื่อม ติดตามทรัพย์สิน
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
                case 'create':
                    if (!AccessControl::check('accounting.create')) {
                        throw new Exception('คุณไม่มีสิทธิ์สร้างทรัพย์สิน');
                    }
                    
                    $data = [
                        'asset_code' => Sanitize::string($_POST['asset_code']),
                        'asset_name' => Sanitize::string($_POST['asset_name']),
                        'asset_category' => Sanitize::string($_POST['asset_category']),
                        'purchase_date' => $_POST['purchase_date'],
                        'purchase_price' => floatval($_POST['purchase_price']),
                        'salvage_value' => floatval($_POST['salvage_value']),
                        'useful_life_years' => intval($_POST['useful_life_years']),
                        'depreciation_method' => $_POST['depreciation_method'],
                        'location' => Sanitize::string($_POST['location'] ?? ''),
                        'description' => Sanitize::string($_POST['description'] ?? '')
                    ];
                    
                    $sql = "INSERT INTO fixed_assets (
                                asset_code, asset_name, asset_category,
                                purchase_date, purchase_price, salvage_value,
                                useful_life_years, depreciation_method,
                                location, description, created_by
                            ) VALUES (
                                :asset_code, :asset_name, :asset_category,
                                :purchase_date, :purchase_price, :salvage_value,
                                :useful_life_years, :depreciation_method,
                                :location, :description, :created_by
                            )";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':asset_code' => $data['asset_code'],
                        ':asset_name' => $data['asset_name'],
                        ':asset_category' => $data['asset_category'],
                        ':purchase_date' => $data['purchase_date'],
                        ':purchase_price' => $data['purchase_price'],
                        ':salvage_value' => $data['salvage_value'],
                        ':useful_life_years' => $data['useful_life_years'],
                        ':depreciation_method' => $data['depreciation_method'],
                        ':location' => $data['location'],
                        ':description' => $data['description'],
                        ':created_by' => $_SESSION['user_id']
                    ]);
                    
                    AuditLog::log('asset_created', "Asset: {$data['asset_code']} - {$data['asset_name']}");
                    
                    $message = 'สร้างทรัพย์สินสำเร็จ';
                    $messageType = 'success';
                    break;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ดึงรายการทรัพย์สิน
$filterCategory = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? 'active';

$sql = "SELECT * FROM fixed_assets WHERE 1=1";
$params = [];

if ($filterCategory) {
    $sql .= " AND asset_category = :category";
    $params[':category'] = $filterCategory;
}

if ($filterStatus === 'active') {
    $sql .= " AND disposal_date IS NULL";
} elseif ($filterStatus === 'disposed') {
    $sql .= " AND disposal_date IS NOT NULL";
}

$sql .= " ORDER BY asset_code";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// สรุปมูลค่า
$totalPurchasePrice = 0;
$totalDepreciation = 0;
$totalBookValue = 0;

foreach ($assets as $asset) {
    if (!$asset['disposal_date']) {
        $totalPurchasePrice += $asset['purchase_price'];
        $totalDepreciation += $asset['accumulated_depreciation'];
        $totalBookValue += $asset['book_value'];
    }
}

// ดึงหมวดหมู่ที่มีอยู่
$sqlCategories = "SELECT DISTINCT asset_category FROM fixed_assets ORDER BY asset_category";
$categories = $pdo->query($sqlCategories)->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการทรัพย์สิน - ระบบบัญชี</title>
    
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
                        <a class="nav-link active" href="assets.php">
                            <i class="fas fa-building"></i> ทรัพย์สิน
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="period-closing.php">
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
                    <i class="fas fa-building me-3"></i>จัดการทรัพย์สิน
                </h1>
                <p class="lead">บันทึกและติดตามทรัพย์สินถาวร (Fixed Assets)</p>
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
                            <i class="fas fa-shopping-cart fa-3x mb-3" style="color: #00adb5;"></i>
                            <h6 class="text-muted">มูลค่าซื้อ</h6>
                            <h3 style="color: #00adb5;"><?php echo format_accounting_amount($totalPurchasePrice); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-3x mb-3" style="color: #ffc107;"></i>
                            <h6 class="text-muted">ค่าเสื่อมสะสม</h6>
                            <h3 style="color: #ffc107;"><?php echo format_accounting_amount($totalDepreciation); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-body text-center">
                            <i class="fas fa-coins fa-3x mb-3" style="color: #28a745;"></i>
                            <h6 class="text-muted">มูลค่าตามบัญชี</h6>
                            <h3 style="color: #28a745;"><?php echo format_accounting_amount($totalBookValue); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Actions -->
            <div class="content-wrapper mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-custom">หมวดหมู่</label>
                        <select name="category" class="form-control form-control-custom">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat); ?>" <?php echo $filterCategory === $cat ? 'selected' : ''; ?>>
                                    <?php echo e($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label-custom">สถานะ</label>
                        <select name="status" class="form-control form-control-custom">
                            <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>ใช้งานอยู่</option>
                            <option value="disposed" <?php echo $filterStatus === 'disposed' ? 'selected' : ''; ?>>จำหน่ายแล้ว</option>
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-filter me-2"></i>กรอง
                        </button>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <?php if (AccessControl::check('accounting.create')): ?>
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#createAssetModal">
                            <i class="fas fa-plus me-2"></i>เพิ่มทรัพย์สิน
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Assets Table -->
            <div class="content-wrapper">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th style="width: 10%;">รหัส</th>
                                <th>ชื่อทรัพย์สิน</th>
                                <th style="width: 12%;">หมวดหมู่</th>
                                <th style="width: 10%;">วันที่ซื้อ</th>
                                <th style="width: 12%;" class="text-end">มูลค่าซื้อ</th>
                                <th style="width: 12%;" class="text-end">ค่าเสื่อมสะสม</th>
                                <th style="width: 12%;" class="text-end">มูลค่าตามบัญชี</th>
                                <th style="width: 10%;" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    ไม่พบรายการทรัพย์สิน
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($assets as $asset): ?>
                                <tr <?php echo $asset['disposal_date'] ? 'style="opacity: 0.6;"' : ''; ?>>
                                    <td>
                                        <strong><?php echo e($asset['asset_code']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo e($asset['asset_name']); ?>
                                        <?php if ($asset['location']): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo e($asset['location']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($asset['asset_category']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($asset['purchase_date'])); ?></td>
                                    <td class="text-end"><?php echo format_accounting_amount($asset['purchase_price']); ?></td>
                                    <td class="text-end" style="color: #ffc107;">
                                        <?php echo format_accounting_amount($asset['accumulated_depreciation']); ?>
                                    </td>
                                    <td class="text-end">
                                        <strong style="color: #28a745;">
                                            <?php echo format_accounting_amount($asset['book_value']); ?>
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info" 
                                                    onclick="viewAsset(<?php echo $asset['id']; ?>)"
                                                    title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if (!$asset['disposal_date'] && AccessControl::check('accounting.edit')): ?>
                                            <button type="button" class="btn btn-warning" 
                                                    onclick="editAsset(<?php echo $asset['id']; ?>)"
                                                    title="แก้ไข">
                                                <i class="fas fa-edit"></i>
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

    <!-- Create Asset Modal -->
    <div class="modal fade" id="createAssetModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 107, 0, 0.3);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255, 107, 0, 0.3);">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2" style="color: #ff6b00;"></i>
                        เพิ่มทรัพย์สินใหม่
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <?php CSRF::insertHiddenField(); ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Asset Code -->
                            <div class="col-md-4">
                                <label class="form-label-custom">รหัสทรัพย์สิน *</label>
                                <input type="text" name="asset_code" class="form-control form-control-custom" 
                                       placeholder="เช่น ASSET-001" required>
                            </div>
                            
                            <!-- Asset Name -->
                            <div class="col-md-8">
                                <label class="form-label-custom">ชื่อทรัพย์สิน *</label>
                                <input type="text" name="asset_name" class="form-control form-control-custom" 
                                       placeholder="เช่น รถยนต์ Honda Civic" required>
                            </div>
                            
                            <!-- Category -->
                            <div class="col-md-6">
                                <label class="form-label-custom">หมวดหมู่ *</label>
                                <input type="text" name="asset_category" class="form-control form-control-custom" 
                                       placeholder="เช่น ยานพาหนะ, อาคาร, เครื่องจักร" required
                                       list="categoryList">
                                <datalist id="categoryList">
                                    <option value="ยานพาหนะ">
                                    <option value="อาคาร">
                                    <option value="เครื่องจักร">
                                    <option value="เครื่องใช้สำนักงาน">
                                    <option value="คอมพิวเตอร์">
                                </datalist>
                            </div>
                            
                            <!-- Purchase Date -->
                            <div class="col-md-6">
                                <label class="form-label-custom">วันที่ซื้อ *</label>
                                <input type="date" name="purchase_date" class="form-control form-control-custom" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <!-- Purchase Price -->
                            <div class="col-md-4">
                                <label class="form-label-custom">มูลค่าซื้อ *</label>
                                <input type="number" name="purchase_price" class="form-control form-control-custom" 
                                       step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                            
                            <!-- Salvage Value -->
                            <div class="col-md-4">
                                <label class="form-label-custom">มูลค่าซาก</label>
                                <input type="number" name="salvage_value" class="form-control form-control-custom" 
                                       step="0.01" min="0" value="0" placeholder="0.00">
                            </div>
                            
                            <!-- Useful Life -->
                            <div class="col-md-4">
                                <label class="form-label-custom">อายุการใช้งาน (ปี) *</label>
                                <input type="number" name="useful_life_years" class="form-control form-control-custom" 
                                       min="1" placeholder="5" required>
                            </div>
                            
                            <!-- Depreciation Method -->
                            <div class="col-md-6">
                                <label class="form-label-custom">วิธีคิดค่าเสื่อม *</label>
                                <select name="depreciation_method" class="form-control form-control-custom" required>
                                    <?php foreach (DEPRECIATION_METHODS as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo e($value); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Location -->
                            <div class="col-md-6">
                                <label class="form-label-custom">สถานที่ตั้ง</label>
                                <input type="text" name="location" class="form-control form-control-custom" 
                                       placeholder="เช่น สำนักงานใหญ่, โกดัง 1">
                            </div>
                            
                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label-custom">คำอธิบาย</label>
                                <textarea name="description" class="form-control form-control-custom" rows="3"
                                          placeholder="รายละเอียดเพิ่มเติม..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Info Alert -->
                        <div class="alert alert-info-custom mt-3">
                            <strong><i class="fas fa-info-circle me-2"></i>หมายเหตุ:</strong>
                            <ul class="mb-0 mt-2">
                                <li>ค่าเสื่อมราคาจะคำนวณอัตโนมัติตามวิธีที่เลือก</li>
                                <li>ระบบจะสร้างรายการบัญชีสำหรับค่าเสื่อมทุกเดือน</li>
                                <li>มูลค่าตามบัญชี = มูลค่าซื้อ - ค่าเสื่อมสะสม</li>
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
        function viewAsset(assetId) {
            window.location.href = 'asset-detail.php?id=' + assetId;
        }
        
        function editAsset(assetId) {
            // TODO: Implement edit
            alert('ฟังก์ชันแก้ไขยังไม่พร้อมใช้งาน');
        }
    </script>
</body>
</html>