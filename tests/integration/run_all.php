<?php
/**
 * Test Harness Integrasi Backend & Blockchain (INT-01 s.d. INT-05)
 * Berdasarkan spesifikasi: docs/week1/07_TEST_PLAN.md & docs/DOKUMENTASI_PROJECT_DAN_PERAN.md
 *
 * Alur: checkout DB -> createOrder on-chain -> receipt -> persist proof -> verifikasi.
 * Test bersifat idempoten: seluruh order uji lama dibersihkan lebih dulu dan
 * setiap eksekusi memakai order_number unik.
 *
 * Pemilik: Luis (Integration Engineer)
 * Validator: Kenjiro B.H.G. (QA & Security)
 *
 * Jalankan: php tests/integration/run_all.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/config/database.php';
require_once $root . '/config/functions.php';
require_once $root . '/api/services/BlockchainClient.php';
require_once $root . '/api/services/ContractService.php';
require_once $root . '/api/services/ReceiptService.php';
require_once $root . '/api/services/ProofRepository.php';
require_once $root . '/api/services/VerificationService.php';
require_once $root . '/api/services/OrderIntegrationService.php';

use HSport\Services\BlockchainClient;
use HSport\Services\ContractService;
use HSport\Services\ReceiptService;
use HSport\Services\ProofRepository;
use HSport\Services\VerificationService;
use HSport\Services\OrderIntegrationService;

echo "===============================================================\n";
echo "   H-SPORT FINANCIAL dAPP — INTEGRATION TEST SUITE (INT-01..05)\n";
echo "   Tanggal: " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n\n";

$client = new BlockchainClient();
$contractService = new ContractService($client);
$receiptService = new ReceiptService($client);
$proofRepo = new ProofRepository($pdo);
$verificationService = new VerificationService($contractService, $proofRepo);
$integrationService = new OrderIntegrationService($client, $contractService, $receiptService, $proofRepo, $verificationService);

$results = [];
$totalTests = 5;
$passedTests = 0;

function assertTest(string $code, string $name, bool $condition, string $detail, array &$results, int &$passedTests): void
{
    if ($condition) {
        $passedTests++;
        $status = 'PASS';
        echo " [✓ PASS] {$code}: {$name}\n";
        echo "         -> {$detail}\n\n";
    } else {
        $status = 'FAIL';
        echo " [✗ FAIL] {$code}: {$name}\n";
        echo "         -> {$detail}\n\n";
    }
    $results[$code] = [
        'name' => $name,
        'status' => $status,
        'detail' => $detail,
    ];
}

function waitForReceipt(ReceiptService $receiptService, string $txHash, int $attempts = 15): array
{
    $receipt = ['found' => false];
    for ($i = 0; $i < $attempts; $i++) {
        $receipt = $receiptService->fetchAndParseReceipt($txHash);
        if (($receipt['found'] ?? false) === true) {
            break;
        }
        usleep(200000);
    }
    return $receipt;
}

// ---------------------------------------------------------------------
// INT-01: Pengujian koneksi RPC dari service backend ke node blockchain
// ---------------------------------------------------------------------
try {
    $health = $integrationService->healthCheck();
    $chainId = $health['chain_id'] ?? 0;
    $blockNum = $health['block_number'] ?? -1;
    $hasCode = $health['contract_deployed'] ?? false;
    $contractAddr = $health['contract_address'] ?? '';

    $int01Pass = ($health['rpc_ok'] === true) && ($chainId === 1337) && ($blockNum >= 0) && $hasCode;
    $detail01 = "RPC: {$health['rpc_url']} | Chain ID: {$chainId} (Expected 1337) | Block: #{$blockNum} | Contract: {$contractAddr} (Bytecode verified)";
    assertTest('INT-01', 'Koneksi RPC Backend ke Ganache Node', $int01Pass, $detail01, $results, $passedTests);
} catch (Exception $e) {
    assertTest('INT-01', 'Koneksi RPC Backend ke Ganache Node', false, 'Exception: ' . $e->getMessage(), $results, $passedTests);
}

// ---------------------------------------------------------------------
// INT-02: Checkout DB lokal + createOrder on-chain
// ---------------------------------------------------------------------
$dbOrderId = 0;
$chainOrderId = 0;
$createTx = '';
$chainOrder = null;
$buyerWallet = '';
$orderNumber = '';
$amountIdr = 10000000; // 1 ETH @ kurs 10.000.000 IDR
$amountWei = bcmul((string)$amountIdr, '100000000000', 0); // 1 IDR = 1e11 wei
$extHash = '';

try {
    // Isolasi data uji: hapus sisa eksekusi sebelumnya (termasuk data lama INV-TEST-001).
    $testIds = $pdo->query("SELECT id FROM orders WHERE order_number LIKE 'INV-INT-%' OR order_number = 'INV-TEST-001'")
        ->fetchAll(PDO::FETCH_COLUMN);
    if ($testIds) {
        $in = implode(',', array_map('intval', $testIds));
        $pdo->exec("DELETE FROM blockchain_transactions WHERE order_id IN ({$in})");
        $pdo->exec("DELETE FROM orders WHERE id IN ({$in})");
    }

    $accounts = $client->accounts();
    if (count($accounts) < 2) {
        throw new RuntimeException('Ganache harus menyediakan minimal 2 akun (owner + buyer).');
    }
    $buyerWallet = strtolower($accounts[1]);

    $suffix = date('His') . substr(uniqid('', true), -4);
    $orderNumber = 'INV-INT-' . $suffix;
    $extHash = '0x' . hash('sha256', $orderNumber . $buyerWallet . $amountIdr);

    $uRow = $pdo->query('SELECT id FROM users LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $userId = $uRow ? (int)$uRow['id'] : 1;

    $stmt = $pdo->prepare("INSERT INTO orders
        (user_id, order_number, total_amount, shipping_method, shipping_address, payment_status, payment_type, status, created_at, updated_at)
        VALUES (?, ?, ?, 'JNE Regular', 'Jl. Uji Integrasi No. 5', 'unpaid', 'crypto', 'pending', NOW(), NOW())");
    $stmt->execute([$userId, $orderNumber, $amountIdr]);
    $dbOrderId = (int)$pdo->lastInsertId();

    // Tulis order ke smart contract dari akun pembeli (Ganache unlocked).
    $createTx = $contractService->createOrder($extHash, $orderNumber, $amountWei, $buyerWallet);
    $createReceipt = waitForReceipt($receiptService, $createTx, 15);

    if (!($createReceipt['found'] ?? false) || !($createReceipt['is_success'] ?? false)) {
        throw new RuntimeException('Transaksi createOrder tidak berhasil: ' . ($createReceipt['message'] ?? 'receipt tidak ditemukan'));
    }

    foreach (($createReceipt['events'] ?? []) as $ev) {
        if (($ev['event'] ?? '') === 'OrderCreated' && !empty($ev['order_id'])) {
            $chainOrderId = (int)$ev['order_id'];
            break;
        }
    }
    if ($chainOrderId <= 0) {
        $chainOrderId = $contractService->orderCount();
    }

    $chainOrder = $contractService->getOrder($chainOrderId);

    $pdo->prepare("UPDATE orders SET
        blockchain_order_id = ?,
        external_order_hash = ?,
        wallet_address = ?,
        contract_address = ?
        WHERE id = ?")
        ->execute([
            $chainOrderId,
            $chainOrder['externalOrderHash'],
            $chainOrder['buyer'],
            $contractService->getContractAddress(),
            $dbOrderId,
        ]);

    $int02Pass = ($dbOrderId > 0)
        && ($chainOrder['id'] === $chainOrderId)
        && (strtolower($chainOrder['buyer']) === $buyerWallet)
        && (strtolower($chainOrder['externalOrderHash']) === strtolower($extHash));

    $detail02 = "DB Order #{$dbOrderId} ({$orderNumber}) -> on-chain order ID #{$chainOrderId} via tx " . substr($createTx, 0, 12)
        . "… (Buyer: {$chainOrder['buyer']}, Status: {$chainOrder['statusLabel']})";
    assertTest('INT-02', 'Sinkronisasi Order DB Lokal dengan Smart Contract', $int02Pass, $detail02, $results, $passedTests);
} catch (Throwable $e) {
    assertTest('INT-02', 'Sinkronisasi Order DB Lokal dengan Smart Contract', false, 'Exception: ' . $e->getMessage(), $results, $passedTests);
}

// ---------------------------------------------------------------------
// INT-03: Pengambilan tx hash, receipt & status blockchain
// ---------------------------------------------------------------------
$createReceipt = null;
try {
    $createReceipt = $createTx !== '' ? waitForReceipt($receiptService, $createTx, 15) : ['found' => false];
    $int03Pass = ($createReceipt['found'] ?? false) === true
        && ($createReceipt['confirmed'] ?? false) === true
        && ($createReceipt['block_number'] ?? 0) > 0
        && ($createReceipt['is_success'] ?? false) === true;

    $detail03 = "Tx Hash: {$createTx} | Status: " . ($createReceipt['status'] ?? '-')
        . " | Block: #" . ($createReceipt['block_number'] ?? 0)
        . " | Gas Used: " . ($createReceipt['gas_used'] ?? 0)
        . " | Contract: " . ($createReceipt['contract_address'] ?? '-');
    assertTest('INT-03', 'Pengambilan Transaction Hash, Receipt & Status Blockchain', $int03Pass, $detail03, $results, $passedTests);
} catch (Throwable $e) {
    assertTest('INT-03', 'Pengambilan Transaction Hash, Receipt & Status Blockchain', false, 'Exception: ' . $e->getMessage(), $results, $passedTests);
}

// ---------------------------------------------------------------------
// INT-04: Penyimpanan bukti kriptografis (tx hash, block, wallet) ke DB
// ---------------------------------------------------------------------
try {
    $proofData = [
        'tx_hash' => $createTx,
        'wallet_address' => $buyerWallet,
        'block_number' => $createReceipt['block_number'] ?? null,
        'contract_address' => $contractService->getContractAddress(),
        'blockchain_order_id' => $chainOrderId,
        'external_order_hash' => $extHash,
        'verification_status' => 'PENDING',
        'payment_status' => 'paid',
        'event_name' => 'OrderCreated',
        'tx_status' => 'CONFIRMED',
        'payload' => ['receipt_tested' => true, 'network' => 'ganache_1337'],
    ];

    $persisted = $proofRepo->persistProof($dbOrderId, $proofData);
    $savedOrder = $proofRepo->getOrderById($dbOrderId);
    $txList = $proofRepo->getTransactionsByOrderId($dbOrderId);

    $int04Pass = $persisted
        && ($savedOrder['tx_hash'] === $createTx)
        && ((int)$savedOrder['block_number'] === (int)$proofData['block_number'])
        && ((int)$savedOrder['blockchain_order_id'] === $chainOrderId)
        && ($savedOrder['payment_type'] === 'crypto')
        && (count($txList) > 0);

    $detail04 = "Proof tersimpan di `orders` (ID: {$dbOrderId}, Tx: " . substr((string)$savedOrder['tx_hash'], 0, 10)
        . "…, Block: {$savedOrder['block_number']}) & dicatat di `blockchain_transactions` (" . count($txList) . " baris)";
    assertTest('INT-04', 'Penyimpanan Bukti Kriptografis (Tx Hash, Block, Wallet) ke DB', $int04Pass, $detail04, $results, $passedTests);
} catch (Throwable $e) {
    assertTest('INT-04', 'Penyimpanan Bukti Kriptografis (Tx Hash, Block, Wallet) ke DB', false, 'Exception: ' . $e->getMessage(), $results, $passedTests);
}

// ---------------------------------------------------------------------
// INT-05: Verifikasi konsistensi data antara status di DB & smart contract
// ---------------------------------------------------------------------
try {
    if (!$chainOrder) {
        throw new RuntimeException('On-chain order tidak tersedia untuk verifikasi.');
    }

    $exactIdr = (int)bcmul((string)$chainOrder['totalAmountEth'], (string)10000000);
    $statusMapping = [0 => 'pending', 1 => 'pending', 2 => 'paid', 3 => 'production', 4 => 'production', 5 => 'shipped', 6 => 'completed', 7 => 'cancelled'];
    $dbMatchingStatus = $statusMapping[(int)$chainOrder['status']] ?? 'paid';

    $pdo->prepare('UPDATE orders SET total_amount = ?, status = ?, wallet_address = ?, external_order_hash = ? WHERE id = ?')
        ->execute([$exactIdr, $dbMatchingStatus, $chainOrder['buyer'], $chainOrder['externalOrderHash'], $dbOrderId]);

    $verifMatch = $verificationService->verifyOrder($dbOrderId, true);
    $stepAPass = ($verifMatch['verification_status'] === 'VERIFIED') && empty($verifMatch['differences']);

    $tamperedAmount = $exactIdr + 500000;
    $pdo->prepare('UPDATE orders SET total_amount = ? WHERE id = ?')->execute([$tamperedAmount, $dbOrderId]);

    $verifTamper = $verificationService->verifyOrder($dbOrderId, true);
    $stepBPass = ($verifTamper['verification_status'] === 'MISMATCH') && !empty($verifTamper['differences']);

    $pdo->prepare('UPDATE orders SET total_amount = ? WHERE id = ?')->execute([$exactIdr, $dbOrderId]);
    $verifRestore = $verificationService->verifyOrder($dbOrderId, true);
    $stepCPass = ($verifRestore['verification_status'] === 'VERIFIED');

    $int05Pass = $stepAPass && $stepBPass && $stepCPass;
    $detail05 = "Cocok -> VERIFIED (" . count($verifMatch['differences']) . " diff) | Manipulasi Rp "
        . number_format($tamperedAmount, 0, ',', '.') . " -> Terdeteksi MISMATCH ("
        . count($verifTamper['differences']) . " diff) | Restore -> VERIFIED";
    assertTest('INT-05', 'Verifikasi Konsistensi Data Status DB vs Smart Contract (VERIFIED / MISMATCH)', $int05Pass, $detail05, $results, $passedTests);
} catch (Throwable $e) {
    assertTest('INT-05', 'Verifikasi Konsistensi Data Status DB vs Smart Contract', false, 'Exception: ' . $e->getMessage(), $results, $passedTests);
}

// ---------------------------------------------------------------------
// Ringkasan Eksekusi
// ---------------------------------------------------------------------
echo "===============================================================\n";
echo "   HASIL AKHIR: {$passedTests} / {$totalTests} INTEGRATION TESTS PASS\n";
echo "   Status: " . ($passedTests === $totalTests ? 'ALL PASS (100%)' : 'PARTIAL / FAIL') . "\n";
echo "===============================================================\n";

$jsonOutput = [
    'executed_at' => date('c'),
    'contract_address' => $contractService->getContractAddress(),
    'chain_id' => 1337,
    'order' => [
        'db_order_id' => $dbOrderId ?? null,
        'chain_order_id' => $chainOrderId ?? null,
        'order_number' => $orderNumber ?? null,
        'tx_hash' => $createTx ?? null,
    ],
    'total' => $totalTests,
    'passed' => $passedTests,
    'all_pass' => ($passedTests === $totalTests),
    'tests' => $results,
];

file_put_contents($root . '/docs/week5/evidence/int01_int05_test.json', json_encode($jsonOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

exit($passedTests === $totalTests ? 0 : 1);
