<?php
/**
 * Blockchain API Handler - H-Sport Financial dApp
 * Issue: W5-LUI-06, W5-LUI-08, W5-LUI-09, W5-LUI-10
 * Pemilik: Luis (Integration Engineer)
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/config/functions.php';
require_once __DIR__ . '/services/BlockchainClient.php';
require_once __DIR__ . '/services/ContractService.php';
require_once __DIR__ . '/services/ReceiptService.php';
require_once __DIR__ . '/services/ProofRepository.php';
require_once __DIR__ . '/services/VerificationService.php';
require_once __DIR__ . '/services/OrderIntegrationService.php';

use HSport\Services\BlockchainClient;
use HSport\Services\ContractService;
use HSport\Services\ReceiptService;
use HSport\Services\ProofRepository;
use HSport\Services\VerificationService;
use HSport\Services\OrderIntegrationService;

header('Content-Type: application/json; charset=utf-8');

// Parse input (JSON body atau GET)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput ?: '{}', true) ?: [];
if (empty($input) && !empty($_GET)) {
    $input = $_GET;
}

$action = $input['action'] ?? '';

$proofRepo = new ProofRepository($pdo);
$client = new BlockchainClient();
$contractService = new ContractService($client);
$receiptService = new ReceiptService($client);
$verificationService = new VerificationService($contractService, $proofRepo);
$integrationService = new OrderIntegrationService(
    $client,
    $contractService,
    $receiptService,
    $proofRepo,
    $verificationService
);

try {
    switch ($action) {
        // ----------------------------------------------------
        // 1. Health check RPC & smart contract
        // ----------------------------------------------------
        case 'health':
            $health = $integrationService->healthCheck();
            echo json_encode($health);
            break;

        // ----------------------------------------------------
        // 2. Simpan order crypto & proof
        // ----------------------------------------------------
        case 'save_crypto_order':
            $orderId = (int)($input['order_id'] ?? 0);
            $txHash = trim((string)($input['tx_hash'] ?? ''));
            $walletAddress = trim((string)($input['wallet_address'] ?? ''));
            $blockchainOrderId = isset($input['blockchain_order_id']) && $input['blockchain_order_id'] !== ''
                ? (int)$input['blockchain_order_id']
                : null;
            $externalOrderHash = isset($input['external_order_hash'])
                ? trim((string)$input['external_order_hash'])
                : null;

            if (!$orderId || !$txHash || !$walletAddress) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'INVALID_INPUT',
                    'message' => 'Parameter order_id, tx_hash, dan wallet_address wajib diisi.'
                ]);
                exit();
            }

            $result = $integrationService->processCryptoPayment(
                $orderId,
                $txHash,
                $walletAddress,
                $blockchainOrderId,
                $externalOrderHash
            );

            if (!$result['success']) {
                http_response_code(isset($result['error']) && $result['error'] === 'DUPLICATE_TX' ? 409 : 400);
            }

            echo json_encode($result);
            break;

        // ----------------------------------------------------
        // 3. Ambil data bukti blockchain pesanan
        // ----------------------------------------------------
        case 'get_order_proof':
            $orderId = (int)($input['order_id'] ?? 0);
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'INVALID_INPUT', 'message' => 'order_id wajib diisi.']);
                exit();
            }

            $order = $proofRepo->getOrderById($orderId);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'ORDER_NOT_FOUND', 'message' => 'Order tidak ditemukan.']);
                exit();
            }

            $txList = $proofRepo->getTransactionsByOrderId($orderId);

            $chainState = null;
            if (!empty($order['blockchain_order_id'])) {
                try {
                    $chainState = $contractService->getOrder((int)$order['blockchain_order_id']);
                } catch (Exception $e) {
                    $chainState = ['error' => $e->getMessage()];
                }
            }

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $order['order_number'],
                'tx_hash' => $order['tx_hash'],
                'block_number' => $order['block_number'],
                'contract_address' => $order['contract_address'],
                'wallet_address' => $order['wallet_address'],
                'blockchain_order_id' => $order['blockchain_order_id'],
                'external_order_hash' => $order['external_order_hash'],
                'verification_status' => $order['verification_status'],
                'verified_at' => $order['verified_at'],
                'paid_at' => $order['paid_at'],
                'on_chain_state' => $chainState,
                'transactions' => $txList,
            ]);
            break;

        // ----------------------------------------------------
        // 4. Verifikasi DB vs Smart Contract
        // ----------------------------------------------------
        case 'verify_order':
            $orderId = (int)($input['order_id'] ?? 0);
            $force = !empty($input['force']);

            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'INVALID_INPUT', 'message' => 'order_id wajib diisi.']);
                exit();
            }

            $verif = $verificationService->verifyOrder($orderId, $force);
            echo json_encode($verif);
            break;

        // ----------------------------------------------------
        // 5. Sinkronisasi status order dari blockchain ke DB
        // ----------------------------------------------------
        case 'sync_order':
            $orderId = (int)($input['order_id'] ?? 0);
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'INVALID_INPUT', 'message' => 'order_id wajib diisi.']);
                exit();
            }

            $order = $proofRepo->getOrderById($orderId);
            if (!$order || empty($order['blockchain_order_id'])) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'NO_CHAIN_ORDER', 'message' => 'Order tidak terdaftar di blockchain.']);
                exit();
            }

            $chainOrder = $contractService->getOrder((int)$order['blockchain_order_id']);
            $chainStatus = (int)$chainOrder['status'];

            $statusMapping = [
                0 => 'pending',
                1 => 'pending',
                2 => 'paid',
                3 => 'production',
                4 => 'production',
                5 => 'shipped',
                6 => 'completed',
                7 => 'cancelled',
            ];

            $newDbStatus = $statusMapping[$chainStatus] ?? $order['status'];
            $updated = false;

            if ($newDbStatus !== $order['status']) {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newDbStatus, $orderId]);
                $updated = true;
            }

            // Jalankan verifikasi ulang
            $verif = $verificationService->verifyOrder($orderId, true);

            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'previous_status' => $order['status'],
                'current_status' => $newDbStatus,
                'on_chain_status' => $chainOrder['statusLabel'],
                'updated' => $updated,
                'verification' => $verif,
            ]);
            break;

        // ----------------------------------------------------
        // 6. Catat pembatalan on-chain (cancelOrder) ke DB
        // ----------------------------------------------------
        case 'cancel_order':
            $orderId = (int)($input['order_id'] ?? 0);
            $txHash = trim((string)($input['tx_hash'] ?? ''));
            $walletAddress = trim((string)($input['wallet_address'] ?? ''));
            $chainOrderId = isset($input['blockchain_order_id']) && $input['blockchain_order_id'] !== ''
                ? (int)$input['blockchain_order_id']
                : null;
            $reason = trim((string)($input['reason'] ?? ''));

            if (!$orderId || $txHash === '' || $walletAddress === '') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'INVALID_INPUT',
                    'message' => 'Parameter order_id, tx_hash, dan wallet_address wajib diisi.',
                ]);
                exit();
            }

            if (strlen($reason) > 64) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'REASON_TOO_LONG',
                    'message' => 'Alasan pembatalan maksimal 64 byte.',
                ]);
                exit();
            }

            $result = $integrationService->processCancellation(
                $orderId,
                $txHash,
                $walletAddress,
                $chainOrderId,
                $reason
            );

            if (!$result['success']) {
                $status = 400;
                if (($result['error'] ?? '') === 'ORDER_NOT_FOUND') {
                    $status = 404;
                } elseif (($result['error'] ?? '') === 'TX_NOT_FOUND') {
                    $status = 202;
                }
                http_response_code($status);
            }

            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'INVALID_ACTION',
                'message' => 'Aksi "' . htmlspecialchars($action) . '" tidak valid.',
            ]);
            break;
    }
} catch (HSport\Services\BlockchainException $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'RPC_UNAVAILABLE',
        'message' => $e->getMessage(),
        'hint' => 'Pastikan Ganache berjalan di RPC_URL dan chain ID 1337.',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => $e->getMessage(),
    ]);
}
