<?php
/**
 * Services Page - หน้าบริการ
 * แสดงบริการและฟอร์มขอใช้บริการ
 */

require_once '../config/database.php';

$message = '';
$message_type = '';

// จัดการฟอร์มส่งคำขอ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $service_id = $_POST['service_id'] ?? '';
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $request_date = $_POST['request_date'] ?? '';

    // Validate
    if ($service_id && $customer_name && $customer_phone && $request_date) {
        $sql = "INSERT INTO service_requests 
                (service_id, customer_name, customer_phone, customer_email, location, description, request_date, status) 
                VALUES (:service_id, :customer_name, :customer_phone, :customer_email, :location, :description, :request_date, 'pending')";
        
        $result = db_insert($sql, [
            ':service_id' => $service_id,
            ':customer_name' => $customer_name,
            ':customer_phone' => $customer_phone,
            ':customer_email' => $customer_email,
            ':location' => $location,
            ':description' => $description,
            ':request_date' => $request_date
        ]);

        if ($result) {
            $message = 'ส่งคำขอใช้บริการเรียบร้อยแล้ว! เราจะติดต่อกลับโดยเร็วที่สุด';
            $message_type = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
            $message_type = 'danger';
        }
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $message_type = 'warning';
    }
}

// ดึงข้อมูลบริการทั้งหมด
$services = db_fetch_all("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บริการ - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
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
                    <i class="fas fa-wrench me-3"></i>บริการของเรา
                </h1>
                <p class="lead">บริการติดตั้งและก่อสร้างโดยช่างมืออาชีพ</p>
            </div>

            <!-- Message Alert -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Services List -->
            <div class="row g-4 mb-5">
                <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-custom h-100">
                        <div class="card-header">
                            <i class="fas fa-cogs me-2"></i>
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?php echo htmlspecialchars($service['description']); ?></p>
                            
                            <div class="mb-3 p-3 rounded" style="background: rgba(255,107,0,0.1);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">ราคาเริ่มต้น</span>
                                    <h5 class="mb-0" style="color: #ff6b00;">
                                        ฿<?php echo number_format($service['base_price'], 2); ?>
                                    </h5>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    ต่อ <?php echo htmlspecialchars($service['unit']); ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-2"></i>
                                    ระยะเวลาโดยประมาณ: 
                                    <strong style="color: #ffc107;">
                                        <?php echo $service['duration_days']; ?> วัน
                                    </strong>
                                </small>
                            </div>

                            <button class="btn btn-primary-custom w-100" 
                                    onclick="selectService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['service_name']); ?>')">
                                <i class="fas fa-hand-pointer me-2"></i>ขอใช้บริการ
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Service Request Form -->
            <div class="content-wrapper" id="request-form-section">
                <h3 class="mb-4">
                    <i class="fas fa-edit me-2" style="color: #ff6b00;"></i>
                    ฟอร์มขอใช้บริการ
                </h3>

                <form method="POST" action="">
                    <div class="row g-3">
                        <!-- Service Selection -->
                        <div class="col-md-6">
                            <label class="form-label-custom">
                                <i class="fas fa-cogs me-2"></i>เลือกบริการ *
                            </label>
                            <select name="service_id" id="service_id" class="form-control form-control-custom" required>
                                <option value="">-- เลือกบริการ --</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>">
                                        <?php echo htmlspecialchars($service['service_name']); ?> 
                                        (฿<?php echo number_format($service['base_price'], 2); ?>/<?php echo $service['unit']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Request Date -->
                        <div class="col-md-6">
                            <label class="form-label-custom">
                                <i class="fas fa-calendar me-2"></i>วันที่ต้องการใช้บริการ *
                            </label>
                            <input type="date" 
                                   name="request_date" 
                                   class="form-control form-control-custom" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   required>
                        </div>

                        <!-- Customer Name -->
                        <div class="col-md-6">
                            <label class="form-label-custom">
                                <i class="fas fa-user me-2"></i>ชื่อ-นามสกุล *
                            </label>
                            <input type="text" 
                                   name="customer_name" 
                                   class="form-control form-control-custom" 
                                   placeholder="กรอกชื่อ-นามสกุล"
                                   required>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label-custom">
                                <i class="fas fa-phone me-2"></i>เบอร์โทรศัพท์ *
                            </label>
                            <input type="tel" 
                                   name="customer_phone" 
                                   class="form-control form-control-custom" 
                                   placeholder="08X-XXX-XXXX"
                                   pattern="[0-9]{9,10}"
                                   required>
                        </div>

                        <!-- Email -->
                        <div class="col-md-12">
                            <label class="form-label-custom">
                                <i class="fas fa-envelope me-2"></i>อีเมล
                            </label>
                            <input type="email" 
                                   name="customer_email" 
                                   class="form-control form-control-custom" 
                                   placeholder="email@example.com">
                        </div>

                        <!-- Location -->
                        <div class="col-md-12">
                            <label class="form-label-custom">
                                <i class="fas fa-map-marker-alt me-2"></i>สถานที่ให้บริการ
                            </label>
                            <textarea name="location" 
                                      class="form-control form-control-custom" 
                                      rows="2" 
                                      placeholder="ที่อยู่หรือสถานที่ที่ต้องการให้บริการ"></textarea>
                        </div>

                        <!-- Description -->
                        <div class="col-md-12">
                            <label class="form-label-custom">
                                <i class="fas fa-comment me-2"></i>รายละเอียดเพิ่มเติม
                            </label>
                            <textarea name="description" 
                                      class="form-control form-control-custom" 
                                      rows="4" 
                                      placeholder="ระบุรายละเอียดเพิ่มเติม เช่น ขนาดพื้นที่ ความต้องการพิเศษ"></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <button type="submit" name="submit_request" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>ส่งคำขอใช้บริการ
                            </button>
                            <button type="reset" class="btn btn-secondary-custom btn-lg">
                                <i class="fas fa-redo me-2"></i>ล้างข้อมูล
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // เลือกบริการจากการ์ด
        function selectService(serviceId, serviceName) {
            document.getElementById('service_id').value = serviceId;
            document.getElementById('request-form-section').scrollIntoView({ behavior: 'smooth' });
            
            // Highlight form
            const form = document.getElementById('request-form-section');
            form.style.border = '2px solid #ff6b00';
            setTimeout(() => {
                form.style.border = '1px solid rgba(255, 107, 0, 0.3)';
            }, 2000);
        }

        // Active menu
        document.querySelector('a[href="services.php"]').classList.add('active');
    </script>
</body>
</html>