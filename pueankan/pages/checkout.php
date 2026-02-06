<?php
/**
 * Checkout Page
 * หน้าชำระเงินและยืนยันคำสั่งซื้อ
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// ตรวจสอบตะกร้าว่าง
$cart = cart_get();
if (empty($cart)) {
    flash_set('warning', 'ตะกร้าสินค้าของคุณว่างเปล่า');
    redirect('products.php');
}

// ดึงข้อมูลสินค้าในตะกร้า
$cart_items = [];
$subtotal = 0;

$product_ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$sql = "SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1";
$stmt = db_query($sql, $product_ids);
$products = $stmt->fetchAll();

foreach ($products as $product) {
    $quantity = $cart[$product['id']];
    $total = $product['price'] * $quantity;
    
    $cart_items[] = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'unit' => $product['unit'],
        'quantity' => $quantity,
        'total' => $total
    ];
    
    $subtotal += $total;
}

// Process Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $customer_name = clean_input($_POST['customer_name'] ?? '');
    $customer_phone = clean_input($_POST['customer_phone'] ?? '');
    $customer_email = clean_input($_POST['customer_email'] ?? '');
    $shipping_address = clean_input($_POST['shipping_address'] ?? '');
    $payment_method = clean_input($_POST['payment_method'] ?? 'cash');
    $notes = clean_input($_POST['notes'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($customer_name)) $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    if (empty($customer_phone)) $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
    if (!is_valid_phone($customer_phone)) $errors[] = 'เบอร์โทรศัพท์ไม่ถูกต้อง';
    if (!empty($customer_email) && !is_valid_email($customer_email)) $errors[] = 'อีเมลไม่ถูกต้อง';
    if (empty($shipping_address)) $errors[] = 'กรุณากรอกที่อยู่จัดส่ง';
    
    if (empty($errors)) {
        try {
            global $pdo;
            $pdo->beginTransaction();
            
            // คำนวณราคา
            $shipping_cost = $subtotal >= 1000 ? 0 : 50;
            $vat = ($subtotal + $shipping_cost) * 0.07;
            $grand_total = $subtotal + $shipping_cost + $vat;
            
            // สร้างเลขที่คำสั่งซื้อ
            $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // บันทึกคำสั่งซื้อ
            $sql = "INSERT INTO orders (order_number, customer_name, customer_phone, customer_email, 
                    shipping_address, total_amount, shipping_cost, vat, grand_total, 
                    payment_method, payment_status, order_status, notes) 
                    VALUES (:order_number, :name, :phone, :email, :address, :total, :shipping, :vat, 
                    :grand_total, :payment_method, 'pending', 'pending', :notes)";
            
            $order_id = db_insert($sql, [
                ':order_number' => $order_number,
                ':name' => $customer_name,
                ':phone' => $customer_phone,
                ':email' => $customer_email,
                ':address' => $shipping_address,
                ':total' => $subtotal,
                ':shipping' => $shipping_cost,
                ':vat' => $vat,
                ':grand_total' => $grand_total,
                ':payment_method' => $payment_method,
                ':notes' => $notes
            ]);
            
            if (!$order_id) {
                throw new Exception('ไม่สามารถสร้างคำสั่งซื้อได้');
            }
            
            // บันทึกรายการสินค้า
            foreach ($cart_items as $item) {
                $sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit, unit_price, total_price) 
                        VALUES (:order_id, :product_id, :name, :quantity, :unit, :price, :total)";
                
                db_insert($sql, [
                    ':order_id' => $order_id,
                    ':product_id' => $item['product_id'],
                    ':name' => $item['name'],
                    ':quantity' => $item['quantity'],
                    ':unit' => $item['unit'],
                    ':price' => $item['price'],
                    ':total' => $item['total']
                ]);
                
                // ลดสต็อกสินค้า
                $sql = "UPDATE products SET stock = stock - :quantity WHERE id = :id";
                db_query($sql, [
                    ':quantity' => $item['quantity'],
                    ':id' => $item['product_id']
                ]);
            }
            
            $pdo->commit();
            
            // ล้างตะกร้า
            cart_clear();
            
            // บันทึก Log
            log_activity('place_order', "Order: {$order_number}, Total: {$grand_total}");
            
            // Redirect ไปหน้าขอบคุณ
            session_set('order_success', [
                'order_number' => $order_number,
                'grand_total' => $grand_total
            ]);
            
            redirect('order-success.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            flash_set('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            flash_set('error', $error);
        }
    }
}

// คำนวณราคา
$shipping_cost = $subtotal >= 1000 ? 0 : 50;
$vat = ($subtotal + $shipping_cost) * 0.07;
$grand_total = $subtotal + $shipping_cost + $vat;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
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
                    <i class="fas fa-credit-card me-3"></i>ชำระเงิน
                </h1>
                <p class="lead">กรอกข้อมูลเพื่อยืนยันคำสั่งซื้อ</p>
            </div>

            <?php flash_display(); ?>

            <form method="POST">
                <div class="row g-4">
                    <!-- Left: Customer Info & Payment -->
                    <div class="col-lg-8">
                        <!-- Customer Information -->
                        <div class="content-wrapper mb-4">
                            <h4 class="mb-4">
                                <i class="fas fa-user me-2" style="color: #ff6b00;"></i>
                                ข้อมูลผู้สั่งซื้อ
                            </h4>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-custom">ชื่อ-นามสกุล *</label>
                                    <input type="text" name="customer_name" class="form-control form-control-custom" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label-custom">เบอร์โทรศัพท์ *</label>
                                    <input type="tel" name="customer_phone" class="form-control form-control-custom" 
                                           pattern="[0-9]{9,10}" placeholder="08X-XXX-XXXX" required>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label-custom">อีเมล</label>
                                    <input type="email" name="customer_email" class="form-control form-control-custom">
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label-custom">ที่อยู่จัดส่ง *</label>
                                    <textarea name="shipping_address" class="form-control form-control-custom" 
                                              rows="3" required></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label-custom">หมายเหตุ</label>
                                    <textarea name="notes" class="form-control form-control-custom" 
                                              rows="2" placeholder="ข้อความถึงผู้ขาย (ถ้ามี)"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="content-wrapper">
                            <h4 class="mb-4">
                                <i class="fas fa-money-bill me-2" style="color: #ff6b00;"></i>
                                เลือกวิธีการชำระเงิน
                            </h4>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="cash" checked>
                                        <div class="payment-card">
                                            <i class="fas fa-money-bill-wave fa-2x mb-2" style="color: #28a745;"></i>
                                            <h6>เงินสด</h6>
                                            <small>ชำระเงินสดเมื่อรับสินค้า</small>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="transfer">
                                        <div class="payment-card">
                                            <i class="fas fa-exchange-alt fa-2x mb-2" style="color: #007bff;"></i>
                                            <h6>โอนเงิน</h6>
                                            <small>โอนเงินผ่านธนาคาร</small>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="qr_payment">
                                        <div class="payment-card">
                                            <i class="fas fa-qrcode fa-2x mb-2" style="color: #17a2b8;"></i>
                                            <h6>QR Payment</h6>
                                            <small>สแกน QR Code ชำระเงิน</small>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="credit_card">
                                        <div class="payment-card">
                                            <i class="fas fa-credit-card fa-2x mb-2" style="color: #ffc107;"></i>
                                            <h6>บัตรเครดิต</h6>
                                            <small>ชำระด้วยบัตรเครดิต/เดบิต</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Order Summary -->
                    <div class="col-lg-4">
                        <div class="content-wrapper sticky-top" style="top: 100px;">
                            <h4 class="mb-4">
                                <i class="fas fa-clipboard-list me-2" style="color: #ff6b00;"></i>
                                สรุปคำสั่งซื้อ
                            </h4>

                            <!-- Items List -->
                            <div class="order-items mb-4" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-3 pb-3" 
                                     style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $item['quantity']; ?> <?php echo $item['unit']; ?> 
                                            × <?php echo format_currency($item['price']); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <strong style="color: #ff6b00;">
                                            <?php echo format_currency($item['total']); ?>
                                        </strong>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Price Summary -->
                            <div class="price-summary p-3 rounded" 
                                 style="background: rgba(22,33,62,0.5); border: 1px solid rgba(255,107,0,0.3);">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ยอดรวมสินค้า:</span>
                                    <strong><?php echo format_currency($subtotal); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ค่าจัดส่ง:</span>
                                    <strong style="color: <?php echo $shipping_cost == 0 ? '#28a745' : '#ffc107'; ?>">
                                        <?php echo $shipping_cost == 0 ? 'ฟรี' : format_currency($shipping_cost); ?>
                                    </strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>ภาษีมูลค่าเพิ่ม 7%:</span>
                                    <strong><?php echo format_currency($vat); ?></strong>
                                </div>
                                <hr style="border-color: rgba(255,255,255,0.2);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">ยอดรวมทั้งสิ้น:</h5>
                                    <h3 class="mb-0" style="color: #ffc107;">
                                        <?php echo format_currency($grand_total); ?>
                                    </h3>
                                </div>
                            </div>

                            <button type="submit" name="place_order" class="btn btn-primary-custom w-100 btn-lg mt-4">
                                <i class="fas fa-check-circle me-2"></i>ยืนยันคำสั่งซื้อ
                            </button>
                            
                            <a href="cart.php" class="btn btn-outline-custom w-100 mt-2">
                                <i class="fas fa-arrow-left me-2"></i>กลับไปแก้ไขตะกร้า
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment option styling
        document.querySelectorAll('.payment-option input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-card').forEach(card => {
                    card.style.borderColor = 'rgba(255,255,255,0.2)';
                });
                if (this.checked) {
                    this.nextElementSibling.style.borderColor = '#ff6b00';
                }
            });
        });

        // Auto-select first payment option styling
        document.querySelector('.payment-option input[checked]').nextElementSibling.style.borderColor = '#ff6b00';
    </script>

    <style>
        .payment-option {
            cursor: pointer;
            display: block;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-card {
            background: rgba(22,33,62,0.5);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .payment-card:hover {
            border-color: rgba(255,107,0,0.5);
            transform: translateY(-2px);
        }
        
        .order-items::-webkit-scrollbar {
            width: 6px;
        }
        
        .order-items::-webkit-scrollbar-thumb {
            background: rgba(255,107,0,0.5);
            border-radius: 3px;
        }
    </style>
</body>
</html>