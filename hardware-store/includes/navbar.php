<!-- 
    Navbar Component
    Navigation Bar สำหรับทุกหน้า
-->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="main-navbar">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-tools me-2"></i>
            <span class="fw-bold">ร้านฮาร์ดแวร์</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> หน้าแรก
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-boxes"></i> สินค้า
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="services.php">
                        <i class="fas fa-wrench"></i> บริการ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="calculator.php">
                        <i class="fas fa-calculator"></i> เครื่องคำนวณ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="quotation.php">
                        <i class="fas fa-file-invoice"></i> ใบเสนอราคา
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">
                        <i class="fas fa-phone"></i> ติดต่อเรา
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> ตะกร้า
                        <?php
                        if (function_exists('cart_count')) {
                            $cart_count = cart_count();
                            if ($cart_count > 0):
                        ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cart_count; ?>
                        </span>
                        <?php 
                            endif;
                        }
                        ?>
                    </a>
                </li>
                
                <?php
                // Auto-load session if not loaded
                if (!function_exists('is_logged_in')) {
                    require_once __DIR__ . '/../config/session.php';
                }
                
                if (is_logged_in()):
                    $user = get_current_user();
                ?>
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($user['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>โปรไฟล์
                            </a></li>
                            <li><a class="dropdown-item" href="my-orders.php">
                                <i class="fas fa-shopping-bag me-2"></i>คำสั่งซื้อของฉัน
                            </a></li>
                            <?php if ($user['role'] === 'admin'): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../admin/index.php">
                                <i class="fas fa-cog me-2"></i>Admin Dashboard
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Login/Register Buttons -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-register" href="register.php">
                            <i class="fas fa-user-plus"></i> สมัครสมาชิก
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php
// Auto-load session if not loaded
if (!function_exists('cart_count')) {
    require_once __DIR__ . '/../config/session.php';
}
?>

<style>
.user-dropdown {
    background: rgba(26, 26, 46, 0.98);
    border: 1px solid rgba(255, 107, 0, 0.3);
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    min-width: 200px;
}

.user-dropdown .dropdown-item {
    color: rgba(255, 255, 255, 0.8);
    padding: 10px 20px;
    transition: all 0.3s ease;
}

.user-dropdown .dropdown-item:hover {
    background: rgba(255, 107, 0, 0.2);
    color: #ff6b00;
}

.user-dropdown .dropdown-divider {
    border-color: rgba(255, 255, 255, 0.1);
}

.btn-register {
    background: rgba(255, 107, 0, 0.2);
    border-radius: 20px;
    padding: 8px 20px !important;
    margin-left: 10px;
}

.btn-register:hover {
    background: linear-gradient(135deg, #ff6b00, #ffc107);
}
</style>

<style>
#main-navbar {
    background: rgba(26, 26, 46, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 2px solid rgba(255, 107, 0, 0.3);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

body.theme-light #main-navbar {
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

body.theme-light #main-navbar .nav-link {
    color: #333333 !important;
}

body.theme-light #main-navbar .nav-link:hover {
    color: #ff6b00 !important;
}

body.theme-light #main-navbar .navbar-brand {
    color: #ff6b00 !important;
}

.navbar-brand {
    font-size: 1.5rem;
    color: #ff6b00 !important;
    transition: all 0.3s ease;
}

.navbar-brand:hover {
    transform: scale(1.05);
    color: #ffc107 !important;
}

.nav-link {
    color: #ffffff !important;
    margin: 0 10px;
    padding: 8px 16px !important;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.nav-link:hover {
    background: rgba(255, 107, 0, 0.2);
    color: #ff6b00 !important;
    transform: translateY(-2px);
}

.nav-link.active {
    background: rgba(255, 107, 0, 0.3);
    color: #ffc107 !important;
}

.navbar-toggler {
    border-color: #ff6b00;
}

.navbar-toggler:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 107, 0, 0.25);
}

@media (max-width: 991px) {
    .navbar-nav {
        background: rgba(22, 33, 62, 0.95);
        border-radius: 10px;
        padding: 15px;
        margin-top: 10px;
    }
    
    .nav-link {
        margin: 5px 0;
    }
}
</style>