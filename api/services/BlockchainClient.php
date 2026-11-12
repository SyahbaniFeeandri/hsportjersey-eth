<?php
/**
 * BlockchainClient - Klien JSON-RPC Ethereum untuk H-Sport Financial dApp
 * Issue: W5-LUI-06, INT-01
 * Pemilik: Luis (Integration Engineer)
 */

declare(strict_types=1);

namespace HSport\Services;

use RuntimeException;
use Exception;

class BlockchainException extends RuntimeException {}

class BlockchainClient
{
    private string $rpcUrl;
    private int $timeout;

    public function __construct(?string $rpcUrl = null, int $timeout = 10)
    {
        $this->rpcUrl = $rpcUrl ?: (getenv('RPC_URL') ?: 'http://127.0.0.1:7545');
        $this->timeout = $timeout;
    }

    public function getRpcUrl(): string
    {
        return $this->rpcUrl;
    }

    /**
     * Eksekusi pemanggilan JSON-RPC
     */
    public function request(string $method, array $params = []): mixed
    {
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id' => time() . mt_rand(100, 999),
            'method' => $method,
            'params' => $params,
        ]);

        $ch = curl_init($this->rpcUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new BlockchainException("Koneksi RPC gagal ({$this->rpcUrl}): {$curlErr}", 503);
        }

        if ($httpCode >= 400) {
            throw new BlockchainException("RPC server mengembalikan HTTP {$httpCode}: {$response}", $httpCode);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new BlockchainException("Format respons RPC tidak valid (bukan JSON): {$response}", 500);
        }

        if (isset($decoded['error'])) {
            $msg = $decoded['error']['message'] ?? 'Unknown RPC error';
            $code = (int)($decoded['error']['code'] ?? 500);
            throw new BlockchainException("RPC error [{$code}]: {$msg}", $code);
        }

        return $decoded['result'] ?? null;
    }

    public function ping(): bool
    {
        try {
            $id = $this->chainId();
            return $id > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function chainId(): int
    {
        $res = (string)$this->request('eth_chainId', []);
        return (int)hexdec($res);
    }

    public function blockNumber(): int
    {
        $res = (string)$this->request('eth_blockNumber', []);
        return (int)hexdec($res);
    }

    public function getCode(string $address, string $tag = 'latest'): string
    {
        return (string)$this->request('eth_getCode', [$address, $tag]);
    }

    public function call(string $to, string $data, string $tag = 'latest'): string
    {
        $res = $this->request('eth_call', [
            ['to' => $to, 'data' => $data],
            $tag,
        ]);
        return (string)$res;
    }

    public function getTransaction(string $txHash): ?array
    {
        $res = $this->request('eth_getTransactionByHash', [$txHash]);
        return is_array($res) ? $res : null;
    }

    public function getReceipt(string $txHash): ?array
    {
        $res = $this->request('eth_getTransactionReceipt', [$txHash]);
        return is_array($res) ? $res : null;
    }
}

