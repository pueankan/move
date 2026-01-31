<?php
/**
 * Home Page - หน้าแรก
 * ร้านฮาร์ดแวร์และวัสดุก่อสร้าง
 */

require_once '../config/database.php';

// ดึงสินค้ายอดนิยม (6 รายการ)
$featured_products = db_fetch_all(
    "SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT 6"
);

// ดึงบริการ (4 รายการ)
$featured_services = db_fetch_all(
    "SELECT * FROM services WHERE is_active = 1 LIMIT 4"
);

// นับสถิติ
$total_products = db_count('products', 'is_active = 1');
$total_services = db_count('services', 'is_active = 1');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <!-- Animated Background -->
    <?php include '../includes/background.php'; ?>
    
    <!-- Theme Switcher -->
    <?php include '../includes/theme-switcher.php'; ?>
    
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="page-container">
        <!-- Hero Section -->
        <section class="hero-section py-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <h1 class="display-4 fw-bold glow-text mb-4">
                            ร้านฮาร์ดแวร์และ<br>วัสดุก่อสร้าง
                        </h1>
                        <p class="lead mb-4" style="color: rgba(255,255,255,0.9);">
                            ผู้เชี่ยวชาญด้านวัสดุก่อสร้างและอุปกรณ์ฮาร์ดแวร์ครบวงจร 
                            พร้อมให้บริการคำปรึกษาและติดตั้งโดยช่างมืออาชีพ
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="products.php" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-boxes me-2"></i>ดูสินค้าทั้งหมด
                            </a>
                            <a href="services.php" class="btn btn-outline-custom btn-lg">
                                <i class="fas fa-wrench me-2"></i>บริการของเรา
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="stats-grid">
                            <div class="stat-card glass-effect p-4 rounded-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="fas fa-box-open fa-3x" style="color: #ff6b00;"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" style="color: #ff6b00;"><?php echo $total_products; ?>+</h3>
                                        <p class="mb-0">ผลิตภัณฑ์</p>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card glass-effect p-4 rounded-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="fas fa-tools fa-3x" style="color: #00adb5;"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" style="color: #00adb5;"><?php echo $total_services; ?>+</h3>
                                        <p class="mb-0">บริการ</p>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card glass-effect p-4 rounded-4">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="fas fa-users fa-3x" style="color: #ffc107;"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0" style="color: #ffc107;">1,000+</h3>
                                        <p class="mb-0">ลูกค้าพึงพอใจ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="products-section py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-5 fw-bold">
                        <i class="fas fa-star me-2" style="color: #ffc107;"></i>
                        สินค้าแนะนำ
                    </h2>
                    <p class="text-muted">สินค้าคุณภาพดีที่ลูกค้าเลือกซื้อมากที่สุด</p>
                </div>

                <div class="row g-4">
                    <?php foreach ($featured_products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card-custom h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <?php 
                                    $badge_class = $product['stock'] > 20 ? 'badge-in-stock' : 
                                                  ($product['stock'] > 0 ? 'badge-low-stock' : 'badge-out-stock');
                                    $badge_text = $product['stock'] > 20 ? 'มีสินค้า' : 
                                                 ($product['stock'] > 0 ? 'เหลือน้อย' : 'หมด');
                                    ?>
                                    <span class="badge-stock <?php echo $badge_class; ?>">
                                        <?php echo $badge_text; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($product['category']); ?>
                                </p>
                                <p class="mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-0" style="color: #ff6b00;">
                                            ฿<?php echo number_format($product['price'], 2); ?>
                                        </h4>
                                        <small class="text-muted">/ <?php echo htmlspecialchars($product['unit']); ?></small>
                                    </div>
                                    <button class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-5">
                    <a href="products.php" class="btn btn-outline-custom btn-lg">
                        ดูสินค้าทั้งหมด <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-5 fw-bold">
                        <i class="fas fa-wrench me-2" style="color: #00adb5;"></i>
                        บริการของเรา
                    </h2>
                    <p class="text-muted">บริการติดตั้งและก่อสร้างโดยช่างมืออาชีพ</p>
                </div>

                <div class="row g-4">
                    <?php foreach ($featured_services as $service): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card-custom h-100 text-center">
                            <div class="card-body">
                                <div class="service-icon mb-3">
                                    <i class="fas fa-hammer fa-3x" style="color: #00adb5;"></i>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($service['description']); ?></p>
                                <div class="mb-3">
                                    <h5 style="color: #ffc107;">
                                        ฿<?php echo number_format($service['base_price'], 2); ?>
                                    </h5>
                                    <small class="text-muted">/ <?php echo htmlspecialchars($service['unit']); ?></small>
                                </div>
                                <a href="services.php" class="btn btn-secondary-custom w-100">
                                    <i class="fas fa-info-circle me-2"></i>รายละเอียด
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-5">
                    <a href="services.php" class="btn btn-outline-custom btn-lg">
                        ดูบริการทั้งหมด <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Quick Tools -->
        <section class="tools-section py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-5 fw-bold">
                        <i class="fas fa-calculator me-2" style="color: #ff6b00;"></i>
                        เครื่องมือช่วยเหลือ
                    </h2>
                    <p class="text-muted">คำนวณและประมาณการได้ง่ายๆ</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card-custom">
                            <div class="card-header">
                                <i class="fas fa-calculator me-2"></i>เครื่องคำนวณช่าง
                            </div>
                            <div class="card-body">
                                <p>คำนวณปริมาณวัสดุที่ต้องใช้ในงานก่อสร้าง เช่น สี ยิปซั่ม ปูน ทราย</p>
                                <a href="calculator.php" class="btn btn-primary-custom w-100">
                                    <i class="fas fa-arrow-right me-2"></i>เริ่มคำนวณ
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-custom">
                            <div class="card-header">
                                <i class="fas fa-file-invoice me-2"></i>ระบบใบเสนอราคา
                            </div>
                            <div class="card-body">
                                <p>สร้างใบเสนอราคาสำหรับลูกค้า พร้อมสรุปราคาอัตโนมัติ</p>
                                <a href="quotation.php" class="btn btn-primary-custom w-100">
                                    <i class="fas fa-arrow-right me-2"></i>สร้างใบเสนอราคา
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>