<footer class="modern-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="footer-brand">
                    <div class="brand-icon-footer" style="background: transparent; box-shadow: none;">
                        <img src="img/logo hsport putih.png" alt="H-Sport Logo" style="height: 100%; width: 100%; object-fit: contain;">
                    </div>
                    <h4>H-Sport Jersey</h4>
                </div>
                <p class="footer-desc">Custom jersey berkualitas tinggi untuk tim Anda. Desain sesuai keinginan dengan bahan terbaik.</p>
                <div class="d-flex align-items-center mt-3">
                    <span class="social-icon me-2" style="text-decoration: none; cursor: default;"><i class="fab fa-instagram"></i></span>
                    <span style="font-size: 1.1rem; font-weight: 500;">@h_sport77sublim</span>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="footer-title">
                    <i class="fas fa-map-marker-alt me-2"></i>Kontak Kami
                </h5>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <span>Telepon</span>
                            <a href="tel:081234567890">0812-3456-7890</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div>
                            <span>Email</span>
                            <a href="mailto:info@hsport.com">info@hsport.com</a>
                        </div>
                    </li>
                    <li>
                        <i class="fab fa-whatsapp"></i>
                        <div>
                            <span>WhatsApp</span>
                            <a href="https://wa.me/6281234567890">0812-3456-7890</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <div>
                            <span>Jam Operasional</span>
                            <p>Senin - Sabtu: 09:00 - 17:00<br>Minggu: Libur</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="footer-title">
                    <i class="fas fa-qrcode me-2"></i>Metode Pembayaran
                </h5>
                <div class="payment-qris">
                    <div class="qris-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="qris-info">
                        <h6>QRIS</h6>
                        <p>Bayar mudah dengan QRIS<br>Scan menggunakan:</p>
                        <div class="qris-apps">
                            <span><i class="fab fa-google-play"></i> Gopay</span>
                            <span><i class="fab fa-google-play"></i> OVO</span>
                            <span><i class="fab fa-google-play"></i> Dana</span>
                            <span><i class="fas fa-university"></i> Mobile Banking</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="quick-links mt-4">
                    <h5 class="footer-title">
                        <i class="fas fa-link me-2"></i>Link Cepat
                    </h5>
                    <ul class="footer-quick-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Beranda</a></li>
                        <li><a href="products.php"><i class="fas fa-chevron-right"></i> Semua Produk</a></li>
                        <li><a href="customize.php"><i class="fas fa-chevron-right"></i> Custom Desain</a></li>
                        <li><a href="cart.php"><i class="fas fa-chevron-right"></i> Keranjang</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> H-Sport Jersey. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    .modern-footer {
        background: linear-gradient(135deg, #004b9c 0%, #3a302c 100%);
        color: #EDE9E6;
        padding: 60px 0 0;
        margin-top: 60px;
        position: relative;
        overflow: hidden;
    }
    
    /* Decorative elements */
    .modern-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #EDE9E6, #d4cec8, #b8aea6, #d4cec8, #EDE9E6);
        background-size: 200% 100%;
        animation: gradientBorder 3s linear infinite;
    }
    
    @keyframes gradientBorder {
        0% { background-position: 0% 0%; }
        100% { background-position: 200% 0%; }
    }
    
    /* Footer Brand */
    .footer-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .brand-icon-footer {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #EDE9E6 0%, #d4cec8 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }
    
    .brand-icon-footer i {
        font-size: 28px;
        color: #004b9c;
        transition: transform 0.3s ease;
    }
    
    .footer-brand:hover .brand-icon-footer i {
        transform: rotate(10deg) scale(1.1);
    }
    
    .footer-brand h4 {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #EDE9E6 0%, #ffffff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0;
    }
    
    .footer-desc {
        color: rgba(237, 233, 230, 0.8);
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    /* Social Icons */
    .social-icons {
        display: flex;
        gap: 12px;
    }
    
    .social-icon {
        width: 40px;
        height: 40px;
        background: rgba(237, 233, 230, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #EDE9E6;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(237, 233, 230, 0.2);
    }
    
    .social-icon:hover {
        background: linear-gradient(135deg, #EDE9E6 0%, #d4cec8 100%);
        color: #004b9c;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    /* Footer Title */
    .footer-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        position: relative;
        display: inline-block;
        padding-bottom: 10px;
    }
    
    .footer-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, #EDE9E6, #d4cec8);
        border-radius: 3px;
    }
    
    /* Contact List */
    .footer-contact {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-contact li {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 15px;
        transition: transform 0.3s ease;
    }
    
    .footer-contact li:hover {
        transform: translateX(5px);
    }
    
    .footer-contact li i {
        width: 35px;
        height: 35px;
        background: rgba(237, 233, 230, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #EDE9E6;
        transition: all 0.3s ease;
    }
    
    .footer-contact li:hover i {
        background: rgba(237, 233, 230, 0.2);
        transform: scale(1.1);
    }
    
    .footer-contact li div {
        flex: 1;
    }
    
    .footer-contact li span {
        display: block;
        font-size: 12px;
        color: rgba(237, 233, 230, 0.6);
        margin-bottom: 3px;
    }
    
    .footer-contact li a,
    .footer-contact li p {
        color: #EDE9E6;
        text-decoration: none;
        margin: 0;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .footer-contact li a:hover {
        color: #d4cec8;
    }
    
    /* QRIS Payment Section */
    .payment-qris {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid rgba(237, 233, 230, 0.2);
    }
    
    .payment-qris:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }
    
    .qris-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #EDE9E6 0%, #d4cec8 100%);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .qris-icon i {
        font-size: 40px;
        color: #004b9c;
    }
    
    .qris-info h6 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: #EDE9E6;
    }
    
    .qris-info p {
        font-size: 12px;
        color: rgba(237, 233, 230, 0.7);
        margin-bottom: 12px;
    }
    
    .qris-apps {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }
    
    .qris-apps span {
        background: rgba(237, 233, 230, 0.15);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        color: #EDE9E6;
        transition: all 0.3s ease;
    }
    
    .qris-apps span:hover {
        background: rgba(237, 233, 230, 0.3);
        transform: scale(1.05);
    }
    
    .qris-apps span i {
        margin-right: 5px;
        font-size: 10px;
    }
    
    /* Quick Links */
    .quick-links {
        margin-top: 25px;
    }
    
    .footer-quick-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-quick-links li {
        margin-bottom: 8px;
    }
    
    .footer-quick-links li a {
        color: rgba(237, 233, 230, 0.7);
        text-decoration: none;
        font-size: 13px;
        transition: all 0.3s ease;
        display: inline-block;
    }
    
    .footer-quick-links li a i {
        font-size: 10px;
        margin-right: 8px;
        transition: transform 0.3s ease;
    }
    
    .footer-quick-links li a:hover {
        color: #EDE9E6;
        transform: translateX(5px);
    }
    
    .footer-quick-links li a:hover i {
        transform: translateX(3px);
    }
    
    /* Footer Bottom */
    .footer-bottom {
        margin-top: 50px;
        padding: 20px 0;
        border-top: 1px solid rgba(237, 233, 230, 0.1);
        background: rgba(0, 0, 0, 0.2);
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 20px;
        justify-content: flex-end;
    }
    
    .footer-links li a {
        color: rgba(237, 233, 230, 0.7);
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .footer-links li a:hover {
        color: #EDE9E6;
        text-decoration: underline;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .modern-footer {
            padding: 40px 0 0;
        }
        
        .footer-links {
            justify-content: center;
            margin-top: 10px;
        }
        
        .social-icons {
            justify-content: center;
        }
        
        .footer-brand {
            justify-content: center;
        }
        
        .footer-desc {
            text-align: center;
        }
        
        .footer-title {
            text-align: center;
            display: block;
        }
        
        .footer-title::after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .footer-contact li {
            justify-content: center;
        }
        
        .payment-qris {
            margin: 0 15px;
        }
        
        .footer-quick-links {
            text-align: center;
        }
        
        .footer-quick-links li a:hover {
            transform: translateX(0);
        }
    }
</style>

<script>
    // Add animation on scroll
    document.addEventListener('DOMContentLoaded', function() {
        const footer = document.querySelector('.modern-footer');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });
        
        if (footer) {
            footer.style.opacity = '0';
            footer.style.transform = 'translateY(20px)';
            footer.style.transition = 'all 0.6s ease';
            observer.observe(footer);
        }
    });
</script>