# Screenshot Demo — H-Sport Financial dApp (`v1.0.0`)

**Milestone:** M8 — Finalisasi & Demo
**Issue terkait:** `W8-ALL-05`, `W8-YAS-12`, `W8-IHS-09`, `W8-LUI-14`
**Tanggal capture:** 16 November 2026, 12.00 WIB
**Alat:** Google Chrome 143 (Playwright 1.65.1, headless), viewport 1440×900, device scale factor 1.5
**Metode:** halaman diakses langsung ke server demo, login memakai akun seed, lalu
screenshot *full page* dipotong ke tinggi konten.

---

## 1. Lingkungan Saat Capture

| Komponen | Nilai |
|---|---|
| Web server | `php -S 127.0.0.1:8080 -t C:\laragon\www\project_jersey` |
| Database | `h_sport_db` — 13 produk, 4 pelanggan |
| Kontrak | `HSportStore` V2 `0xe78A0F7E598Cc8b0Bb87894B0F60dD2a88d6a8Ab` |
| Ganache | deterministik, chainId 1337, networkId 5777 |
| Order ber-chain | **#46** `INV-INT-1819227328` — VERIFIED, block **66** |
| Order checkout demo | **#49** `INV-20261116-8621` — Rp 900.000, QRIS |
| Akun customer | `customer@example.com` / `customer123` |
| Akun admin | `admin@hsport.com` / `admin123` |

> Catatan: instance Ganache untuk capture ini adalah hasil `npm run migrate` setelah
> restart environment, sehingga order ber-chain yang dipakai adalah **#46 (block 66)**,
> bukan #43 (block 2) yang tercatat pada sesi demo sebelumnya di
> [`../05_DEMO_EVIDENCE.md`](../../05_DEMO_EVIDENCE.md). Keduanya sah; yang menentukan
> adalah `verification_status = VERIFIED` pada DB dan state `Created` on-chain.

---

## 2. Daftar Screenshot

| # | File | Peran | Halaman | Isi yang dibuktikan |
|---:|---|---|---|---|
| 1 | `01_customer_home.png` | Customer | `index.php` | Storefront, katalog unggulan, navigasi login |
| 2 | `02_customer_catalog.png` | Customer | `products.php` | 13 produk seed, filter kategori, harga |
| 3 | `03_customer_product_detail.png` | Customer | `product-detail.php?id=1` | Nama, harga, pilihan ukuran, custom text |
| 4 | `04_customer_cart_checkout.png` | Customer | `cart.php` | Item keranjang, total, form checkout, QRIS & Crypto |
| 5 | `05_customer_payment_qris.png` | Customer | `generate-qr.php?order_id=49` | QR, nominal, kode pembayaran, upload bukti |
| 6 | `06_customer_orders.png` | Customer | `orders.php` | Riwayat order + timeline status |
| 7 | `07_customer_order_detail.png` | Customer | `order-detail.php?id=49` | Detail item, alamat, status pembayaran |
| 8 | `08_admin_dashboard.png` | Admin | `admin/dashboard.php` | 13 produk, 27 pesanan, 4 pelanggan, 7 pending |
| 9 | `09_admin_orders.png` | Admin | `admin/orders.php` | Kolom **Verifikasi** + badge VERIFIED/PENDING, receipt chain |
| 10 | `10_admin_verify_verified.png` | Admin | `admin/orders.php` (POST) | Pesan `Verifikasi order #46: VERIFIED (data DB cocok dengan on-chain)` |
| 11 | `11_admin_order_detail.png` | Admin | `admin/order-detail.php?id=46` | Detail order ber-chain dari sisi admin |
| 12 | `12_blockchain_proof_onchain.png` | Admin | `blockchain-proof.php?order_id=46` | Tx hash, block 66, wallet, contract, state `Created`, event |
| 13 | `13_admin_performance.png` | Admin | `admin/performance.php` | Ringkasan benchmark, matriks 16 konfigurasi, grafik speedup, regresi 21/21 |
| 14 | `14_admin_reports.png` | Admin | `admin/reports.php` | Laporan penjualan & analitik |
| 15 | `15_admin_products.png` | Admin | `admin/products.php` | Manajemen produk (13 item) |

Metadata teknis (URL, judul, tinggi, ukuran byte) tersimpan di
[`screenshots.json`](screenshots.json).

---

## 3. Pemetaan ke Bukti Demo & UAT

| Langkah demo (`04_DEMO_PLAN_PER_ROLE.md` §4) | Screenshot |
|---|---|
| 1 Login customer | 01 (navigasi sudah login) |
| 2 Buka katalog & filter | 01, 02 |
| 3 Tambah ke keranjang | 03 → 04 |
| 4 Buka keranjang | 04 |
| 5 Checkout JNE + QRIS | 04 → order #49 |
| 6 Halaman pembayaran | 05 |
| 7 Riwayat & detail order | 06, 07 |
| 8 Login admin | 08 |
| 9 Dashboard admin | 08 |
| 10 Manajemen order + kolom verifikasi | 09 |
| 11 Retry verifikasi DB vs chain (REQ-11) | 10 |
| 12 Halaman bukti blockchain (REQ-12) | 12 |
| 13 Laporan penjualan | 14 |

| UAT | Screenshot |
|---|---|
| UAT-01..UAT-09 (customer) | 01–07 |
| UAT-10..UAT-12 (admin) | 08, 09, 11 |
| UAT-13 status order | 07 |
| UAT-14 verifikasi DB vs chain | 09, 10 |
| UAT-15 halaman bukti | 12 |
| UAT-16 laporan penjualan | 14 |

---

## 4. Catatan Teknis

1. Capture memakai sesi cookie terpisah untuk customer dan admin, sama seperti
   skrip bukti `demo_http.log`.
2. Halaman pembayaran QRIS menampilkan kode unik yang di-generate server
   (`QRIS-INV-20261116-8621-…`), jadi nilainya berbeda setiap order.
3. Screenshot 06 dan 13 sengaja dipotong pada tinggi 6000 px karena halaman
   berisi daftar panjang; bagian teratas memuat seluruh informasi yang relevan.
4. Footer aplikasi memakai teks terang di atas latar transparan sehingga area
   bawah halaman tampak kosong; screenshot dipotong ke tinggi konten agar tidak
   menyertakan area kosong tersebut.
5. Order #49 dibuat khusus untuk capture ini dan sengaja dibiarkan berstatus
   *pending / belum dibayar* agar alur pembayaran dapat ditunjukkan.

---

## 5. Cara Membuat Ulang

```bash
# 1) Pastikan environment aktif (MySQL, Ganache, kontrak, web server)
php tests/integration/run_all.php      # memastikan ada order ber-chain VERIFIED
php -S 127.0.0.1:8080 -t .

# 2) Siapkan dependensi capture (tanpa mengunduh browser)
cd docs/week8/evidence/screenshots
npm init -y
npm i playwright-core

# 3) Jalankan skrip capture
CHAIN_ORDER_ID=<id order ber-chain> node capture.js
```

Skrip [`capture.js`](capture.js) memakai `playwright-core` dengan Chrome yang
sudah terpasang di sistem (`C:\Program Files\Google\Chrome\Application\chrome.exe`),
sehingga tidak perlu mengunduh browser Playwright.

> Jika Chrome berada di lokasi lain, ubah konstanta `CHROME` pada bagian atas
> `capture.js`. Skrip akan menimpa seluruh PNG dan `screenshots.json` di folder
> ini.
