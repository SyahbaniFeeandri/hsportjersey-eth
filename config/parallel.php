<?php
/**
 * Konfigurasi queue & worker untuk verifikasi DB vs blockchain.
 * Issue: W5-FDR-06
 * Pemilik: Farelly De Rossi
 *
 * File ini menjadi satu sumber kebenaran untuk skema antrean (migration_parallel.sql)
 * dan parameter worker yang akan dipakai pada M6 (parallel processing).
 */

declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'h_sport_db',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],

    // Jumlah worker maksimum yang didukung skema. M6 menguji 1/2/4/8.
    'worker_counts' => [1, 2, 4, 8],

    // Ukuran partisi default (jumlah order per job).
    'default_partition_size' => 500,

    // Lease job agar worker mati tidak mengunci antrean selamanya.
    'lease_seconds' => 300,

    // Percobaan ulang maksimum sebelum job ditandai DEAD.
    'max_attempts' => 3,

    // Mode batch yang valid sesuai ENUM job_batches.mode.
    'modes' => ['ALGORITHM', 'INTEGRATION'],
];
