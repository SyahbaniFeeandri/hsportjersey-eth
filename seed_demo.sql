-- ============================================================
-- SEED DATA DEMO: H-Sport Financial dApp
-- File      : seed_demo.sql
-- Tujuan    : Menyediakan data awal yang konsisten untuk demo/UAT:
--             2 akun (admin + customer) dan 13 produk jersey.
-- Sifat     : Idempotent (ON DUPLICATE KEY UPDATE), aman diulang.
-- Prasyarat : Database h_sport_db sudah dibuat dari dump
--             `h_sport_db (1).sql` dan migrasi sudah dijalankan.
-- Cara pakai:
--   mysql -u root h_sport_db < seed_demo.sql
--   (atau phpMyAdmin -> Import)
-- Kredensial demo:
--   Admin    : admin@hsport.com    / admin123
--   Customer : customer@example.com / customer123
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = "+00:00";

START TRANSACTION;

-- ------------------------------------------------------------
-- 1. Akun demo
--    Hash = MD5 dari password (sesuai implementasi login proyek).
--    admin123    -> 0192023a7bbd73250516f069df18b500
--    customer123 -> f4ad231214cb99a985dff0f056a36242
-- ------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`)
VALUES
    (1, 'admin', 'admin@hsport.com', '0192023a7bbd73250516f069df18b500', 'admin'),
    (2, 'demo_customer', 'customer@example.com', 'f4ad231214cb99a985dff0f056a36242', 'customer')
ON DUPLICATE KEY UPDATE
    `username` = VALUES(`username`),
    `email`    = VALUES(`email`),
    `password` = VALUES(`password`),
    `role`     = VALUES(`role`);

-- ------------------------------------------------------------
-- 2. Katalog produk (13 jersey)
--    File gambar mengacu ke folder `uploads/` yang ada di repo.
-- ------------------------------------------------------------
INSERT INTO `products`
    (`id`, `name`, `category`, `material`, `price`, `description`, `image`, `sizes`, `colors`, `stock`)
VALUES
    (1, 'Jersey Sepeda Ravina', 'Sepeda', 'Dryfit', 900000.00,
     'Jersey sepeda premium Ravina dengan bahan Dryfit', '1779769268_4b09c82bcebdae14fd49013639e5b177.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (2, 'Jersey Timnas Indonesia', 'Sepak Bola', 'Cutton', 550000.00,
     'Jersey Timnas Indonesia Premium', '1779769482_d8782772c62cba381a3726c720eaf712.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (3, 'Jersey Pink Panther', 'Basket', 'Polyester', 200000.00,
     'Jersey basket karakter "Pink Panther" dengan bahan polyester', '1779769863_d5c755c828bc891cf5251a55d1c4cb3b.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (4, 'Jersey Cycle Racing', 'Sepeda', 'Microfiber', 170000.00,
     'Jersey sepeda aerodinamis dengan bahan microfiber yang cepat kering dan ringan.', '1779769978_27fa8ae66ee02a60f0c196b4697e2fd2.jpg',
     'S,M,L,XL,XXL', 'Kuning,Hijau,Biru,Merah,Putih', 100),
    (5, 'Jersey Basket Spongebob + shorts', 'Basket', 'Polyester', 200000.00,
     'Jersey Basket karakter "Spongebob" dengan bahan polyester', '1779770129_63a1b124652350a1e10b2a37dc4df098.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (6, 'Jersey Club Bangkrut', 'Sepak Bola', 'Cutton', 1499000.00,
     'Jersey Bola Barcelona premium dengan bahan Cutton', '1779770176_9a6a3538d9edf4b83dfb7044540179e0.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (7, 'Jersey Persija', 'Sepak Bola', 'Cotton', 500000.00,
     'Jersey sepak bola Persija dengan bahan Cotton', '1779770227_76f3de478b22ef98f48e9341ec40ec82.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (8, 'Jersey Persib Bandung', 'Sepak Bola', 'Dryfit', 500000.00,
     'Jersey sepak bola Persib Bandung dengan bahan Dryfit', '1779770318_3ae6fd20a71d257ec837bb76660d9420.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (9, 'Jersey Sepeda Teka', 'Sepeda', 'Polyester', 1200000.00,
     'Jersey Sepeda TEKA dengan bahan Polyester', '1779769205_cf5400d90490573d0de83cfdcac2458f.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (10, 'Jersey Padel Hijau Zamrud', 'Padel', 'Cutton', 560000.00,
     'Jersey Padel kualitas premium', '1779768911_320d700b00143d19414ff0d1c9cb4282.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (11, 'Jersey MU', 'Sepak Bola', 'Cutton', 1500000.00,
     'Jersey Sepak Bola Manchester United dengan bahan Cutton', '1779768868_4833dfd2aa45e669238deec263aa2f56.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (12, 'Jersey Sepak Bola Umbro', 'Sepak Bola', 'Cutton', 900000.00,
     'Jersey Sepak Bola dengan bahan Cutton', '1779768850_327fd7fdd96b8f94d68f3691c7389cf0.jpg',
     'S,M,L,XL,XXL', NULL, 100),
    (13, 'Jersey Basket Supreme', 'Basket', 'Cutton', 800000.00,
     'Jersey Basket Supreme dengan bahan Cutton', '1779768684_cf9cb45b54a3ff488d3569d8dc88b794.jpg',
     'S,M,L,XL,XXL', NULL, 100)
ON DUPLICATE KEY UPDATE
    `name`        = VALUES(`name`),
    `category`    = VALUES(`category`),
    `material`    = VALUES(`material`),
    `price`       = VALUES(`price`),
    `description` = VALUES(`description`),
    `image`       = VALUES(`image`),
    `sizes`       = VALUES(`sizes`),
    `colors`      = VALUES(`colors`),
    `stock`       = VALUES(`stock`);

COMMIT;

-- ------------------------------------------------------------
-- 3. Verifikasi hasil seed
-- ------------------------------------------------------------
SELECT 'SEED OK' AS status;
SELECT COUNT(*) AS total_users    FROM `users`;
SELECT COUNT(*) AS total_products FROM `products`;
SELECT id, username, email, role FROM `users` WHERE id IN (1, 2);
