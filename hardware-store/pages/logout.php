<?php
/**
 * Logout Page
 * ออกจากระบบ
 */

require_once '../config/session.php';
require_once '../config/helpers.php';

// Log activity ก่อน logout
if (is_logged_in()) {
    $user = get_current_user();
    log_activity('logout', 'User logged out', $user['id']);
}

// ทำลาย Session
session_destroy_all();

// Redirect ไปหน้า Login พร้อมข้อความ
flash_set('success', 'ออกจากระบบเรียบร้อยแล้ว');
redirect('login.php');