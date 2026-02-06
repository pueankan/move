<?php
/**
 * Admin Dashboard
 * แดชบอร์ดสำหรับผู้ดูแลระบบ
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// ดึงสถิติต่างๆ
$stats = [
    'total_products' => db_count('products', 'is_active = 1'),
    'total_services' => db_count('services', 'is_active = 1'),
    'total_orders' => db_count('orders'),
    'pending_orders' => db_count('orders', 'order_status = ?', ['pending']),
    'total_quotations' => db_count('quotations'),
    'low_stock_products' => db_count('products', 'stock < 10 AND is_active = 1'),
];

// คำสั่งซื้อล่าสุด
$recent_orders = db_fetch_all(
    "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10"
);

// สินค้าใกล้หมด
$low_stock_products = db_fetch_all(
    "SELECT * FROM products WHERE stock < 10 AND is_active = 1 ORDER BY stock ASC LIMIT 10"
);

// ยอดขายรวม
$total_sales = db_fetch_one(
    "SELECT SUM(grand_total) as total FROM orders WHERE payment_status = 'paid'"
);
$total_sales_amount = $total_sales['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <?php include '../includes/background.php'; ?>
    
    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(10px); border-bottom: 2px solid rgba(255, 107, 0, 0.3);">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tools me-2"></i>
                <span class="fw-bold">Admin Dashboard</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-boxes"></i> จัดการสินค้า
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> คำสั่งซื้อ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/index.php">
                            <i class="fas fa-store"></i> ไปหน้าร้าน
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="page-container">
        <div class="container py-5">
            <!-- Page Header -->
            <div class="mb-5">
                <h1 class="display-4 fw-bold glow-text">
                    <i class="fas fa-chart-line me-3"></i>Dashboard
                </h1>
                <p class="lead">ภาพรวมระบบและสถิติ</p>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-5">
                <!-- Total Products -->
                <div class="col-md-6 col-lg-3">
                    <div class="card-custom h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-box fa-3x mb-3" style="color: #ff6b00;"></i>
                            <h3 class="mb-1"><?php echo $stats['total_products']; ?></h3>
                            <p class="text-muted mb-0">สินค้าทั้งหมด</p>
                        </div>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="col-md-6 col-lg-3">
                    <div class="card-custom h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-bag fa-3x mb-3" style="color: #00adb5;"></i>
                            <h3 class="mb-1"><?php echo $stats['total_orders']; ?></h3>
                            <p class="text-muted mb-0">คำสั่งซื้อทั้งหมด</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="col-md-6 col-lg-3">
                    <div class="card-custom h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-3x mb-3" style="color: #ffc107;"></i>
                            <h3 class="mb-1"><?php echo $stats['pending_orders']; ?></h3>
                            <p class="text-muted mb-0">รอดำเนินการ</p>
                        </div>
                    </div>
                </div>

                <!-- Total Sales -->
                <div class="col-md-6 col-lg-3">
                    <div class="card-custom h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-dollar-sign fa-3x mb-3" style="color: #28a745;"></i>
                            <h3 class="mb-1"><?php echo format_currency($total_sales_amount, 0); ?></h3>
                            <p class="text-muted mb-0">ยอดขายรวม</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Orders -->
                <div class="col-lg-7">
                    <div class="content-wrapper">
                        <h4 class="mb-4">
                            <i class="fas fa-list me-2" style="color: #ff6b00;"></i>
                            คำสั่งซื้อล่าสุด
                        </h4>
                        
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>เลขที่</th>
                                        <th>ลูกค้า</th>
                                        <th>ยอดรวม</th>
                                        <th>สถานะ</th>
                                        <th>วันที่</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">ยังไม่มีคำสั่งซื้อ</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo format_currency($order['grand_total']); ?></td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'info',
                                                    'preparing' => 'primary',
                                                    'shipping' => 'primary',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $status_text = [
                                                    'pending' => 'รอดำเนินการ',
                                                    'confirmed' => 'ยืนยันแล้ว',
                                                    'preparing' => 'กำลังเตรียม',
                                                    'shipping' => 'กำลังจัดส่ง',
                                                    'completed' => 'สำเร็จ',
                                                    'cancelled' => 'ยกเลิก'
                                                ];
                                                $color = $status_colors[$order['order_status']] ?? 'secondary';
                                                $text = $status_text[$order['order_status']] ?? $order['order_status'];
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo $text; ?>
                                                </span>
                                            </td>
                                            <td><?php echo format_date($order['created_at']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="col-lg-5">
                    <div class="content-wrapper">
                        <h4 class="mb-4">
                            <i class="fas fa-exclamation-triangle me-2" style="color: #ffc107;"></i>
                            สินค้าใกล้หมด (<?php echo $stats['low_stock_products']; ?>)
                        </h4>
                        
                        <div class="low-stock-list" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($low_stock_products)): ?>
                                <p class="text-center text-muted">ไม่มีสินค้าใกล้หมด</p>
                            <?php else: ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3" 
                                     style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($product['category']); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <h5 class="mb-0" style="color: <?php echo $product['stock'] == 0 ? '#dc3545' : '#ffc107'; ?>">
                                            <?php echo $product['stock']; ?>
                                        </h5>
                                        <small class="text-muted"><?php echo $product['unit']; ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mt-4">
                <div class="col-12">
                    <div class="content-wrapper">
                        <h4 class="mb-4">
                            <i class="fas fa-bolt me-2" style="color: #ff6b00;"></i>
                            การดำเนินการด่วน
                        </h4>
                        
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="products.php?action=add" class="btn btn-primary-custom w-100">
                                    <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="orders.php" class="btn btn-primary-custom w-100">
                                    <i class="fas fa-list me-2"></i>ดูคำสั่งซื้อทั้งหมด
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="reports.php" class="btn btn-primary-custom w-100">
                                    <i class="fas fa-chart-bar me-2"></i>ดูรายงาน
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="../pages/index.php" class="btn btn-outline-custom w-100">
                                    <i class="fas fa-store me-2"></i>ไปหน้าร้าน
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .low-stock-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .low-stock-list::-webkit-scrollbar-thumb {
            background: rgba(255,107,0,0.5);
            border-radius: 3px;
        }
    </style>
</body>
</html>