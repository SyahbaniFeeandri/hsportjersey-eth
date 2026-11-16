<?php
/**
 * SequentialVerifier - Mesin verifikasi sequential DB vs state on-chain.
 * Issue: W4-FDR-05
 * Pemilik: Farelly De Rossi
 *
 * Implementasi ini adalah baseline (worker_count = 1) yang nantinya menjadi
 * pembanding hasil parallel di Minggu 6. Aturan verifikasi mengikuti
 * docs/week1/04_CONTRACT_AND_PARALLEL_DESIGN.md §6.
 */

declare(strict_types=1);

namespace HSport\Benchmark;

require_once __DIR__ . '/Checksum.php';

class SequentialVerifier
{
    private MockChainAdapter $chain;
    private int $ethRateIdr;

    private const VALID_DB_STATUS = [
        0 => ['pending', 'created'],
        1 => ['pending', 'awaiting_payment'],
        2 => ['paid', 'production'],
        3 => ['production', 'paid'],
        4 => ['production', 'ready', 'ready_to_ship'],
        5 => ['shipped'],
        6 => ['completed'],
        7 => ['cancelled'],
    ];

    public function __construct(MockChainAdapter $chain, int $ethRateIdr = 10000000)
    {
        $this->chain = $chain;
        $this->ethRateIdr = $ethRateIdr;
    }

    /**
     * @param array<string,mixed> $dbOrder
     * @return array{id:int,verification:string,amount_idr:int,amount_wei:string,differences:array<int,string>,error:?string}
     */
    public function verify(array $dbOrder): array
    {
        $orderId = (int)$dbOrder['id'];
        $amountIdr = (int)$dbOrder['total_amount'];
        $amountWei = (string)$dbOrder['amount_wei'];

        try {
            $chain = $this->chain->getOrder($orderId);
        } catch (MockChainException $e) {
            return [
                'id' => $orderId,
                'verification' => 'ERROR',
                'amount_idr' => $amountIdr,
                'amount_wei' => $amountWei,
                'paid' => (($dbOrder['payment_status'] ?? '') === 'paid'),
                'differences' => [],
                'error' => $e->getMessage(),
            ];
        }

        $differences = [];

        if (strtolower((string)$dbOrder['wallet_address']) !== strtolower((string)$chain['buyer'])) {
            $differences[] = 'buyer_wallet';
        }

        if ((string)$dbOrder['amount_wei'] !== (string)$chain['totalAmountWei']) {
            $differences[] = 'total_amount';
        }

        $chainStatus = (int)$chain['status'];
        $dbStatus = strtolower((string)$dbOrder['status']);
        $allowed = self::VALID_DB_STATUS[$chainStatus] ?? [];
        if (!in_array($dbStatus, $allowed, true)) {
            $differences[] = 'order_status';
        }

        if (strtolower((string)$dbOrder['external_order_hash']) !== strtolower((string)$chain['externalOrderHash'])) {
            $differences[] = 'external_order_hash';
        }

        $expectedRecorded = (bool)$chain['paymentRecorded'];
        $dbPaid = ($dbOrder['payment_status'] === 'paid');
        if ($expectedRecorded !== $dbPaid) {
            $differences[] = 'payment_recorded';
        }

        return [
            'id' => $orderId,
            'verification' => empty($differences) ? 'VERIFIED' : 'MISMATCH',
            'amount_idr' => $amountIdr,
            'amount_wei' => $amountWei,
            'paid' => ($dbOrder['payment_status'] === 'paid'),
            'differences' => $differences,
            'error' => null,
        ];
    }

    /**
     * Menjalankan verifikasi untuk seluruh dataset secara berurutan.
     *
     * @param array{meta: array<string,mixed>, orders: array<int,array<string,mixed>>} $dataset
     * @return array<string,mixed>
     */
    public function run(array $dataset): array
    {
        $startedAt = microtime(true);
        $results = [];
        $verified = 0;
        $mismatch = 0;
        $errors = 0;
        $revenueAll = 0;
        $revenueVerified = 0;
        $paidCount = 0;
        $unpaidCount = 0;

        foreach ($dataset['orders'] as $order) {
            $result = $this->verify($order);
            $results[] = $result;

            if ($result['verification'] === 'VERIFIED') {
                $verified++;
                if ($result['paid'] === true) {
                    $revenueVerified += $result['amount_idr'];
                }
            } elseif ($result['verification'] === 'MISMATCH') {
                $mismatch++;
            } else {
                $errors++;
            }

            $revenueAll += $result['amount_idr'];
            if ($result['paid'] === true) {
                $paidCount++;
            } else {
                $unpaidCount++;
            }
        }

        $durationUs = (int)round((microtime(true) - $startedAt) * 1000000);
        $durationMs = (int)round($durationUs / 1000);

        // Checksum stabil: urutkan berdasarkan id, lalu hash baris hasil.
        $checksum = Checksum::compute($results);

        $total = count($results);

        return [
            'mode' => 'SEQUENTIAL',
            'worker_count' => 1,
            'dataset_name' => $dataset['meta']['dataset_name'],
            'dataset_size' => $dataset['meta']['dataset_size'],
            'dataset_hash' => $dataset['meta']['dataset_hash'],
            'seed' => $dataset['meta']['seed'],
            'started_at' => date('c', (int)$startedAt),
            'finished_at' => date('c'),
            'duration_ms' => $durationMs,
            'duration_us' => $durationUs,
            'total_orders' => $total,
            'verified_count' => $verified,
            'mismatch_count' => $mismatch,
            'error_count' => $errors,
            'paid_count' => $paidCount,
            'unpaid_count' => $unpaidCount,
            'revenue_all' => $revenueAll,
            'revenue_verified' => $revenueVerified,
            'aov' => $total > 0 ? round($revenueAll / $total, 4) : 0,
            'throughput_per_sec' => $durationUs > 0 ? round($total / ($durationUs / 1000000), 2) : null,
            'checksum' => $checksum,
            'results' => $results,
        ];
    }
}
