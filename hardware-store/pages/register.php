<?php
/**
 * Register Page
 * หน้าสมัครสมาชิก
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/helpers.php';

// ถ้า Login แล้วให้ไปหน้าแรก
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $username = clean_input($_POST['username'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $accept_terms = isset($_POST['accept_terms']);
    
    // Validation
    if (empty($full_name)) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    }
    
    if (empty($username)) {
        $errors[] = 'กรุณากรอกชื่อผู้ใช้';
    } elseif (strlen($username) < 4) {
        $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 4 ตัวอักษร';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'ชื่อผู้ใช้ต้องเป็นตัวอักษร ตัวเลข หรือ _ เท่านั้น';
    }
    
    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!is_valid_email($email)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    
    if (!empty($phone) && !is_valid_phone($phone)) {
        $errors[] = 'เบอร์โทรศัพท์ไม่ถูกต้อง';
    }
    
    if (empty($password)) {
        $errors[] = 'กรุณากรอกรหัสผ่าน';
    } elseif (strlen($password) < 6) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'รหัสผ่านไม่ตรงกัน';
    }
    
    if (!$accept_terms) {
        $errors[] = 'กรุณายอมรับข้อกำหนดและเงื่อนไข';
    }
    
    // ตรวจสอบ username ซ้ำ
    if (empty($errors)) {
        $existing_user = db_fetch_one(
            "SELECT id FROM users WHERE username = :username",
            [':username' => $username]
        );
        
        if ($existing_user) {
            $errors[] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
        }
    }
    
    // ตรวจสอบ email ซ้ำ
    if (empty($errors)) {
        $existing_email = db_fetch_one(
            "SELECT id FROM users WHERE email = :email",
            [':email' => $email]
        );
        
        if ($existing_email) {
            $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
        }
    }
    
    // บันทึกข้อมูล
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, full_name, phone, role, is_active) 
                VALUES (:username, :email, :password, :full_name, :phone, 'customer', 1)";
        
        $user_id = db_insert($sql, [
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashed_password,
            ':full_name' => $full_name,
            ':phone' => $phone
        ]);
        
        if ($user_id) {
            log_activity('register', "New user registered: {$username}", $user_id);
            
            $success = true;
            
            // Auto login หลังสมัคร
            session_set('user_id', $user_id);
            session_set('user_name', $full_name);
            session_set('user_email', $email);
            session_set('user_role', 'customer');
            
            flash_set('success', 'สมัครสมาชิกสำเร็จ! ยินดีต้อนรับ');
            
            // Redirect หลังจาก 2 วินาที
            header("Refresh: 2; url=index.php");
        } else {
            $errors[] = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ร้านฮาร์ดแวร์และวัสดุก่อสร้าง</title>
    
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
        <div class="auth-card auth-card-wide">
            <!-- Logo -->
            <div class="auth-logo text-center mb-4">
                <i class="fas fa-user-plus fa-3x mb-3" style="color: #ff6b00;"></i>
                <h2 class="fw-bold">สมัครสมาชิก</h2>
                <p class="text-muted">สร้างบัญชีใหม่</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success-custom">
                <i class="fas fa-check-circle me-2"></i>
                สมัครสมาชิกสำเร็จ! กำลังพาคุณไปหน้าแรก...
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger-custom">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" id="registerForm">
                <div class="row g-3">
                    <!-- Full Name -->
                    <div class="col-md-6">
                        <label class="form-label-custom">
                            <i class="fas fa-id-card me-2"></i>ชื่อ-นามสกุล *
                        </label>
                        <input type="text" 
                               name="full_name" 
                               class="form-control form-control-custom" 
                               placeholder="นายสมชาย ใจดี"
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                               required>
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label class="form-label-custom">
                            <i class="fas fa-user me-2"></i>ชื่อผู้ใช้ *
                        </label>
                        <input type="text" 
                               name="username" 
                               class="form-control form-control-custom" 
                               placeholder="username (อย่างน้อย 4 ตัว)"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               pattern="[a-zA-Z0-9_]{4,}"
                               required>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label-custom">
                            <i class="fas fa-envelope me-2"></i>อีเมล *
                        </label>
                        <input type="email" 
                               name="email" 
                               class="form-control form-control-custom" 
                               placeholder="email@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required>
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label class="form-label-custom">
                            <i class="fas fa-phone me-2"></i>เบอร์โทรศัพท์
                        </label>
                        <input type="tel" 
                               name="phone" 
                               class="form-control form-control-custom" 
                               placeholder="08X-XXX-XXXX"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               pattern="[0-9]{9,10}">
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label class="form-label-custom">
                            <i class="fas fa-lock me-2"></i>รหัสผ่าน *
                        </label>
                        <div class="password-input">
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control form-control-custom" 
                                   placeholder="อย่างน้อย 6 ตัว"
                                   minlength="6"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2" id="passwordStrength"></div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6">
                        <label class="form-label-custom">
                            <i class="fas fa-lock me-2"></i>ยืนยันรหัสผ่าน *
                        </label>
                        <div class="password-input">
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password"
                                   class="form-control form-control-custom" 
                                   placeholder="กรอกรหัสผ่านอีกครั้ง"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="accept_terms" name="accept_terms" required>
                            <label class="form-check-label" for="accept_terms">
                                ฉันยอมรับ 
                                <a href="#" class="text-decoration-none" style="color: #ff6b00;">ข้อกำหนดและเงื่อนไข</a>
                                และ
                                <a href="#" class="text-decoration-none" style="color: #ff6b00;">นโยบายความเป็นส่วนตัว</a>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary-custom w-100 btn-lg">
                            <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
                        </button>
                    </div>

                    <!-- Login Link -->
                    <div class="col-12 text-center">
                        <p class="mb-0">
                            มีบัญชีอยู่แล้ว? 
                            <a href="login.php" class="text-decoration-none fw-bold" style="color: #ff6b00;">
                                เข้าสู่ระบบ
                            </a>
                        </p>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
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

        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            const levels = ['', 'อ่อนแอ', 'ปานกลาง', 'ดี', 'แข็งแกร่ง', 'แข็งแกร่งมาก'];
            const colors = ['', '#dc3545', '#ffc107', '#00adb5', '#28a745', '#28a745'];
            
            if (password.length > 0) {
                strengthDiv.innerHTML = `<small style="color: ${colors[strength]}">ความแข็งแกร่ง: ${levels[strength]}</small>`;
            } else {
                strengthDiv.innerHTML = '';
            }
        });

        // Confirm password validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('รหัสผ่านไม่ตรงกัน');
                return false;
            }
        });
    </script>
</body>
</html>