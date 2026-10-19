<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    redirect('orders.php');
}

// Pastikan tipe pembayaran tercatat sebagai crypto
if (($order['payment_type'] ?? '') !== 'crypto') {
    $pdo->prepare("UPDATE orders SET payment_type = 'crypto' WHERE id = ?")->execute([$order_id]);
    $order['payment_type'] = 'crypto';
}

// Ambil item pesanan beserta detail produk
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order_id]);
$order_items = $stmtItems->fetchAll();

// Kontrak Smart Contract
$contract_address = file_exists('blockchain/config/contract_address.txt')
    ? trim(file_get_contents('blockchain/config/contract_address.txt'))
    : '0xcc53Bc2F3Acd9EBbc293f512f4cd82B58E884A75';

$contract_abi = file_exists('blockchain/config/contract_abi.json')
    ? trim(file_get_contents('blockchain/config/contract_abi.json'))
    : '[]';

// Rate konversi simulasi: 1 ETH = Rp 10.000.000
$eth_rate_idr = 10000000;
$total_eth = $order['total_amount'] / $eth_rate_idr;
$is_paid = ($order['payment_status'] === 'paid');

// Color map untuk preview warna
$colorMap = [
    'Merah' => '#FF0000',
    'Biru' => '#0000FF',
    'Hijau' => '#00FF00',
    'Kuning' => '#FFFF00',
    'Hitam' => '#000000',
    'Putih' => '#FFFFFF',
    'Abu-abu' => '#808080',
    'Orange' => '#FFA500',
    'Ungu' => '#800080',
    'Pink' => '#FF69B4'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Crypto (ETH) - Pesanan #<?php echo htmlspecialchars($order['order_number']); ?> - H-Sport Jersey</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Canvas Confetti -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        :root {
            --crypto-bg: #0b0f19;
            --crypto-surface: rgba(17, 24, 39, 0.75);
            --crypto-surface-hover: rgba(31, 41, 55, 0.85);
            --crypto-border: rgba(255, 255, 255, 0.08);
            --crypto-border-glow: rgba(98, 126, 234, 0.35);
            --eth-blue: #627EEA;
            --eth-purple: #8A2BE2;
            --eth-cyan: #00F2FE;
            --eth-green: #10B981;
            --eth-amber: #F59E0B;
            --eth-red: #EF4444;
            --text-primary: #F3F4F6;
            --text-secondary: #9CA3AF;
            --text-muted: #6B7280;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--crypto-bg);
            background-image: 
                radial-gradient(at 10% 15%, rgba(98, 126, 234, 0.18) 0px, transparent 45%),
                radial-gradient(at 90% 25%, rgba(138, 43, 226, 0.14) 0px, transparent 40%),
                radial-gradient(at 50% 85%, rgba(0, 242, 254, 0.12) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--text-primary);
        }

        /* Glassmorphic Card */
        .glass-card {
            background: var(--crypto-surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--crypto-border);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            border-color: rgba(255, 255, 255, 0.14);
        }

        /* Hero Header */
        .crypto-hero {
            padding: 30px 0 10px;
        }

        .web3-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            background: rgba(98, 126, 234, 0.15);
            border: 1px solid rgba(98, 126, 234, 0.35);
            color: #93c5fd;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--eth-green);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseGreen 2s infinite;
        }

        @keyframes pulseGreen {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .pulse-dot.pulse-amber {
            background-color: var(--eth-amber);
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
            animation: pulseAmber 2s infinite;
        }

        @keyframes pulseAmber {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }

        /* Stepper Progress Bar */
        .stepper-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin-bottom: 25px;
            padding: 0 10px;
        }

        .stepper-progress-bg {
            position: absolute;
            top: 20px;
            left: 40px;
            right: 40px;
            height: 3px;
            background: rgba(255, 255, 255, 0.08);
            z-index: 1;
        }

        .stepper-progress-fill {
            height: 100%;
            width: 33%;
            background: linear-gradient(90deg, var(--eth-blue), var(--eth-cyan));
            transition: width 0.4s ease;
        }

        .step-item {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-bubble {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        .step-item.active .step-bubble {
            background: linear-gradient(135deg, var(--eth-blue), var(--eth-purple));
            border-color: #93c5fd;
            color: white;
            box-shadow: 0 0 18px rgba(98, 126, 234, 0.5);
            transform: scale(1.08);
        }

        .step-item.completed .step-bubble {
            background: var(--eth-green);
            border-color: #34d399;
            color: white;
        }

        .step-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.3s;
        }

        .step-item.active .step-label,
        .step-item.completed .step-label {
            color: var(--text-primary);
        }

        /* Wallet Box */
        .wallet-card {
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .wallet-card.connected {
            border-color: rgba(16, 185, 129, 0.35);
            background: rgba(16, 185, 129, 0.04);
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.08);
        }

        .wallet-card.wrong-network {
            border-color: rgba(245, 158, 11, 0.4);
            background: rgba(245, 158, 11, 0.05);
        }

        /* Mono typography */
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Copy Button */
        .btn-copy {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-secondary);
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .btn-copy:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateY(-1px);
        }

        /* Escrow Shield Card */
        .escrow-banner {
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(98, 126, 234, 0.1) 0%, rgba(138, 43, 226, 0.06) 100%);
            border: 1px solid rgba(98, 126, 234, 0.25);
            padding: 16px 20px;
        }

        /* Price Conversion Box */
        .price-badge-box {
            background: linear-gradient(135deg, rgba(22, 30, 49, 0.9) 0%, rgba(30, 41, 59, 0.7) 100%);
            border: 1px solid rgba(98, 126, 234, 0.3);
            border-radius: 16px;
            padding: 22px;
            position: relative;
            overflow: hidden;
        }

        .price-badge-box::after {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(98, 126, 234, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        .eth-amount-display {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 30%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        /* Action Buttons */
        .btn-web3-connect {
            background: linear-gradient(135deg, #f6851b 0%, #e2761b 100%);
            border: none;
            color: white;
            font-weight: 700;
            padding: 14px 24px;
            border-radius: 14px;
            font-size: 1.05rem;
            box-shadow: 0 8px 24px rgba(246, 133, 27, 0.35);
            transition: all 0.3s ease;
        }

        .btn-web3-connect:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(246, 133, 27, 0.5);
            color: white;
        }

        .btn-web3-pay {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            border: none;
            color: white;
            font-weight: 800;
            padding: 16px 28px;
            border-radius: 14px;
            font-size: 1.15rem;
            letter-spacing: 0.3px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.35);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-web3-pay:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.5);
            color: white;
        }

        .btn-web3-pay:disabled {
            background: rgba(255, 255, 255, 0.12);
            color: var(--text-muted);
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        .btn-web3-switch {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            border: none;
            color: white;
            font-weight: 700;
            padding: 14px 24px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.35);
            transition: all 0.3s ease;
        }

        .btn-web3-switch:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.5);
            color: white;
        }

        /* Order items list */
        .item-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-thumb {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            object-fit: cover;
            flex-shrink: 0;
        }

        .color-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, 0.3);
            vertical-align: middle;
            margin-right: 4px;
        }

        /* Toast container */
        .toast-floating {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 10050;
            max-width: 420px;
            width: calc(100% - 48px);
            pointer-events: none;
        }

        .toast-box {
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(16px);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            color: white;
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            animation: slideDownToast 0.35s ease;
            transition: all 0.3s ease;
        }

        @keyframes slideDownToast {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .toast-box.success { border-color: rgba(16, 185, 129, 0.5); }
        .toast-box.warning { border-color: rgba(245, 158, 11, 0.5); }
        .toast-box.error { border-color: rgba(239, 68, 68, 0.5); }
        .toast-box.info { border-color: rgba(98, 126, 234, 0.5); }

        /* Modal Progress */
        .modal-web3 .modal-content {
            background: rgba(15, 23, 42, 0.96);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            color: white;
        }

        .tx-step-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .tx-step-item.active {
            background: rgba(98, 126, 234, 0.15);
            border: 1px solid rgba(98, 126, 234, 0.4);
        }

        .tx-step-item.done {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
        }

        /* Success screen card */
        .success-hero-card {
            text-align: center;
            padding: 50px 30px;
        }

        .success-icon-badge {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.4));
            border: 2px solid var(--eth-green);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
            color: #34d399;
            box-shadow: 0 0 35px rgba(16, 185, 129, 0.4);
            animation: bounceIn 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.1); opacity: 0.9; }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Accordion technical */
        .accordion-tech .accordion-button {
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-secondary);
            font-size: 0.88rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            box-shadow: none;
        }

        .accordion-tech .accordion-button:not(.collapsed) {
            background: rgba(255, 255, 255, 0.06);
            color: white;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .accordion-tech .accordion-body {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-top: none;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Toast Notifications -->
    <div id="toastContainer" class="toast-floating"></div>

    <div class="container crypto-hero mb-5">
        <!-- Top Breadcrumbs / Back navigation -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <a href="orders.php" class="text-decoration-none text-secondary d-inline-flex align-items-center gap-2 mb-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Pesanan Saya</span>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <h2 class="fw-bold mb-0 text-white">
                        <i class="fab fa-ethereum me-2" style="color: var(--eth-blue);"></i>
                        Web3 Payment Gateway
                    </h2>
                    <span class="web3-pill">
                        <span class="pulse-dot" id="headerDot"></span>
                        <span id="headerNetworkLabel">Ganache Testnet</span>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary small">Smart Contract Escrow v1.0</span>
                <span class="badge bg-secondary bg-opacity-25 text-light font-mono px-3 py-2">
                    Order #<?php echo htmlspecialchars($order['order_number']); ?>
                </span>
            </div>
        </div>

        <?php if($is_paid): ?>
            <!-- ================= STATE: SUDAH LUNAS / PAID ================= -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="glass-card success-hero-card">
                        <div class="success-icon-badge">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2">Pembayaran Crypto Berhasil Dikonfirmasi!</h3>
                        <p class="text-secondary mb-4">
                            Transaksi Anda telah diverifikasi di blockchain Ganache dan dana diamankan di Smart Contract Escrow.
                        </p>

                        <div class="wallet-card text-start mb-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Nomor Pesanan</small>
                                    <strong class="font-mono text-white"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block">Total Terbayar</small>
                                    <strong class="text-success font-mono">
                                        Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?> 
                                        (<?php echo number_format($total_eth, 4); ?> ETH)
                                    </strong>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Transaction Hash (TX Hash)</small>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <code class="font-mono text-info flex-grow-1 text-break bg-dark bg-opacity-50 p-2 rounded">
                                            <?php echo htmlspecialchars($order['tx_hash'] ?: 'Tercatat di Blockchain'); ?>
                                        </code>
                                        <?php if(!empty($order['tx_hash'])): ?>
                                        <button class="btn btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($order['tx_hash']); ?>', 'TX Hash disalin!')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if(!empty($order['wallet_address'])): ?>
                                <div class="col-12">
                                    <small class="text-muted d-block">Wallet Pengirim</small>
                                    <code class="font-mono text-light bg-dark bg-opacity-50 p-2 rounded d-block mt-1">
                                        <?php echo htmlspecialchars($order['wallet_address']); ?>
                                    </code>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="orders.php" class="btn btn-primary px-4 py-3 fw-bold rounded-3">
                                <i class="fas fa-truck me-2"></i> Lacak Pengiriman di Pesanan Saya
                            </a>
                            <a href="index.php" class="btn btn-outline-light px-4 py-3 fw-bold rounded-3">
                                <i class="fas fa-home me-2"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- ================= STATE: UNPAID / PAYMENT TERMINAL ================= -->

            <!-- Stepper Progress Tracker -->
            <div class="stepper-container">
                <div class="stepper-progress-bg">
                    <div class="stepper-progress-fill" id="stepperFill"></div>
                </div>
                <div class="step-item active" id="step1">
                    <div class="step-bubble"><i class="fas fa-wallet"></i></div>
                    <div class="step-label">1. Dompet Web3</div>
                </div>
                <div class="step-item" id="step2">
                    <div class="step-bubble"><i class="fas fa-network-wired"></i></div>
                    <div class="step-label">2. Jaringan Ganache</div>
                </div>
                <div class="step-item" id="step3">
                    <div class="step-bubble"><i class="fas fa-signature"></i></div>
                    <div class="step-label">3. Konfirmasi & Bayar</div>
                </div>
                <div class="step-item" id="step4">
                    <div class="step-bubble"><i class="fas fa-check-circle"></i></div>
                    <div class="step-label">4. Escrow Aktif</div>
                </div>
            </div>

            <div class="row g-4">
                <!-- LEFT COLUMN: Web3 Terminal & Controls -->
                <div class="col-lg-7">
                    <div class="glass-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-white">
                                <i class="fas fa-plug me-2 text-primary"></i>Status Dompet Web3
                            </h5>
                            <span id="walletBadge" class="badge bg-secondary bg-opacity-50 px-3 py-2">
                                <i class="fas fa-circle-notch fa-spin me-1"></i> Mendeteksi...
                            </span>
                        </div>

                        <!-- MetaMask Not Installed Alert -->
                        <div id="noMetaMaskBox" class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-white d-none mb-3">
                            <div class="d-flex align-items-start gap-3">
                                <i class="fas fa-exclamation-triangle fs-3 text-danger mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">MetaMask Tidak Terdeteksi!</h6>
                                    <p class="small text-secondary mb-2">
                                        Peramban Anda belum memiliki ekstensi MetaMask. Pasang ekstensi terlebih dahulu untuk melanjutkan pembayaran terdesentralisasi.
                                    </p>
                                    <a href="https://metamask.io/download/" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-danger fw-bold">
                                        <i class="fas fa-download me-1"></i> Pasang MetaMask Extension
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Wallet Card (Connected or Disconnected) -->
                        <div id="walletCard" class="wallet-card mb-4">
                            <!-- Disconnected View -->
                            <div id="walletDisconnectedView" class="text-center py-3">
                                <div class="mb-3">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/36/MetaMask_Fox.svg" alt="MetaMask" style="width: 54px; height: 54px;">
                                </div>
                                <h6 class="fw-bold text-white mb-1">Hubungkan Dompet MetaMask Anda</h6>
                                <p class="text-secondary small mb-3">
                                    Akses akun pengujian Ganache Anda yang berisi saldo ETH testnet untuk melakukan pembayaran.
                                </p>
                                <button type="button" class="btn btn-web3-connect w-100" onclick="connectWallet()">
                                    <i class="fab fa-ethereum me-2"></i> Hubungkan MetaMask Sekarang
                                </button>
                            </div>

                            <!-- Connected View -->
                            <div id="walletConnectedView" class="d-none">
                                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #627EEA, #9b51e0); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                            🦊
                                        </div>
                                        <div>
                                            <span class="text-muted small d-block">Akun Terhubung</span>
                                            <strong class="font-mono text-white" id="displayAddressShort">0x000...0000</strong>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-copy" onclick="copyConnectedAddress()" title="Salin Address">
                                            <i class="fas fa-copy me-1"></i> Salin
                                        </button>
                                        <button class="btn btn-copy" onclick="connectWallet()" title="Ganti Akun">
                                            <i class="fas fa-sync-alt me-1"></i> Ganti
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-2 pt-2 border-top border-white border-opacity-10">
                                    <div class="col-sm-6">
                                        <div class="p-2 rounded bg-dark bg-opacity-40">
                                            <span class="text-muted small d-block">Saldo ETH Dompet</span>
                                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                                <span class="font-mono fs-5 fw-bold text-white" id="displayBalance">0.0000</span>
                                                <span class="small text-info fw-semibold">ETH</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-2 rounded bg-dark bg-opacity-40">
                                            <span class="text-muted small d-block">Status Saldo</span>
                                            <div class="mt-1" id="balanceStatusBadge">
                                                <span class="badge bg-success bg-opacity-25 text-success">
                                                    <i class="fas fa-check me-1"></i> Cukup
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Network Switcher Alert (if wrong chain) -->
                        <div id="wrongNetworkBox" class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 text-white d-none mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <i class="fas fa-exclamation-circle fs-3 text-warning mt-1"></i>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1 text-warning">Jaringan Belum Sesuai!</h6>
                                    <p class="small text-secondary mb-2">
                                        MetaMask terdeteksi di Chain ID: <strong id="detectedChainId" class="font-mono">...</strong>.
                                        Kontrak H-Sport berjalan di <strong>Ganache Local Testnet (Chain ID 1337 / 0x539)</strong>.
                                    </p>
                                    <button type="button" class="btn btn-web3-switch btn-sm w-100" onclick="switchOrAddGanache()">
                                        <i class="fas fa-random me-2"></i> Pindah ke Jaringan Ganache (1337)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Escrow Security Assurance Card -->
                        <div class="escrow-banner mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <div style="font-size: 26px; color: #60a5fa;" class="mt-1">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-white mb-1">Proteksi Escrow Smart Contract Aktif</h6>
                                    <p class="text-secondary small mb-0">
                                        Dana pembayaran Anda tidak langsung masuk ke rekening penjual, melainkan 
                                        <strong>ditahan secara aman di Smart Contract</strong> sampai Anda mengonfirmasi penerimaan barang di dashboard pesanan Anda.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Main Action Button -->
                        <div class="action-zone">
                            <button id="payBtn" type="button" class="btn btn-web3-pay w-100 py-3" onclick="payWithETH()" disabled>
                                <span id="payBtnIcon"><i class="fab fa-ethereum me-2"></i></span>
                                <span id="payBtnText">1. Hubungkan MetaMask Terlebih Dahulu</span>
                            </button>
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-gas-pump me-1"></i> Estimasi Gas Limit: ~300.000 Gwei (Ditanggung Saldo Testnet)
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Technical & Smart Contract Accordion -->
                    <div class="accordion accordion-tech" id="techAccordion">
                        <div class="accordion-item glass-card border-0 mb-3">
                            <h2 class="accordion-header" id="headingTech">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTech">
                                    <i class="fas fa-code-branch me-2 text-info"></i> Detail Teknis Smart Contract & Transparansi
                                </button>
                            </h2>
                            <div id="collapseTech" class="accordion-collapse collapse" data-bs-parent="#techAccordion">
                                <div class="accordion-body">
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Contract Address (HSportStore.sol):</span>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <code class="font-mono text-info flex-grow-1 text-break bg-dark bg-opacity-75 p-2 rounded" id="contractAddr">
                                                <?php echo htmlspecialchars($contract_address); ?>
                                            </code>
                                            <button class="btn btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($contract_address); ?>', 'Contract address disalin!')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-2 text-secondary">
                                        <div class="col-6">
                                            <small class="d-block text-muted">Target Chain ID</small>
                                            <span class="font-mono text-light">1337 (0x539)</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="d-block text-muted">RPC Endpoint</small>
                                            <span class="font-mono text-light">http://127.0.0.1:7545</span>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <small class="d-block text-muted">Metode Eksekusi</small>
                                            <code class="font-mono text-warning">HSportStore.createOrder(string _orderNumber, uint256 _amount)</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Order Summary & Currency Breakdown -->
                <div class="col-lg-5">
                    <!-- Price Card -->
                    <div class="glass-card price-badge-box mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small fw-bold text-uppercase">Total Tagihan Pesanan</span>
                            <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25 px-2 py-1">
                                Kurs Tetap Dev
                            </span>
                        </div>

                        <div class="eth-amount-display mb-1">
                            <i class="fab fa-ethereum text-info"></i>
                            <span class="font-mono" id="summaryEthAmount"><?php echo number_format($total_eth, 4); ?></span>
                            <span class="fs-4 text-secondary font-mono">ETH</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-white border-opacity-10">
                            <div>
                                <span class="text-muted small">Ekuivalen Rupiah:</span>
                                <h5 class="text-success fw-bold mb-0">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></h5>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block">Kurs Acuan:</span>
                                <span class="small text-secondary font-mono">1 ETH ≈ Rp 10.000.000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Items & Delivery Card -->
                    <div class="glass-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-white">
                                <i class="fas fa-tshirt me-2 text-info"></i>Daftar Jersey yang Dipesan
                            </h6>
                            <span class="badge bg-secondary bg-opacity-25 text-light">
                                <?php echo count($order_items); ?> Produk
                            </span>
                        </div>

                        <div class="order-items-scroll mb-4" style="max-height: 280px; overflow-y: auto; padding-right: 5px;">
                            <?php if(!empty($order_items)): ?>
                                <?php foreach($order_items as $item): ?>
                                    <div class="item-row">
                                        <?php if(!empty($item['image']) && file_exists('uploads/' . $item['image'])): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Product" class="item-thumb">
                                        <?php else: ?>
                                            <div class="item-thumb d-flex align-items-center justify-content-center text-secondary">
                                                <i class="fas fa-tshirt fs-4"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-bold text-white text-truncate"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="small text-secondary d-flex flex-wrap gap-2 mt-1">
                                                <span class="badge bg-dark border border-secondary border-opacity-25">Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                                <?php if(!empty($item['color'])): ?>
                                                    <span class="badge bg-dark border border-secondary border-opacity-25">
                                                        <span class="color-dot" style="background-color: <?php echo $colorMap[$item['color']] ?? '#aaa'; ?>"></span>
                                                        <?php echo htmlspecialchars($item['color']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if(!empty($item['custom_text'])): ?>
                                                    <span class="badge bg-primary bg-opacity-25 text-info">
                                                        "<?php echo htmlspecialchars($item['custom_text']); ?>"
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <div class="text-white small fw-bold font-mono">
                                                Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                                            </div>
                                            <small class="text-muted"><?php echo $item['quantity']; ?> pcs</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small">Detail produk pesanan tidak ditemukan.</p>
                            <?php endif; ?>
                        </div>

                        <hr style="border-color: rgba(255,255,255,0.08);">

                        <!-- Delivery destination -->
                        <div class="mb-3">
                            <span class="text-muted small d-block mb-1">
                                <i class="fas fa-map-marker-alt me-1 text-danger"></i> Tujuan Pengiriman (<?php echo htmlspecialchars($order['shipping_method']); ?>):
                            </span>
                            <div class="p-2 rounded bg-dark bg-opacity-40 small text-secondary">
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </div>
                        </div>

                        <div class="text-center pt-2">
                            <a href="orders.php" class="btn btn-outline-secondary btn-sm text-light w-100">
                                <i class="fas fa-arrow-left me-1"></i> Batal & Kembali ke Pesanan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Progress Modal -->
            <div class="modal fade modal-web3" id="txModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content p-4">
                        <div class="modal-body text-center">
                            <div id="txModalIcon" class="mb-3">
                                <div class="spinner-border text-info" style="width: 3.5rem; height: 3.5rem;" role="status"></div>
                            </div>
                            <h4 id="txModalTitle" class="fw-bold mb-2">Memproses Transaksi...</h4>
                            <p id="txModalDesc" class="text-secondary small mb-4">
                                Silakan konfirmasi transaksi di jendela pop-up MetaMask Anda.
                            </p>

                            <div class="text-start">
                                <div id="txStep1" class="tx-step-item active">
                                    <i class="fas fa-spinner fa-spin text-info" id="iconStep1"></i>
                                    <div>
                                        <strong class="d-block text-white small">1. Otorisasi Dompet</strong>
                                        <small class="text-muted">Menunggu tanda tangan transaksi di MetaMask</small>
                                    </div>
                                </div>
                                <div id="txStep2" class="tx-step-item">
                                    <i class="far fa-circle text-muted" id="iconStep2"></i>
                                    <div>
                                        <strong class="d-block text-white small">2. Penambangan Blok Ganache</strong>
                                        <small class="text-muted">Mengirim transaksi ke smart contract escrow</small>
                                    </div>
                                </div>
                                <div id="txStep3" class="tx-step-item">
                                    <i class="far fa-circle text-muted" id="iconStep3"></i>
                                    <div>
                                        <strong class="d-block text-white small">3. Verifikasi & Simpan Status</strong>
                                        <small class="text-muted">Sinkronisasi status lunas dengan server H-Sport</small>
                                    </div>
                                </div>
                            </div>

                            <div id="txHashBox" class="d-none mt-3 p-2 rounded bg-dark text-start">
                                <small class="text-muted d-block">TX Hash Terkonfirmasi:</small>
                                <code id="txHashText" class="font-mono text-info small text-break"></code>
                            </div>

                            <div id="txModalActions" class="mt-4 d-none">
                                <button type="button" class="btn btn-primary w-100 fw-bold" onclick="redirectToOrders()">
                                    <i class="fas fa-check-circle me-1"></i> Buka Pesanan Saya
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Web3 & Logic Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js"></script>

    <script>
        // Data dari PHP
        const orderId = <?php echo (int)$order_id; ?>;
        const orderNumber = '<?php echo addslashes($order['order_number']); ?>';
        const totalAmountIdr = <?php echo (float)$order['total_amount']; ?>;
        const contractAddress = '<?php echo trim($contract_address); ?>';
        const contractABI = <?php echo !empty($contract_abi) ? $contract_abi : '[]'; ?>;
        const isPaid = <?php echo $is_paid ? 'true' : 'false'; ?>;

        // Kurs acuan: 1 ETH = 10,000,000 IDR
        const ethRate = 10000000;
        const calculatedEth = (totalAmountIdr / ethRate);

        // Target Chain ID: Ganache (1337 / 0x539)
        const TARGET_CHAIN_ID_HEX = '0x539';
        const TARGET_CHAIN_ID_DEC = 1337;

        let web3 = null;
        let contract = null;
        let userAccount = null;
        let userBalanceEth = 0;
        let txModalInstance = null;

        // Floating Toast
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = 'toast-box ' + type;

            let icon = 'info-circle text-info';
            if (type === 'success') icon = 'check-circle text-success';
            if (type === 'warning') icon = 'exclamation-circle text-warning';
            if (type === 'error') icon = 'times-circle text-danger';

            toast.innerHTML = `
                <i class="fas fa-${icon} fs-5 mt-1"></i>
                <div class="flex-grow-1 small">${message}</div>
                <button type="button" class="btn-close btn-close-white btn-sm" onclick="this.parentElement.remove()"></button>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 300);
            }, 4500);
        }

        // Salin ke Clipboard
        function copyToClipboard(text, successMsg = 'Teks disalin!') {
            if (!navigator.clipboard) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showToast(successMsg, 'success');
                return;
            }
            navigator.clipboard.writeText(text).then(() => {
                showToast(successMsg, 'success');
            }).catch(err => {
                showToast('Gagal menyalin: ' + err.message, 'error');
            });
        }

        function copyConnectedAddress() {
            if (userAccount) {
                copyToClipboard(userAccount, 'Alamat wallet disalin: ' + userAccount.substring(0, 10) + '...');
            }
        }

        // Update Stepper Progress
        function updateStepper(step) {
            const fill = document.getElementById('stepperFill');
            const s1 = document.getElementById('step1');
            const s2 = document.getElementById('step2');
            const s3 = document.getElementById('step3');
            const s4 = document.getElementById('step4');

            if (!fill || !s1 || !s2 || !s3 || !s4) return;

            [s1, s2, s3, s4].forEach(el => {
                el.classList.remove('active', 'completed');
            });

            if (step === 1) {
                fill.style.width = '10%';
                s1.classList.add('active');
            } else if (step === 2) {
                fill.style.width = '40%';
                s1.classList.add('completed');
                s2.classList.add('active');
            } else if (step === 3) {
                fill.style.width = '70%';
                s1.classList.add('completed');
                s2.classList.add('completed');
                s3.classList.add('active');
            } else if (step === 4) {
                fill.style.width = '100%';
                s1.classList.add('completed');
                s2.classList.add('completed');
                s3.classList.add('completed');
                s4.classList.add('active');
            }
        }

        // Format short address
        function formatShortAddress(addr) {
            if (!addr) return '';
            return addr.substring(0, 6) + '...' + addr.substring(addr.length - 4);
        }

        // Check MetaMask Installation & State
        async function checkWalletConnection(silent = false) {
            if (isPaid) return;

            const badge = document.getElementById('walletBadge');
            const noMetaMaskBox = document.getElementById('noMetaMaskBox');
            const walletDisconnectedView = document.getElementById('walletDisconnectedView');
            const walletConnectedView = document.getElementById('walletConnectedView');
            const walletCard = document.getElementById('walletCard');
            const payBtn = document.getElementById('payBtn');
            const payBtnText = document.getElementById('payBtnText');
            const wrongNetworkBox = document.getElementById('wrongNetworkBox');
            const headerDot = document.getElementById('headerDot');
            const headerNetworkLabel = document.getElementById('headerNetworkLabel');

            if (typeof window.ethereum === 'undefined') {
                if (badge) {
                    badge.className = 'badge bg-danger bg-opacity-25 text-danger px-3 py-2';
                    badge.innerHTML = '<i class="fas fa-times-circle me-1"></i> Tidak Terpasang';
                }
                if (noMetaMaskBox) noMetaMaskBox.classList.remove('d-none');
                if (payBtn) {
                    payBtn.disabled = true;
                    payBtnText.textContent = 'MetaMask Tidak Terpasang';
                }
                updateStepper(1);
                return;
            }

            if (noMetaMaskBox) noMetaMaskBox.classList.add('d-none');

            try {
                web3 = new Web3(window.ethereum);
                const accounts = await window.ethereum.request({ method: 'eth_accounts' });

                if (!accounts || accounts.length === 0) {
                    // Not connected yet
                    if (badge) {
                        badge.className = 'badge bg-warning bg-opacity-25 text-warning px-3 py-2';
                        badge.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Belum Terhubung';
                    }
                    if (walletDisconnectedView) walletDisconnectedView.classList.remove('d-none');
                    if (walletConnectedView) walletConnectedView.classList.add('d-none');
                    if (walletCard) {
                        walletCard.classList.remove('connected', 'wrong-network');
                    }
                    if (wrongNetworkBox) wrongNetworkBox.classList.add('d-none');
                    if (payBtn) {
                        payBtn.disabled = true;
                        payBtnText.textContent = '1. Hubungkan MetaMask Terlebih Dahulu';
                    }
                    updateStepper(1);
                    return;
                }

                // Connected
                userAccount = accounts[0];

                // Check Chain ID
                const chainId = await window.ethereum.request({ method: 'eth_chainId' });
                const isGanache = (chainId === TARGET_CHAIN_ID_HEX || parseInt(chainId, 16) === TARGET_CHAIN_ID_DEC);

                if (!isGanache) {
                    if (badge) {
                        badge.className = 'badge bg-warning bg-opacity-25 text-warning px-3 py-2';
                        badge.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Jaringan Salah';
                    }
                    if (wrongNetworkBox) {
                        wrongNetworkBox.classList.remove('d-none');
                        const detectedEl = document.getElementById('detectedChainId');
                        if (detectedEl) detectedEl.textContent = chainId + ' (' + parseInt(chainId, 16) + ')';
                    }
                    if (headerDot) headerDot.className = 'pulse-dot pulse-amber';
                    if (headerNetworkLabel) headerNetworkLabel.textContent = 'Jaringan Salah (' + chainId + ')';
                    if (walletCard) {
                        walletCard.classList.add('wrong-network');
                        walletCard.classList.remove('connected');
                    }
                    if (payBtn) {
                        payBtn.disabled = true;
                        payBtnText.textContent = '2. Beralih ke Jaringan Ganache (1337)';
                    }
                    updateStepper(2);
                } else {
                    if (badge) {
                        badge.className = 'badge bg-success bg-opacity-25 text-success px-3 py-2';
                        badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> Terhubung ke Ganache';
                    }
                    if (wrongNetworkBox) wrongNetworkBox.classList.add('d-none');
                    if (headerDot) headerDot.className = 'pulse-dot';
                    if (headerNetworkLabel) headerNetworkLabel.textContent = 'Ganache Testnet (1337)';
                    if (walletCard) {
                        walletCard.classList.add('connected');
                        walletCard.classList.remove('wrong-network');
                    }
                    updateStepper(3);
                }

                // Fetch Balance
                const balanceWei = await web3.eth.getBalance(userAccount);
                userBalanceEth = parseFloat(web3.utils.fromWei(balanceWei, 'ether'));

                // Render Connected View
                if (walletDisconnectedView) walletDisconnectedView.classList.add('d-none');
                if (walletConnectedView) walletConnectedView.classList.remove('d-none');

                const displayAddressShort = document.getElementById('displayAddressShort');
                if (displayAddressShort) displayAddressShort.textContent = formatShortAddress(userAccount);

                const displayBalance = document.getElementById('displayBalance');
                if (displayBalance) displayBalance.textContent = userBalanceEth.toFixed(4);

                const balanceBadge = document.getElementById('balanceStatusBadge');
                if (userBalanceEth < calculatedEth) {
                    if (balanceBadge) {
                        balanceBadge.innerHTML = `
                            <span class="badge bg-danger bg-opacity-25 text-danger">
                                <i class="fas fa-times me-1"></i> Kurang (Butuh ${calculatedEth.toFixed(4)} ETH)
                            </span>
                        `;
                    }
                    if (payBtn && isGanache) {
                        payBtn.disabled = true;
                        payBtnText.textContent = 'Saldo ETH Tidak Mencukupi';
                    }
                } else {
                    if (balanceBadge) {
                        balanceBadge.innerHTML = `
                            <span class="badge bg-success bg-opacity-25 text-success">
                                <i class="fas fa-check me-1"></i> Saldo Cukup
                            </span>
                        `;
                    }
                    if (payBtn && isGanache) {
                        payBtn.disabled = false;
                        payBtnText.innerHTML = `Bayar ${calculatedEth.toFixed(4)} ETH Sekarang`;
                    }
                }

                // Init Smart Contract object
                contract = new web3.eth.Contract(contractABI, contractAddress);

            } catch (err) {
                console.error("Wallet check error:", err);
                if (!silent) showToast("Pemeriksaan dompet gagal: " + err.message, "error");
            }
        }

        // Connect Wallet Action
        async function connectWallet() {
            if (typeof window.ethereum === 'undefined') {
                showToast('MetaMask belum terinstall! Silakan install di https://metamask.io', 'error');
                return;
            }

            try {
                showToast('Membuka permintaan otorisasi MetaMask...', 'info');
                await window.ethereum.request({ method: 'eth_requestAccounts' });
                await checkWalletConnection();
                showToast('MetaMask berhasil dihubungkan!', 'success');
            } catch (err) {
                console.error("Connect error:", err);
                showToast('Gagal menghubungkan: ' + err.message, 'error');
            }
        }

        // Switch or Add Ganache Network
        async function switchOrAddGanache() {
            if (typeof window.ethereum === 'undefined') return;

            showToast('Meminta peralihan jaringan ke Ganache (Chain ID 1337)...', 'info');
            try {
                await window.ethereum.request({
                    method: 'wallet_switchEthereumChain',
                    params: [{ chainId: TARGET_CHAIN_ID_HEX }]
                });
                await checkWalletConnection();
                showToast('Berhasil beralih ke Ganache Local Testnet!', 'success');
            } catch (switchError) {
                // Error 4902: Chain has not been added yet
                if (switchError.code === 4902 || switchError.message.includes('4902')) {
                    try {
                        showToast('Menambahkan konfigurasi jaringan Ganache ke MetaMask...', 'info');
                        await window.ethereum.request({
                            method: 'wallet_addEthereumChain',
                            params: [{
                                chainId: TARGET_CHAIN_ID_HEX,
                                chainName: 'Ganache Local Testnet',
                                nativeCurrency: {
                                    name: 'Ethereum',
                                    symbol: 'ETH',
                                    decimals: 18
                                },
                                rpcUrls: ['http://127.0.0.1:7545']
                            }]
                        });
                        await checkWalletConnection();
                        showToast('Jaringan Ganache berhasil ditambahkan dan diaktifkan!', 'success');
                    } catch (addError) {
                        showToast('Gagal menambahkan jaringan Ganache: ' + addError.message, 'error');
                    }
                } else {
                    showToast('Gagal beralih jaringan: ' + switchError.message, 'error');
                }
            }
        }

        // Process Crypto Payment
        async function payWithETH() {
            if (!userAccount) {
                connectWallet();
                return;
            }

            // Validasi jaringan
            const chainId = await window.ethereum.request({ method: 'eth_chainId' });
            if (chainId !== TARGET_CHAIN_ID_HEX && parseInt(chainId, 16) !== TARGET_CHAIN_ID_DEC) {
                showToast('Harap beralih ke jaringan Ganache terlebih dahulu!', 'warning');
                switchOrAddGanache();
                return;
            }

            // Validasi saldo
            if (userBalanceEth < calculatedEth) {
                showToast('Saldo ETH Anda tidak mencukupi untuk pesanan ini!', 'error');
                return;
            }

            // Buka Modal Progress
            const modalEl = document.getElementById('txModal');
            txModalInstance = new bootstrap.Modal(modalEl);
            txModalInstance.show();

            const title = document.getElementById('txModalTitle');
            const desc = document.getElementById('txModalDesc');
            const step1 = document.getElementById('txStep1');
            const step2 = document.getElementById('txStep2');
            const step3 = document.getElementById('txStep3');
            const iconStep1 = document.getElementById('iconStep1');
            const iconStep2 = document.getElementById('iconStep2');
            const iconStep3 = document.getElementById('iconStep3');
            const txHashBox = document.getElementById('txHashBox');
            const txHashText = document.getElementById('txHashText');
            const txModalActions = document.getElementById('txModalActions');
            const modalIcon = document.getElementById('txModalIcon');

            // Reset modal state
            title.textContent = 'Menunggu Persetujuan MetaMask...';
            desc.textContent = 'Periksa ekstensi MetaMask Anda dan klik tombol Konfirmasi (Confirm).';
            step1.className = 'tx-step-item active';
            step2.className = 'tx-step-item';
            step3.className = 'tx-step-item';
            iconStep1.className = 'fas fa-spinner fa-spin text-info';
            iconStep2.className = 'far fa-circle text-muted';
            iconStep3.className = 'far fa-circle text-muted';
            txHashBox.classList.add('d-none');
            txModalActions.classList.add('d-none');

            try {
                // Hitung amount wei
                const amountWei = web3.utils.toWei(calculatedEth.toFixed(18), 'ether');

                // Encode createOrder data
                const encodedData = web3.eth.abi.encodeFunctionCall({
                    name: 'createOrder',
                    type: 'function',
                    inputs: [
                        { type: 'string', name: '_orderNumber' },
                        { type: 'uint256', name: '_amount' }
                    ]
                }, [orderNumber, amountWei]);

                // Kirim transaksi ke Ganache
                const tx = await web3.eth.sendTransaction({
                    from: userAccount,
                    to: contractAddress,
                    value: amountWei,
                    data: encodedData,
                    gas: 300000
                });

                // Step 1 Selesai -> Step 2
                step1.className = 'tx-step-item done';
                iconStep1.className = 'fas fa-check-circle text-success';
                step2.className = 'tx-step-item active';
                iconStep2.className = 'fas fa-spinner fa-spin text-info';
                title.textContent = 'Transaksi Diterima di Blockchain!';
                desc.textContent = 'Memverifikasi blok transaksi dan mengunci dana di Escrow...';

                // Tampilkan TX Hash
                txHashBox.classList.remove('d-none');
                txHashText.textContent = tx.transactionHash;

                // Step 2 Selesai -> Step 3: Sinkronisasi ke PHP Backend
                step2.className = 'tx-step-item done';
                iconStep2.className = 'fas fa-check-circle text-success';
                step3.className = 'tx-step-item active';
                iconStep3.className = 'fas fa-spinner fa-spin text-info';
                title.textContent = 'Menyimpan Status Pesanan...';
                desc.textContent = 'Mengonfirmasi pembayaran dengan basis data H-Sport Jersey...';

                const response = await fetch('api/blockchain-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_crypto_order',
                        order_id: orderId,
                        tx_hash: tx.transactionHash,
                        wallet_address: userAccount,
                        amount: totalAmountIdr
                    })
                });

                const result = await response.json();

                if (result.success) {
                    step3.className = 'tx-step-item done';
                    iconStep3.className = 'fas fa-check-circle text-success';
                    title.textContent = 'Pembayaran Berhasil! 🎉';
                    desc.textContent = 'Dana Anda telah diamankan di Smart Contract. Pesanan masuk antrean produksi.';

                    modalIcon.innerHTML = `
                        <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(16, 185, 129, 0.2); border: 2px solid #10B981; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; color: #34d399;">
                            <i class="fas fa-check"></i>
                        </div>
                    `;

                    updateStepper(4);
                    txModalActions.classList.remove('d-none');

                    // Trigger Confetti Celebration!
                    if (typeof confetti === 'function') {
                        confetti({
                            particleCount: 100,
                            spread: 70,
                            origin: { y: 0.6 }
                        });
                    }

                    // Auto redirect setelah 3.5 detik
                    setTimeout(() => {
                        window.location.href = 'orders.php?payment_success=1';
                    }, 3500);

                } else {
                    title.textContent = 'Peringatan Sinkronisasi';
                    desc.textContent = 'Transaksi blockchain berhasil, namun sinkronisasi server memerlukan verifikasi manual: ' + (result.message || '');
                    txModalActions.classList.remove('d-none');
                }

            } catch (error) {
                console.error("Payment error:", error);
                title.textContent = 'Transaksi Dibatalkan / Gagal';
                desc.textContent = error.message || 'Terjadi kesalahan saat memproses transaksi di MetaMask.';
                modalIcon.innerHTML = `
                    <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(239, 68, 68, 0.2); border: 2px solid #EF4444; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; color: #F87171;">
                        <i class="fas fa-times"></i>
                    </div>
                `;
                txModalActions.innerHTML = `
                    <button type="button" class="btn btn-outline-light w-100 fw-bold" data-bs-dismiss="modal">
                        Tutup & Coba Lagi
                    </button>
                `;
                txModalActions.classList.remove('d-none');
            }
        }

        function redirectToOrders() {
            window.location.href = 'orders.php';
        }

        // Listener Event MetaMask
        if (typeof window.ethereum !== 'undefined') {
            window.ethereum.on('accountsChanged', function(accounts) {
                console.log("MetaMask account changed:", accounts);
                checkWalletConnection(true);
            });

            window.ethereum.on('chainChanged', function(chainId) {
                console.log("MetaMask chain changed:", chainId);
                checkWalletConnection(true);
            });
        }

        // Auto check saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            if (!isPaid) {
                checkWalletConnection(true);
            }
        });
    </script>
</body>
</html>