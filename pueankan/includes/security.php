<?php
/**
 * ============================================
 * Security Functions & Helpers
 * ============================================
 * ฟังก์ชันด้านความปลอดภัยสำหรับทุกหน้า
 * ใช้แทน config/security.php สำหรับหน้าที่ไม่ได้ใช้ระบบบัญชี
 * ============================================
 */

// ป้องกันการเข้าถึงไฟล์โดยตรง
if (!defined('SECURITY_LOADED')) {
    define('SECURITY_LOADED', true);
}

/**
 * Escape Output (XSS Protection)
 * ใช้สำหรับแสดงผลข้อมูลที่มาจาก User Input
 */
if (!function_exists('e')) {
    function e($string) {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('escape_output')) {
    function escape_output($string) {
        return e($string);
    }
}

/**
 * Sanitize String
 * ทำความสะอาดข้อความ
 */
if (!function_exists('sanitize_string')) {
    function sanitize_string($string) {
        if ($string === null) {
            return '';
        }
        $string = trim($string);
        $string = stripslashes($string);
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize Email
 */
if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        if ($email === null) {
            return '';
        }
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}

/**
 * Sanitize Integer
 */
if (!function_exists('sanitize_int')) {
    function sanitize_int($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
}

/**
 * Sanitize Float
 */
if (!function_exists('sanitize_float')) {
    function sanitize_float($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}

/**
 * Validate Email
 */
if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Generate CSRF Token
 */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

/**
 * Validate CSRF Token
 */
if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Insert CSRF Hidden Field
 */
if (!function_exists('csrf_field')) {
    function csrf_field() {
        $token = generate_csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

/**
 * Get CSRF Token
 */
if (!function_exists('csrf_token')) {
    function csrf_token() {
        return generate_csrf_token();
    }
}

/**
 * Redirect Helper
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/**
 * Check if Request is POST
 */
if (!function_exists('is_post')) {
    function is_post() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

/**
 * Check if Request is GET
 */
if (!function_exists('is_get')) {
    function is_get() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}

/**
 * Get POST Data
 */
if (!function_exists('post')) {
    function post($key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
}

/**
 * Get GET Data
 */
if (!function_exists('get')) {
    function get($key, $default = null) {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
}

/**
 * Flash Message Helper
 */
if (!function_exists('set_flash')) {
    function set_flash($type, $message) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flash;
        }
        
        return null;
    }
}

if (!function_exists('has_flash')) {
    function has_flash() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['flash_message']);
    }
}

/**
 * Display Flash Message HTML
 */
if (!function_exists('display_flash')) {
    function display_flash() {
        $flash = get_flash();
        if ($flash) {
            $typeColors = [
                'success' => 'success',
                'error' => 'danger',
                'danger' => 'danger',
                'warning' => 'warning',
                'info' => 'info'
            ];
            
            $alertType = isset($typeColors[$flash['type']]) ? $typeColors[$flash['type']] : 'info';
            
            echo '<div class="alert alert-' . $alertType . ' alert-dismissible fade show" role="alert">';
            echo '<i class="fas fa-' . ($alertType === 'success' ? 'check-circle' : 'exclamation-triangle') . ' me-2"></i>';
            echo e($flash['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
    }
}

/**
 * Check if user is logged in
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
}

/**
 * Require Login
 */
if (!function_exists('require_login')) {
    function require_login($redirect_to = '../pages/login.php') {
        if (!is_logged_in()) {
            redirect($redirect_to);
        }
    }
}

/**
 * Get Current User ID
 */
if (!function_exists('current_user_id')) {
    function current_user_id() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
}

/**
 * Get Current User Name
 */
if (!function_exists('current_user_name')) {
    function current_user_name() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
    }
}

/**
 * Password Hash Helper
 */
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

/**
 * Password Verify Helper
 */
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Generate Random String
 */
if (!function_exists('random_string')) {
    function random_string($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

/**
 * Get User IP Address
 */
if (!function_exists('get_ip_address')) {
    function get_ip_address() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

/**
 * Get User Agent
 */
if (!function_exists('get_user_agent')) {
    function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    }
}

/**
 * Check if Request is AJAX
 */
if (!function_exists('is_ajax')) {
    function is_ajax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

/**
 * JSON Response Helper
 */
if (!function_exists('json_response')) {
    function json_response($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

/**
 * Simple Rate Limiting
 */
if (!function_exists('check_rate_limit')) {
    function check_rate_limit($action, $max_attempts = 5, $timeout = 300) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . $action;
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        // Reset if timeout passed
        if ($now - $data['start'] > $timeout) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        // Check if exceeded
        if ($data['count'] >= $max_attempts) {
            return false;
        }
        
        // Increment
        $_SESSION[$key]['count']++;
        return true;
    }
}

/**
 * Clean String for Filename
 */
if (!function_exists('clean_filename')) {
    function clean_filename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_');
    }
}

/**
 * Format File Size
 */
if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

/**
 * Truncate String
 */
if (!function_exists('str_limit')) {
    function str_limit($string, $limit = 100, $end = '...') {
        if (mb_strlen($string) <= $limit) {
            return $string;
        }
        
        return mb_substr($string, 0, $limit) . $end;
    }
}

/**
 * Debug Helper
 */
if (!function_exists('dd')) {
    function dd(...$vars) {
        echo '<pre style="background: #1a1a2e; color: #00ff00; padding: 20px; border: 2px solid #ff6b00; border-radius: 5px;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n";
        }
        echo '</pre>';
        die();
    }
}

/**
 * Simple Logger
 */
if (!function_exists('log_message')) {
    function log_message($message, $level = 'INFO') {
        $log_file = __DIR__ . '/../logs/app.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}