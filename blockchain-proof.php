<?php
/**
 * Blockchain Proof Page — Halaman bukti & audit on-chain untuk pesanan.
 * Issue: W5-IHS-06
 * Pemilik: Ihsanul
 *
 * Menampilkan bukti kriptografis yang tersimpan di DB (tx hash, block,
 * wallet, contract, external hash), riwayat event di `blockchain_transactions`,
 * serta state on-chain terkini (bila RPC tersedia).
 */

require_once 'config/database.php';
require_once 'config/functions.php';
require_once __DIR__ . '/api/services/BlockchainClient.php';
require_once __DIR__ . '/api/services/ContractService.php';
require_once __DIR__ . '/api/services/ProofRepository.php';

use HSport\Services\BlockchainClient;
use HSport\Services\ContractService;
use HSport\Services\ProofRepository;

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = (int)$_SESSION['user_id'];
$isAdmin = isAdmin();
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$order = null;
$transactions = [];
$chainState = null;
$chainError = null;

if ($orderId > 0) {
    if ($isAdmin) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$orderId, $userId]);
    }
    $order = $stmt->fetch() ?: null;

    if (!$order) {
        $_SESSION['message'] = 'Pesanan tidak ditemukan atau bukan milik Anda.';
        $_SESSION['message_type'] = 'danger';
        redirect('orders.php');
    }

    $txStmt = $pdo->prepare('SELECT * FROM blockchain_transactions WHERE order_id = ? ORDER BY id ASC');
    $txStmt->execute([$orderId]);
    $transactions = $txStmt->fetchAll();

    if (!empty($order['blockchain_order_id'])) {
        try {
            $contract = new ContractService(new BlockchainClient());
            $chainState = $contract->getOrder((int)$order['blockchain_order_id']);
        } catch (Throwable $e) {
            $chainError = $e->getMessage();
        }
    }
} else {
    // Daftar order kripto milik pengguna (atau semua untuk admin).
    $sql = "SELECT id, order_number, status, verification_status, tx_hash, blockchain_order_id, created_at
            FROM orders
            WHERE payment_type = 'crypto'
              AND (tx_hash IS NOT NULL OR blockchain_order_id IS NOT NULL)";
    $params = [];
    if (!$isAdmin) {
        $sql .= ' AND user_id = ?';
        $params[] = $userId;
    }
    $sql .= ' ORDER BY id DESC LIMIT 50';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cryptoOrders = $stmt->fetchAll();
}

$verificationBadge = [
    'VERIFIED' => ['bg-success', 'Terverifikasi'],
    'MISMATCH' => ['bg-danger', 'Mismatch'],
    'PENDING' => ['bg-warning text-dark', 'Menunggu Verifikasi'],
];
$statusLabels = [
    0 => 'Created', 1 => 'AwaitingPayment', 2 => 'Paid', 3 => 'InProduction',
    4 => 'ReadyToShip', 5 => 'Shipped', 6 => 'Completed', 7 => 'Cancelled',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Blockchain - H-Sport Jersey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .proof-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            border-radius: 14px;
            padding: 24px;
        }
        .proof-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            word-break: break-all;
        }
        .proof-box {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            word-break: break-all;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
        }
        .timeline-item {
            border-left: 3px solid #004b9c;
            padding: 8px 0 8px 16px;
            margin-left: 6px;
        }
    </style>
</head>
<body class="bg-light">
<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
    <?php if ($orderId === 0): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1"><i class="fas fa-link me-2" style="color:#004b9c;"></i>Bukti Blockchain</h3>
                <p class="text-muted mb-0">Pilih pesanan kripto untuk melihat bukti transaksi on-chain.</p>
            </div>
            <a href="orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Pesanan</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($cryptoOrders)): ?>
                    <div class="p-4 text-muted">
                        Belum ada pesanan kripto dengan bukti on-chain.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Status</th>
                                    <th>Verifikasi</th>
                                    <th>On-chain ID</th>
                                    <th>Tx Hash</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($cryptoOrders as $row): ?>
                                <?php $badge = $verificationBadge[$row['verification_status']] ?? ['bg-secondary', $row['verification_status']]; ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['order_number']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td><span class="badge <?php echo $badge[0]; ?>"><?php echo htmlspecialchars($badge[1]); ?></span></td>
                                    <td><?php echo $row['blockchain_order_id'] !== null ? '#' . (int)$row['blockchain_order_id'] : '-'; ?></td>
                                    <td class="proof-mono"><?php echo $row['tx_hash'] ? htmlspecialchars(substr($row['tx_hash'], 0, 18)) . '…' : '-'; ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-primary" href="blockchain-proof.php?order_id=<?php echo (int)$row['id']; ?>">
                                            Lihat Bukti
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php
        $badge = $verificationBadge[$order['verification_status']] ?? ['bg-secondary', $order['verification_status']];
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1"><i class="fas fa-link me-2" style="color:#004b9c;"></i>Bukti Blockchain</h3>
                <p class="text-muted mb-0">Pesanan <strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
            </div>
            <div>
                <a href="order-detail.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-receipt me-1"></i>Detail Pesanan
                </a>
            </div>
        </div>

        <div class="proof-hero mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-uppercase small text-secondary mb-1">Status Verifikasi</div>
                    <span class="badge <?php echo $badge[0]; ?> fs-6"><?php echo htmlspecialchars($badge[1]); ?></span>
                    <?php if (!empty($order['verified_at'])): ?>
                        <span class="small text-secondary ms-2"><?php echo htmlspecialchars($order['verified_at']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <div class="text-uppercase small text-secondary mb-1">Order on-chain</div>
                    <div class="fs-5">
                        <?php echo $order['blockchain_order_id'] !== null ? '#' . (int)$order['blockchain_order_id'] : '-'; ?>
                        <?php if ($chainState): ?>
                            <span class="badge bg-info text-dark ms-2"><?php echo htmlspecialchars($chainState['statusLabel']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-secondary mb-1">Transaction Hash</div>
                    <div class="proof-mono"><?php echo $order['tx_hash'] ? htmlspecialchars($order['tx_hash']) : '-'; ?></div>
                </div>
                <div class="col-md-6">
                    <div class="small text-secondary mb-1">External Order Hash</div>
                    <div class="proof-mono"><?php echo $order['external_order_hash'] ? htmlspecialchars($order['external_order_hash']) : '-'; ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-secondary mb-1">Block</div>
                    <div><?php echo $order['block_number'] !== null ? (int)$order['block_number'] : '-'; ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-secondary mb-1">Wallet Pembeli</div>
                    <div class="proof-mono"><?php echo $order['wallet_address'] ? htmlspecialchars($order['wallet_address']) : '-'; ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-secondary mb-1">Contract</div>
                    <div class="proof-mono"><?php echo $order['contract_address'] ? htmlspecialchars($order['contract_address']) : '-'; ?></div>
                </div>
            </div>
        </div>

        <?php if ($chainError): ?>
            <div class="alert alert-warning small">
                <i class="fas fa-triangle-exclamation me-1"></i>
                State on-chain tidak dapat dibaca saat ini: <?php echo htmlspecialchars($chainError); ?>
            </div>
        <?php elseif ($chainState): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-cubes me-2" style="color:#004b9c;"></i>State On-chain Saat Ini</h5>
                    <div class="row g-3">
                        <div class="col-md-3"><div class="small text-muted">Status</div><div class="fw-semibold"><?php echo htmlspecialchars($chainState['statusLabel']); ?> (<?php echo (int)$chainState['status']; ?>)</div></div>
                        <div class="col-md-3"><div class="small text-muted">Buyer</div><div class="proof-mono"><?php echo htmlspecialchars($chainState['buyer']); ?></div></div>
                        <div class="col-md-3"><div class="small text-muted">Amount</div><div><?php echo htmlspecialchars($chainState['totalAmountEth']); ?> ETH</div></div>
                        <div class="col-md-3"><div class="small text-muted">Payment Recorded</div><div><?php echo $chainState['paymentRecorded'] ? 'Ya' : 'Belum'; ?></div></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-clock-rotate-left me-2" style="color:#004b9c;"></i>Riwayat Event Blockchain</h5>
                    <button class="btn btn-sm btn-outline-primary" id="verifyBtn" onclick="verifyOrder()">
                        <i class="fas fa-shield-halved me-1"></i>Verifikasi Ulang
                    </button>
                </div>

                <?php if (empty($transactions)): ?>
                    <p class="text-muted mb-0">Belum ada event yang tercatat untuk pesanan ini.</p>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <div class="timeline-item">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($tx['event_name'] ?? 'Event'); ?></span>
                                    <span class="badge <?php echo ($tx['status'] ?? '') === 'CONFIRMED' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo htmlspecialchars($tx['status'] ?? '-'); ?>
                                    </span>
                                    <span class="small text-muted ms-2">Block <?php echo $tx['block_number'] !== null ? (int)$tx['block_number'] : '-'; ?></span>
                                </div>
                                <div class="small text-muted"><?php echo htmlspecialchars($tx['created_at'] ?? ''); ?></div>
                            </div>
                            <div class="proof-mono mt-1"><?php echo htmlspecialchars($tx['tx_hash']); ?></div>
                            <?php if (!empty($tx['payload'])): ?>
                                <details class="mt-1">
                                    <summary class="small text-muted">Payload</summary>
                                    <div class="proof-box mt-1"><?php echo htmlspecialchars((string)$tx['payload']); ?></div>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="verifyResult" class="mb-4"></div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($orderId > 0): ?>
<script>
    async function verifyOrder() {
        const button = document.getElementById('verifyBtn');
        const resultBox = document.getElementById('verifyResult');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memverifikasi...';

        try {
            const response = await fetch('api/blockchain-api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'verify_order', order_id: <?php echo (int)$orderId; ?>, force: true })
            });
            const result = await response.json();
            const status = result.verification_status || 'UNKNOWN';
            const cls = status === 'VERIFIED' ? 'success' : (status === 'MISMATCH' ? 'danger' : 'warning');
            let detail = '';
            if (Array.isArray(result.differences) && result.differences.length > 0) {
                detail = '<ul class="mb-0 mt-2">' + result.differences.map(d =>
                    '<li>' + (d.field || 'field') + ': ' + (d.message || JSON.stringify(d)) + '</li>'
                ).join('') + '</ul>';
            }
            resultBox.innerHTML = '<div class="alert alert-' + cls + '"><strong>Hasil verifikasi: ' + status + '</strong>' + detail + '</div>';
            setTimeout(() => window.location.reload(), 1500);
        } catch (err) {
            resultBox.innerHTML = '<div class="alert alert-danger">Gagal memverifikasi: ' + err.message + '</div>';
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-shield-halved me-1"></i>Verifikasi Ulang';
        }
    }
</script>
<?php endif; ?>
</body>
</html>
