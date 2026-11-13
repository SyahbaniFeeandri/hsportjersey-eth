<?php
/**
 * Checksum - Format baris hasil verifikasi dan checksum batch.
 * Issue: W6-FDR-09, W6-FDR-11
 * Pemilik: Farelly De Rossi
 *
 * Checksum WAJIB identik antara sequential dan parallel. Karena itu format
 * baris dijadikan satu sumber kebenaran di sini dan dipakai oleh
 * SequentialVerifier, worker, serta reducer.
 *
 * Format baris: `<id>|<verification>|<amount_wei>|<differences dipisah koma>`
 */

declare(strict_types=1);

namespace HSport\Benchmark;

class Checksum
{
    /**
     * @param array<string,mixed> $result
     */
    public static function line(array $result): string
    {
        $differences = $result['differences'] ?? [];
        if (!is_array($differences)) {
            $differences = [];
        }

        return (int)$result['id']
            . '|' . (string)$result['verification']
            . '|' . (string)$result['amount_wei']
            . '|' . implode(',', $differences);
    }

    /**
     * Menghitung checksum dari sekumpulan hasil. Hasil diurutkan berdasarkan id
     * agar urutan partisi/worker tidak memengaruhi checksum.
     *
     * @param array<int,array<string,mixed>> $results
     */
    public static function compute(array $results): string
    {
        usort($results, static fn(array $a, array $b): int => (int)$a['id'] <=> (int)$b['id']);

        $lines = [];
        foreach ($results as $result) {
            $lines[] = self::line($result);
        }

        return hash('sha256', implode("\n", $lines));
    }
}
