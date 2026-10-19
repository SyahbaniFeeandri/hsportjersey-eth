<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    $confirm_password = md5($_POST['confirm_password']);
    
    // Validasi
    if($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } elseif(strlen($_POST['password']) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek email sudah terdaftar
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Cek username sudah terdaftar
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if($stmt->fetch()) {
                $error = 'Username sudah terdaftar!';
            } else {
                // Insert user baru
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
                if($stmt->execute([$username, $email, $password])) {
                    $success = 'Registrasi berhasil! Silahkan login.';
                    echo '<meta http-equiv="refresh" content="2;url=login.php">';
                } else {
                    $error = 'Registrasi gagal, silahkan coba lagi!';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - H-Sport Jersey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #004b9c;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: #5C766D;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .logo-icon i {
            font-size: 40px;
            color: white;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #5C766D 0%, #5C766D 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            font-size: 16px;
            transition: transform 0.2s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="logo-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            
            <h3 class="text-center mb-2" style="color: #333;">Daftar Akun Baru</h3>
            <p class="text-center text-muted mb-4">Bergabunglah dengan H-Sport Jersey</p>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user text-muted"></i>
                        </span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope text-muted"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="contoh@email.com" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required onkeyup="checkPasswordStrength()">
                    </div>
                    <div id="passwordStrength" class="password-strength"></div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-check-circle text-muted"></i>
                        </span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Syarat dan Ketentuan</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-register text-white w-100">
                    <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
                </button>
            </form>
            
            <div class="login-link">
                <p class="mb-0">Sudah punya akun? <a href="login.php">Login Sekarang</a></p>
            </div>
        </div>
    </div>
    
    <!-- Modal Terms -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Syarat dan Ketentuan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Pendaftaran Akun</h6>
                    <p>Anda harus mendaftar dengan data yang benar dan akurat.</p>
                    
                    <h6>2. Keamanan Akun</h6>
                    <p>Anda bertanggung jawab atas keamanan password Anda.</p>
                    
                    <h6>3. Pemesanan</h6>
                    <p>Pemesanan akan diproses setelah pembayaran dikonfirmasi.</p>
                    
                    <h6>4. Pembatalan</h6>
                    <p>Pembatalan pesanan dapat dilakukan maksimal 1x24 jam.</p>
                    
                    <h6>5. Privasi</h6>
                    <p>Data Anda akan kami jaga kerahasiaannya.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Saya Mengerti</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordStrength() {
            var password = document.getElementById('password').value;
            var strengthDiv = document.getElementById('passwordStrength');
            
            if(password.length == 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            var strength = 0;
            if(password.length >= 6) strength++;
            if(password.length >= 8) strength++;
            if(password.match(/[a-z]+/)) strength++;
            if(password.match(/[A-Z]+/)) strength++;
            if(password.match(/[0-9]+/)) strength++;
            if(password.match(/[$@#&!]+/)) strength++;
            
            if(strength <= 2) {
                strengthDiv.innerHTML = '<span class="strength-weak"><i class="fas fa-exclamation-circle"></i> Password lemah</span>';
            } else if(strength <= 4) {
                strengthDiv.innerHTML = '<span class="strength-medium"><i class="fas fa-chart-line"></i> Password sedang</span>';
            } else {
                strengthDiv.innerHTML = '<span class="strength-strong"><i class="fas fa-check-circle"></i> Password kuat</span>';
            }
        }
    </script>
</body>
</html>