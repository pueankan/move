<?php
/**
 * Session Management
 * ห้ามมี BOM / space / newline ก่อนแท็กนี้
 */

// ถ้า headers ยังไม่ถูกส่ง และ session ยังไม่เริ่ม → ค่อย start
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * =========================
 * Session Helpers
 * =========================
 */

if (!function_exists('session_set')) {
    function session_set($key, $value) {
        $_SESSION[$key] = $value;
    }
}

if (!function_exists('session_get')) {
    function session_get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('session_delete')) {
    function session_delete($key) {
        unset($_SESSION[$key]);
    }
}

if (!function_exists('session_destroy_all')) {
    function session_destroy_all() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
}

/**
 * =========================
 * Authentication
 * =========================
 */

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user')) {
    function current_user() {
        if (!is_logged_in()) {
            return null;
        }

        return [
            'id'    => $_SESSION['user_id'] ?? null,
            'name'  => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role'  => $_SESSION['user_role'] ?? 'customer'
        ];
    }
}

/**
 * =========================
 * Flash Message
 * =========================
 */

if (!function_exists('flash_set')) {
    function flash_set($type, $message) {
        $_SESSION['flash_message'] = compact('type', 'message');
    }
}

if (!function_exists('flash_get')) {
    function flash_get() {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flash;
        }
        return null;
    }
}

/**
 * =========================
 * Cart
 * =========================
 */

if (!function_exists('cart_add')) {
    function cart_add($id, $qty = 1) {
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    }
}

if (!function_exists('cart_get')) {
    function cart_get() {
        return $_SESSION['cart'] ?? [];
    }
}

/**
 * =========================
 * CSRF
 * =========================
 */

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}   