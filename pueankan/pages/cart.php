<?php
/**
 * Shopping Cart Page
 * ตะกร้าสินค้า
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

/**
 * Remove a product from the cart.
 * @param int $product_id
 */
function cart_remove($product_id) {
    if (!isset($_SESSION['cart'])) {
        return;
    }
    unset($_SESSION['cart'][$product_id]);
}

/**
 * Update the quantity of a product in the cart.
 * @param int $product_id
 * @param float $quantity
 */
function cart_update($product_id, $quantity) {
    if (!isset($_SESSION['cart'])) {
        return;
    }
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
}

/**
 * Clear all items from the cart.
 */
function cart_clear() {
    unset($_SESSION['cart']);
}

/**
 * Display flash messages and clear them from session.
 */
function flash_display() {
    if (!empty($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $message) {
            $alertClass = $type === 'success' ? 'alert-success-custom' : ($type === 'error' ? 'alert-danger-custom' : 'alert-info-custom');
            echo '<div class="alert ' . $alertClass . ' mb-4" role="alert">';
            echo htmlspecialchars($message);
            echo '</div>';
        }
        unset($_SESSION['flash']);
    }
}

// จัดการการเพิ่ม/ลบสินค้า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $product_id = intval($_POST['product_id'] ?? 0);
                $quantity = floatval($_POST['quantity'] ?? 1);
                if ($product_id > 0 && check_stock($product_id, $quantity)) {
                    cart_add($product_id, $quantity);
                    flash_set('success', 'เพิ่มสินค้าลงตะกร้าแล้ว');
                } else {
                    flash_set('error', 'สินค้าไม่เพียงพอหรือไม่พบสินค้า');
                }
                break;
                
            case 'update':
                $product_id = intval($_POST['product_id'] ?? 0);
                $quantity = floatval($_POST['quantity'] ?? 0);
                if ($product_id > 0) {
                    if (check_stock($product_id, $quantity)) {
                        cart_update($product_id, $quantity);
                        flash_set('success', 'อัพเดทจำนวนสินค้าแล้ว');
                    } else {
                        flash_set('error', 'สินค้าไม่เพียงพอ');
                    }
                }
                break;
                
            case 'remove':
                $product_id = intval($_POST['product_id'] ?? 0);
                if ($product_id > 0) {
                    cart_remove($product_id);
                    flash_set('success', 'ลบสินค้าออกจากตะกร้าแล้ว');
                }
                break;
                
            case 'clear':
                cart_clear();
                flash_set('success', 'ล้างตะกร้าสินค้าแล้ว');
                break;
        }
        
        redirect('cart.php');
    }
}

// ดึงข้อมูลสินค้าในตะกร้า
$cart = cart_get();
$cart_items = [];
$subtotal = 0;

if (!empty($cart)) {
    $product_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1";
    $stmt = db_query($sql, $product_ids);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $quantity = $cart[$product['id']];
        $total = $product['price'] * $quantity;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'unit' => $product['unit'],
            'stock' => $product['stock'],
            'quantity' => $quantity,
            'total' => $total
        ];
        
        $subtotal += $total;
    }
}

// คำนวณราคาทั้งหมด
$shipping = $subtotal >= 1000 ? 0 : 50;
$vat_percent = 7;
$vat = ($subtotal + $shipping) * ($vat_percent / 100);
$grand_total = $subtotal + $shipping + $vat;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
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
                    <i class="fas fa-shopping-cart me-3"></i>ตะกร้าสินค้า
                </h1>
                <p class="lead">สินค้าที่คุณเลือก <?php echo count($cart_items); ?> รายการ</p>
            </div>

            <?php flash_display(); ?>

            <?php if (empty($cart_items)): ?>
                <!-- Empty Cart -->
                <div class="content-wrapper text-center py-5">
                    <i class="fas fa-shopping-basket fa-5x mb-4" style="color: rgba(255,255,255,0.3);"></i>
                    <h3>ตะกร้าสินค้าว่างเปล่า</h3>
                    <p class="text-muted mb-4">คุณยังไม่มีสินค้าในตะกร้า</p>
                    <a href="products.php" class="btn btn-primary-custom btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>เลือกซื้อสินค้า
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <!-- Cart Items -->
                    <div class="col-lg-8">
                        <div class="content-wrapper">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4>
                                    <i class="fas fa-list me-2" style="color: #ff6b00;"></i>
                                    รายการสินค้า
                                </h4>
                                <form method="POST" onsubmit="return confirm('ต้องการล้างตะกร้าสินค้าทั้งหมดหรือไม่?')">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash me-2"></i>ล้างตะกร้า
                                    </button>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>สินค้า</th>
                                            <th>ราคา</th>
                                            <th style="width: 150px;">จำนวน</th>
                                            <th>รวม</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    คงเหลือ: <?php echo $item['stock']; ?> <?php echo $item['unit']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo format_currency($item['price']); ?>
                                                <br>
                                                <small class="text-muted">/ <?php echo $item['unit']; ?></small>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-flex align-items-center gap-2">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                    <input type="number" 
                                                           name="quantity" 
                                                           class="form-control form-control-sm form-control-custom" 
                                                           value="<?php echo $item['quantity']; ?>"
                                                           min="0.01" 
                                                           max="<?php echo $item['stock']; ?>"
                                                           step="0.01"
                                                           onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td>
                                                <strong style="color: #ff6b00;">
                                                    <?php echo format_currency($item['total']); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('ต้องการลบสินค้านี้หรือไม่?')">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <div class="content-wrapper sticky-top" style="top: 100px;">
                            <h4 class="mb-4">
                                <i class="fas fa-receipt me-2" style="color: #ff6b00;"></i>
                                สรุปคำสั่งซื้อ
                            </h4>

                            <div class="summary-box">
                                <div class="d-flex justify-content-between mb-3">
                                    <span>ยอดรวมสินค้า:</span>
                                    <strong><?php echo format_currency($subtotal); ?></strong>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span>ค่าจัดส่ง:</span>
                                    <strong style="color: <?php echo $shipping == 0 ? '#28a745' : '#ffc107'; ?>">
                                        <?php echo $shipping == 0 ? 'ฟรี' : format_currency($shipping); ?>
                                    </strong>
                                </div>
                                
                                <?php if ($subtotal < 1000 && $shipping > 0): ?>
                                <div class="alert alert-info-custom mb-3" style="font-size: 0.9rem;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    ซื้อเพิ่มอีก <?php echo format_currency(1000 - $subtotal); ?> เพื่อฟรีค่าจัดส่ง!
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span>ภาษีมูลค่าเพิ่ม (<?php echo $vat_percent; ?>%):</span>
                                    <strong><?php echo format_currency($vat); ?></strong>
                                </div>
                                
                                <hr style="border-color: rgba(255,255,255,0.2);">
                                
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">ยอดรวมทั้งสิ้น:</h5>
                                    <h3 class="mb-0" style="color: #ffc107;">
                                        <?php echo format_currency($grand_total); ?>
                                    </h3>
                                </div>

                                <a href="checkout.php" class="btn btn-primary-custom w-100 btn-lg mb-2">
                                    <i class="fas fa-credit-card me-2"></i>ดำเนินการชำระเงิน
                                </a>
                                
                                <a href="products.php" class="btn btn-outline-custom w-100">
                                    <i class="fas fa-shopping-bag me-2"></i>เลือกซื้อสินค้าเพิ่ม
                                </a>
                            </div>

                            <!-- Promo Code (ถ้ามี) -->
                            <div class="mt-4">
                                <h6 class="mb-3">มีโค้ดส่วนลดหรือไม่?</h6>
                                <form>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control form-control-custom" 
                                               placeholder="กรอกโค้ดส่วนลด">
                                        <button class="btn btn-secondary-custom" type="submit">
                                            ใช้
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>