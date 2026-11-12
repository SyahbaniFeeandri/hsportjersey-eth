<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$order_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    redirect('orders.php');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Color mapping for display
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

// --- Data untuk UI pembatalan on-chain (W4-IHS-04) ---
$contract_address = file_exists('blockchain/config/contract_address.txt')
    ? trim(file_get_contents('blockchain/config/contract_address.txt'))
    : '';
$contract_abi = file_exists('blockchain/config/contract_abi.json')
    ? trim(file_get_contents('blockchain/config/contract_abi.json'))
    : '[]';
$eth_rate_idr = 10000000;

$is_crypto_order = (($order['payment_type'] ?? '') === 'crypto');
$has_chain_order = !empty($order['blockchain_order_id']) || !empty($order['external_order_hash']);
$is_final_order = in_array($order['status'], ['cancelled', 'completed'], true);
$show_cancel_panel = $is_crypto_order && $has_chain_order && !$is_final_order && $order['status'] !== 'shipped';

$cancel_event = null;
if ($order['status'] === 'cancelled') {
    try {
        $stmtCancel = $pdo->prepare("SELECT * FROM blockchain_transactions
                                     WHERE order_id = ? AND event_name = 'OrderCancelled'
                                     ORDER BY id DESC LIMIT 1");
        $stmtCancel->execute([$order_id]);
        $cancel_event = $stmtCancel->fetch() ?: null;
    } catch (Exception $e) {
        $cancel_event = null;
    }
}
$cancel_reason = '';
if ($cancel_event && !empty($cancel_event['payload'])) {
    $payload = json_decode((string)$cancel_event['payload'], true);
    $cancel_reason = is_array($payload) ? (string)($payload['reason'] ?? '') : '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - H-Sport Jersey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .color-preview {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .btn-payment {
            background-color: #004b9c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-payment:hover {
            background-color: #003366;
            transform: translateY(-2px);
        }
        .cancel-card {
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff7f7;
        }
        .cancel-card .reason-counter {
            font-size: 12px;
        }
        .cancel-card .reason-counter.over-limit {
            color: #dc3545;
            font-weight: 600;
        }
        .proof-box {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            word-break: break-all;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-receipt me-2" style="color: #004b9c;"></i>
                Detail Pesanan
            </h2>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="info-box">
                    <h5 class="mb-3">
                        <i class="fas fa-box me-2" style="color: #004b9c;"></i>
                        Produk yang Dipesan
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Detail</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-tag"></i> Ukuran: <strong><?php echo $item['size']; ?></strong>
                                        </small>
                                        <?php if($item['color']): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-palette"></i> Warna: 
                                            <span class="color-preview" style="background-color: <?php 
                                                $color = $item['color'];
                                                echo isset($colorMap[$color]) ? $colorMap[$color] : '#CCCCCC'; 
                                            ?>;"></span>
                                            <strong><?php echo htmlspecialchars($item['color']); ?></strong>
                                        </small>
                                        <?php endif; ?>
                                        <?php if($item['custom_text']): ?>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-text-height"></i> Teks: <strong><?php echo htmlspecialchars($item['custom_text']); ?></strong>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['quantity']; ?> pcs</td>
                                    <td class="text-success fw-bold">
                                        Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Keseluruhan:</td>
                                    <td class="fw-bold">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="info-box">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle me-2" style="color: #004b9c;"></i>
                        Informasi Pesanan
                    </h5>
                    <hr>
                    <p><strong>No. Pesanan:</strong><br> <?php echo $order['order_number']; ?></p>
                    <p><strong>Tanggal Pemesanan:</strong><br> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status Pesanan:</strong><br>
                        <span class="status-badge bg-<?php 
                            echo $order['status'] == 'pending' ? 'warning' : 
                                ($order['status'] == 'paid' ? 'info' : 
                                ($order['status'] == 'production' ? 'primary' : 
                                ($order['status'] == 'shipped' ? 'success' : 'secondary'))); 
                        ?> text-white">
                            <?php 
                            $status_map = [
                                'pending' => 'Menunggu Pembayaran',
                                'paid' => 'Dibayar',
                                'production' => 'Dalam Produksi',
                                'shipped' => 'Dikirim',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan'
                            ];
                            echo $status_map[$order['status']];
                            ?>
                        </span>
                    </p>
                    <p><strong>Status Pembayaran:</strong><br>
                        <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'danger'; ?>">
                            <?php echo $order['payment_status'] == 'paid' ? '✅ Lunas' : '❌ Belum Dibayar'; ?>
                        </span>
                    </p>
                    <?php if (!empty($order['tx_hash']) || !empty($order['blockchain_order_id'])): ?>
                    <hr>
                    <a href="blockchain-proof.php?order_id=<?php echo (int)$order['id']; ?>"
                       class="btn btn-outline-primary w-100 fw-semibold">
                        <i class="fas fa-link me-2"></i>Lihat Bukti Blockchain
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="info-box">
                    <h5 class="mb-3">
                        <i class="fas fa-truck me-2" style="color: #004b9c;"></i>
                        Informasi Pengiriman
                    </h5>
                    <hr>
                    <p><strong>Metode Kirim:</strong><br> <?php echo $order['shipping_method']; ?></p>
                    <p><strong>Alamat Pengiriman:</strong><br> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <?php if($order['notes']): ?>
                    <p><strong>Catatan:</strong><br> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if($order['payment_status'] == 'unpaid' && $order['status'] == 'pending'): ?>
                    <?php if(($order['payment_type'] ?? '') === 'crypto'): ?>
                    <div class="info-box" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: white; border: 1px solid rgba(129, 140, 248, 0.3);">
                        <h5 class="mb-3">
                            <i class="fab fa-ethereum me-2 text-warning"></i>
                            Pembayaran Crypto (ETH)
                        </h5>
                        <hr style="background-color: rgba(255,255,255,0.2);">
                        <p class="mb-1"><small class="text-light">Metode: <strong>Ethereum via MetaMask (Smart Contract)</strong></small></p>
                        <p><strong>Total Pembayaran:</strong><br> 
                            <span style="font-size: 1.3rem; font-weight: bold; color: #38bdf8;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                            <br><small class="text-warning">≈ <?php echo number_format($order['total_amount'] / 10000000, 4); ?> ETH</small>
                        </p>
                        <a href="crypto-payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-warning w-100 fw-bold py-2">
                            <i class="fab fa-ethereum me-2"></i> Lanjutkan Pembayaran ETH
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="info-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="mb-3">
                            <i class="fas fa-credit-card me-2"></i>
                            Informasi Pembayaran
                        </h5>
                        <hr style="background-color: rgba(255,255,255,0.3);">
                        <p><strong>Total Pembayaran:</strong><br> 
                            <span style="font-size: 1.3rem; font-weight: bold;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                        </p>
                        <a href="generate-qr.php?order_id=<?php echo $order['id']; ?>" class="btn btn-light w-100 fw-bold mb-2 text-primary">
                            <i class="fas fa-qrcode me-2"></i> Bayar dengan QRIS
                        </a>
                        <p class="small text-white-50 mb-0"><i class="fas fa-info-circle me-1"></i> Scan QRIS untuk verifikasi instan.</p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if($order['status'] === 'cancelled'): ?>
                <div class="info-box">
                    <h5 class="mb-3">
                        <i class="fas fa-ban me-2" style="color: #dc3545;"></i>
                        Bukti Pembatalan
                    </h5>
                    <hr>
                    <?php if($cancel_event): ?>
                        <p class="mb-2"><strong>Alasan:</strong><br>
                            <?php echo htmlspecialchars($cancel_reason !== '' ? $cancel_reason : '-'); ?>
                        </p>
                        <p class="mb-2"><strong>Oleh wallet:</strong><br>
                            <code><?php echo htmlspecialchars($cancel_event['wallet_address'] ?? '-'); ?></code>
                        </p>
                        <p class="mb-2"><strong>Block:</strong><br>
                            <?php echo $cancel_event['block_number'] !== null ? (int)$cancel_event['block_number'] : '-'; ?>
                        </p>
                        <p class="mb-0"><strong>Tx hash:</strong></p>
                        <div class="proof-box mt-1"><?php echo htmlspecialchars($cancel_event['tx_hash']); ?></div>
                    <?php else: ?>
                        <p class="text-muted mb-0 small">
                            Order berstatus dibatalkan, namun bukti tx on-chain belum tercatat di database.
                        </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if($show_cancel_panel): ?>
                <div class="cancel-card">
                    <h5 class="mb-2">
                        <i class="fas fa-ban me-2" style="color: #dc3545;"></i>
                        Batalkan Order
                    </h5>
                    <p class="small text-muted mb-2">
                        Pembatalan dilakukan on-chain melalui <code>cancelOrder</code>.
                        Escrow yang sudah terisi otomatis dikembalikan ke pembeli.
                    </p>
                    <p class="small mb-3" id="cancelWalletStatus">
                        <i class="fas fa-wallet me-1"></i> Wallet belum diperiksa.
                    </p>
                    <button type="button" class="btn btn-outline-danger w-100 fw-bold" onclick="openCancelModal()">
                        <i class="fas fa-ban me-2"></i> Batalkan Order
                    </button>
                    <p class="small text-muted mt-2 mb-0">
                        Hanya pembeli (state Created/AwaitingPayment) atau operator (Paid/InProduction/ReadyToShip)
                        yang diizinkan. Order Shipped/Completed/Cancelled tidak dapat dibatalkan.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if($show_cancel_panel): ?>
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ban text-danger me-2"></i>Batalkan Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        Order akan dibatalkan permanen di smart contract. Tindakan ini tidak bisa dibatalkan.
                    </div>

                    <div class="mb-3">
                        <label for="cancelReason" class="form-label fw-semibold">Alasan pembatalan</label>
                        <textarea class="form-control" id="cancelReason" rows="3" maxlength="64"
                                  placeholder="Contoh: salah ukuran / berubah pikiran"></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Maksimal 64 byte (UTF-8).</small>
                            <small id="reasonCounter" class="reason-counter text-muted">0 / 64 byte</small>
                        </div>
                    </div>

                    <div class="small" id="chainStateBox">
                        <span class="text-muted">Status on-chain: belum dibaca.</span>
                    </div>

                    <div class="mt-3 d-none" id="cancelResultBox">
                        <div class="alert alert-success small mb-2" id="cancelResultMessage"></div>
                        <div class="proof-box" id="cancelResultTx"></div>
                    </div>

                    <div class="alert alert-danger small mt-3 d-none" id="cancelErrorBox"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger fw-bold" id="cancelSubmitBtn" onclick="submitCancel()">
                        <i class="fas fa-paper-plane me-1"></i> Kirim Pembatalan
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if($show_cancel_panel): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/web3/4.2.2/web3.min.js"></script>
    <script>
        // Data dari PHP (W4-IHS-04 — UI cancel on-chain)
        const cancelOrderId = <?php echo (int)$order['id']; ?>;
        const cancelChainOrderId = <?php echo (int)($order['blockchain_order_id'] ?? 0); ?>;
        const cancelExternalHash = '<?php echo addslashes((string)($order['external_order_hash'] ?? '')); ?>';
        const cancelContractAddress = '<?php echo addslashes($contract_address); ?>';
        const cancelContractABI = <?php echo !empty($contract_abi) ? $contract_abi : '[]'; ?>;
        const targetChainIdHex = '0x539';
        const targetChainIdDec = 1337;
        const statusLabels = {
            0: 'Created', 1: 'AwaitingPayment', 2: 'Paid', 3: 'InProduction',
            4: 'ReadyToShip', 5: 'Shipped', 6: 'Completed', 7: 'Cancelled'
        };

        let cancelWeb3 = null;
        let cancelContract = null;
        let cancelAccount = null;
        let cancelModalInstance = null;
        let cancelAllowed = false;

        function reasonByteLength(value) {
            return new TextEncoder().encode(value).length;
        }

        function updateReasonCounter() {
            const input = document.getElementById('cancelReason');
            const counter = document.getElementById('reasonCounter');
            if (!input || !counter) return;
            const bytes = reasonByteLength(input.value);
            counter.textContent = bytes + ' / 64 byte';
            counter.classList.toggle('over-limit', bytes > 64);
        }

        function setWalletStatus(html, cls) {
            const el = document.getElementById('cancelWalletStatus');
            if (!el) return;
            el.innerHTML = '<i class="fas fa-wallet me-1"></i> ' + html;
            el.className = 'small mb-3 ' + (cls || 'text-muted');
        }

        function showCancelError(message) {
            const box = document.getElementById('cancelErrorBox');
            if (!box) return;
            box.textContent = message;
            box.classList.remove('d-none');
        }

        function hideCancelError() {
            const box = document.getElementById('cancelErrorBox');
            if (box) box.classList.add('d-none');
        }

        async function ensureWallet() {
            if (typeof window.ethereum === 'undefined') {
                throw new Error('MetaMask belum terpasang. Install MetaMask lalu muat ulang halaman.');
            }

            cancelWeb3 = new Web3(window.ethereum);
            let accounts = await window.ethereum.request({ method: 'eth_accounts' });
            if (!accounts || accounts.length === 0) {
                accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            }
            if (!accounts || accounts.length === 0) {
                throw new Error('Tidak ada akun MetaMask yang terhubung.');
            }

            cancelAccount = accounts[0];
            const chainId = await window.ethereum.request({ method: 'eth_chainId' });
            const isGanache = (chainId === targetChainIdHex || parseInt(chainId, 16) === targetChainIdDec);

            if (!isGanache) {
                setWalletStatus('Beralih ke jaringan Ganache (1337)...', 'text-warning');
                try {
                    await window.ethereum.request({
                        method: 'wallet_switchEthereumChain',
                        params: [{ chainId: targetChainIdHex }]
                    });
                } catch (switchError) {
                    if (switchError.code === 4902 || String(switchError.message || '').includes('4902')) {
                        await window.ethereum.request({
                            method: 'wallet_addEthereumChain',
                            params: [{
                                chainId: targetChainIdHex,
                                chainName: 'Ganache Local Testnet',
                                nativeCurrency: { name: 'Ethereum', symbol: 'ETH', decimals: 18 },
                                rpcUrls: ['http://127.0.0.1:7545']
                            }]
                        });
                    } else {
                        throw new Error('Jaringan MetaMask bukan Ganache (1337). Silakan ganti jaringan manual.');
                    }
                }
            }

            cancelContract = new cancelWeb3.eth.Contract(cancelContractABI, cancelContractAddress);
            setWalletStatus('Terhubung: <code>' + cancelAccount.substring(0, 8) + '...' + cancelAccount.slice(-4) + '</code>', 'text-success');
            return cancelAccount;
        }

        async function resolveChainOrderId() {
            if (cancelChainOrderId > 0) return cancelChainOrderId;
            if (!cancelExternalHash) throw new Error('Order ini belum memiliki ID blockchain.');
            const found = await cancelContract.methods.orderIdByHash(cancelExternalHash).call();
            const id = Number(found);
            if (!id) throw new Error('Order tidak ditemukan di smart contract.');
            return id;
        }

        async function readCancelContext() {
            const chainId = await resolveChainOrderId();
            const onChain = await cancelContract.methods.getOrder(chainId).call();
            const status = Number(onChain.status !== undefined ? onChain.status : onChain[4]);
            const buyer = String(onChain.buyer !== undefined ? onChain.buyer : onChain[2]).toLowerCase();
            const owner = String(await cancelContract.methods.owner().call()).toLowerCase();
            const account = cancelAccount.toLowerCase();

            let isOperator = account === owner;
            if (!isOperator) {
                try {
                    isOperator = await cancelContract.methods.operators(cancelAccount).call();
                } catch (e) {
                    isOperator = false;
                }
            }

            let allowed = false;
            let message = '';
            if (status === 0 || status === 1) {
                allowed = account === buyer || isOperator;
                message = allowed ? 'Anda berwenang membatalkan order ini.' : 'Hanya pembeli atau operator yang dapat membatalkan pada state ini.';
            } else if (status === 2 || status === 3 || status === 4) {
                allowed = isOperator;
                message = allowed ? 'Anda operator; pembatalan akan me-refund escrow.' : 'Pembatalan pada state ini hanya dapat dilakukan operator.';
            } else if (status === 7) {
                message = 'Order sudah dibatalkan on-chain.';
            } else if (status === 5) {
                message = 'Order sudah dikirim (Shipped) dan tidak dapat dibatalkan.';
            } else if (status === 6) {
                message = 'Order sudah selesai (Completed) dan tidak dapat dibatalkan.';
            } else {
                message = 'State order tidak dikenal.';
            }

            return { chainId, status, buyer, allowed, message, isOperator };
        }

        async function refreshChainState() {
            const box = document.getElementById('chainStateBox');
            const submitBtn = document.getElementById('cancelSubmitBtn');
            if (!box) return;

            try {
                const ctx = await readCancelContext();
                cancelAllowed = ctx.allowed;
                const badgeColor = ctx.allowed ? 'success' : 'danger';
                box.innerHTML = '<div class="alert alert-' + badgeColor + ' small mb-0">' +
                    '<strong>Status on-chain:</strong> ' + statusLabels[ctx.status] + ' (' + ctx.status + ')<br>' +
                    ctx.message + '</div>';
                if (submitBtn) submitBtn.disabled = !ctx.allowed;
            } catch (err) {
                cancelAllowed = false;
                box.innerHTML = '<div class="alert alert-warning small mb-0">Gagal membaca state on-chain: ' +
                    (err.message || err) + '</div>';
                if (submitBtn) submitBtn.disabled = true;
            }
        }

        async function openCancelModal() {
            hideCancelError();
            const resultBox = document.getElementById('cancelResultBox');
            if (resultBox) resultBox.classList.add('d-none');
            const modalEl = document.getElementById('cancelModal');
            cancelModalInstance = new bootstrap.Modal(modalEl);
            cancelModalInstance.show();

            updateReasonCounter();
            setWalletStatus('Menghubungkan MetaMask...', 'text-muted');
            try {
                await ensureWallet();
                await refreshChainState();
            } catch (err) {
                setWalletStatus('Wallet belum siap.', 'text-danger');
                showCancelError(err.message || String(err));
            }
        }

        async function submitCancel() {
            hideCancelError();
            const input = document.getElementById('cancelReason');
            const submitBtn = document.getElementById('cancelSubmitBtn');
            const resultBox = document.getElementById('cancelResultBox');
            const resultMessage = document.getElementById('cancelResultMessage');
            const resultTx = document.getElementById('cancelResultTx');
            const reason = (input ? input.value : '').trim();

            if (!reason) {
                showCancelError('Alasan pembatalan wajib diisi.');
                return;
            }
            if (reasonByteLength(reason) > 64) {
                showCancelError('Alasan melebihi 64 byte UTF-8. Ringkas alasan Anda.');
                return;
            }

            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menunggu MetaMask...';
                }

                await ensureWallet();
                const ctx = await readCancelContext();
                if (!ctx.allowed) {
                    throw new Error(ctx.message);
                }

                const receipt = await cancelContract.methods.cancelOrder(ctx.chainId, reason)
                    .send({ from: cancelAccount, gas: 250000 });

                const txHash = receipt.transactionHash;
                let refundEth = null;
                if (receipt.events && receipt.events.EscrowRefunded) {
                    const amountWei = receipt.events.EscrowRefunded.returnValues.amount;
                    if (amountWei) refundEth = cancelWeb3.utils.fromWei(String(amountWei), 'ether');
                }

                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan bukti...';
                }

                const response = await fetch('api/blockchain-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'cancel_order',
                        order_id: cancelOrderId,
                        tx_hash: txHash,
                        wallet_address: cancelAccount,
                        blockchain_order_id: ctx.chainId,
                        reason: reason
                    })
                });
                const result = await response.json();

                if (result.success) {
                    if (resultBox) resultBox.classList.remove('d-none');
                    if (resultMessage) {
                        resultMessage.innerHTML = '<i class="fas fa-check-circle me-1"></i> Pembatalan tercatat. ' +
                            (refundEth !== null
                                ? 'Escrow <strong>' + refundEth + ' ETH</strong> dikembalikan ke pembeli. '
                                : '') +
                            'Status order menjadi <strong>Cancelled</strong>.';
                    }
                    if (resultTx) resultTx.textContent = txHash;
                    if (input) input.disabled = true;

                    setTimeout(() => window.location.reload(), 3000);
                } else {
                    throw new Error('Transaksi on-chain berhasil, tetapi sinkronisasi DB gagal: ' +
                        (result.message || 'unknown error'));
                }
            } catch (err) {
                let message = err.message || String(err);
                if (err.code === 4001 || message.includes('User denied')) {
                    message = 'Transaksi dibatalkan di MetaMask.';
                }
                showCancelError(message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Kirim Pembatalan';
                }
                // Refresh state karena transaksi bisa saja sudah mined.
                try { await refreshChainState(); } catch (e) {}
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('cancelReason');
            if (input) input.addEventListener('input', updateReasonCounter);

            if (typeof window.ethereum !== 'undefined') {
                window.ethereum.on('accountsChanged', function() {
                    if (cancelModalInstance) {
                        ensureWallet().then(refreshChainState).catch(err => showCancelError(err.message));
                    }
                });
                window.ethereum.on('chainChanged', function() {
                    if (cancelModalInstance) {
                        ensureWallet().then(refreshChainState).catch(err => showCancelError(err.message));
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>