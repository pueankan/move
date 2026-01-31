<?php
/**
 * Calculator Page - เครื่องคำนวณช่าง
 * คำนวณปริมาณวัสดุก่อสร้าง
 */

require_once '../config/database.php';

$result_message = '';
$calculation_result = [];

// จัดการการคำนวณ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $calc_type = $_POST['calc_type'] ?? '';
    
    switch ($calc_type) {
        case 'paint':
            $calculation_result = calculate_paint($_POST);
            break;
        case 'gypsum':
            $calculation_result = calculate_gypsum($_POST);
            break;
        case 'concrete':
            $calculation_result = calculate_concrete($_POST);
            break;
        case 'transport':
            $calculation_result = calculate_transport($_POST);
            break;
    }
    
    // บันทึกลง DB
    if (!empty($calculation_result)) {
        $sql = "INSERT INTO calculator_logs (calculator_type, input_data, result_data, customer_name, customer_phone) 
                VALUES (:type, :input, :result, :name, :phone)";
        db_insert($sql, [
            ':type' => $calc_type,
            ':input' => json_encode($_POST),
            ':result' => json_encode($calculation_result),
            ':name' => $_POST['customer_name'] ?? null,
            ':phone' => $_POST['customer_phone'] ?? null
        ]);
    }
}

// ฟังก์ชันคำนวณสี
function calculate_paint($data) {
    $area = floatval($data['area'] ?? 0);
    $coats = intval($data['coats'] ?? 2);
    $coverage = 10; // 1 ลิตรทาได้ 10 ตร.ม.
    
    $liters_needed = ($area * $coats) / $coverage;
    $gallons_needed = ceil($liters_needed / 3.785); // 1 แกลลอน = 3.785 ลิตร
    $estimated_cost = $gallons_needed * 850; // ราคาประมาณ 850 บาท/ถัง
    
    return [
        'area' => $area,
        'coats' => $coats,
        'liters' => round($liters_needed, 2),
        'gallons' => $gallons_needed,
        'cost' => $estimated_cost
    ];
}

// ฟังก์ชันคำนวณยิปซั่ม
function calculate_gypsum($data) {
    $area = floatval($data['area'] ?? 0);
    $sheet_size = 2.88; // แผ่นยิปซั่มขนาด 1.2x2.4 ม. = 2.88 ตร.ม.
    
    $sheets_needed = ceil($area / $sheet_size);
    $screws_needed = $sheets_needed * 50; // 50 ตัว/แผ่น
    $estimated_cost = ($sheets_needed * 180) + (ceil($screws_needed / 100) * 50);
    
    return [
        'area' => $area,
        'sheets' => $sheets_needed,
        'screws' => $screws_needed,
        'cost' => $estimated_cost
    ];
}

// ฟังก์ชันคำนวณคอนกรีต
function calculate_concrete($data) {
    $length = floatval($data['length'] ?? 0);
    $width = floatval($data['width'] ?? 0);
    $thickness = floatval($data['thickness'] ?? 0.1);
    
    $volume = $length * $width * $thickness; // ลูกบาศก์เมตร
    $cement_bags = ceil($volume * 6); // 6 ถุง/ลบ.ม.
    $sand_cubic = round($volume * 0.5, 2); // 0.5 คิว/ลบ.ม.
    $gravel_cubic = round($volume * 0.8, 2); // 0.8 คิว/ลบ.ม.
    
    $estimated_cost = ($cement_bags * 150) + ($sand_cubic * 120) + ($gravel_cubic * 180);
    
    return [
        'volume' => round($volume, 2),
        'cement' => $cement_bags,
        'sand' => $sand_cubic,
        'gravel' => $gravel_cubic,
        'cost' => $estimated_cost
    ];
}

// ฟังก์ชันคำนวณค่าขนส่ง
function calculate_transport($data) {
    $distance = floatval($data['distance'] ?? 0);
    $weight = floatval($data['weight'] ?? 0);
    
    $base_rate = 500; // ค่าพื้นฐาน
    $distance_rate = $distance * 10; // 10 บาท/กม.
    $weight_rate = $weight * 5; // 5 บาท/กก.
    
    $total_cost = $base_rate + $distance_rate + $weight_rate;
    
    return [
        'distance' => $distance,
        'weight' => $weight,
        'cost' => round($total_cost, 2)
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เครื่องคำนวณช่าง - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
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
                    <i class="fas fa-calculator me-3"></i>เครื่องคำนวณช่าง
                </h1>
                <p class="lead">คำนวณปริมาณวัสดุที่ต้องใช้อย่างแม่นยำ</p>
            </div>

            <div class="row g-4">
                <!-- Calculator Menu -->
                <div class="col-lg-4">
                    <div class="content-wrapper sticky-top" style="top: 100px;">
                        <h4 class="mb-4">
                            <i class="fas fa-list me-2" style="color: #ff6b00;"></i>
                            เลือกประเภท
                        </h4>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary-custom calc-menu-btn active" data-calc="paint">
                                <i class="fas fa-paint-roller me-2"></i>คำนวณสี
                            </button>
                            <button class="btn btn-primary-custom calc-menu-btn" data-calc="gypsum">
                                <i class="fas fa-border-all me-2"></i>คำนวณยิปซั่ม
                            </button>
                            <button class="btn btn-primary-custom calc-menu-btn" data-calc="concrete">
                                <i class="fas fa-cube me-2"></i>คำนวณคอนกรีต
                            </button>
                            <button class="btn btn-primary-custom calc-menu-btn" data-calc="transport">
                                <i class="fas fa-truck me-2"></i>คำนวณค่าขนส่ง
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Calculator Forms -->
                <div class="col-lg-8">
                    <!-- Paint Calculator -->
                    <div class="content-wrapper calc-form active" id="paint-calc">
                        <h4 class="mb-4">
                            <i class="fas fa-paint-roller me-2" style="color: #ff6b00;"></i>
                            คำนวณปริมาณสี
                        </h4>
                        <form method="POST">
                            <input type="hidden" name="calc_type" value="paint">
                            <div class="mb-3">
                                <label class="form-label-custom">พื้นที่ที่ต้องการทา (ตร.ม.) *</label>
                                <input type="number" name="area" class="form-control form-control-custom" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom">จำนวนชั้น *</label>
                                <select name="coats" class="form-control form-control-custom" required>
                                    <option value="1">1 ชั้น</option>
                                    <option value="2" selected>2 ชั้น</option>
                                    <option value="3">3 ชั้น</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">ชื่อผู้ใช้งาน</label>
                                    <input type="text" name="customer_name" class="form-control form-control-custom">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">เบอร์โทร</label>
                                    <input type="tel" name="customer_phone" class="form-control form-control-custom">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary-custom btn-lg w-100">
                                <i class="fas fa-calculator me-2"></i>คำนวณ
                            </button>
                        </form>
                        
                        <?php if (isset($calculation_result['liters']) && $_POST['calc_type'] === 'paint'): ?>
                        <div class="mt-4 p-4 rounded" style="background: rgba(0,173,181,0.1); border: 2px solid #00adb5;">
                            <h5 class="mb-3"><i class="fas fa-check-circle me-2"></i>ผลการคำนวณ</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <p class="mb-1 text-muted">ปริมาณสีที่ต้องใช้</p>
                                    <h4 class="mb-0" style="color: #00adb5;"><?php echo $calculation_result['liters']; ?> ลิตร</h4>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1 text-muted">จำนวนถัง (แกลลอน)</p>
                                    <h4 class="mb-0" style="color: #00adb5;"><?php echo $calculation_result['gallons']; ?> ถัง</h4>
                                </div>
                                <div class="col-12">
                                    <p class="mb-1 text-muted">ราคาโดยประมาณ</p>
                                    <h3 class="mb-0" style="color: #ffc107;">฿<?php echo number_format($calculation_result['cost'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Gypsum Calculator -->
                    <div class="content-wrapper calc-form" id="gypsum-calc">
                        <h4 class="mb-4">
                            <i class="fas fa-border-all me-2" style="color: #ff6b00;"></i>
                            คำนวณแผ่นยิปซั่ม
                        </h4>
                        <form method="POST">
                            <input type="hidden" name="calc_type" value="gypsum">
                            <div class="mb-3">
                                <label class="form-label-custom">พื้นที่ฝ้า/ผนัง (ตร.ม.) *</label>
                                <input type="number" name="area" class="form-control form-control-custom" step="0.01" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">ชื่อผู้ใช้งาน</label>
                                    <input type="text" name="customer_name" class="form-control form-control-custom">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">เบอร์โทร</label>
                                    <input type="tel" name="customer_phone" class="form-control form-control-custom">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary-custom btn-lg w-100">
                                <i class="fas fa-calculator me-2"></i>คำนวณ
                            </button>
                        </form>
                        
                        <?php if (isset($calculation_result['sheets']) && $_POST['calc_type'] === 'gypsum'): ?>
                        <div class="mt-4 p-4 rounded" style="background: rgba(0,173,181,0.1); border: 2px solid #00adb5;">
                            <h5 class="mb-3"><i class="fas fa-check-circle me-2"></i>ผลการคำนวณ</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <p class="mb-1 text-muted">แผ่นยิปซั่ม</p>
                                    <h4 class="mb-0" style="color: #00adb5;"><?php echo $calculation_result['sheets']; ?> แผ่น</h4>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1 text-muted">สกรูยิปซั่ม</p>
                                    <h4 class="mb-0" style="color: #00adb5;"><?php echo $calculation_result['screws']; ?> ตัว</h4>
                                </div>
                                <div class="col-12">
                                    <p class="mb-1 text-muted">ราคาโดยประมาณ</p>
                                    <h3 class="mb-0" style="color: #ffc107;">฿<?php echo number_format($calculation_result['cost'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Concrete Calculator -->
                    <div class="content-wrapper calc-form" id="concrete-calc">
                        <h4 class="mb-4">
                            <i class="fas fa-cube me-2" style="color: #ff6b00;"></i>
                            คำนวณคอนกรีต
                        </h4>
                        <form method="POST">
                            <input type="hidden" name="calc_type" value="concrete">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label-custom">ความยาว (ม.) *</label>
                                    <input type="number" name="length" class="form-control form-control-custom" step="0.01" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-custom">ความกว้าง (ม.) *</label>
                                    <input type="number" name="width" class="form-control form-control-custom" step="0.01" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-custom">ความหนา (ม.) *</label>
                                    <input type="number" name="thickness" class="form-control form-control-custom" step="0.01" value="0.1" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">ชื่อผู้ใช้งาน</label>
                                    <input type="text" name="customer_name" class="form-control form-control-custom">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">เบอร์โทร</label>
                                    <input type="tel" name="customer_phone" class="form-control form-control-custom">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary-custom btn-lg w-100">
                                <i class="fas fa-calculator me-2"></i>คำนวณ
                            </button>
                        </form>
                        
                        <?php if (isset($calculation_result['volume']) && $_POST['calc_type'] === 'concrete'): ?>
                        <div class="mt-4 p-4 rounded" style="background: rgba(0,173,181,0.1); border: 2px solid #00adb5;">
                            <h5 class="mb-3"><i class="fas fa-check-circle me-2"></i>ผลการคำนวณ</h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <p class="mb-1 text-muted">ปริมาตร</p>
                                    <h4 class="mb-0" style="color: #00adb5;"><?php echo $calculation_result['volume']; ?> ลบ.ม.</h4>
                                </div>
                                <div class="col-4">
                                    <p class="mb-1 text-muted">ปูนซีเมนต์</p>
                                    <h5 class="mb-0"><?php echo $calculation_result['cement']; ?> ถุง</h5>
                                </div>
                                <div class="col-4">
                                    <p class="mb-1 text-muted">ทราย</p>
                                    <h5 class="mb-0"><?php echo $calculation_result['sand']; ?> คิว</h5>
                                </div>
                                <div class="col-4">
                                    <p class="mb-1 text-muted">หินเกล็ด</p>
                                    <h5 class="mb-0"><?php echo $calculation_result['gravel']; ?> คิว</h5>
                                </div>
                                <div class="col-12">
                                    <p class="mb-1 text-muted">ราคาโดยประมาณ</p>
                                    <h3 class="mb-0" style="color: #ffc107;">฿<?php echo number_format($calculation_result['cost'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Transport Calculator -->
                    <div class="content-wrapper calc-form" id="transport-calc">
                        <h4 class="mb-4">
                            <i class="fas fa-truck me-2" style="color: #ff6b00;"></i>
                            คำนวณค่าขนส่ง
                        </h4>
                        <form method="POST">
                            <input type="hidden" name="calc_type" value="transport">
                            <div class="mb-3">
                                <label class="form-label-custom">ระยะทาง (กม.) *</label>
                                <input type="number" name="distance" class="form-control form-control-custom" step="0.1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-custom">น้ำหนักสินค้า (กก.) *</label>
                                <input type="number" name="weight" class="form-control form-control-custom" step="0.1" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">ชื่อผู้ใช้งาน</label>
                                    <input type="text" name="customer_name" class="form-control form-control-custom">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label-custom">เบอร์โทร</label>
                                    <input type="tel" name="customer_phone" class="form-control form-control-custom">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary-custom btn-lg w-100">
                                <i class="fas fa-calculator me-2"></i>คำนวณ
                            </button>
                        </form>
                        
                        <?php if (isset($calculation_result['distance']) && $_POST['calc_type'] === 'transport'): ?>
                        <div class="mt-4 p-4 rounded" style="background: rgba(0,173,181,0.1); border: 2px solid #00adb5;">
                            <h5 class="mb-3"><i class="fas fa-check-circle me-2"></i>ผลการคำนวณ</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <p class="mb-1 text-muted">ระยะทาง</p>
                                    <h5 class="mb-0"><?php echo $calculation_result['distance']; ?> กม.</h5>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1 text-muted">น้ำหนัก</p>
                                    <h5 class="mb-0"><?php echo $calculation_result['weight']; ?> กก.</h5>
                                </div>
                                <div class="col-12">
                                    <p class="mb-1 text-muted">ค่าขนส่ง</p>
                                    <h3 class="mb-0" style="color: #ffc107;">฿<?php echo number_format($calculation_result['cost'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculator switching
        document.querySelectorAll('.calc-menu-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const calcType = this.dataset.calc;
                
                // Update buttons
                document.querySelectorAll('.calc-menu-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide forms
                document.querySelectorAll('.calc-form').forEach(form => {
                    form.classList.remove('active');
                    form.style.display = 'none';
                });
                
                const targetForm = document.getElementById(calcType + '-calc');
                targetForm.style.display = 'block';
                setTimeout(() => targetForm.classList.add('active'), 10);
            });
        });

        // Initial setup
        document.querySelectorAll('.calc-form').forEach((form, index) => {
            if (index !== 0) {
                form.style.display = 'none';
                form.classList.remove('active');
            }
        });

        // Active menu
        document.querySelector('a[href="calculator.php"]').classList.add('active');
    </script>
</body>
</html>