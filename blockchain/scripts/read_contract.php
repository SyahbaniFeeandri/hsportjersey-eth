<?php
/**
 * Prototipe service read-only kontrak HSportStore (W3-LUI-04).
 *
 * Membaca kontrak lewat JSON-RPC tanpa private key dan tanpa menulis state.
 * Dipakai untuk memastikan backend PHP bisa berbicara dengan kontrak V1
 * sebelum service penuh dibangun di Minggu 5.
 *
 * Pemakaian:
 *   php read_contract.php            # owner, orderCount, chain info
 *   php read_contract.php 1          # tambah status order ID 1
 *
 * Selector dihitung sekali dari ABI. Cara regenerasi (folder blockchain/):
 *   node -e "const {Web3}=require('web3');const w=new Web3();
 *     ['owner()','orderCount()','getOrderStatus(uint256)'].forEach(s =>
 *     console.log(s, w.eth.abi.encodeFunctionSignature(s)))"
 */

declare(strict_types=1);

const SELECTOR_OWNER = '0x8da5cb5b';              // owner()
const SELECTOR_ORDER_COUNT = '0x2453ffa8';        // orderCount()
const SELECTOR_ORDER_STATUS = '0x45fa8aae';       // getOrderStatus(uint256)

$root = dirname(__DIR__);
$rpcUrl = getenv('RPC_URL') ?: 'http://127.0.0.1:7545';
$addressFile = $root . '/config/contract_address.txt';

if (!is_file($addressFile)) {
    fwrite(STDERR, "contract_address.txt tidak ditemukan. Jalankan `npm run migrate` dulu.\n");
    exit(1);
}

$contract = trim((string) file_get_contents($addressFile));
if (!preg_match('/^0x[0-9a-fA-F]{40}$/', $contract)) {
    fwrite(STDERR, "Address kontrak tidak valid: {$contract}\n");
    exit(1);
}

function rpc(string $url, string $method, array $params)
{
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => $method,
        'params' => $params,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        fwrite(STDERR, "RPC gagal: {$error}\n");
        exit(1);
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || isset($decoded['error'])) {
        fwrite(STDERR, "RPC error: " . ($decoded['error']['message'] ?? $response) . "\n");
        exit(1);
    }

    return $decoded['result'] ?? [];
}

function ethCall(string $url, string $contract, string $data): string
{
    return (string) rpc($url, 'eth_call', [
        ['to' => $contract, 'data' => $data],
        'latest',
    ]);
}

function decodeAddress(string $hex): string
{
    return '0x' . substr(preg_replace('/^0x/', '', $hex), -40);
}

function decodeUint(string $hex): string
{
    $clean = preg_replace('/^0x/', '', $hex);
    return $clean === '' ? '0' : (string) hexdec($clean);
}

try {
    $chainId = decodeUint((string) rpc($rpcUrl, 'eth_chainId', []));
    $blockNumber = decodeUint((string) rpc($rpcUrl, 'eth_blockNumber', []));
    $code = (string) rpc($rpcUrl, 'eth_getCode', [$contract, 'latest']);

    $owner = decodeAddress(ethCall($rpcUrl, $contract, SELECTOR_OWNER));
    $orderCount = decodeUint(ethCall($rpcUrl, $contract, SELECTOR_ORDER_COUNT));

    echo "RPC URL        : {$rpcUrl}\n";
    echo "Chain ID       : {$chainId}\n";
    echo "Block number   : {$blockNumber}\n";
    echo "Contract       : {$contract}\n";
    echo "Bytecode size  : " . (strlen(preg_replace('/^0x/', '', $code)) / 2) . " bytes\n";
    echo "Owner          : {$owner}\n";
    echo "Order count    : {$orderCount}\n";

    $orderId = isset($argv[1]) ? (int) $argv[1] : 0;
    if ($orderId > 0) {
        if ($orderId > (int) $orderCount) {
            fwrite(STDERR, "Order {$orderId} tidak ada (orderCount={$orderCount}).\n");
            exit(2);
        }
        $data = SELECTOR_ORDER_STATUS . str_pad(dechex($orderId), 64, '0', STR_PAD_LEFT);
        $status = decodeUint(ethCall($rpcUrl, $contract, $data));
        $labels = ['Created', 'AwaitingPayment', 'Paid', 'InProduction', 'ReadyToShip', 'Shipped', 'Completed', 'Cancelled'];
        $label = $labels[(int) $status] ?? 'Unknown';
        echo "Order #{$orderId}       : {$status} ({$label})\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Gagal membaca kontrak: {$e->getMessage()}\n");
    exit(1);
}
