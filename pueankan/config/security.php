<?php
/**
 * ============================================
 * ไฟล์: config/security.php
 * คำอธิบาย: Security Configuration & Middleware
 * วัตถุประสงค์: ป้องกันการโจมตี และควบคุมการเข้าถึง
 * ============================================
 */

// Start Session Securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * CSRF Token Generation & Validation
 * ป้องกัน Cross-Site Request Forgery
 */
class CSRF {
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function insertHiddenField() {
        echo '<input type="hidden" name="csrf_token" value="' . self::generateToken() . '">';
    }
}

/**
 * Input Sanitization
 * ทำความสะอาด Input ทุกตัว
 */
class Sanitize {
    public static function string($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function number($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    public static function integer($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    public static function email($input) {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }
    
    public static function array($input) {
        if (!is_array($input)) return [];
        return array_map([self::class, 'string'], $input);
    }
}

/**
 * Input Validation
 * ตรวจสอบความถูกต้องของข้อมูล
 */
class Validate {
    public static function required($value) {
        return !empty(trim($value));
    }
    
    public static function number($value) {
        return is_numeric($value);
    }
    
    public static function positiveNumber($value) {
        return is_numeric($value) && $value > 0;
    }
    
    public static function date($value) {
        return (bool)strtotime($value);
    }
    
    public static function enum($value, $allowedValues) {
        return in_array($value, $allowedValues, true);
    }
}

/**
 * Access Control
 * ควบคุมสิทธิ์การเข้าถึง
 */
class AccessControl {
    private static $permissions = [
        'admin' => ['all'],
        'accountant' => [
            'accounting.view',
            'accounting.create',
            'accounting.edit',
            'accounting.reports'
        ],
        'manager' => [
            'accounting.view',
            'accounting.reports',
            'sales.all',
            'inventory.all'
        ],
        'staff' => [
            'sales.create',
            'sales.view',
            'inventory.view'
        ]
    ];
    
    public static function check($permission) {
        $userRole = $_SESSION['user_role'] ?? 'guest';
        
        // Admin มีสิทธิ์ทุกอย่าง
        if ($userRole === 'admin') return true;
        
        // ตรวจสอบสิทธิ์ตาม role
        $rolePermissions = self::$permissions[$userRole] ?? [];
        return in_array($permission, $rolePermissions, true);
    }
    
    public static function requirePermission($permission) {
        if (!self::check($permission)) {
            http_response_code(403);
            die('Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
    }
}

/**
 * Audit Logger
 * บันทึกการกระทำทั้งหมด
 */
class AuditLog {
    public static function log($action, $details = '', $severity = 'info') {
        global $pdo;
        
        try {
            $sql = "INSERT INTO audit_logs 
                    (user_id, action, details, severity, ip_address, user_agent) 
                    VALUES (:user_id, :action, :details, :severity, :ip, :agent)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':action' => $action,
                ':details' => $details,
                ':severity' => $severity,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (PDOException $e) {
            error_log("Audit Log Error: " . $e->getMessage());
        }
    }
    
    public static function logAccountingEntry($type, $amount, $description) {
        self::log(
            'accounting.' . $type,
            "Amount: {$amount}, Description: {$description}",
            'critical'
        );
    }
}

/**
 * Error Handler
 * จัดการ Error แบบปลอดภัย
 */
class SecureError {
    public static function handle($message, $logDetails = null) {
        // Log รายละเอียดจริง
        if ($logDetails) {
            error_log("Error: {$message} | Details: {$logDetails}");
        }
        
        // แสดงข้อความที่เป็นมิตรกับผู้ใช้
        return "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
    }
    
    public static function dbError($e) {
        error_log("Database Error: " . $e->getMessage());
        return "ไม่สามารถดำเนินการได้ กรุณาติดต่อผู้ดูแลระบบ";
    }
}

/**
 * Rate Limiting (Simple)
 * จำกัดจำนวนครั้งในการเข้าถึง
 */
class RateLimit {
    public static function check($action, $limit = 100, $period = 3600) {
        $key = 'rate_limit_' . $action . '_' . ($_SESSION['user_id'] ?? session_id());
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'start' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Reset ถ้าเกินช่วงเวลา
        if (time() - $data['start'] > $period) {
            $_SESSION[$key] = ['count' => 1, 'start' => time()];
            return true;
        }
        
        // ตรวจสอบ limit
        if ($data['count'] >= $limit) {
            http_response_code(429);
            die('Too Many Requests: กรุณารอสักครู่');
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
}

/**
 * XSS Protection Helper
 */
function escape_output($value) {
    if (is_array($value)) {
        return array_map('escape_output', $value);
    }
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Auto-escape template variable
 */
function e($value) {
    return escape_output($value);
}