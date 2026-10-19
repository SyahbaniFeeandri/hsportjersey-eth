<nav class="navbar navbar-expand-lg sticky-top modern-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <div class="brand-icon" style="background: transparent; box-shadow: none;">
                <img src="img/logo hsport putih.png" alt="H-Sport Logo" style="height: 100%; width: 100%; object-fit: contain;">
            </div>
            <span class="brand-text">H-Sport Jersey</span>
        </a>
        <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-tshirt me-1"></i>Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customize.php">
                        <i class="fas fa-palette me-1"></i>Custom
                    </a>
                </li>
                <?php if(isLoggedIn()): ?>
                    <?php if(isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-chart-line me-1"></i>Dashboard Admin
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link cart-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Keranjang
                            <span class="cart-badge" id="cartCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-clipboard-list me-1"></i>Pesanan Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="review.php">
                            <i class="fas fa-star me-1"></i>Ulasan Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link login-btn" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link register-btn" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Modern Navbar Styles */
    .modern-navbar {
        background: linear-gradient(135deg, #004b9c 0%, #003366 100%);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        padding: 12px 0;
        transition: all 0.3s ease;
    }
    
    /* Brand Style */
    .modern-navbar .navbar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform 0.3s ease;
    }
    
    .modern-navbar .navbar-brand:hover {
        transform: scale(1.05);
    }
    
    .brand-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #EDE9E6 0%, #d4cec8 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }
    
    .brand-icon i {
        font-size: 22px;
        color: #004b9c;
        transition: transform 0.3s ease;
    }
    
    .modern-navbar .navbar-brand:hover .brand-icon i {
        transform: rotate(10deg);
    }
    
    .brand-text {
        font-size: 1.4rem;
        font-weight: 700;
        background: linear-gradient(135deg, #EDE9E6 0%, #ffffff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: 0.5px;
    }
    
    /* Navbar Links */
    .modern-navbar .nav-link {
        color: #EDE9E6 !important;
        font-weight: 500;
        margin: 0 5px;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .modern-navbar .nav-link i {
        font-size: 14px;
        transition: transform 0.3s ease;
    }
    
    .modern-navbar .nav-link:hover {
        background: rgba(237, 233, 230, 0.15);
        transform: translateY(-2px);
    }
    
    .modern-navbar .nav-link:hover i {
        transform: translateX(3px);
    }
    
    /* Cart Badge */
    .cart-badge {
        display: inline-block;
        background: #EDE9E6;
        color: #004b9c;
        font-size: 11px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
        transition: all 0.3s ease;
        min-width: 20px;
        text-align: center;
    }
    
    .cart-link {
        position: relative;
    }
    
    .nav-link:hover .cart-badge {
        transform: scale(1.1);
        background: white;
    }
    
    /* Special buttons */
    .modern-navbar .logout-link {
        background: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.5);
    }
    
    .modern-navbar .logout-link:hover {
        background: rgba(220, 53, 69, 0.35);
        border-color: rgba(220, 53, 69, 0.8);
    }
    
    .modern-navbar .login-btn {
        background: rgba(237, 233, 230, 0.15);
        border: 1px solid rgba(237, 233, 230, 0.3);
    }
    
    .modern-navbar .register-btn {
        background: linear-gradient(135deg, #EDE9E6 0%, #d4cec8 100%);
        color: #004b9c !important;
        font-weight: 600;
    }
    
    .modern-navbar .register-btn:hover {
        background: linear-gradient(135deg, #d4cec8 0%, #c0b8b0 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .modern-navbar .register-btn i {
        color: #004b9c;
    }
    
    /* Toggler Button */
    .custom-toggler {
        border: none;
        background: rgba(237, 233, 230, 0.2);
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .custom-toggler:hover {
        background: rgba(237, 233, 230, 0.3);
    }
    
    .custom-toggler .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(237, 233, 230, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }
    
    /* Dropdown menu for mobile */
    @media (max-width: 991px) {
        .modern-navbar .navbar-collapse {
            background: linear-gradient(135deg, #004b9c 0%, #003366 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modern-navbar .nav-link {
            padding: 12px 16px;
            margin: 4px 0;
        }
        
        .brand-text {
            font-size: 1.2rem;
        }
        
        .brand-icon {
            width: 35px;
            height: 35px;
        }
        
        .brand-icon i {
            font-size: 18px;
        }
    }
    
    /* Scroll effect */
    .modern-navbar.scrolled {
        padding: 8px 0;
        background: linear-gradient(135deg, #004b9c 0%, #003366 100%);
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.2);
    }
</style>

<script>
    // Function to update cart count
    function updateCartCount() {
        if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    if (cartBadge) {
                        if (data.count > 0) {
                            cartBadge.textContent = data.count;
                            cartBadge.style.display = 'inline-block';
                        } else {
                            cartBadge.textContent = '0';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }
    
    // Update cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
        
        // Add active class to current page link
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.modern-navbar .nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'index.php')) {
                link.classList.add('active');
            }
        });
    });
    
    // Add scroll effect to navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.modern-navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>