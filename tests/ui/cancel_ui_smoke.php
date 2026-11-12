<?php
/**
 * Smoke test statis untuk UI cancel on-chain (W4-IHS-04).
 *
 * Memeriksa bahwa seluruh bagian minimum tersedia dan konsisten:
 * - ABI memuat cancelOrder(uint256,string)
 * - Halaman detail order memuat panel/modal cancel, validasi 64 byte, listener event
 * - API backend menangani action cancel_order
 * - Service menyimpan bukti pembatalan
 *
 * Ini bukan pengganti UAT browser; ini gate cepat agar UI tidak hilang saat refactor.
 * Jalankan: php tests/ui/cancel_ui_smoke.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$checks = [];

function check(string $name, bool $ok, string $detail = ''): void
{
    global $checks;
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
}

function fileContains(string $path, string $needle): bool
{
    return is_file($path) && str_contains((string)file_get_contents($path), $needle);
}

$abiPath = $root . '/blockchain/config/contract_abi.json';
$abi = is_file($abiPath) ? json_decode((string)file_get_contents($abiPath), true) : [];
$cancelFn = null;
if (is_array($abi)) {
    foreach ($abi as $entry) {
        if (($entry['type'] ?? '') === 'function' && ($entry['name'] ?? '') === 'cancelOrder') {
            $cancelFn = $entry;
            break;
        }
    }
}

check(
    'ABI memuat cancelOrder(uint256,string)',
    is_array($cancelFn)
        && count($cancelFn['inputs'] ?? []) === 2
        && ($cancelFn['inputs'][0]['type'] ?? '') === 'uint256'
        && ($cancelFn['inputs'][1]['type'] ?? '') === 'string',
    $abiPath
);

$page = $root . '/order-detail.php';
check('Halaman detail memuat modal cancel', fileContains($page, 'id="cancelModal"'), 'order-detail.php');
check('Halaman detail memuat panel cancel', fileContains($page, 'class="cancel-card"'), 'order-detail.php');
check('Halaman detail memvalidasi 64 byte UTF-8', fileContains($page, 'reasonByteLength') && fileContains($page, '> 64'), 'order-detail.php');
check('Halaman detail memanggil cancelOrder', fileContains($page, "cancelOrder(ctx.chainId, reason)"), 'order-detail.php');
check('Halaman detail menampilkan EscrowRefunded', fileContains($page, 'EscrowRefunded'), 'order-detail.php');
check('Halaman detail menyimpan bukti via API', fileContains($page, "action: 'cancel_order'"), 'order-detail.php');
check('Halaman detail menangani perubahan akun MetaMask', fileContains($page, "accountsChanged"), 'order-detail.php');

check(
    'API menangani action cancel_order',
    fileContains($root . '/api/blockchain-api.php', "case 'cancel_order'"),
    'api/blockchain-api.php'
);
check(
    'OrderIntegrationService menyediakan processCancellation',
    fileContains($root . '/api/services/OrderIntegrationService.php', 'function processCancellation'),
    'api/services/OrderIntegrationService.php'
);
check(
    'ProofRepository menyimpan bukti pembatalan',
    fileContains($root . '/api/services/ProofRepository.php', 'function recordCancellation'),
    'api/services/ProofRepository.php'
);
check(
    'ReceiptService mem-parse alasan OrderCancelled',
    fileContains($root . '/api/services/ReceiptService.php', 'decodeStringFromData'),
    'api/services/ReceiptService.php'
);

$passed = 0;
$failed = 0;
echo "===============================================================\n";
echo " H-SPORT — CANCEL UI SMOKE TEST (W4-IHS-04)\n";
echo " Tanggal: " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n";
foreach ($checks as $check) {
    if ($check['ok']) {
        $passed++;
        echo " [PASS] {$check['name']}\n";
    } else {
        $failed++;
        echo " [FAIL] {$check['name']} -> {$check['detail']}\n";
    }
}
echo "---------------------------------------------------------------\n";
echo " HASIL: {$passed} PASS, {$failed} FAIL dari " . count($checks) . " pemeriksaan\n";
echo "===============================================================\n";

exit($failed === 0 ? 0 : 1);
