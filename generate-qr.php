<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    header('Location: orders.php');
    exit();
}

// Generate unique payment code
$payment_code = 'QRIS-' . $order['order_number'] . '-' . time();

// Simpan ke session untuk verifikasi
$_SESSION['payment_code'] = $payment_code;
$_SESSION['payment_order_id'] = $order_id;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QRIS - Pembayaran H-Sport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .payment-container {
            max-width: 500px;
            width: 100%;
        }
        .payment-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
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
        .qris-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 2px dashed #004b9c;
        }
        #qrcode {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        #qrcode img, #qrcode canvas {
            width: 200px;
            height: 200px;
        }
        .payment-status {
            text-align: center;
            margin-top: 20px;
        }
        .btn-check-payment {
            background: linear-gradient(135deg, #004b9c 0%, #3a302c 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
        }
        .btn-check-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(92, 79, 74, 0.4);
        }
        .success-animation {
            animation: pulse 0.5s ease;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="text-center mb-4">
                <div class="qris-icon mb-3">
                    <i class="fas fa-qrcode fa-4x" style="color: #004b9c;"></i>
                </div>
                <h3>Pembayaran QRIS</h3>
                <p class="text-muted">Scan QR Code untuk melakukan pembayaran</p>
            </div>
            
            <div class="qris-container">
                <div id="qrcode"></div>
                <div class="payment-info mt-3">
                    <p><strong>Nomor Pesanan:</strong> <?php echo $order['order_number']; ?></p>
                    <p><strong>Total Pembayaran:</strong> <span class="fw-bold text-success">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span></p>
                    <p><strong>Kode Pembayaran:</strong> <code><?php echo $payment_code; ?></code></p>
                </div>
            </div>
            
            <div class="payment-status mt-4">
                <h5>Instruksi Pembayaran:</h5>
                <ol class="text-start text-muted">
                    <li>Buka aplikasi QRIS (Gopay, OVO, Dana, atau Mobile Banking)</li>
                    <li>Pilih menu Scan QR Code</li>
                    <li>Scan QR Code di atas</li>
                    <li>Konfirmasi pembayaran</li>
                    <li>Upload bukti pembayaran di bawah ini</li>
                </ol>

                <!-- Upload Bukti Bayar -->
                <div class="border rounded p-3 mb-3 text-start bg-light">
                    <h6 class="text-dark mb-2"><i class="fas fa-upload me-2 text-primary"></i>Upload Bukti Pembayaran</h6>
                    <form id="uploadProofForm" enctype="multipart/form-data">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <div class="mb-2">
                            <input type="file" name="payment_proof" id="proofFile" class="form-control form-control-sm" accept="image/*" required>
                            <small class="text-muted">Format: JPG/PNG/WEBP, maks. 2MB</small>
                        </div>
                        <div id="previewContainer" class="mb-2 d-none">
                            <img id="previewImg" src="" style="max-height:150px;border-radius:8px;object-fit:contain" class="img-thumbnail">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100" id="uploadBtn">
                            <i class="fas fa-paper-plane me-1"></i> Kirim Bukti & Konfirmasi
                        </button>
                    </form>
                    <div id="uploadResult" class="mt-2"></div>
                </div>
                
                <button class="btn btn-check-payment w-100 mt-2" onclick="checkPayment()" id="checkPaymentBtn">
                    <i class="fas fa-search"></i> Cek Status Pembayaran
                </button>
                
                <button class="btn btn-secondary w-100 mt-2" onclick="window.location.href='orders.php'">
                    <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
                </button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate QR Code
        const qrData = JSON.stringify({
            order_id: <?php echo $order['id']; ?>,
            order_number: '<?php echo $order['order_number']; ?>',
            amount: <?php echo $order['total_amount']; ?>,
            payment_code: '<?php echo $payment_code; ?>',
            merchant: 'H-Sport Jersey',
            timestamp: '<?php echo date('Y-m-d H:i:s'); ?>'
        });
        
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 200,
            height: 200,
            colorDark: "#004b9c",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Function to check payment
        let checkInterval = null;
        let isProcessing = false;
        
        function checkPayment() {
            if (isProcessing) return;
            
            const btn = document.getElementById('checkPaymentBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Memeriksa...';
            btn.disabled = true;
            isProcessing = true;
            
            fetch('verify-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: <?php echo $order['id']; ?>,
                    payment_code: '<?php echo $payment_code; ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success animation
                    const container = document.querySelector('.payment-card');
                    container.classList.add('success-animation');
                    
                    // Update status
                    document.querySelector('.payment-status').innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                            <h5>Pembayaran Berhasil!</h5>
                            <p>${data.message}</p>
                            <p>Status pesanan Anda akan segera diupdate menjadi <strong>Sedang Dikirim</strong></p>
                            <a href="orders.php" class="btn btn-success mt-2">
                                <i class="fas fa-truck"></i> Lihat Status Pesanan
                            </a>
                        </div>
                    `;
                    
                    // Stop checking
                    if (checkInterval) clearInterval(checkInterval);
                    
                    // Redirect after 3 seconds
                    setTimeout(() => {
                        window.location.href = 'orders.php?payment_success=1';
                    }, 3000);
                } else {
                    alert(data.message || 'Pembayaran belum terdeteksi. Silahkan scan QR Code dan lakukan pembayaran terlebih dahulu.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    isProcessing = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silahkan coba lagi.');
                btn.innerHTML = originalText;
                btn.disabled = false;
                isProcessing = false;
            });
        }
        
        // Auto check every 5 seconds
        checkInterval = setInterval(() => {
            if (!isProcessing) {
                checkPayment();
            }
        }, 5000);
        
        // Stop checking when page unloads
        window.addEventListener('beforeunload', () => {
            if (checkInterval) clearInterval(checkInterval);
        });

        // Preview bukti bayar
        document.getElementById('proofFile').addEventListener('change', function() {
            const file = this.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('previewContainer').classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });

        // Upload bukti bayar
        document.getElementById('uploadProofForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('uploadBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengupload...';
            const formData = new FormData(this);
            try {
                const res = await fetch('upload-payment-proof.php', { method:'POST', body: formData });
                const data = await res.json();
                const resultDiv = document.getElementById('uploadResult');
                if(data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success py-1"><i class="fas fa-check me-1"></i>' + data.message + '</div>';
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Bukti Terkirim';
                    setTimeout(() => window.location.href = 'orders.php', 2000);
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger py-1">' + data.message + '</div>';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Kirim Bukti & Konfirmasi';
                }
            } catch(err) {
                document.getElementById('uploadResult').innerHTML = '<div class="alert alert-danger py-1">Gagal upload. Coba lagi.</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Kirim Bukti & Konfirmasi';
            }
        });
    </script>
</body>
</html>