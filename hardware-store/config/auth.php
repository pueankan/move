<?php
/**
 * ========================================
 * Authentication System
 * ========================================
 * ระบบจัดการ Login, Register, Logout
 * และการตรวจสอบสิทธิ์การเข้าใช้งาน
 * ========================================
 */

// เริ่ม Session (ถ้ายังไม่ได้เริ่ม)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ========================================
 * User Registration
 * ========================================
 */
function register_user($username, $email, $password, $full_name, $phone = '', $address = '') {
    global $pdo;
    
    try {
        // ตรวจสอบว่า username หรือ email ซ้ำหรือไม่
        $check = db_fetch_one(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($check) {
            return [
                'success' => false,
                'message' => 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว'
            ];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $user_id = db_insert(
            "INSERT INTO users (username, email, password, full_name, phone, address, role) 
             VALUES (?, ?, ?, ?, ?, ?, 'customer')",
            [$username, $email, $hashed_password, $full_name, $phone, $address]
        );
        
        if ($user_id) {
            // Log activity
            log_activity($user_id, 'user_registered', "New user registered: $username");
            
            return [
                'success' => true,
                'message' => 'ลงทะเบียนสำเร็จ',
                'user_id' => $user_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'ไม่สามารถลงทะเบียนได้ กรุณาลองใหม่อีกครั้ง'
        ];
        
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
}

/**
 * ========================================
 * User Login
 * ========================================
 */
function login_user($username_or_email, $password, $remember = false) {
    global $pdo;
    
    try {
        // ค้นหาผู้ใช้
        $user = db_fetch_one(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username_or_email, $username_or_email]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            ];
        }
        
        // ตรวจสอบรหัสผ่าน
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            ];
        }
        
        // เก็บข้อมูลลง Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        // อัพเดท last_login
        db_query(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );
        
        // Log activity
        log_activity($user['id'], 'user_login', "User logged in: {$user['username']}");
        
        // Remember Me (ถ้าเลือก)
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
            // TODO: Save token to database
        }
        
        return [
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'user' => $user
        ];
        
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
}

/**
 * ========================================
 * User Logout
 * ========================================
 */
function logout_user() {
    if (isset($_SESSION['user_id'])) {
        // Log activity
        log_activity($_SESSION['user_id'], 'user_logout', "User logged out: {$_SESSION['username']}");
    }
    
    // ลบ Session
    session_unset();
    session_destroy();
    
    // ลบ Remember Me Cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    return true;
}

/**
 * ========================================
 * Check if user is logged in
 * ========================================
 */
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * ========================================
 * Get current user
 * ========================================
 */
function get_current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? 'customer'
    ];
}

/**
 * ========================================
 * Check user role
 * ========================================
 */
function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function is_admin() {
    return has_role('admin');
}

function is_staff() {
    return has_role('staff') || has_role('admin');
}

/**
 * ========================================
 * Require Login
 * ========================================
 */
function require_login($redirect = '/pages/login.php') {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect");
        exit;
    }
}

/**
 * ========================================
 * Require Admin
 * ========================================
 */
function require_admin($redirect = '/pages/index.php') {
    require_login();
    
    if (!is_admin()) {
        $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
        header("Location: $redirect");
        exit;
    }
}

/**
 * ========================================
 * Require Staff
 * ========================================
 */
function require_staff($redirect = '/pages/index.php') {
    require_login();
    
    if (!is_staff()) {
        $_SESSION['error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
        header("Location: $redirect");
        exit;
    }
}

/**
 * ========================================
 * Log Activity
 * ========================================
 */
function log_activity($user_id, $action, $details = '') {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        db_query(
            "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?)",
            [$user_id, $action, $details, $ip_address, $user_agent]
        );
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

/**
 * ========================================
 * Change Password
 * ========================================
 */
function change_password($user_id, $old_password, $new_password) {
    try {
        // ดึงข้อมูลผู้ใช้
        $user = db_fetch_one("SELECT password FROM users WHERE id = ?", [$user_id]);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'ไม่พบผู้ใช้'
            ];
        }
        
        // ตรวจสอบรหัสผ่านเดิม
        if (!password_verify($old_password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'รหัสผ่านเดิมไม่ถูกต้อง'
            ];
        }
        
        // เปลี่ยนรหัสผ่าน
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $result = db_query(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashed_password, $user_id]
        );
        
        if ($result) {
            log_activity($user_id, 'password_changed', 'User changed password');
            
            return [
                'success' => true,
                'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้'
        ];
        
    } catch (Exception $e) {
        error_log("Change Password Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
}

/**
 * ========================================
 * Update User Profile
 * ========================================
 */
function update_profile($user_id, $data) {
    try {
        $allowed_fields = ['full_name', 'phone', 'address'];
        $updates = [];
        $params = [];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return [
                'success' => false,
                'message' => 'ไม่มีข้อมูลที่จะอัพเดท'
            ];
        }
        
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $result = db_query($sql, $params);
        
        if ($result) {
            log_activity($user_id, 'profile_updated', 'User updated profile');
            
            return [
                'success' => true,
                'message' => 'อัพเดทข้อมูลสำเร็จ'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'ไม่สามารถอัพเดทข้อมูลได้'
        ];
        
    } catch (Exception $e) {
        error_log("Update Profile Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
}

/**
 * ========================================
 * Password Reset Token
 * ========================================
 */
function generate_reset_token($email) {
    try {
        $user = db_fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            // Don't reveal if email exists
            return [
                'success' => true,
                'message' => 'ถ้าอีเมลนี้มีในระบบ คุณจะได้รับลิงก์รีเซ็ตรหัสผ่าน'
            ];
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // TODO: Save token to database
        // TODO: Send email with reset link
        
        return [
            'success' => true,
            'message' => 'ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลแล้ว',
            'token' => $token
        ];
        
    } catch (Exception $e) {
        error_log("Reset Token Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในระบบ'
        ];
    }
}
