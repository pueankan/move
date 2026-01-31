<?php
/**
 * Quotation Page - ระบบใบเสนอราคา
 * สร้างใบเสนอราคาสำหรับลูกค้า
 */

require_once '../config/database.php';

$message = '';
$message_type = '';
$quotation_id = null;

// จัดการบันทึกใบเสนอราคา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quotation'])) {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $items = json_decode($_POST['items_json'] ?? '[]', true);
    $discount = floatval($_POST['discount'] ?? 0);
    $vat_percent = floatval($_POST['vat_percent'] ?? 7);
    
    if ($customer_name && !empty($items)) {
        // สร้างเลขที่ใบเสนอราคา
        $quotation_number = 'QT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // คำนวณราคา
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['total'];
        }
        
        $discount_amount = ($total_amount * $discount) / 100;
        $after_discount = $total_amount - $discount_amount;
        $vat_amount = ($after_discount * $vat_percent) / 100;
        $grand_total = $after_discount + $vat_amount;
        
        // Valid until (30 วันจากวันนี้)
        $valid_until = date('Y-m-d', strtotime('+30 days'));
        
        // บันทึกใบเสนอราคา
        $sql = "INSERT INTO quotations (quotation_number, customer_name, customer_phone, customer_address, 
                total_amount, discount, vat, grand_total, valid_until, status) 
                VALUES (:number, :name, :phone, :address, :total, :discount, :vat, :grand, :valid, 'draft')";
        
        $quotation_id = db_insert($sql, [
            ':number' => $quotation_number,
            ':name' => $customer_name,
            ':phone' => $customer_phone,
            ':address' => $customer_address,
            ':total' => $total_amount,
            ':discount' => $discount_amount,
            ':vat' => $vat_amount,
            ':grand' => $grand_total,
            ':valid' => $valid_until
        ]);
        
        // บันทึกรายการสินค้า
        if ($quotation_id) {
            foreach ($items as $item) {
                $sql = "INSERT INTO quotation_items (quotation_id, item_type, item_name, description, 
                        quantity, unit, unit_price, total_price) 
                        VALUES (:qid, :type, :name, :desc, :qty, :unit, :price, :total)";
                
                db_insert($sql, [
                    ':qid' => $quotation_id,
                    ':type' => $item['type'],
                    ':name' => $item['name'],
                    ':desc' => $item['description'],
                    ':qty' => $item['quantity'],
                    ':unit' => $item['unit'],
                    ':price' => $item['price'],
                    ':total' => $item['total']
                ]);
            }
            
            $message = "สร้างใบเสนอราคาเลขที่ {$quotation_number} สำเร็จ!";
            $message_type = 'success';
        }
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วนและเพิ่มรายการสินค้าอย่างน้อย 1 รายการ';
        $message_type = 'warning';
    }
}

// ดึงสินค้าและบริการสำหรับเลือก
$products = db_fetch_all("SELECT id, name, price, unit FROM products WHERE is_active = 1 ORDER BY name");
$services = db_fetch_all("SELECT id, service_name as name, base_price as price, unit FROM services WHERE is_active = 1 ORDER BY service_name");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสนอราคา - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
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
                    <i class="fas fa-file-invoice me-3"></i>สร้างใบเสนอราคา
                </h1>
                <p class="lead">ระบบสร้างใบเสนอราคาอัตโนมัติ</p>
            </div>

            <!-- Message Alert -->
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" id="quotationForm">
                <div class="row g-4">
                    <!-- Left Column: Customer Info & Items -->
                    <div class="col-lg-8">
                        <!-- Customer Information -->
                        <div class="content-wrapper mb-4">
                            <h4 class="mb-4">
                                <i class="fas fa-user me-2" style="color: #ff6b00;"></i>
                                ข้อมูลลูกค้า
                            </h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-custom">ชื่อ-นามสกุล *</label>
                                    <input type="text" name="customer_name" class="form-control form-control-custom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom">เบอร์โทรศัพท์ *</label>
                                    <input type="tel" name="customer_phone" class="form-control form-control-custom" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-custom">ที่อยู่</label>
                                    <textarea name="customer_address" class="form-control form-control-custom" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Add Items -->
                        <div class="content-wrapper mb-4">
                            <h4 class="mb-4">
                                <i class="fas fa-plus-circle me-2" style="color: #ff6b00;"></i>
                                เพิ่มรายการ
                            </h4>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label-custom">ประเภท</label>
                                    <select id="item_type" class="form-control form-control-custom">
                                        <option value="product">สินค้า</option>
                                        <option value="service">บริการ</option>
                                        <option value="custom">กำหนดเอง</option>
                                    </select>
                                </div>
                                <div class="col-md-9" id="item_select_wrapper">
                                    <label class="form-label-custom">เลือกสินค้า/บริการ</label>
                                    <select id="item_select" class="form-control form-control-custom">
                                        <option value="">-- เลือก --</option>
                                        <optgroup label="สินค้า" id="products_group">
                                            <?php foreach ($products as $p): ?>
                                                <option value="product_<?php echo $p['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                                        data-price="<?php echo $p['price']; ?>"
                                                        data-unit="<?php echo $p['unit']; ?>">
                                                    <?php echo htmlspecialchars($p['name']); ?> (฿<?php echo number_format($p['price'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="บริการ" id="services_group">
                                            <?php foreach ($services as $s): ?>
                                                <option value="service_<?php echo $s['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($s['name']); ?>"
                                                        data-price="<?php echo $s['price']; ?>"
                                                        data-unit="<?php echo $s['unit']; ?>">
                                                    <?php echo htmlspecialchars($s['name']); ?> (฿<?php echo number_format($s['price'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-5" id="custom_name_wrapper" style="display: none;">
                                    <label class="form-label-custom">ชื่อรายการ</label>
                                    <input type="text" id="custom_name" class="form-control form-control-custom">
                                </div>
                                <div class="col-md-4" id="custom_desc_wrapper" style="display: none;">
                                    <label class="form-label-custom">คำอธิบาย</label>
                                    <input type="text" id="custom_desc" class="form-control form-control-custom">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label-custom">จำนวน</label>
                                    <input type="number" id="item_quantity" class="form-control form-control-custom" value="1" step="0.01">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label-custom">หน่วย</label>
                                    <input type="text" id="item_unit" class="form-control form-control-custom" value="ชิ้น">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label-custom">ราคา/หน่วย</label>
                                    <input type="number" id="item_price" class="form-control form-control-custom" step="0.01">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary-custom w-100" onclick="addItem()">
                                        <i class="fas fa-plus me-2"></i>เพิ่ม
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="content-wrapper">
                            <h4 class="mb-4">
                                <i class="fas fa-list me-2" style="color: #ff6b00;"></i>
                                รายการสินค้า/บริการ
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>ลำดับ</th>
                                            <th>รายการ</th>
                                            <th>จำนวน</th>
                                            <th>ราคา/หน่วย</th>
                                            <th>รวม</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items_table">
                                        <tr id="empty_row">
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                                ยังไม่มีรายการ
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Summary -->
                    <div class="col-lg-4">
                        <div class="content-wrapper sticky-top" style="top: 100px;">
                            <h4 class="mb-4">
                                <i class="fas fa-calculator me-2" style="color: #ff6b00;"></i>
                                สรุปยอด
                            </h4>

                            <div class="mb-3">
                                <label class="form-label-custom">ส่วนลด (%)</label>
                                <input type="number" name="discount" id="discount" class="form-control form-control-custom" 
                                       value="0" step="0.01" min="0" max="100" onchange="updateSummary()">
                            </div>

                            <div class="mb-4">
                                <label class="form-label-custom">ภาษีมูลค่าเพิ่ม (%)</label>
                                <input type="number" name="vat_percent" id="vat_percent" class="form-control form-control-custom" 
                                       value="7" step="0.01" min="0" onchange="updateSummary()">
                            </div>

                            <div class="summary-box p-4 rounded" style="background: rgba(22, 33, 62, 0.8); border: 2px solid rgba(255,107,0,0.3);">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ยอดรวม:</span>
                                    <strong id="subtotal_display">฿0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ส่วนลด:</span>
                                    <strong id="discount_display" style="color: #28a745;">฿0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>ภาษีมูลค่าเพิ่ม:</span>
                                    <strong id="vat_display">฿0.00</strong>
                                </div>
                                <hr style="border-color: rgba(255,255,255,0.2);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">ยอดสุทธิ:</h5>
                                    <h3 class="mb-0" style="color: #ffc107;" id="grand_total_display">฿0.00</h3>
                                </div>
                            </div>

                            <input type="hidden" name="items_json" id="items_json">
                            
                            <button type="submit" name="create_quotation" class="btn btn-primary-custom btn-lg w-100 mt-4">
                                <i class="fas fa-save me-2"></i>สร้างใบเสนอราคา
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let items = [];
        let itemCounter = 0;

        // Switch item type
        document.getElementById('item_type').addEventListener('change', function() {
            const type = this.value;
            const selectWrapper = document.getElementById('item_select_wrapper');
            const customNameWrapper = document.getElementById('custom_name_wrapper');
            const customDescWrapper = document.getElementById('custom_desc_wrapper');
            
            if (type === 'custom') {
                selectWrapper.style.display = 'none';
                customNameWrapper.style.display = 'block';
                customDescWrapper.style.display = 'block';
            } else {
                selectWrapper.style.display = 'block';
                customNameWrapper.style.display = 'none';
                customDescWrapper.style.display = 'none';
                
                // Show/hide optgroups
                document.getElementById('products_group').style.display = type === 'product' ? 'block' : 'none';
                document.getElementById('services_group').style.display = type === 'service' ? 'block' : 'none';
            }
        });

        // Auto-fill from select
        document.getElementById('item_select').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                document.getElementById('item_price').value = option.dataset.price;
                document.getElementById('item_unit').value = option.dataset.unit;
            }
        });

        // Add item
        function addItem() {
            const type = document.getElementById('item_type').value;
            let name, description;
            
            if (type === 'custom') {
                name = document.getElementById('custom_name').value;
                description = document.getElementById('custom_desc').value;
            } else {
                const select = document.getElementById('item_select');
                const option = select.options[select.selectedIndex];
                if (!option.value) {
                    alert('กรุณาเลือกรายการ');
                    return;
                }
                name = option.dataset.name;
                description = '';
            }
            
            const quantity = parseFloat(document.getElementById('item_quantity').value) || 0;
            const unit = document.getElementById('item_unit').value;
            const price = parseFloat(document.getElementById('item_price').value) || 0;
            const total = quantity * price;
            
            if (!name || quantity <= 0 || price <= 0) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            const item = {
                id: ++itemCounter,
                type: type,
                name: name,
                description: description,
                quantity: quantity,
                unit: unit,
                price: price,
                total: total
            };
            
            items.push(item);
            renderItems();
            updateSummary();
            
            // Reset form
            document.getElementById('item_quantity').value = 1;
            document.getElementById('item_price').value = '';
            document.getElementById('custom_name').value = '';
            document.getElementById('custom_desc').value = '';
            document.getElementById('item_select').value = '';
        }

        // Remove item
        function removeItem(id) {
            items = items.filter(item => item.id !== id);
            renderItems();
            updateSummary();
        }

        // Render items table
        function renderItems() {
            const tbody = document.getElementById('items_table');
            const emptyRow = document.getElementById('empty_row');
            
            if (items.length === 0) {
                emptyRow.style.display = 'table-row';
                return;
            }
            
            emptyRow.style.display = 'none';
            
            let html = '';
            items.forEach((item, index) => {
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            <strong>${item.name}</strong><br>
                            ${item.description ? '<small class="text-muted">' + item.description + '</small>' : ''}
                        </td>
                        <td>${item.quantity} ${item.unit}</td>
                        <td>฿${item.price.toFixed(2)}</td>
                        <td><strong style="color: #ff6b00;">฿${item.total.toFixed(2)}</strong></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${item.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html + emptyRow.outerHTML;
        }

        // Update summary
        function updateSummary() {
            const subtotal = items.reduce((sum, item) => sum + item.total, 0);
            const discountPercent = parseFloat(document.getElementById('discount').value) || 0;
            const vatPercent = parseFloat(document.getElementById('vat_percent').value) || 0;
            
            const discountAmount = (subtotal * discountPercent) / 100;
            const afterDiscount = subtotal - discountAmount;
            const vatAmount = (afterDiscount * vatPercent) / 100;
            const grandTotal = afterDiscount + vatAmount;
            
            document.getElementById('subtotal_display').textContent = '฿' + subtotal.toFixed(2);
            document.getElementById('discount_display').textContent = '฿' + discountAmount.toFixed(2);
            document.getElementById('vat_display').textContent = '฿' + vatAmount.toFixed(2);
            document.getElementById('grand_total_display').textContent = '฿' + grandTotal.toFixed(2);
            
            // Update JSON for form submission
            document.getElementById('items_json').value = JSON.stringify(items);
        }

        // Active menu
        document.querySelector('a[href="quotation.php"]').classList.add('active');
    </script>
</body>
</html>