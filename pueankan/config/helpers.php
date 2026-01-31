<?php
/**
 * Helper Functions
 * ฟังก์ชันช่วยเหลือต่างๆ ที่ใช้ทั่วทั้งระบบ
 */

/**
 * Sanitize Input
 */
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate Email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Phone
 */
function is_valid_phone($phone) {
    // รองรับเบอร์ไทย 10 หลัก (08X-XXX-XXXX, 06X-XXX-XXXX, 09X-XXX-XXXX)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^0[689]\d{8}$/', $phone);
}

/**
 * Format Phone Number
 */
function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    return $phone;
}

/**
 * Format Currency (Thai Baht)
 */
function format_currency($amount, $decimals = 2) {
    return '฿' . number_format($amount, $decimals);
}

/**
 * Format Date (Thai)
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format DateTime (Thai)
 */
function format_datetime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Thai Month Names
 */
function thai_month($month_num) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    return $months[$month_num] ?? '';
}

/**
 * Time Ago (เวลาผ่านมา)
 */
function time_ago($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'เมื่อสักครู่';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' นาทีที่แล้ว';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ชั่วโมงที่แล้ว';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' วันที่แล้ว';
    } else {
        return format_date($timestamp);
    }
}

/**
 * Redirect
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Current URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Generate Random String
 */
function random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * File Upload Helper
 */
function upload_file($file, $target_dir = 'uploads/', $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'ไม่พบไฟล์'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'ประเภทไฟล์ไม่ถูกต้อง'];
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $target_file
        ];
    }
    
    return ['success' => false, 'message' => 'อัพโหลดไฟล์ไม่สำเร็จ'];
}

/**
 * Pagination Helper
 */
function paginate($total_items, $items_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

/**
 * Create Slug from Thai Text
 */
function create_slug($text) {
    // แปลงเป็นตัวพิมพ์เล็ก
    $text = strtolower($text);
    
    // แทนที่ช่องว่างด้วย -
    $text = preg_replace('/\s+/', '-', $text);
    
    // ลบอักขระพิเศษออก
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    
    // ลบ - ซ้ำซ้อน
    $text = preg_replace('/-+/', '-', $text);
    
    // ตัด - ที่หัวท้าย
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Calculate Discount
 */
function calculate_discount($original_price, $discount_percent) {
    $discount_amount = ($original_price * $discount_percent) / 100;
    $final_price = $original_price - $discount_amount;
    
    return [
        'original_price' => $original_price,
        'discount_percent' => $discount_percent,
        'discount_amount' => $discount_amount,
        'final_price' => $final_price
    ];
}

/**
 * Generate Invoice Number
 */
function generate_invoice_number($prefix = 'INV') {
    return $prefix . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Array to CSV
 */
function array_to_csv($array, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // BOM สำหรับ UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    if (!empty($array)) {
        fputcsv($output, array_keys($array[0]));
    }
    
    // Data
    foreach ($array as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Send Email (Simple)
 */
function send_email($to, $subject, $message, $from = 'noreply@hardware.com') {
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Log Activity
 */
function log_activity($action, $details = '', $user_id = null) {
    require_once __DIR__ . '/database.php';
    
    if ($user_id === null && function_exists('get_current_user')) {
        $user = get_current_user();
        $user_id = $user['id'] ?? null;
    }
    
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES (:user_id, :action, :details, :ip, :agent, NOW())";
    
    db_query($sql, [
        ':user_id' => $user_id,
        ':action' => $action,
        ':details' => $details,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Check Stock Availability
 */
function check_stock($product_id, $quantity) {
    require_once __DIR__ . '/database.php';
    
    $product = db_fetch_one("SELECT stock FROM products WHERE id = :id", [':id' => $product_id]);
    
    if (!$product) {
        return false;
    }
    
    return $product['stock'] >= $quantity;
}

/**
 * Calculate Shipping Cost
 */
function calculate_shipping($distance_km, $weight_kg) {
    $base_cost = 50;
    $distance_rate = 5; // บาท/กม.
    $weight_rate = 2;   // บาท/กก.
    
    $total = $base_cost + ($distance_km * $distance_rate) + ($weight_kg * $weight_rate);
    
    return max($total, 50); // ขั้นต่ำ 50 บาท
}

/**
 * Debug Helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

function dump($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}