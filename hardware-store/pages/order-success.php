<?php
/**
 * Order Success Page
 * หน้าแสดงผลสำเร็จหลังสั่งซื้อ
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// ตรวจสอบว่ามีข้อมูลคำสั่งซื้อหรือไม่
$order_data = session_get('order_success');

if (!$order_data) {
    redirect('index.php');
}

// ลบข้อมูล session หลังแสดงผลแล้ว
session_delete('order_success');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อสำเร็จ - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
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
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="content-wrapper text-center">
                        <!-- Success Icon -->
                        <div class="success-icon mb-4">
                            <div class="checkmark-circle">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>

                        <!-- Success Message -->
                        <h1 class="display-4 fw-bold mb-3" style="color: #28a745;">
                            สั่งซื้อสำเร็จ!
                        </h1>
                        
                        <p class="lead mb-4">
                            ขอบคุณที่ไว้วางใจเลือกซื้อสินค้ากับเรา
                        </p>

                        <!-- Order Details -->
                        <div class="order-details p-4 rounded mb-4" 
                             style="background: rgba(40,167,69,0.1); border: 2px solid rgba(40,167,69,0.3);">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">เลขที่คำสั่งซื้อ</p>
                                    <h4 class="mb-0" style="color: #28a745;">
                                        <?php echo htmlspecialchars($order_data['order_number']); ?>
                                    </h4>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">ยอดรวมทั้งสิ้น</p>
                                    <h4 class="mb-0" style="color: #ffc107;">
                                        <?php echo format_currency($order_data['grand_total']); ?>
                                    </h4>
                                </div>
                            </div>
                        </div>

                        <!-- Next Steps -->
                        <div class="next-steps mb-4 text-start">
                            <h5 class="mb-3">
                                <i class="fas fa-clipboard-list me-2" style="color: #ff6b00;"></i>
                                ขั้นตอนถัดไป
                            </h5>
                            <div class="steps">
                                <div class="step-item mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="step-number me-3">1</div>
                                        <div>
                                            <h6 class="mb-1">ตรวจสอบคำสั่งซื้อ</h6>
                                            <p class="text-muted mb-0">
                                                เจ้าหน้าที่จะติดต่อกลับเพื่อยืนยันคำสั่งซื้อภายใน 24 ชั่วโมง
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="step-item mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="step-number me-3">2</div>
                                        <div>
                                            <h6 class="mb-1">เตรียมสินค้า</h6>
                                            <p class="text-muted mb-0">
                                                เราจะเตรียมสินค้าและแพ็คอย่างดี
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="step-item">
                                    <div class="d-flex align-items-start">
                                        <div class="step-number me-3">3</div>
                                        <div>
                                            <h6 class="mb-1">จัดส่งสินค้า</h6>
                                            <p class="text-muted mb-0">
                                                จัดส่งสินค้าตามที่อยู่ที่ระบุไว้
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="alert alert-info-custom mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            หากมีข้อสงสัย โปรดติดต่อ <strong>081-234-5678</strong> หรือ 
                            <strong>info@hardware.com</strong>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="index.php" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-home me-2"></i>กลับหน้าแรก
                            </a>
                            <a href="products.php" class="btn btn-outline-custom btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>เลือกซื้อสินค้าเพิ่ม
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .success-icon {
            animation: successPop 0.6s ease-out;
        }

        .checkmark-circle {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 40px rgba(40, 167, 69, 0.3);
        }

        .checkmark-circle i {
            font-size: 60px;
            color: white;
            animation: checkmarkDraw 0.6s 0.3s ease-out forwards;
            opacity: 0;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ff6b00, #ffc107);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }

        @keyframes successPop {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes checkmarkDraw {
            to {
                opacity: 1;
            }
        }
    </style>
</body>
</html>