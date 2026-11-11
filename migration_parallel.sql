-- ============================================================
-- MIGRATION: Skema parallel processing & benchmark
-- File      : migration_parallel.sql
-- Tujuan    : Menyediakan tabel antrean, batch, checkpoint, dan log
--             untuk verifikasi order DB-vs-chain secara paralel.
-- Sifat     : Idempotent (CREATE TABLE IF NOT EXISTS).
-- Prasyarat : Database h_sport_db sudah ada.
-- Cara pakai:
--   phpMyAdmin -> pilih database h_sport_db -> Import -> file ini
--   atau CLI  -> mysql -u root h_sport_db < migration_parallel.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. job_batches
--    Satu baris = satu batch benchmark / verifikasi.
--    worker_count = 1 dipakai untuk sequential baseline.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_code` VARCHAR(64) NOT NULL,
    `mode` ENUM('ALGORITHM','INTEGRATION') NOT NULL DEFAULT 'ALGORITHM',
    `dataset_name` VARCHAR(64) NOT NULL,
    `dataset_size` INT UNSIGNED NOT NULL,
    `dataset_hash` CHAR(64) DEFAULT NULL,
    `worker_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `is_baseline` TINYINT(1) NOT NULL DEFAULT 0,
    `parent_batch_id` BIGINT UNSIGNED DEFAULT NULL,
    `status` ENUM('PENDING','RUNNING','DONE','FAILED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    `total_orders` INT UNSIGNED NOT NULL DEFAULT 0,
    `verified_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `mismatch_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `error_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `revenue_all` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `revenue_verified` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `aov` DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    `checksum` CHAR(64) DEFAULT NULL,
    `duration_ms` BIGINT UNSIGNED DEFAULT NULL,
    `started_at` DATETIME DEFAULT NULL,
    `finished_at` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_job_batches_code` (`batch_code`),
    KEY `idx_job_batches_status` (`status`),
    KEY `idx_job_batches_dataset` (`dataset_name`, `dataset_size`),
    KEY `idx_job_batches_workers` (`worker_count`),
    KEY `idx_job_batches_parent` (`parent_batch_id`),
    CONSTRAINT `fk_job_batches_parent`
        FOREIGN KEY (`parent_batch_id`) REFERENCES `job_batches` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. verification_jobs
--    Satu baris = satu partisi order yang diproses satu worker.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `verification_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_id` BIGINT UNSIGNED NOT NULL,
    `partition_no` SMALLINT UNSIGNED NOT NULL,
    `range_start` BIGINT UNSIGNED NOT NULL,
    `range_end` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('PENDING','RUNNING','DONE','FAILED','DEAD','CANCELLED') NOT NULL DEFAULT 'PENDING',
    `worker_id` VARCHAR(64) DEFAULT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `leased_at` DATETIME DEFAULT NULL,
    `started_at` DATETIME DEFAULT NULL,
    `finished_at` DATETIME DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_verification_jobs_partition` (`batch_id`, `partition_no`),
    KEY `idx_verification_jobs_status` (`status`),
    KEY `idx_verification_jobs_worker` (`worker_id`),
    KEY `idx_verification_jobs_batch_status` (`batch_id`, `status`),
    CONSTRAINT `fk_verification_jobs_batch`
        FOREIGN KEY (`batch_id`) REFERENCES `job_batches` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. checkpoints
--    Hasil parsial per partisi. Reducer membaca tabel ini.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checkpoints` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED NOT NULL,
    `partition_no` SMALLINT UNSIGNED NOT NULL,
    `processed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `verified_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `mismatch_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `error_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `revenue_all` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `revenue_verified` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `checksum` CHAR(64) DEFAULT NULL,
    `payload` JSON DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_checkpoints_job` (`job_id`),
    KEY `idx_checkpoints_batch` (`batch_id`),
    KEY `idx_checkpoints_partition` (`batch_id`, `partition_no`),
    CONSTRAINT `fk_checkpoints_batch`
        FOREIGN KEY (`batch_id`) REFERENCES `job_batches` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_checkpoints_job`
        FOREIGN KEY (`job_id`) REFERENCES `verification_jobs` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. worker_logs
--    Log per job/worker untuk audit dan troubleshooting.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `worker_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batch_id` BIGINT UNSIGNED NOT NULL,
    `job_id` BIGINT UNSIGNED DEFAULT NULL,
    `worker_id` VARCHAR(64) NOT NULL,
    `level` ENUM('DEBUG','INFO','WARN','ERROR') NOT NULL DEFAULT 'INFO',
    `message` TEXT NOT NULL,
    `context` JSON DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_worker_logs_batch` (`batch_id`),
    KEY `idx_worker_logs_job` (`job_id`),
    KEY `idx_worker_logs_worker` (`worker_id`),
    KEY `idx_worker_logs_level` (`level`),
    CONSTRAINT `fk_worker_logs_batch`
        FOREIGN KEY (`batch_id`) REFERENCES `job_batches` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_worker_logs_job`
        FOREIGN KEY (`job_id`) REFERENCES `verification_jobs` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. View ringkasan benchmark
-- ------------------------------------------------------------
DROP VIEW IF EXISTS `v_benchmark_summary`;
CREATE VIEW `v_benchmark_summary` AS
SELECT
    b.`id`,
    b.`batch_code`,
    b.`mode`,
    b.`dataset_name`,
    b.`dataset_size`,
    b.`worker_count`,
    b.`is_baseline`,
    b.`status`,
    b.`total_orders`,
    b.`verified_count`,
    b.`mismatch_count`,
    b.`error_count`,
    b.`revenue_all`,
    b.`revenue_verified`,
    b.`aov`,
    b.`duration_ms`,
    ROUND(CASE WHEN b.`duration_ms` > 0
        THEN b.`total_orders` / (b.`duration_ms` / 1000.0)
        ELSE NULL END, 4) AS `throughput_per_sec`,
    CASE
        WHEN base.`duration_ms` IS NULL OR base.`duration_ms` = 0 OR b.`duration_ms` IS NULL THEN NULL
        ELSE ROUND(base.`duration_ms` / b.`duration_ms`, 4)
    END AS `speedup`,
    CASE
        WHEN base.`duration_ms` IS NULL OR b.`duration_ms` IS NULL OR b.`worker_count` = 0 THEN NULL
        ELSE ROUND((base.`duration_ms` / b.`duration_ms`) / b.`worker_count`, 4)
    END AS `efficiency`,
    CASE
        WHEN base.`checksum` IS NULL OR b.`checksum` IS NULL THEN NULL
        WHEN base.`checksum` = b.`checksum` THEN 'MATCH'
        ELSE 'DIFFERENT'
    END AS `correctness_gate`,
    b.`started_at`,
    b.`finished_at`,
    b.`created_at`
FROM `job_batches` b
LEFT JOIN `job_batches` base
    ON base.`dataset_name` = b.`dataset_name`
   AND base.`dataset_size` = b.`dataset_size`
   AND base.`mode` = b.`mode`
   AND base.`is_baseline` = 1
   AND base.`status` = 'DONE';

-- ------------------------------------------------------------
-- 6. Verifikasi
-- ------------------------------------------------------------
SHOW TABLES LIKE 'job_batches';
SHOW TABLES LIKE 'verification_jobs';
SHOW TABLES LIKE 'checkpoints';
SHOW TABLES LIKE 'worker_logs';
SHOW COLUMNS FROM `job_batches` LIKE 'worker_count';
SHOW COLUMNS FROM `verification_jobs` LIKE 'status';

-- ------------------------------------------------------------
-- 7. Catatan operasional
-- ------------------------------------------------------------
-- - Jalankan sequential baseline lebih dulu:
--     worker_count = 1, is_baseline = 1, mode = 'ALGORITHM'
-- - Batch paralel mengisi parent_batch_id ke baseline yang sama.
-- - Correctness gate di view membandingkan checksum dengan baseline.
-- - Semua tabel aman dibuat ulang; tidak ada DROP TABLE.
