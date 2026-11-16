<?php
/**
 * DatasetGenerator - Pembangkit dataset mock deterministik untuk baseline sequential.
 * Issue: W4-FDR-05
 * Pemilik: Farelly De Rossi
 *
 * Komposisi mengikuti docs/week1/04_CONTRACT_AND_PARALLEL_DESIGN.md §7:
 *   70% VERIFIED, 20% MISMATCH, 10% ERROR.
 */

declare(strict_types=1);

namespace HSport\Benchmark;

class DatasetGenerator
{
    private string $seed;
    private int $ethRateIdr;

    private const STATUSES = [0, 2, 3, 4, 5, 6, 7];

    private const DB_STATUS_BY_CHAIN = [
        0 => 'pending',
        1 => 'pending',
        2 => 'paid',
        3 => 'production',
        4 => 'production',
        5 => 'shipped',
        6 => 'completed',
        7 => 'cancelled',
    ];

    public function __construct(string $seed = 'HSPORT-2026', int $ethRateIdr = 10000000)
    {
        $this->seed = $seed;
        $this->ethRateIdr = $ethRateIdr;
    }

    /**
     * @return array{meta: array<string,mixed>, orders: array<int,array<string,mixed>>}
     */
    public function generate(int $size, string $datasetName = ''): array
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Ukuran dataset harus > 0.');
        }

        $name = $datasetName !== '' ? $datasetName : 'D' . $size;
        $orders = [];

        for ($i = 1; $i <= $size; $i++) {
            $orders[] = $this->buildOrder($i, $size);
        }

        $datasetHash = hash('sha256', json_encode($orders, JSON_UNESCAPED_SLASHES));

        return [
            'meta' => [
                'dataset_name' => $name,
                'dataset_size' => $size,
                'seed' => $this->seed,
                'eth_rate_idr' => $this->ethRateIdr,
                'generated_at' => date('c'),
                'dataset_hash' => $datasetHash,
                'composition' => [
                    'verified' => count(array_filter($orders, fn($o) => $o['mock_category'] === 'VERIFIED')),
                    'mismatch' => count(array_filter($orders, fn($o) => $o['mock_category'] === 'MISMATCH')),
                    'error' => count(array_filter($orders, fn($o) => $o['mock_category'] === 'ERROR')),
                ],
            ],
            'orders' => $orders,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildOrder(int $id, int $size): array
    {
        $bucket = ($id - 1) % 10;
        if ($bucket <= 6) {
            $category = 'VERIFIED';
        } elseif ($bucket <= 8) {
            $category = 'MISMATCH';
        } else {
            $category = 'ERROR';
        }

        $chainStatus = self::STATUSES[($id - 1) % count(self::STATUSES)];
        $orderNumber = 'INV-BENCH-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
        $totalAmount = 100000 + ((int)(hexdec(substr(hash('sha256', $this->seed . '|amount|' . $id), 0, 6)) % 2400) + 1) * 1000;
        $amountWei = (string)($totalAmount * 100000000000); // 1 IDR = 1e11 wei pada kurs 1 ETH = 10.000.000 IDR
        $wallet = $this->walletFor($id);

        $dbStatus = self::DB_STATUS_BY_CHAIN[$chainStatus];
        $paymentRecorded = $chainStatus >= 2 && $chainStatus !== 7;
        if ($chainStatus === 7) {
            $paymentRecorded = true; // pernah dibayar lalu direfund
        }
        $paymentStatus = $paymentRecorded ? 'paid' : 'unpaid';

        $externalHash = '0x' . hash('sha256', $orderNumber . '|' . $wallet . '|' . $amountWei);

        // State on-chain dasar = sama dengan DB. Perbedaan diterapkan oleh MockChainAdapter.
        return [
            'id' => $id,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'amount_wei' => $amountWei,
            'payment_status' => $paymentStatus,
            'status' => $dbStatus,
            'wallet_address' => $wallet,
            'blockchain_order_id' => $id,
            'external_order_hash' => $externalHash,
            'chain_order_number' => $orderNumber,
            'chain_buyer' => $wallet,
            'chain_amount_wei' => $amountWei,
            'chain_status' => $chainStatus,
            'chain_payment_recorded' => $paymentRecorded,
            'chain_external_order_hash' => $externalHash,
            'mock_category' => $category,
            'mock_dataset_size' => $size,
        ];
    }

    private function walletFor(int $id): string
    {
        return '0x' . substr(hash('sha256', $this->seed . '|wallet|' . $id), 0, 40);
    }
}
