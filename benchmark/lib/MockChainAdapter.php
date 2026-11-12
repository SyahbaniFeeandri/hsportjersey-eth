<?php
/**
 * MockChainAdapter - Adapter state on-chain deterministik (Mode A: Algorithm benchmark).
 * Issue: W4-FDR-05
 * Pemilik: Farelly De Rossi
 *
 * Tidak memanggil Ganache. Perilaku ditentukan penuh oleh dataset + seed, sehingga
 * hasil baseline dapat direproduksi. Validasi adapter terhadap Ganache nyata
 * dilakukan pada Mode B (D100) di Minggu 6/7.
 */

declare(strict_types=1);

namespace HSport\Benchmark;

class MockChainAdapter
{
    /** @var array<int,array<string,mixed>> */
    private array $orders = [];

    /**
     * @param array<int,array<string,mixed>> $orders
     */
    public function __construct(array $orders)
    {
        foreach ($orders as $order) {
            $this->orders[(int)$order['id']] = $order;
        }
    }

    /**
     * Mengembalikan state on-chain semu untuk satu order.
     *
     * @throws MockChainException saat order tidak ditemukan / RPC disimulasikan gagal.
     */
    public function getOrder(int $orderId): array
    {
        if (!isset($this->orders[$orderId])) {
            throw new MockChainException("Order not found: {$orderId}");
        }

        $db = $this->orders[$orderId];

        if ($db['mock_category'] === 'ERROR') {
            // 10% dataset: simulasikan order tidak ditemukan / RPC error.
            throw new MockChainException("Simulated RPC error for order {$orderId}");
        }

        $chain = [
            'id' => (int)$db['id'],
            'buyer' => (string)$db['chain_buyer'],
            'totalAmountWei' => (string)$db['chain_amount_wei'],
            'status' => (int)$db['chain_status'],
            'paymentRecorded' => (bool)$db['chain_payment_recorded'],
            'externalOrderHash' => (string)$db['chain_external_order_hash'],
            'orderNumber' => (string)$db['chain_order_number'],
        ];

        if ($db['mock_category'] === 'MISMATCH') {
            $this->applyMismatch($chain, $db, $orderId);
        }

        return $chain;
    }

    /**
     * @param array<string,mixed> $chain
     * @param array<string,mixed> $db
     */
    private function applyMismatch(array &$chain, array $db, int $orderId): void
    {
        switch (($orderId - 1) % 4) {
            case 0:
                // Amount on-chain berbeda 100.000 IDR.
                $chain['totalAmountWei'] = (string)((int)$db['chain_amount_wei'] + 100000 * 100000000000);
                break;
            case 1:
                // Wallet pembeli berbeda.
                $chain['buyer'] = '0x' . substr(hash('sha256', 'mismatch-wallet|' . $orderId), 0, 40);
                break;
            case 2:
                // Status on-chain bergeser sehingga mapping DB-nya pasti berbeda.
                $chain['status'] = ((int)$chain['status'] + 3) % 8;
                break;
            default:
                // External order hash berbeda.
                $chain['externalOrderHash'] = '0x' . hash('sha256', 'mismatch-hash|' . $orderId);
                break;
        }
    }
}
