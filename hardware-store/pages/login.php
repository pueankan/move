<?php
/**
 * Login Page
 * หน้าเข้าสู่ระบบ
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// ถ้า Login แล้วให้ไปหน้าแรก
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        // ค้นหาผู้ใช้
        $user = db_fetch_one(
            "SELECT * FROM users WHERE (username = :username OR email = :username) AND is_active = 1",
            [':username' => $username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            // Login สำเร็จ
            session_set('user_id', $user['id']);
            session_set('user_name', $user['full_name']);
            session_set('user_email', $user['email']);
            session_set('user_role', $user['role']);
            
            // อัพเดท last_login
            db_query(
                "UPDATE users SET last_login = NOW() WHERE id = :id",
                [':id' => $user['id']]
            );
            
            // Log activity
            log_activity('login', 'User logged in', $user['id']);
            
            flash_set('success', 'เข้าสู่ระบบสำเร็จ');
            
            // Redirect ตาม role
            if ($user['role'] === 'admin') {
                redirect('../admin/index.php');
            } else {
                redirect('index.php');
            }
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            log_activity('login_failed', "Failed login attempt: {$username}");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <!-- Animated Background -->
    <?php include '../includes/background.php'; ?>
    
    <div class="auth-container">
        <div class="auth-card">
            <!-- Logo -->
            <div class="auth-logo text-center mb-4">
                <i class="fas fa-tools fa-3x mb-3" style="color: #ff6b00;"></i>
                <h2 class="fw-bold">ร้านฮาร์ดแวร์</h2>
                <p class="text-muted">เข้าสู่ระบบ</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger-custom">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <!-- Username/Email -->
                <div class="mb-3">
                    <label class="form-label-custom">
                        <i class="fas fa-user me-2"></i>ชื่อผู้ใช้ หรือ อีเมล
                    </label>
                    <input type="text" 
                           name="username" 
                           class="form-control form-control-custom" 
                           placeholder="username หรือ email@example.com"
                           required 
                           autofocus>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="form-label-custom">
                        <i class="fas fa-lock me-2"></i>รหัสผ่าน
                    </label>
                    <div class="password-input">
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="form-control form-control-custom" 
                               placeholder="••••••••"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">
                            จดจำฉันไว้
                        </label>
                    </div>
                    <a href="forgot-password.php" class="text-decoration-none" style="color: #ff6b00;">
                        ลืมรหัสผ่าน?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary-custom w-100 btn-lg mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                </button>

                <!-- Register Link -->
                <div class="text-center">
                    <p class="mb-0">
                        ยังไม่มีบัญชี? 
                        <a href="register.php" class="text-decoration-none fw-bold" style="color: #ff6b00;">
                            สมัครสมาชิก
                        </a>
                    </p>
                </div>
            </form>

            <!-- Divider -->
            <div class="divider my-4">
                <span>หรือ</span>
            </div>

            <!-- Guest Access -->
            <a href="index.php" class="btn btn-outline-custom w-100">
                <i class="fas fa-user-circle me-2"></i>เข้าชมแบบไม่ลงทะเบียน
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.username.value.trim();
            const password = this.password.value;

            if (!username || !password) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
        });
    </script>
</body>
</html>