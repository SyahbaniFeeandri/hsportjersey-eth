-- ============================================================
-- MIGRATION: Kolom bukti blockchain untuk H-Sport Financial dApp
-- File      : migration_blockchain.sql
-- Tujuan    : Menambahkan kolom proof/verifikasi ke tabel `orders`
--             dan tabel riwayat transaksi blockchain.
-- Sifat     : Idempotent (aman dijalankan berulang kali).
-- Cara pakai:
--   phpMyAdmin -> pilih database h_sport_db -> Import -> file ini
--   atau CLI  -> mysql -u root h_sport_db < migration_blockchain.sql
-- ============================================================

-- ------------------------------------------------------------
-- Helper: tambah kolom hanya jika belum ada
-- ------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS add_column_if_missing$$
CREATE PROCEDURE add_column_if_missing(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT(
            'ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition
        );
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS add_index_if_missing$$
CREATE PROCEDURE add_index_if_missing(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @ddl = CONCAT(
            'ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')'
        );
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ------------------------------------------------------------
-- 1. Kolom blockchain pada tabel `orders`
-- ------------------------------------------------------------
CALL add_column_if_missing(
    'orders', 'payment_type',
    'VARCHAR(20) DEFAULT NULL AFTER `payment_status`'
);

CALL add_column_if_missing(
    'orders', 'tx_hash',
    'VARCHAR(66) DEFAULT NULL AFTER `payment_type`'
);

CALL add_column_if_missing(
    'orders', 'wallet_address',
    'VARCHAR(42) DEFAULT NULL AFTER `tx_hash`'
);

CALL add_column_if_missing(
    'orders', 'block_number',
    'BIGINT UNSIGNED DEFAULT NULL AFTER `wallet_address`'
);

CALL add_column_if_missing(
    'orders', 'contract_address',
    'VARCHAR(42) DEFAULT NULL AFTER `block_number`'
);

CALL add_column_if_missing(
    'orders', 'blockchain_order_id',
    'BIGINT UNSIGNED DEFAULT NULL AFTER `contract_address`'
);

CALL add_column_if_missing(
    'orders', 'external_order_hash',
    'CHAR(66) DEFAULT NULL AFTER `blockchain_order_id`'
);

CALL add_column_if_missing(
    'orders', 'verification_status',
    'ENUM(\'PENDING\',\'VERIFIED\',\'MISMATCH\') NOT NULL DEFAULT \'PENDING\' AFTER `external_order_hash`'
);

CALL add_column_if_missing(
    'orders', 'verified_at',
    'DATETIME DEFAULT NULL AFTER `verification_status`'
);

CALL add_column_if_missing(
    'orders', 'paid_at',
    'DATETIME DEFAULT NULL AFTER `verified_at`'
);

-- ------------------------------------------------------------
-- 2. Index untuk pencarian proof dan verifikasi
-- ------------------------------------------------------------
CALL add_index_if_missing('orders', 'idx_orders_tx_hash', '`tx_hash`');
CALL add_index_if_missing('orders', 'idx_orders_blockchain_order', '`blockchain_order_id`');
CALL add_index_if_missing('orders', 'idx_orders_external_hash', '`external_order_hash`');
CALL add_index_if_missing('orders', 'idx_orders_verification', '`verification_status`');

-- ------------------------------------------------------------
-- 3. Riwayat transaksi/event blockchain
--    Dipakai oleh receipt/event reader dan verification service.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blockchain_transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `blockchain_order_id` BIGINT UNSIGNED DEFAULT NULL,
    `tx_hash` VARCHAR(66) NOT NULL,
    `block_number` BIGINT UNSIGNED DEFAULT NULL,
    `contract_address` VARCHAR(42) DEFAULT NULL,
    `wallet_address` VARCHAR(42) DEFAULT NULL,
    `event_name` VARCHAR(64) DEFAULT NULL,
    `status` ENUM('PENDING','CONFIRMED','FAILED') NOT NULL DEFAULT 'PENDING',
    `verification_status` ENUM('PENDING','VERIFIED','MISMATCH') NOT NULL DEFAULT 'PENDING',
    `payload` JSON DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bt_order` (`order_id`),
    KEY `idx_bt_tx` (`tx_hash`),
    KEY `idx_bt_blockchain_order` (`blockchain_order_id`),
    KEY `idx_bt_verification` (`verification_status`),
    CONSTRAINT `fk_bt_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Bersihkan helper
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS add_column_if_missing;
DROP PROCEDURE IF EXISTS add_index_if_missing;

-- ------------------------------------------------------------
-- 5. Verifikasi
-- ------------------------------------------------------------
SHOW COLUMNS FROM `orders` LIKE 'tx_hash';
SHOW COLUMNS FROM `orders` LIKE 'verification_status';
SHOW TABLES LIKE 'blockchain_transactions';
