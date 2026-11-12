<?php
/**
 * VerificationService - Verifikasi Konsistensi Data DB vs Blockchain
 * Issue: W5-LUI-09, INT-05
 * Pemilik: Luis (Integration Engineer)
 */

declare(strict_types=1);

namespace HSport\Services;

use PDO;
use Exception;

class VerificationService
{
    private ContractService $contractService;
    private ProofRepository $proofRepo;
    private int $ethRateIdr;

    public function __construct(?ContractService $contractService = null, ?ProofRepository $proofRepo = null, int $ethRateIdr = 10000000)
    {
        $this->contractService = $contractService ?: new ContractService();
        if ($proofRepo !== null) {
            $this->proofRepo = $proofRepo;
        } else {
            global $pdo;
            if (!$pdo instanceof PDO) {
                require_once dirname(__DIR__, 2) . '/config/database.php';
            }
            $this->proofRepo = new ProofRepository($pdo);
        }
        $this->ethRateIdr = $ethRateIdr;
    }

    /**
     * Memverifikasi satu pesanan antara MySQL dan Smart Contract
     */
    public function verifyOrder(int $orderId, bool $force = false): array
    {
        $dbOrder = $this->proofRepo->getOrderById($orderId);
        if (!$dbOrder) {
            return [
                'success' => false,
                'order_id' => $orderId,
                'verification_status' => 'MISMATCH',
                'error' => 'ORDER_NOT_FOUND',
                'message' => "Order ID {$orderId} tidak ditemukan di database.",
            ];
        }

        $blockchainOrderId = isset($dbOrder['blockchain_order_id']) && $dbOrder['blockchain_order_id'] > 0
            ? (int)$dbOrder['blockchain_order_id']
            : null;

        // Jika blockchain_order_id belum ada, coba cari lewat external_order_hash
        if (!$blockchainOrderId && !empty($dbOrder['external_order_hash'])) {
            try {
                $foundId = $this->contractService->getOrderIdByHash($dbOrder['external_order_hash']);
                if ($foundId > 0) {
                    $blockchainOrderId = $foundId;
                }
            } catch (Exception $e) {}
        }

        if (!$blockchainOrderId) {
            return [
                'success' => false,
                'order_id' => $orderId,
                'verification_status' => 'PENDING',
                'error' => 'NO_BLOCKCHAIN_ID',
                'message' => "Order belum memiliki ID blockchain yang tercatat on-chain.",
            ];
        }

        // Ambil data on-chain
        try {
            $chainOrder = $this->contractService->getOrder($blockchainOrderId);
        } catch (Exception $e) {
            return [
                'success' => false,
                'order_id' => $orderId,
                'blockchain_order_id' => $blockchainOrderId,
                'verification_status' => 'MISMATCH',
                'error' => 'CHAIN_READ_ERROR',
                'message' => "Gagal membaca order di smart contract: " . $e->getMessage(),
            ];
        }

        $differences = [];

        // 1. Verifikasi Buyer / Wallet Address
        $dbWallet = strtolower(trim((string)($dbOrder['wallet_address'] ?? '')));
        $chainBuyer = strtolower(trim((string)($chainOrder['buyer'] ?? '')));
        if ($dbWallet !== '' && $chainBuyer !== '' && $dbWallet !== $chainBuyer) {
            $differences[] = [
                'field' => 'buyer_wallet',
                'db' => $dbWallet,
                'chain' => $chainBuyer,
                'message' => 'Alamat wallet pembeli tidak sesuai antara DB dan kontrak.',
            ];
        }

        // 2. Verifikasi Nilai Transaksi (Total Amount)
        // IDR to ETH: total_amount / 10.000.000
        $expectedEth = bcdiv((string)$dbOrder['total_amount'], (string)$this->ethRateIdr, 6);
        $chainEth = $chainOrder['totalAmountEth'];
        if (bccomp($expectedEth, $chainEth, 4) !== 0) {
            $differences[] = [
                'field' => 'total_amount',
                'db' => (string)$dbOrder['total_amount'] . " IDR (~{$expectedEth} ETH)",
                'chain' => "{$chainEth} ETH ({$chainOrder['totalAmountWei']} Wei)",
                'message' => 'Nilai transaksi (amount) tidak cocok antara database dan on-chain.',
            ];
        }

        // 3. Verifikasi Status Pesanan
        $dbStatus = strtolower(trim((string)$dbOrder['status']));
        $chainStatus = (int)$chainOrder['status'];
        $chainStatusLabel = $chainOrder['statusLabel'];

        $validStatusMap = [
            0 => ['pending', 'created'],
            1 => ['pending', 'awaiting_payment'],
            2 => ['paid', 'production'],
            3 => ['production', 'paid'],
            4 => ['production', 'ready', 'ready_to_ship'],
            5 => ['shipped'],
            6 => ['completed'],
            7 => ['cancelled'],
        ];

        $allowedDbStatuses = $validStatusMap[$chainStatus] ?? [];
        if (!in_array($dbStatus, $allowedDbStatuses, true)) {
            $differences[] = [
                'field' => 'order_status',
                'db' => $dbStatus,
                'chain' => "{$chainStatus} ({$chainStatusLabel})",
                'message' => "Status pesanan DB ({$dbStatus}) tidak konsisten dengan state on-chain ({$chainStatusLabel}).",
            ];
        }

        // 4. Verifikasi External Order Hash (jika tersedia)
        if (!empty($dbOrder['external_order_hash'])) {
            $dbExtHash = strtolower(trim((string)$dbOrder['external_order_hash']));
            $chainExtHash = strtolower(trim((string)$chainOrder['externalOrderHash']));
            if ($dbExtHash !== $chainExtHash) {
                $differences[] = [
                    'field' => 'external_order_hash',
                    'db' => $dbExtHash,
                    'chain' => $chainExtHash,
                    'message' => 'Kriptografis external order hash tidak cocok.',
                ];
            }
        }

        // 5. Verifikasi Nomor Pesanan (Order Number)
        if (!empty($chainOrder['orderNumber']) && !empty($dbOrder['order_number'])) {
            if ($chainOrder['orderNumber'] !== $dbOrder['order_number']) {
                $differences[] = [
                    'field' => 'order_number',
                    'db' => $dbOrder['order_number'],
                    'chain' => $chainOrder['orderNumber'],
                    'message' => 'Nomor pesanan pada smart contract berbeda dengan database.',
                ];
            }
        }

        $isVerified = empty($differences);
        $newStatus = $isVerified ? 'VERIFIED' : 'MISMATCH';
        $verifiedAt = date('Y-m-d H:i:s');

        // Update status di database
        $this->proofRepo->updateVerificationStatus($orderId, $newStatus, $verifiedAt);

        return [
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $dbOrder['order_number'],
            'blockchain_order_id' => $blockchainOrderId,
            'verification_status' => $newStatus,
            'is_verified' => $isVerified,
            'differences' => $differences,
            'verified_at' => $verifiedAt,
            'db_summary' => [
                'total_amount' => $dbOrder['total_amount'],
                'status' => $dbOrder['status'],
                'wallet_address' => $dbOrder['wallet_address'],
            ],
            'chain_summary' => [
                'total_amount_eth' => $chainEth,
                'status' => $chainOrder['status'],
                'status_label' => $chainStatusLabel,
                'buyer' => $chainOrder['buyer'],
            ],
        ];
    }
}

