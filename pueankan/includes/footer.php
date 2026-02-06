<!-- 
    Footer Component
    ส่วนท้ายของเว็บไซต์ - Hardware Store Theme
-->
<footer class="footer mt-5">
    <div class="container">
        <div class="row py-5">
            <!-- คอลัมน์ 1: เกี่ยวกับเรา -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">
                    <i class="fas fa-store"></i> <?php echo COMPANY_NAME ?? 'ร้านเพื่อนกัน'; ?>
                </h5>
                <p class="footer-text">
                    ระบบบัญชีและการเงินแบบครบวงจร พร้อมความปลอดภัยระดับสูง
                    สำหรับธุรกิจร้านฮาร์ดแวร์และวัสดุก่อสร้าง
                </p>
                <div class="social-links mt-3">
                    <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-line"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fas fa-phone"></i></a>
                </div>
            </div>

            <!-- คอลัมน์ 2: เมนูด่วน -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">เมนูระบบ</h5>
                <ul class="footer-menu">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Dashboard</a></li>
                    <li><a href="chart-of-accounts.php"><i class="fas fa-chevron-right"></i> ผังบัญชี</a></li>
                    <li><a href="journal-entries.php"><i class="fas fa-chevron-right"></i> รายการบัญชี</a></li>
                    <li><a href="accounts-receivable.php"><i class="fas fa-chevron-right"></i> ลูกหนี้</a></li>
                    <li><a href="financial-statements.php"><i class="fas fa-chevron-right"></i> งบการเงิน</a></li>
                </ul>
            </div>

            <!-- คอลัมน์ 3: ติดต่อเรา -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">ข้อมูลบริษัท</h5>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-building"></i>
                        <?php echo COMPANY_NAME ?? 'ร้านเพื่อนกัน'; ?>
                    </li>
                    <li>
                        <i class="fas fa-id-card"></i>
                        เลขประจำตัวผู้เสียภาษี: <?php echo COMPANY_TAX_ID ?? 'X-XXXX-XXXXX-XX-X'; ?>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:info@pueankan.com">info@pueankan.com</a>
                    </li>
                    <li>
                        <i class="fas fa-shield-alt"></i>
                        Security-First Architecture<br>
                        Double-Entry Bookkeeping System
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME ?? 'ร้านเพื่อนกัน'; ?> | 
                        ระบบบัญชีและการเงินแบบครบวงจร
                        <br>
                        <small style="opacity: 0.7;">
                            Developed with ❤️ | All Rights Reserved
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background: linear-gradient(180deg, rgba(26, 26, 46, 0.95) 0%, rgba(22, 33, 62, 0.98) 100%);
    color: #ffffff;
    position: relative;
    border-top: 2px solid rgba(255, 107, 0, 0.3);
}

body.theme-light .footer {
    background: linear-gradient(180deg, rgba(248, 249, 250, 0.95) 0%, rgba(233, 236, 239, 0.98) 100%);
    color: #000000;
    border-top: 2px solid rgba(255, 107, 0, 0.5);
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255, 107, 0, 0.5) 50%, 
        transparent 100%
    );
}

.footer-title {
    color: #ff6b00;
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 1.2rem;
}

body.theme-light .footer-title {
    color: #ff6b00;
}

.footer-text {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.8;
}

body.theme-light .footer-text {
    color: rgba(0, 0, 0, 0.7);
}

.social-links {
    display: flex;
    gap: 15px;
}

.social-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 107, 0, 0.2);
    color: #ff6b00;
    border-radius: 50%;
    transition: all 0.3s ease;
    text-decoration: none;
}

.social-icon:hover {
    background: #ff6b00;
    color: #ffffff;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(255, 107, 0, 0.4);
}

.footer-menu {
    list-style: none;
    padding: 0;
}

.footer-menu li {
    margin-bottom: 12px;
}

.footer-menu a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
}

body.theme-light .footer-menu a {
    color: rgba(0, 0, 0, 0.7);
}

.footer-menu a:hover {
    color: #ff6b00;
    transform: translateX(5px);
}

.footer-menu i {
    font-size: 0.8rem;
    margin-right: 8px;
}

.footer-contact {
    list-style: none;
    padding: 0;
}

.footer-contact li {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
}

body.theme-light .footer-contact li {
    color: rgba(0, 0, 0, 0.7);
}

.footer-contact i {
    color: #ff6b00;
    margin-right: 12px;
    margin-top: 3px;
    min-width: 20px;
}

.footer-contact a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s ease;
}

body.theme-light .footer-contact a {
    color: rgba(0, 0, 0, 0.7);
}

.footer-contact a:hover {
    color: #ff6b00;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px 0;
    margin-top: 30px;
}

body.theme-light .footer-bottom {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.footer-bottom p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

body.theme-light .footer-bottom p {
    color: rgba(0, 0, 0, 0.6);
}

@media (max-width: 768px) {
    .footer .col-md-4 {
        text-align: center;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .footer-menu,
    .footer-contact {
        text-align: left;
        display: inline-block;
    }
}
</style>