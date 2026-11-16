<?php
/**
 * PartitionPlanner - Partisi contiguous berbasis ID untuk verifikasi paralel.
 * Issue: W6-FDR-09
 * Pemilik: Farelly De Rossi
 *
 * Aturan (docs/week1/04_CONTRACT_AND_PARALLEL_DESIGN.md §8.1):
 * - P = jumlah worker yang diuji.
 * - Partisi contiguous, ukuran ceil(N / P) dengan sisa dibagi ke partisi awal.
 * - Setiap partisi menjadi satu baris verification_jobs (partition_no 1..P).
 */

declare(strict_types=1);

namespace HSport\Benchmark;

class PartitionPlanner
{
    /**
     * @return array<int,array{partition_no:int,range_start:int,range_end:int,size:int}>
     */
    public static function plan(int $totalOrders, int $workers): array
    {
        if ($totalOrders <= 0) {
            throw new \InvalidArgumentException('Jumlah order harus > 0.');
        }

        $workers = max(1, min($workers, $totalOrders));
        $base = intdiv($totalOrders, $workers);
        $remainder = $totalOrders % $workers;

        $partitions = [];
        $start = 1;
        for ($i = 1; $i <= $workers; $i++) {
            $size = $base + ($i <= $remainder ? 1 : 0);
            if ($size <= 0) {
                continue;
            }

            $partitions[] = [
                'partition_no' => count($partitions) + 1,
                'range_start' => $start,
                'range_end' => $start + $size - 1,
                'size' => $size,
            ];
            $start += $size;
        }

        return $partitions;
    }

    /**
     * Validasi bahwa partisi menutup 1..N tanpa celah/overlap.
     *
     * @param array<int,array{range_start:int,range_end:int}> $partitions
     */
    public static function coversAll(int $totalOrders, array $partitions): bool
    {
        $expectedStart = 1;
        foreach ($partitions as $partition) {
            if ((int)$partition['range_start'] !== $expectedStart) {
                return false;
            }
            $expectedStart = (int)$partition['range_end'] + 1;
        }

        return $expectedStart === $totalOrders + 1;
    }
}
