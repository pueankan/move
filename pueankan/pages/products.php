<?php
/**
 * Products Page - หน้าสินค้า
 * แสดงสินค้าทั้งหมดพร้อมระบบค้นหาและกรอง
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// จัดการ Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = floatval($_POST['quantity'] ?? 1);
    
    if ($product_id > 0 && check_stock($product_id, $quantity)) {
        cart_add($product_id, $quantity);
        flash_set('success', 'เพิ่มสินค้าลงตะกร้าแล้ว');
        redirect('products.php');
    } else {
        flash_set('error', 'สินค้าไม่เพียงพอ');
    }
}

// รับค่าจากฟอร์มค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// สร้าง SQL Query
$sql = "SELECT * FROM products WHERE is_active = 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($category)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

$sql .= " ORDER BY name ASC";

$products = db_fetch_all($sql, $params);

// ดึงหมวดหมู่ทั้งหมด
$categories = db_fetch_all("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าทั้งหมด - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <?php include '../includes/background.php'; ?>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="page-container">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold glow-text">
                    <i class="fas fa-boxes me-3"></i>สินค้าทั้งหมด
                </h1>
                <p class="lead">วัสดุก่อสร้างและอุปกรณ์ฮาร์ดแวร์คุณภาพดี</p>
            </div>

            <!-- Search & Filter -->
            <div class="content-wrapper mb-4">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label-custom">
                            <i class="fas fa-search me-2"></i>ค้นหาสินค้า
                        </label>
                        <input type="text" 
                               name="search" 
                               class="form-control form-control-custom" 
                               placeholder="ชื่อสินค้าหรือคำอธิบาย..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-custom">
                            <i class="fas fa-filter me-2"></i>หมวดหมู่
                        </label>
                        <select name="category" class="form-control form-control-custom">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                        <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary-custom w-50">
                            <i class="fas fa-search me-2"></i>ค้นหา
                        </button>
                        <a href="products.php" class="btn btn-secondary-custom w-50">
                            <i class="fas fa-redo me-2"></i>รีเซ็ต
                        </a>
                    </div>
                </form>
            </div>

            <!-- Products Count -->
            <div class="mb-4">
                <?php flash_display(); ?>
                <div class="alert alert-info-custom">
                    <i class="fas fa-info-circle me-2"></i>
                    พบสินค้า <strong><?php echo count($products); ?></strong> รายการ
                    <?php if ($search): ?>
                        สำหรับคำค้นหา "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                    <?php if ($category): ?>
                        ในหมวดหมู่ "<strong><?php echo htmlspecialchars($category); ?></strong>"
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div class="content-wrapper text-center py-5">
                    <i class="fas fa-box-open fa-5x mb-4" style="color: rgba(255,255,255,0.3);"></i>
                    <h3>ไม่พบสินค้าที่ค้นหา</h3>
                    <p class="text-muted">กรุณาลองค้นหาด้วยคำอื่น หรือเลือกหมวดหมู่อื่น</p>
                    <a href="products.php" class="btn btn-primary-custom mt-3">
                        <i class="fas fa-arrow-left me-2"></i>กลับไปดูสินค้าทั้งหมด
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card-custom h-100">
                            <div class="card-body">
                                <!-- Product Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0 flex-grow-1">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h5>
                                    <?php 
                                    $stock = (int)$product['stock'];
                                    if ($stock > 20) {
                                        $badge_class = 'badge-in-stock';
                                        $badge_text = 'มีสินค้า';
                                        $badge_icon = 'fa-check-circle';
                                    } elseif ($stock > 0) {
                                        $badge_class = 'badge-low-stock';
                                        $badge_text = "เหลือ {$stock}";
                                        $badge_icon = 'fa-exclamation-triangle';
                                    } else {
                                        $badge_class = 'badge-out-stock';
                                        $badge_text = 'สินค้าหมด';
                                        $badge_icon = 'fa-times-circle';
                                    }
                                    ?>
                                    <span class="badge-stock <?php echo $badge_class; ?>">
                                        <i class="fas <?php echo $badge_icon; ?> me-1"></i>
                                        <?php echo $badge_text; ?>
                                    </span>
                                </div>

                                <!-- Category -->
                                <p class="mb-2">
                                    <span class="badge" style="background: rgba(0,173,181,0.2); color: #00adb5;">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars($product['category']); ?>
                                    </span>
                                </p>

                                <!-- Description -->
                                <p class="text-muted mb-3" style="min-height: 48px;">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </p>

                                <!-- Stock Info -->
                                <div class="mb-3 p-2 rounded" style="background: rgba(255,255,255,0.05);">
                                    <small class="text-muted">
                                        <i class="fas fa-warehouse me-2"></i>
                                        คงเหลือ: <strong style="color: #ffc107;"><?php echo $stock; ?></strong> 
                                        <?php echo htmlspecialchars($product['unit']); ?>
                                    </small>
                                </div>

                                <!-- Price & Action -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-0" style="color: #ff6b00;">
                                            ฿<?php echo number_format($product['price'], 2); ?>
                                        </h4>
                                        <small class="text-muted">/ <?php echo htmlspecialchars($product['unit']); ?></small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if ($stock > 0): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" name="add_to_cart" class="btn btn-primary-custom btn-sm" 
                                                        title="เพิ่มลงตะกร้า">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary-custom btn-sm" disabled>
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToQuote(productId) {
            // ฟังก์ชันเพิ่มสินค้าเข้าใบเสนอราคา
            alert('เพิ่มสินค้า ID: ' + productId + ' เข้าใบเสนอราคา (ยังไม่ได้เชื่อมต่อ)');
            // TODO: เชื่อมต่อกับระบบตะกร้าสินค้า/ใบเสนอราคา
        }

        // Active menu
        document.querySelector('a[href="products.php"]').classList.add('active');
    </script>
</body>
</html>