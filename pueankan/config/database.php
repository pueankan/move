<?php
/**
 * Database Connection Configuration
 * ไฟล์เชื่อมต่อฐานข้อมูล MySQL ด้วย PDO
 */

// การตั้งค่าฐานข้อมูล
define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'hardware_store');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ตัวแปร PDO สำหรับเชื่อมต่อ
$pdo = null;

try {
    // สร้าง DSN (Data Source Name)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // ตัวเลือก PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // สร้างการเชื่อมต่อ PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // จัดการข้อผิดพลาด
    error_log("Database Connection Error: " . $e->getMessage());
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}

/**
 * ฟังก์ชันช่วยเหลือในการ Query
 */

// ฟังก์ชัน Query แบบ Prepared Statement
function db_query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันดึงข้อมูลทั้งหมด
function db_fetch_all($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// ฟังก์ชันดึงข้อมูลแถวเดียว
function db_fetch_one($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

// ฟังก์ชัน Insert และคืน ID
function db_insert($sql, $params = []) {
    global $pdo;
    if (db_query($sql, $params)) {
        return $pdo->lastInsertId();
    }
    return false;
}

// ฟังก์ชัน Count rows
function db_count($table, $where = '', $params = []) {
    $sql = "SELECT COUNT(*) as total FROM {$table}";
    if ($where) {
        $sql .= " WHERE {$where}";
    }
    $result = db_fetch_one($sql, $params);
    return $result ? (int)$result['total'] : 0;
}