<!-- 
    Footer Component
    ส่วนท้ายของเว็บไซต์
-->
<footer class="footer mt-5">
    <div class="container">
        <div class="row py-5">
            <!-- คอลัมน์ 1: เกี่ยวกับเรา -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">
                    <i class="fas fa-tools"></i> ร้านฮาร์ดแวร์และวัสดุก่อสร้าง
                </h5>
                <p class="footer-text">
                    ผู้เชี่ยวชาญด้านวัสดุก่อสร้างและอุปกรณ์ฮาร์ดแวร์ 
                    พร้อมให้บริการครบวงจรสำหรับงานก่อสร้างทุกประเภท
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
                <h5 class="footer-title">เมนูด่วน</h5>
                <ul class="footer-menu">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> หน้าแรก</a></li>
                    <li><a href="products.php"><i class="fas fa-chevron-right"></i> สินค้าทั้งหมด</a></li>
                    <li><a href="services.php"><i class="fas fa-chevron-right"></i> บริการของเรา</a></li>
                    <li><a href="calculator.php"><i class="fas fa-chevron-right"></i> เครื่องคำนวณ</a></li>
                    <li><a href="quotation.php"><i class="fas fa-chevron-right"></i> ขอใบเสนอราคา</a></li>
                </ul>
            </div>

            <!-- คอลัมน์ 3: ติดต่อเรา -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">ติดต่อเรา</h5>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        123 ถนนก่อสร้าง ตำบลวัสดุ<br>
                        อำเภอฮาร์ดแวร์ จังหวัดไทย 10000
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <a href="tel:0812345678">081-234-5678</a>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:info@hardware.com">info@hardware.com</a>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        จันทร์ - เสาร์: 08:00 - 18:00<br>
                        อาทิตย์: 09:00 - 15:00
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> ร้านฮาร์ดแวร์และวัสดุก่อสร้าง | 
                        พัฒนาด้วย ❤️ โดย Senior Full-Stack Developer
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

.footer-text {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.8;
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

.footer-contact a:hover {
    color: #ff6b00;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px 0;
    margin-top: 30px;
}

.footer-bottom p {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
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