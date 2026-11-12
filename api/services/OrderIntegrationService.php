<?php
/**
 * OrderIntegrationService - Orkestrasi Lengkap Integrasi PHP–Blockchain
 * Issue: W5-LUI-06, W5-LUI-08, INT-02, INT-04
 * Pemilik: Luis (Integration Engineer)
 */

declare(strict_types=1);

namespace HSport\Services;

use PDO;
use Exception;

class OrderIntegrationService
{
    private BlockchainClient $client;
    private ContractService $contractService;
    private ReceiptService $receiptService;
    private ProofRepository $proofRepo;
    private VerificationService $verificationService;

    public function __construct(
        ?BlockchainClient $client = null,
        ?ContractService $contractService = null,
        ?ReceiptService $receiptService = null,
        ?ProofRepository $proofRepo = null,
        ?VerificationService $verificationService = null
    ) {
        $this->client = $client ?: new BlockchainClient();
        $this->contractService = $contractService ?: new ContractService($this->client);
        $this->receiptService = $receiptService ?: new ReceiptService($this->client);
        
        if ($proofRepo !== null) {
            $this->proofRepo = $proofRepo;
        } else {
            global $pdo;
            if (!$pdo instanceof PDO) {
                require_once dirname(__DIR__, 2) . '/config/database.php';
            }
            $this->proofRepo = new ProofRepository($pdo);
        }

        $this->verificationService = $verificationService ?: new VerificationService(
            $this->contractService,
            $this->proofRepo
        );
    }

    public function getClient(): BlockchainClient { return $this->client; }
    public function getContractService(): ContractService { return $this->contractService; }
    public function getReceiptService(): ReceiptService { return $this->receiptService; }
    public function getProofRepo(): ProofRepository { return $this->proofRepo; }
    public function getVerificationService(): VerificationService { return $this->verificationService; }

    /**
     * Memproses penyimpanan order crypto, membaca receipt, dan menyimpan proof
     */
    public function processCryptoPayment(
        int $orderId,
        string $txHash,
        string $walletAddress,
        ?int $blockchainOrderId = null,
        ?string $externalOrderHash = null
    ): array {
        $order = $this->proofRepo->getOrderById($orderId);
        if (!$order) {
            return [
                'success' => false,
                'error' => 'ORDER_NOT_FOUND',
                'message' => "Order #{$orderId} tidak ditemukan di database.",
            ];
        }

        // Cek duplikasi tx_hash pada order lain
        $existingOrder = $this->proofRepo->getOrderByTxHash($txHash);
        if ($existingOrder && (int)$existingOrder['id'] !== $orderId) {
            return [
                'success' => false,
                'error' => 'DUPLICATE_TX',
                'message' => "Tx hash {$txHash} sudah digunakan oleh pesanan #{$existingOrder['order_number']}.",
            ];
        }

        // Ambil receipt dari blockchain
        $receiptData = $this->receiptService->fetchAndParseReceipt($txHash);
        $isReceiptFound = $receiptData['found'] ?? false;
        $isConfirmed = $receiptData['confirmed'] ?? false;
        $blockNumber = $receiptData['block_number'] ?? null;
        $contractAddress = $receiptData['contract_address'] ?? $this->contractService->getContractAddress();
        $eventName = 'OrderCreated';
        $detectedChainOrderId = $blockchainOrderId;
        $detectedExtHash = $externalOrderHash;

        // Ambil info dari parsed events jika ada
        if ($isReceiptFound && !empty($receiptData['events'])) {
            foreach ($receiptData['events'] as $ev) {
                if (($ev['event'] ?? '') === 'OrderCreated') {
                    $eventName = 'OrderCreated';
                    if (!$detectedChainOrderId && isset($ev['order_id']) && $ev['order_id'] > 0) {
                        $detectedChainOrderId = (int)$ev['order_id'];
                    }
                    if (!$detectedExtHash && !empty($ev['external_order_hash'])) {
                        $detectedExtHash = $ev['external_order_hash'];
                    }
                }
            }
        }

        // Persist proof ke database
        $proofRecord = [
            'tx_hash' => $txHash,
            'wallet_address' => strtolower($walletAddress),
            'block_number' => $blockNumber,
            'contract_address' => $contractAddress,
            'blockchain_order_id' => $detectedChainOrderId,
            'external_order_hash' => $detectedExtHash,
            'verification_status' => $isConfirmed ? 'PENDING' : 'PENDING',
            'payment_status' => 'paid',
            'event_name' => $eventName,
            'tx_status' => ($receiptData['status'] ?? 'CONFIRMED'),
            'payload' => $receiptData['raw_receipt'] ?? ['tx_hash' => $txHash],
        ];

        $persisted = $this->proofRepo->persistProof($orderId, $proofRecord);

        // Jika confirmed dan ada on-chain order ID, jalankan verifikasi langsung
        $verificationResult = null;
        if ($isConfirmed && $detectedChainOrderId > 0) {
            $verificationResult = $this->verificationService->verifyOrder($orderId);
        }

        return [
            'success' => $persisted,
            'order_id' => $orderId,
            'order_number' => $order['order_number'],
            'tx_hash' => $txHash,
            'receipt_confirmed' => $isConfirmed,
            'block_number' => $blockNumber,
            'contract_address' => $contractAddress,
            'blockchain_order_id' => $detectedChainOrderId,
            'external_order_hash' => $detectedExtHash,
            'verification' => $verificationResult,
        ];
    }

    /**
     * Mencatat pembatalan on-chain (cancelOrder) ke database.
     * Issue: W4-IHS-04, UAT-09/UAT-10
     */
    public function processCancellation(
        int $orderId,
        string $txHash,
        string $walletAddress,
        ?int $blockchainOrderId = null,
        string $reason = ''
    ): array {
        $order = $this->proofRepo->getOrderById($orderId);
        if (!$order) {
            return [
                'success' => false,
                'error' => 'ORDER_NOT_FOUND',
                'message' => "Order #{$orderId} tidak ditemukan di database.",
            ];
        }

        if (in_array((string)$order['status'], ['completed', 'cancelled'], true)) {
            return [
                'success' => false,
                'error' => 'ORDER_FINAL',
                'message' => "Order #{$orderId} sudah berstatus {$order['status']} dan tidak bisa dibatalkan.",
            ];
        }

        $receiptData = $this->receiptService->fetchAndParseReceipt($txHash);
        if (!($receiptData['found'] ?? false)) {
            return [
                'success' => false,
                'error' => 'TX_NOT_FOUND',
                'message' => "Receipt tx {$txHash} belum tersedia di blockchain.",
            ];
        }
        if (!($receiptData['is_success'] ?? false)) {
            return [
                'success' => false,
                'error' => 'TX_FAILED',
                'message' => "Transaksi {$txHash} gagal di blockchain.",
            ];
        }

        $chainOrderId = $blockchainOrderId ?: (int)($order['blockchain_order_id'] ?? 0);
        $detectedReason = $reason;

        foreach (($receiptData['events'] ?? []) as $ev) {
            if (($ev['event'] ?? '') === 'OrderCancelled') {
                if (!$chainOrderId && !empty($ev['order_id'])) {
                    $chainOrderId = (int)$ev['order_id'];
                }
                if (!empty($ev['reason'])) {
                    $detectedReason = (string)$ev['reason'];
                }
            }
        }

        if ($chainOrderId <= 0) {
            return [
                'success' => false,
                'error' => 'CHAIN_ORDER_UNKNOWN',
                'message' => 'Order ID on-chain tidak diketahui; sertakan blockchain_order_id.',
            ];
        }

        // Pastikan kontrak benar-benar berstatus Cancelled (7).
        $chainOrder = $this->contractService->getOrder($chainOrderId);
        if ((int)$chainOrder['status'] !== 7) {
            return [
                'success' => false,
                'error' => 'NOT_CANCELLED_ON_CHAIN',
                'message' => "Status on-chain order #{$chainOrderId} masih {$chainOrder['statusLabel']}.",
                'on_chain_status' => $chainOrder['statusLabel'],
            ];
        }

        $saved = $this->proofRepo->recordCancellation($orderId, [
            'tx_hash' => $txHash,
            'wallet_address' => strtolower($walletAddress),
            'block_number' => $receiptData['block_number'] ?? null,
            'contract_address' => $this->contractService->getContractAddress(),
            'blockchain_order_id' => $chainOrderId,
            'reason' => $detectedReason,
            'tx_status' => 'CONFIRMED',
            'payload' => [
                'reason' => $detectedReason,
                'cancelled_by' => strtolower($walletAddress),
                'events' => $receiptData['events'] ?? [],
            ],
        ]);

        $verification = null;
        try {
            $verification = $this->verificationService->verifyOrder($orderId, true);
        } catch (Exception $e) {
            // Verifikasi tidak boleh menggagalkan pencatatan cancel.
        }

        return [
            'success' => $saved,
            'order_id' => $orderId,
            'order_number' => $order['order_number'],
            'tx_hash' => $txHash,
            'block_number' => $receiptData['block_number'] ?? null,
            'blockchain_order_id' => $chainOrderId,
            'reason' => $detectedReason,
            'on_chain_status' => $chainOrder['statusLabel'],
            'verification' => $verification,
        ];
    }

    /**
     * Health check endpoint
     */
    public function healthCheck(): array
    {
        try {
            $chainId = $this->client->chainId();
            $blockNumber = $this->client->blockNumber();
            $contractAddress = $this->contractService->getContractAddress();
            $code = $this->client->getCode($contractAddress);
            $hasCode = strlen(preg_replace('/^0x/', '', $code)) > 0;

            return [
                'success' => true,
                'rpc_ok' => true,
                'rpc_url' => $this->client->getRpcUrl(),
                'chain_id' => $chainId,
                'block_number' => $blockNumber,
                'contract_address' => $contractAddress,
                'contract_deployed' => $hasCode,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'rpc_ok' => false,
                'rpc_url' => $this->client->getRpcUrl(),
                'error' => $e->getMessage(),
            ];
        }
    }
}

